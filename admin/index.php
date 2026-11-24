<?php
// admin/index.php
session_start();

//Sécurité : admin uniquement
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

require '../includes/db.php';

// Recherche
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Récupération des produits (filtrée par recherche si présent)
if (!empty($search)) {
    $stmt = $conn->prepare("SELECT * FROM produits WHERE nom LIKE ? ORDER BY id DESC");
    $stmt->execute([$search . '%']);
    $produits = $stmt->fetchAll();
} else {
    $produits = $conn->query("SELECT * FROM produits ORDER BY id DESC")->fetchAll();
}

// Requête pour promotions
$promoActivesStmt = $conn->prepare("SELECT COUNT(*) AS nb FROM promotions WHERE actif = 1 AND (date_debut IS NULL OR date_debut <= CURDATE()) AND (date_fin IS NULL OR date_fin >= CURDATE())");
$promoActivesStmt->execute();
$promoActives = (int)($promoActivesStmt->fetch(PDO::FETCH_ASSOC)['nb'] ?? 0);

// Récupération des administrateurs
$adminStmt = $conn->prepare(
    "SELECT id, nom, prenom, email, date_creation
     FROM utilisateurs
     WHERE role = 'admin'
     ORDER BY id DESC"
);
$adminStmt->execute();
$administrateurs = $adminStmt->fetchAll(PDO::FETCH_ASSOC);

// Token de sécurité pour la suppression
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Administration | Mednine Store</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>

<?php include '../includes/header.php'; ?>

<section class="section">
    <h2>📦 Liste des produits</h2>

    <?php if ($promoActives > 0): ?>
        <div class="alert alert-success" style="text-align:center;">🔥 Il y a des promotions actives.</div>
    <?php endif; ?>


    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">
            ✅ Opération réussie
        </div>
    <?php endif; ?>

    <!-- Barre de recherche -->
    <form method="GET" action="" style="display:flex; gap:10px; margin-bottom:30px; max-width:500px;">
        <input type="text" name="search" placeholder="🔍 Rechercher un produit..." value="<?= htmlspecialchars($search) ?>" style="flex:1; padding:12px 18px; border:2px solid var(--border); border-radius:50px; font-size:1em;">
        <button type="submit" class="btn btn-small" style="padding:12px 28px;">Rechercher</button>
        <?php if (!empty($search)): ?>
            <a href="index.php" class="btn btn-small btn-outline" style="padding:12px 20px;">✕</a>
        <?php endif; ?>
    </form>
    <a href="ajouter_produit.php" class="btn btn-small">
        ➕ Ajouter un produit
    </a>
    <table class="admin-table">
        <thead>
            <tr>
                <th>Image</th>
                <th>Nom</th>
                <th>Prix</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($produits as $p): ?>
                <tr>
                    <td>
                        <?php
$image = (!empty($p['image']) && file_exists(__DIR__ . '/../img/' . $p['image']))
    ? '/medninestore/img/' . $p['image'] . '?v=' . filemtime(__DIR__ . '/../img/' . $p['image'])
    : '/medninestore/img/default.jpg';
?>

<img src="<?= $image ?>" alt="<?= htmlspecialchars($p['nom']) ?>"width="60">


                       
                    </td>

                    <td><?= htmlspecialchars($p['nom']) ?></td>
                    <td><?= number_format($p['prix'], 2) ?> DT</td>

                    <td>
                        <a href="modifier_produit.php?id=<?= $p['id'] ?>">✏️</a>
                        <a href="supprimer_produit.php?id=<?= $p['id'] ?>"
                           onclick="return confirm('Supprimer ce produit ?')">🗑️</a>
                    </td>
                </tr>
            <?php endforeach; ?>

            <?php if (count($produits) === 0): ?>
                <tr>
                    <td colspan="4">Aucun produit disponible.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <br>



<?php include '../includes/footer.php'; ?>

</body>
</html>
