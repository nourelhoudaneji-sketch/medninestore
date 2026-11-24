
<?php
session_start();

include 'includes/db.php';
include 'includes/header.php';

// Récupérer les catégories
$categories = $conn->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);

// Catégorie sélectionnée
$categorie_id = isset($_GET['categorie']) ? (int)$_GET['categorie'] : 0;

// Recherche
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$marques = [];
$produits = [];

// Trouver la catégorie Cosmétiques
$catCosmetiqueId = null;

$stmtCat = $conn->query("SELECT id, nom FROM categories");

foreach ($stmtCat->fetchAll(PDO::FETCH_ASSOC) as $c) {
    $nomCat = isset($c['nom']) ? (string)$c['nom'] : '';

    if (stripos($nomCat, 'cosm') !== false) {
        $catCosmetiqueId = (int)$c['id'];
        break;
    }
}

// IDs des catégories
$categorieParfumsHFId = 8;
$catAccessoiresId = 5;
$catSkincareId = 9;

// Déterminer le type d'affichage
$showMarques =
    ($catCosmetiqueId !== null && $categorie_id === $catCosmetiqueId)
    || ($categorie_id === $categorieParfumsHFId);

$showProduitsAccessSkincare =
    ($categorie_id === $catAccessoiresId)
    || ($categorie_id === $catSkincareId);

// Récupérer les marques ou les produits
if ($showMarques) {

    $stmtM = $conn->prepare(
        "SELECT DISTINCT m.id, m.nom, m.logo
         FROM marques m
         INNER JOIN produits p ON p.marque_id = m.id
         WHERE p.categorie_id = ?
         ORDER BY m.nom"
    );

    $stmtM->execute([$categorie_id]);
    $marques = $stmtM->fetchAll(PDO::FETCH_ASSOC);

} else {

    $orderSQL = "ORDER BY p.nouveau DESC, p.id DESC";

    if ($categorie_id === 0) {

        $sql = "SELECT p.*, 
               c.nom AS categorie_nom,
               m.nom AS marque_nom
        FROM produits p
        LEFT JOIN categories c ON c.id = p.categorie_id
        LEFT JOIN marques m ON m.id = p.marque_id";

        $params = [];

        if (!empty($search)) {
            $sql .= " WHERE p.nom LIKE ?";
            $params[] = $search . '%';
        }

        $sql .= " $orderSQL";

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);

        $produits = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } else {

        if ($showProduitsAccessSkincare) {

            $sql = "SELECT p.*, 
               c.nom AS categorie_nom,
               m.nom AS marque_nom
        FROM produits p
        LEFT JOIN categories c ON c.id = p.categorie_id
        LEFT JOIN marques m ON m.id = p.marque_id
        WHERE p.categorie_id = ?
        AND p.marque_id = 0";

            $params = [$categorie_id];

        } else {

            $sql = "SELECT p.*, 
               c.nom AS categorie_nom,
               m.nom AS marque_nom
        FROM produits p
        LEFT JOIN categories c ON c.id = p.categorie_id
        LEFT JOIN marques m ON m.id = p.marque_id
        WHERE p.categorie_id = ?";

            $params = [$categorie_id];
        }

        if (!empty($search)) {
            $sql .= " AND p.nom LIKE ?";
            $params[] = $search . '%';
        }

        $sql .= " $orderSQL";

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);

        $produits = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

<!-- En-tête de la page -->
<section class="page-header">
    <h1>Nos <span>Produits</span></h1>
</section>

