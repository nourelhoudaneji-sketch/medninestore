<?php
include 'includes/header.php';
include 'includes/db.php';

if (!isset($_GET['id'])) {
    die("Catégorie introuvable");
}

$categorie_id = (int) $_GET['id'];

// Catégorie
$stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
$stmt->execute([$categorie_id]);
$categorie = $stmt->fetch();

if (!$categorie) {
    die("Catégorie inexistante");
}

// Produits
$stmt = $conn->prepare("SELECT * FROM produits WHERE categorie_id = ?");
$stmt->execute([$categorie_id]);
$produits = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<section class="page-header">
    <h1><?= htmlspecialchars($categorie['nom']) ?></h1>
</section>

<section class="section produits-grid">

<?php if ($produits): ?>
    <?php foreach ($produits as $p): ?>
        <div class="produit-card">
<img loading="lazy" decoding="async" fetchpriority="low" src="img/<?= $p['image'] ?: 'par.jpg' ?>" alt="">
            <h3><?= htmlspecialchars($p['nom']) ?></h3>
            <p><?= number_format($p['prix'], 2) ?> DT</p>

            <a href="ajouter_panier.php?id=<?= $p['id'] ?>" class="btn">
                🛒 Ajouter au panier
            </a>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <p>Aucun produit dans cette catégorie.</p>
<?php endif; ?>

</section>

<?php include 'includes/footer.php'; ?>
