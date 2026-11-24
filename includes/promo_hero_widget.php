<?php
// Widget compact pour la bannière (hero) - 1 seule promotion active

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($conn)) {
    require __DIR__ . '/../includes/db.php';
}

$today = date('Y-m-d');

$sql = "
    SELECT 
      pr.id AS promotion_id,
      pr.titre,
      pr.description,
      pr.type_promo,
      pr.pourcentage,
      pr.prix_fixe,
      p.nom AS produit_nom,
      p.image AS produit_image,
      p.prix AS produit_prix
    FROM promotions pr
    INNER JOIN promotion_produits pp ON pp.promotion_id = pr.id
    INNER JOIN produits p ON p.id = pp.produit_id
    WHERE pr.actif = 1
      AND (pr.date_debut IS NULL OR pr.date_debut <= :today)
      AND (pr.date_fin IS NULL OR pr.date_fin >= :today)
    ORDER BY pr.date_creation DESC
    LIMIT 1
";

$stmt = $conn->prepare($sql);
$stmt->execute(['today' => $today]);
$promo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$promo) {
    return;
}

$prixProduit = (float)($promo['produit_prix'] ?? 0);
$prixPromo = $prixProduit;

$pf = $promo['prix_fixe'] ?? null;
$pc = $promo['pourcentage'] ?? null;

type_promo($promo, $pf, $pc, $prixProduit, $prixPromo);

function type_promo($promo, $pf, $pc, $prixProduit, &$prixPromo) {
    if ($pf !== null && $pf !== '') {
        $prixPromo = (float)$pf;
        return;
    }
    if ($pc !== null && $pc !== '') {
        $pourcentageVal = (float)$pc;
        $prixPromo = $prixProduit * (1 - ($pourcentageVal / 100));
        return;
    }
}

// Construire un texte promo pro
$promoText = '';

if ($pf !== null && $pf !== '') {
    $promoText = 'Prix fixe';
} elseif ($pc !== null && $pc !== '') {
    $promoText = '- ' . trim((string)$pc) . '%';
} else {
    $promoText = 'Offre spéciale';
}
?>

<div class="hero-promo-card">
    <span class="promo-label">🔥 PROMO</span>

    <h2 class="hero-promo-title"><?= htmlspecialchars($promoText) ?></h2>

    <?php if (!empty($promo['titre'])): ?>
        <p class="hero-promo-subtitle"><?= htmlspecialchars($promo['titre']) ?></p>
    <?php endif; ?>

    <?php if (!empty($promo['description'])): ?>
        <p class="hero-promo-desc"><?= htmlspecialchars($promo['description']) ?></p>
    <?php endif; ?>

    <div class="hero-promo-product">
        <?php if (!empty($promo['produit_image'])): ?>
            <div class="hero-promo-image">
<img loading="eager" decoding="async" fetchpriority="high" src="/medninestore/img/<?= rawurlencode($promo['produit_image']) ?>" alt="<?= htmlspecialchars($promo['produit_nom'] ?? 'Produit promo') ?>" width="64" height="64">

            </div>
        <?php endif; ?>

        <?php if (!empty($promo['produit_nom'])): ?>
            <div class="hero-promo-product-name"><?= htmlspecialchars($promo['produit_nom']) ?></div>
        <?php endif; ?>
    </div>

    <div class="hero-promo-prices">
        <span class="price-old"><?= number_format($prixProduit, 2) ?> DT</span>
        <span class="price-new"><?= number_format($prixPromo, 2) ?> DT</span>
    </div>
    <a class="btn btn-small hero-promo-btn" href="promotion.php?id=<?= (int)($promo['promotion_id'] ?? 0) ?>">Voir</a>
</div>