<!-- Recherche et catégories -->
<div class="catalogue-controls">

    <!-- Barre de recherche -->
    <form method="GET" action="produits.php" class="catalogue-search">

        <?php if ($categorie_id > 0): ?>
            <input
                type="hidden"
                name="categorie"
                value="<?= $categorie_id ?>"
            >
        <?php endif; ?>

        <input
            type="text"
            name="search"
            placeholder="🔍 Rechercher un produit..."
            value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"
        >

        <button type="submit" class="btn btn-small">
            Rechercher
        </button>

        <?php if (!empty($search)): ?>
            <a
                href="produits.php<?= $categorie_id > 0 ? '?categorie=' . $categorie_id : '' ?>"
                class="btn btn-small btn-outline"
                aria-label="Effacer la recherche"
            >
                ✕
            </a>
        <?php endif; ?>

    </form>

    <!-- Barre des catégories -->
    <div class="categories-bar">

        <a
            href="produits.php"
            class="<?= $categorie_id == 0 ? 'active' : '' ?>"
        >
            Tous
        </a>

        <?php
        // Ordre d'affichage des catégories
        $ordreNoms = [
            'Parfums Homme/Femme',
            'Cosmétiques',
            'Skincare',
            'Accessoires'
        ];

        $catsByName = [];

        foreach ($categories as $cat) {
            $key = mb_strtolower(trim((string)$cat['nom']), 'UTF-8');
            $catsByName[$key] = $cat;
        }

        // Afficher les catégories dans l'ordre demandé
        foreach ($ordreNoms as $nom) {

            $key = mb_strtolower($nom, 'UTF-8');

            if (isset($catsByName[$key])) {

                $cat = $catsByName[$key];
                ?>

                <a
                    href="produits.php?categorie=<?= (int)$cat['id'] ?>"
                    class="<?= $categorie_id == (int)$cat['id'] ? 'active' : '' ?>"
                >
                    <?= htmlspecialchars($cat['nom'], ENT_QUOTES, 'UTF-8') ?>
                </a>

                <?php
            }
        }

        // Afficher les autres catégories
        $ordreSet = array_map(
            fn($x) => mb_strtolower($x, 'UTF-8'),
            $ordreNoms
        );

        foreach ($categories as $cat) {

            $key = mb_strtolower(trim((string)$cat['nom']), 'UTF-8');

            if (in_array($key, $ordreSet, true)) {
                continue;
            }
            ?>

            <a
                href="produits.php?categorie=<?= (int)$cat['id'] ?>"
                class="<?= $categorie_id == (int)$cat['id'] ? 'active' : '' ?>"
            >
                <?= htmlspecialchars($cat['nom'], ENT_QUOTES, 'UTF-8') ?>
            </a>

            <?php
        }
        ?>

    </div>

</div>

<!-- Contenu du catalogue -->
<section class="section catalogue-section">

    <?php if ($showMarques && !$showProduitsAccessSkincare): ?>

        <!-- Affichage des marques -->
        <?php if (empty($marques)): ?>

            <p class="catalogue-empty">Aucune marque trouvée.</p>

        <?php else: ?>

            <div class="produits-grid">

                <?php foreach ($marques as $m): ?>

                    <div class="produit-card marque-card">

                        <img
                            loading="lazy"
                            decoding="async"
                            src="/medninestore/img-resize.php?src=img/marques/<?= rawurlencode($m['logo']) ?>&w=64"
                            alt="<?= htmlspecialchars($m['nom'], ENT_QUOTES, 'UTF-8') ?>"
                            width="64"
                            height="64"
                        >

                        <h2>
                            <?= htmlspecialchars($m['nom'], ENT_QUOTES, 'UTF-8') ?>
                        </h2>

                        <a
                            href="marque.php?id=<?= (int)$m['id'] ?>&categorie=<?= (int)$categorie_id ?>"
                            class="btn"
                        >
                            Voir les parfums de cette marque
                        </a>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    <?php elseif ($categorie_id === 0 || $showProduitsAccessSkincare): ?>

        <!-- Affichage des produits -->
        <div class="produits-grid">

            <?php if (empty($produits)): ?>

                <p class="catalogue-empty">Aucun produit trouvé.</p>

            <?php endif; ?>

            <?php foreach ($produits as $p): ?>

                <div class="produit-card">

                    <div class="produit-image-wrap">

                        <img
                            loading="lazy"
                            decoding="async"
                            src="/medninestore/img/<?= rawurlencode($p['image']) ?>?v=<?= file_exists(__DIR__ . '/img/' . $p['image']) ? filemtime(__DIR__ . '/img/' . $p['image']) : time() ?>"
                            alt="<?= htmlspecialchars($p['nom'], ENT_QUOTES, 'UTF-8') ?>"
                        >

                        <?php if (!empty($p['nouveau'])): ?>

                            <span class="badge-nouveau">
                                ✨ Nouveau
                            </span>

                        <?php endif; ?>

                    </div>

                    <h3>
                        <?= htmlspecialchars($p['nom'], ENT_QUOTES, 'UTF-8') ?>
                    </h3>

                    <p class="produit-prix">
                        <?= number_format((float)$p['prix'], 2) ?> DT
                    </p>

                    <p class="produit-description">
                        <?= htmlspecialchars(
                            mb_substr($p['description'] ?? '', 0, 120),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </p>
<h3>
                    <small class="produit-marque">
    Marque :
    <?= htmlspecialchars($p['marque_nom'], ENT_QUOTES, 'UTF-8') ?>
</small></h3>

                    <div class="btn-row">

                        <a
                            href="ajouter_panier.php?id=<?= (int)$p['id'] ?>"
                            class="btn btn-small"
                        >
                            🛒 Ajouter
                        </a>

                        <a
                            href="produit.php?id=<?= (int)$p['id'] ?>"
                            class="btn btn-small btn-outline"
                        >
                            Détails
                        </a>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    <?php else: ?>

        <p class="catalogue-empty">
            Aucune marque disponible pour cette catégorie.
        </p>

    <?php endif; ?>

</section>

<?php include 'includes/footer.php'; ?>
