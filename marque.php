<?php
session_start();
$_SESSION['retour_produits'] = $_SERVER['REQUEST_URI'];
include 'includes/db.php';
include 'includes/header.php';

$marque_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($marque_id <= 0) {
    die('Marque introuvable');
}

// Marque + catégorie
$stmt = $conn->prepare("SELECT m.id AS marque_id, m.nom AS marque, m.logo, c.id AS categorie_id, c.nom AS categorie
                       FROM marques m
                       INNER JOIN categories c ON c.id = m.categorie_id
                       WHERE m.id = ?");
$stmt->execute([$marque_id]);
$marque = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$marque) {
    die('Marque introuvable');
}

// 0 => tous, sinon on filtre par catégorie
$filtree_categorie_id = isset($_GET['categorie']) ? (int)$_GET['categorie'] : 0;

$categorie_id = (int)$marque['categorie_id'];

// Produits de cette marque (uniquement)
$sqlP = "SELECT * FROM produits WHERE marque_id = ?";
$paramsP = [$marque_id];

if ($filtree_categorie_id > 0) {
    $sqlP .= " AND categorie_id = ?";
    $paramsP[] = $filtree_categorie_id;
}

$sqlP .= " ORDER BY nouveau DESC, id DESC";
$stmtP = $conn->prepare($sqlP);
$stmtP->execute($paramsP);
$produits = $stmtP->fetchAll(PDO::FETCH_ASSOC);
?>

<section class="page-header">
    <h1 style="display:flex; align-items:center; gap:12px;">
        <?php $logo = $marque['logo'] ?? null; ?>
<?php if (!empty($logo)): ?>
        <img loading="lazy" decoding="async" fetchpriority="low" src="/medninestore/img/marques/<?= rawurlencode($logo) ?>" alt="" style="width:200px; height:auto; max-height:200px; object-fit:contain;">
        <?php endif; ?>
        <?= htmlspecialchars($marque['marque']) ?> <span>Collection</span>
    </h1>
</section>

<section class="section">
    <div class="categories-bar" style="margin-bottom: 18px;">
        <a href="marques.php?categorie=<?= (int)$categorie_id ?>" class="active">Retour <?= htmlspecialchars($marque['categorie']) ?></a>
        <span style="margin: 0 8px;"></span>
        <a href="produits.php?categorie=<?= (int)$categorie_id; ?>">Voir la page Parfums</a>
    </div>



    <div class="produits-grid">
        <?php if (empty($produits)): ?>
            <p>Aucun produit pour cette marque.</p>
        <?php else: ?>
            <?php foreach ($produits as $p): ?>
                <div class="produit-card" id="produit-<?= (int)$p['id'] ?>">
<?php $imgName = $p['image'] ?? ''; ?>
<?php if (!empty($imgName)): ?>
                    <div class="produit-image-wrap">
                        <img loading="lazy" decoding="async" fetchpriority="low" src="/medninestore/img/<?= rawurlencode($imgName) ?>" alt="<?= htmlspecialchars($p['nom']) ?>">
                        <?php if (!empty($p['nouveau'])): ?>
                            <span class="badge-nouveau">✨ Nouveau</span>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div style="width:100%;height:180px;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,0.04);border-radius:var(--radius-lg);color:var(--text-light);">Image indisponible</div>
                <?php endif; ?>

                    <h3><?= htmlspecialchars($p['nom']) ?></h3>
                    <p><?= number_format((float)$p['prix'], 2) ?> DT</p>
                    <div class="btn-row">
                        <a href="ajouter_panier.php?id=<?= (int)$p['id'] ?>" class="btn btn-small">🛒 Ajouter</a>
                        <a href="produit.php?id=<?= (int)$p['id'] ?>" class="btn btn-small btn-outline">Détails</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>

