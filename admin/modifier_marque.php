<?php
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

require __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/header.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    die('Marque introuvable');
}

// Récupérer les catégories
$categories = $conn->query("SELECT id, nom FROM categories ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);

// Charger la marque
$stmt = $conn->prepare("SELECT id, nom, categorie_id, logo FROM marques WHERE id = ?");
$stmt->execute([$id]);
$marque = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$marque) {
    die('Marque introuvable');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $categorie_id = (int)($_POST['categorie_id'] ?? 0);

    if ($nom === '' || $categorie_id <= 0) {
        $error = 'Nom et catégorie sont obligatoires.';
    } else {
        $logoName = $marque['logo'] ?? null;

        // Si un nouveau logo est uploadé, on remplace
        if (!empty($_FILES['logo']['name'])) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            $type = $_FILES['logo']['type'] ?? '';

            if (!in_array($type, $allowedTypes, true)) {
                $error = 'Format de logo non autorisé.';
            } else {
                $logoName = time() . '_' . basename($_FILES['logo']['name']);

                $targetDir = __DIR__ . '/../img/marques/';
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0777, true);
                }

                if (!move_uploaded_file($_FILES['logo']['tmp_name'], $targetDir . $logoName)) {
                    $error = 'Impossible d’envoyer le fichier logo.';
                } else {
                    // Optionnel : supprimer l’ancien logo (si différent)
                    if (!empty($marque['logo']) && $marque['logo'] !== $logoName) {
                        $oldPath = $targetDir . $marque['logo'];
                        if (is_file($oldPath)) {
                            @unlink($oldPath);
                        }
                    }
                }
            }
        }

        if (!$error) {
            $stmtU = $conn->prepare("UPDATE marques SET nom = ?, categorie_id = ?, logo = ?, actif = 1 WHERE id = ?");
            $stmtU->execute([$nom, $categorie_id, $logoName, $id]);
            $success = 'Marque mise à jour.';

            header('Location: marques.php');
            exit;
        }
    }
}
?>

<section class="page-header">
    <h1>Modifier <span>Marque</span></h1>
</section>

<section class="section">
    <div class="compte-card">
        <h3>Modifier la marque</h3>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" class="admin-form">
            <div class="form-group">
                <label>Nom de la marque</label>
                <input type="text" name="nom" value="<?= htmlspecialchars($marque['nom'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label>Catégorie</label>
                <select name="categorie_id" required>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= (int)$c['id'] ?>" <?= ((int)$marque['categorie_id'] === (int)$c['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['nom']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Logo actuel</label><br>
                <?php if (!empty($marque['logo'])): ?>
                    <img src="../img/marques/<?= htmlspecialchars($marque['logo']) ?>" alt="" style="width:60px;height:60px;object-fit:contain;" />
                <?php else: ?>
                    <span>Aucun logo</span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label>Nouveau logo</label>
                <input type="file" name="logo" accept="image/*">
            </div>

            <div class="admin-actions">
                <a href="marques.php" class="btn btn-outline">← Retour</a>
                <button type="submit" class="btn">💾 Enregistrer</button>
            </div>
        </form>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>

