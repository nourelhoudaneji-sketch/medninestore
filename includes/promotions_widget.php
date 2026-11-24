<?php
// Affiche une section promotions claire et attractive sur la page d'accueil
// Source DB : promotions + promotion_produits + produits

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($conn)) {
    // Si inclus sans connexion, tenter de charger
    require __DIR__ . '/../includes/db.php';
}


$today = date('Y-m-d');

// Mode compact pour la bannière (hero) : affiche uniquement la promotion la plus récente.
// Utilisation: $mode = 'hero'; (optionnel) et/ou $limit_promo = 1
$mode = $mode ?? 'default';
$limit_promo = isset($limit_promo) ? (int)$limit_promo : null;

// Par défaut on affiche toutes les promotions.
// (Si $limit_promo est défini ailleurs, on le respecte.)




$sql = "
    SELECT 
      pr.id AS promotion_id,
      pr.titre,
      pr.description,
      pr.type_promo,
      pr.pourcentage,
      pr.prix_fixe,
      pp.produit_id,
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
";

$stmt = $conn->prepare($sql);
$stmt->execute(['today' => $today]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($rows)) {
    return;
}


// Regrouper par promotion (on affiche au max 3 produits par promotion)
$promos = [];
foreach ($rows as $r) {
    $pid = (int)$r['promotion_id'];

    if (!isset($promos[$pid])) {
        // $limit_promo n'est utilisé que pour le mode compact/hero.
        // Dans le widget normal, on l'ignore pour afficher toutes les promotions.
        if (isset($limit_promo) && $limit_promo !== null && $mode === 'hero') {
            if (count($promos) >= $limit_promo) {
                continue;
            }
        }

        $promos[$pid] = [
            'titre' => $r['titre'],
            'description' => $r['description'],
            'type_promo' => $r['type_promo'],
            'pourcentage' => $r['pourcentage'],
            'prix_fixe' => $r['prix_fixe'],
            'produits' => []
        ];
    }

    if (count($promos[$pid]['produits']) < 3) {


        $produitPrix = (float)$r['produit_prix'];
        $nouveauPrix = $produitPrix;

        $type = $r['type_promo'];
        $pf = $r['prix_fixe'];
        $pc = $r['pourcentage'];

        // Priorité: prix_fixe (comme choisi) quand disponible.
        if ($pf !== null && $pf !== '') {
            $nouveauPrix = (float)$pf;
        } elseif ($pc !== null && $pc !== '') {
            $pourcentageVal = (float)$pc;
            $nouveauPrix = $produitPrix * (1 - ($pourcentageVal / 100));
        }

        $promos[$pid]['produits'][] = [
'nom' => $r['produit_nom'],
            'image' => $r['produit_image'],
            'produit_id' => $r['produit_id'] ?? null,
            'prix' => $produitPrix,
            'prix_promo' => $nouveauPrix
        ];
    }
}
?>

<section class="promotions-section">
    <div class="promotions-grid">
        <?php foreach ($promos as $promoId => $promo): ?>
            <div class="promotion-card">
                <div class="promotion-badge">🔥 Promo</div>

                <h3 class="promotion-title"><?= htmlspecialchars($promo['titre'] ?? '') ?></h3>
                <?php if (!empty($promo['description'])): ?>
                    <p class="promotion-desc"><?= htmlspecialchars($promo['description']) ?></p>
                <?php endif; ?>

                <div class="promotion-products">
                    <?php foreach ($promo['produits'] as $prod): ?>
                        <div class="promo-product">
                <div class="promo-product-image">
                                <?php if (!empty($prod['image'])): ?>
<img loading="lazy" decoding="async" fetchpriority="low" src="/medninestore/img/<?= rawurlencode($prod['image']) ?>" alt="<?= htmlspecialchars($prod['nom']) ?>" width="90" height="50">
                                <?php endif; ?>
                            </div>

                            <div class="promo-product-info">
                                <div class="promo-product-name"><?= htmlspecialchars($prod['nom']) ?></div>
                                <div class="promo-product-prices">
                                    <span class="price-old"><?= number_format((float)$prod['prix'], 2) ?> DT</span>
                                    <span class="price-new"><?= number_format((float)$prod['prix_promo'], 2) ?> DT</span>
                                </div>
<a class="btn btn-small" href="promotion.php?id=<?= (int)$promoId ?>" style="margin-top:10px;">Voir</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php
// fin widget promotions
?>


