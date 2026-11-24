<?php
session_start();
include 'includes/db.php';
include 'includes/header.php';

// Filtre Homme/Femme via categorie
$categories = $conn->query("SELECT id, nom FROM categories ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);
$categorie_id = isset($_GET['categorie']) ? (int)$_GET['categorie'] : 0; // 0 => tous

// Récupérer les marques pour la catégorie choisie (Homme/Femme)
// IMPORTANT: sur ta table categories, le libellé “Parfums Homme/Femme” existe (id=8)
// mais tu veux lister uniquement les marques qui appartiennent à un Homme OU Femme.
// Donc on filtre sur categorie_id seulement si la catégorie choisie est un Homme ou une Femme.

$sqlM = "SELECT id, nom, logo FROM marques";
$paramsM = [];
if ($categorie_id > 0) {
    $sqlM .= " WHERE categorie_id = ?";
    $paramsM[] = $categorie_id;
}

// Empêche les doublons à l'affichage
$sqlM .= " GROUP BY id, nom, logo ORDER BY nom";
$stmtM = $conn->prepare($sqlM);
$stmtM->execute($paramsM);
$marques = $stmtM->fetchAll(PDO::FETCH_ASSOC);
?>

<section class="page-header">
    <h1>Nos <span>Parfums</span></h1>
</section>

<section class="section">
    <div class="categories-bar">
        <a href="marques.php" class="<?= $categorie_id==0?'active':'' ?>">Tous</a>
        <?php foreach ($categories as $cat): ?>
            <a href="marques.php?categorie=<?= (int)$cat['id'] ?>" class="<?= $categorie_id==(int)$cat['id']?'active':'' ?>">
                <?= htmlspecialchars($cat['nom']) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <div style="margin-top: 18px;">
        <?php if (empty($marques)): ?>
            <p>Aucune marque trouvée.</p>
        <?php else: ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 12px;">
                <?php foreach ($marques as $m): ?>
                    <a href="marque.php?id=<?= (int)$m['id'] ?>" style="text-decoration:none; color: inherit;">
                        <div style="padding: 12px; border: 1px dashed rgba(0,0,0,0.15); border-radius: 10px;">
<?php $logo = $m['logo'] ?? null; ?>
                            <?php if (!empty($logo)): ?>
<img
    loading="lazy"
    src="img/marques/<?= rawurlencode($logo) ?>"
    alt="<?= htmlspecialchars($m['nom']) ?>"
    style="width:40px;height:40px;object-fit:contain;margin-right:10px;vertical-align:middle;"
    width="40"
    height="40">
                            <?php endif; ?>
                            <strong><?= htmlspecialchars($m['nom']) ?></strong>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>

