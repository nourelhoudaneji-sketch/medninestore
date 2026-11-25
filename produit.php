<?php
session_start();
include 'includes/db.php';
include 'includes/header.php';

// Récupérer l'ID du produit depuis l'URL
$produit_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
// Retour vers la page d'origine (marque.php, produits.php avec filtres, etc.)
$retour = 'produits.php';
$ref = $_SERVER['HTTP_REFERER'] ?? '';
if ($ref && parse_url($ref, PHP_URL_HOST) === ($_SERVER['HTTP_HOST'] ?? '')) {
    $retour = strtok($ref, '#'); // enlève une éventuelle ancre existante
}
$retour .= '#produit-' . $produit_id;
$produit = null;
if ($produit_id > 0) {
    $stmt = $conn->prepare(
        "SELECT p.*, c.nom AS categorie_nom
         FROM produits p
         LEFT JOIN categories c ON c.id = p.categorie_id
         WHERE p.id = ?"
    );
    $stmt->execute([$produit_id]);
    $produit = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<section class="page-header">
    <h1>Détail <span>Produit</span></h1>
</section>

<section class="section">
<?php if ($produit): ?>
    <div class="produit-detail" style="display:flex; flex-wrap:wrap; gap:30px; max-width:900px; margin:0 auto;">
        <div style="flex:1; min-width:280px;">
            <?php
                $imgFile = $produit['image'] ?? 'm_s.jpg';
                $imgPath = __DIR__ . '/img/' . $imgFile;
                $imgVersion = (file_exists($imgPath)) ? filemtime($imgPath) : time();
            ?>
            <img loading="lazy" decoding="async" fetchpriority="high"
                 src="/medninestore/img/<?= rawurlencode($imgFile) ?>?v=<?= $imgVersion ?>"
                 alt="<?= htmlspecialchars($produit['nom']) ?>"
                 style="width:100%; max-width:400px; height:auto; border-radius:var(--radius-lg); object-fit:cover;">
        </div>
        <div style="flex:1; min-width:280px;">
            <h2 style="font-size:1.8rem; margin-bottom:8px;"><?= htmlspecialchars($produit['nom']) ?></h2>
            <?php if (!empty($produit['categorie_nom'])): ?>
                <small style="display:block; color:var(--text-faded); margin-bottom:12px;">
                    Catégorie : <?= htmlspecialchars($produit['categorie_nom']) ?>
                </small>
            <?php endif; ?>
            <p style="font-size:1.6rem; font-weight:600; color:var(--accent); margin:16px 0;">
                <?= number_format((float)$produit['prix'], 2) ?> DT
            </p>
            <p style="line-height:1.7; color:var(--text); margin-bottom:20px;">
                <?= nl2br(htmlspecialchars($produit['description'])) ?>
            </p>
            <a href="ajouter_panier.php?id=<?= (int)$produit['id'] ?>" class="btn" style="margin-right:10px;">
                🛒 Ajouter au panier
            </a>
           <a href="<?= htmlspecialchars($retour, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline">← Retour aux produits</a>
        </div>
    </div>
<?php else: ?>
    <div class="empty-state">
        <h2>Produit introuvable</h2>
        <p>Ce produit n'existe pas ou a été supprimé.</p>
        <a href="produits.php" class="btn">Voir tous les produits</a>
    </div>
<?php endif; ?>
</section>

<?php include 'includes/footer.php'; ?>
