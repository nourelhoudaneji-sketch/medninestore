<?php
session_start();
include 'includes/db.php';
include 'includes/header.php';

$promotion_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$today = date('Y-m-d');

function calcPrixPromo(float $prixProduit, $pf, $pc): float {
    if ($pf !== null && $pf !== '') {
        return (float)$pf; // prix fixe prioritaire
    }
    if ($pc !== null && $pc !== '') {
        $pourcentageVal = (float)$pc;
        return $prixProduit * (1 - ($pourcentageVal / 100));
    }
    return $prixProduit;
}

// =========================
// DÉTAIL
// =========================
if ($promotion_id > 0) {
    $stmt = $conn->prepare(
        "SELECT id, titre, description, type_promo, pourcentage, prix_fixe
         FROM promotions
         WHERE id = ? AND actif = 1
           AND (date_debut IS NULL OR date_debut <= ?)
           AND (date_fin IS NULL OR date_fin >= ?)"
    );
    $stmt->execute([$promotion_id, $today, $today]);
    $promo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$promo) {
        die('Promotion introuvable ou expirée');
    }

    $stmtP = $conn->prepare(
        "SELECT p.id, p.nom, p.image, p.prix
         FROM promotion_produits pp
         INNER JOIN produits p ON p.id = pp.produit_id
         WHERE pp.promotion_id = ?
         ORDER BY p.id DESC"
    );
    $stmtP->execute([$promotion_id]);
    $produits = $stmtP->fetchAll(PDO::FETCH_ASSOC);

    $pf = $promo['prix_fixe'] ?? null;
    $pc = $promo['pourcentage'] ?? null;

    $pageTitle = $promo['titre'] ?? 'Promotion';

} else {
    // =========================
    // LISTE
    // =========================

    $sql = "
        SELECT 
          pr.id AS promotion_id,
          pr.titre,
          pr.description,
          pr.pourcentage,
          pr.prix_fixe,
          p.id AS produit_id,
          p.nom AS produit_nom,
          p.image AS produit_image,
          p.prix AS produit_prix
        FROM promotions pr
        INNER JOIN promotion_produits pp ON pp.promotion_id = pr.id
        INNER JOIN produits p ON p.id = pp.produit_id
        WHERE pr.actif = 1
          AND (pr.date_debut IS NULL OR pr.date_debut <= :today)
          AND (pr.date_fin IS NULL OR pr.date_fin >= :today)
        ORDER BY pr.date_creation DESC, p.id DESC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute(['today' => $today]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Construire tableau promotions => produits
    $promos = [];
    foreach ($rows as $r) {
        $pid = (int)$r['promotion_id'];
        if (!isset($promos[$pid])) {
            $promos[$pid] = [
                'titre' => $r['titre'],
                'description' => $r['description'],
                'pourcentage' => $r['pourcentage'],
                'prix_fixe' => $r['prix_fixe'],
                'produits' => []
            ];
        }

        $produitPrix = (float)($r['produit_prix'] ?? 0);
        $nouveauPrix = calcPrixPromo(
            $produitPrix,
            $promos[$pid]['prix_fixe'] ?? null,
            $promos[$pid]['pourcentage'] ?? null
        );

        $promos[$pid]['produits'][] = [
            'id' => (int)($r['produit_id'] ?? 0),
            'nom' => $r['produit_nom'],
            'image' => $r['produit_image'],
            'prix' => $produitPrix,
            'prix_promo' => $nouveauPrix
        ];
    }

    $pageTitle = 'Promotions';
}
?>

<section class="page-header">
    <h1>
        <span>Promotions</span> <?= htmlspecialchars($pageTitle) ?>
    </h1>
</section>

<section class="section">
    <div class="produits-grid">

        <?php if ($promotion_id > 0): ?>
            <?php if (empty($produits)): ?>
                <p>Aucun produit pour cette promotion.</p>
            <?php else: ?>
                <?php foreach ($produits as $p): ?>
                    <?php
                        $prixProduit = (float)($p['prix'] ?? 0);
                        $prixPromo = calcPrixPromo($prixProduit, $pf, $pc);
                    ?>
                    <div class="produit-card">
                        <img loading="lazy" decoding="async" fetchpriority="low" src="/medninestore/img/<?= rawurlencode($p['image'] ?? 'm_s.jpg') ?>" alt="<?= htmlspecialchars($p['nom'] ?? '') ?>">
                        <h3><?= htmlspecialchars($p['nom'] ?? '') ?></h3>
                        <p><?= number_format($prixProduit, 2) ?> DT</p>
                        <small style="display:block; color: var(--text-light); margin-top:6px;">
                            Prix promo : <?= number_format($prixPromo, 2) ?> DT
                        </small>
                        <a href="ajouter_panier.php?id=<?= (int)$p['id'] ?>" class="btn">Ajouter au panier</a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

        <?php else: ?>
            <?php if (empty($promos)): ?>
                <p>Aucune promotion active.</p>
            <?php else: ?>
                <?php foreach ($promos as $promoId => $promoBlock): ?>
                    <div class="admin-card" style="margin-bottom:28px;">
                        <h3 style="margin-bottom:10px;">🔥 <?= htmlspecialchars($promoBlock['titre'] ?? '') ?></h3>
                        <?php if (!empty($promoBlock['description'])): ?>
                            <p style="color: var(--text-light); margin-top:0;"><?= htmlspecialchars($promoBlock['description']) ?></p>
                        <?php endif; ?>

                        <div class="produits-grid" style="margin-top:14px;">
                            <?php foreach ($promoBlock['produits'] as $p): ?>
                                <div class="produit-card">
                                    <img loading="lazy" decoding="async" fetchpriority="low" src="/medninestore/img/<?= rawurlencode($p['image'] ?? 'm_s.jpg') ?>" alt="<?= htmlspecialchars($p['nom'] ?? '') ?>">
                                    <h3><?= htmlspecialchars($p['nom'] ?? '') ?></h3>
                                    <p><?= number_format((float)$p['prix'], 2) ?> DT</p>
                                    <small style="display:block; color: var(--text-light); margin-top:6px;">
                                        Prix promo : <?= number_format((float)$p['prix_promo'], 2) ?> DT
                                    </small>
                                    <a href="ajouter_panier.php?id=<?= (int)$p['id'] ?>" class="btn">Ajouter au panier</a>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div style="margin-top:12px;">
                            <a class="btn btn-small btn-outline" href="promotion.php?id=<?= (int)$promoId ?>">Voir détails</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        <?php endif; ?>

    </div>
</section>

<?php include 'includes/footer.php'; ?>

