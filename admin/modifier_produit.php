<?php
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

require '../includes/db.php';
$id = $_GET['id'];

$produit = $conn->prepare("SELECT * FROM produits WHERE id=?");
$produit->execute([$id]);
$p = $produit->fetch();
// Récupérer les marques enregistrées
$stmtMarques = $conn->prepare("SELECT id, nom FROM marques ORDER BY nom ASC");

$marques = $stmtMarques->fetchAll(PDO::FETCH_ASSOC);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nouveau = isset($_POST['nouveau']) ? 1 : 0;
    $marque_id = !empty($_POST['marque_id']) ? (int) $_POST['marque_id'] : null;

    // Gestion de l'upload d'image
    $imageName = $p['image']; // conserver l'ancienne image par défaut
    if (!empty($_FILES['image']['name'])) {
        // Vérifier les erreurs d'upload
        $uploadError = $_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($uploadError !== UPLOAD_ERR_OK) {
            $uploadErrorMessages = [
                UPLOAD_ERR_INI_SIZE => 'Le fichier dépasse la taille maximale autorisée par le serveur.',
                UPLOAD_ERR_FORM_SIZE => 'Le fichier dépasse la taille maximale du formulaire.',
                UPLOAD_ERR_PARTIAL => 'Le fichier n\'a été que partiellement téléchargé.',
                UPLOAD_ERR_NO_FILE => 'Aucun fichier sélectionné.',
                UPLOAD_ERR_NO_TMP_DIR => 'Dossier temporaire manquant.',
                UPLOAD_ERR_CANT_WRITE => 'Échec de l\'écriture du fichier sur le disque.',
            ];
            $error = $uploadErrorMessages[$uploadError] ?? 'Erreur inconnue lors de l\'upload.';
        } else {
            // Valider l'extension du fichier (plus fiable que le MIME type)
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            $origExt = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            if (!in_array($origExt, $allowedExtensions, true)) {
                $error = "Extension de fichier non autorisée ($origExt). Utilisez : jpg, jpeg, png, webp, gif.";
            } else {
                // Nettoyer le nom du fichier
                $origName = pathinfo($_FILES['image']['name'], PATHINFO_FILENAME);
                $cleanOrig = preg_replace('/[^a-zA-Z0-9_-]/', '_', $origName);
                $cleanOrig = preg_replace('/_+/', '_', $cleanOrig);
                $cleanOrig = trim($cleanOrig, '_');
                if (empty($cleanOrig)) $cleanOrig = 'produit';
                $imageName = time() . '_' . $cleanOrig . '.' . $origExt;

                // Déplacer le fichier uploadé
                $targetPath = __DIR__ . '/../img/' . $imageName;
                if (!move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                    $error = "Impossible de déplacer le fichier uploadé vers : img/$imageName";
                } else {
                    // Supprimer l'ancienne image si elle existe et différente
                    if (!empty($p['image']) && $p['image'] !== $imageName) {
                        $oldPath = __DIR__ . '/../img/' . $p['image'];
                        if (file_exists($oldPath)) {
                            @unlink($oldPath);
                        }
                    }
                }
            }
        }
    }

    if (empty($error)) {
        $stmt = $conn->prepare(
            "UPDATE produits SET nom=?, description=?, prix=?, image=?, nouveau=?, marque_id=? WHERE id=?"
        );
        $stmt->execute([
            $_POST['nom'],
            $_POST['description'],
            $_POST['prix'],
            $imageName,
            $nouveau,
            $marque_id,
            $id
        ]);

        header("Location: index.php?success=1");
        exit();
    }
}
?>

<link rel="stylesheet" href="../style.css">

<section class="admin-section">
    <div class="admin-title">
        <h1>Modifier produit</h1>
        <p>Mise à jour de la collection Mednine Store</p>
    </div>

    <div class="admin-card">

        <?php if (!empty($error)): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form class="admin-form" method="POST" enctype="multipart/form-data">

            <div class="form-group">
                <label>Nom du produit</label>
                <input type="text" name="nom" required value="<?= htmlspecialchars($p['nom'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea name="description"><?= htmlspecialchars($p['description'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label>Prix (DT)</label>
                <input type="number" step="0.01" name="prix" required value="<?= htmlspecialchars($p['prix'] ?? '') ?>">
            </div>
            <div class="form-group">
    <label for="marque_id">Marque du produit</label>

    <select name="marque_id" id="marque_id">
        <option value="">-- Sélectionner une marque --</option>

        <?php foreach ($marques as $marque): ?>
            <option value="<?= (int)$marque['id'] ?>"
                <?= ((string)($p['marque_id'] ?? '') === (string)$marque['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($marque['nom']) ?>
            </option>
        <?php endforeach; ?>

    </select>
</div>

            <div class="form-group">
                <label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
                    <input type="checkbox" name="nouveau" value="1" id="check_nouveau" style="width:auto; margin:0;" <?= !empty($p['nouveau']) ? 'checked' : '' ?>>
                    <span>✨ Marquer comme <strong>Nouveau</strong> (affiche un badge sur la fiche produit)</span>
                </label>
            </div>

            <div class="admin-image-preview">
                <p>Image actuelle</p>
                <?php if (!empty($p['image']) && file_exists(__DIR__ . '/../img/' . $p['image'])): ?>
                    <img src="/medninestore/img/<?= rawurlencode($p['image']) ?>?v=<?= time() ?>" alt="Image actuelle" style="max-width:200px; border-radius:8px;">
                <?php else: ?>
                    <img src="/medninestore/img/m_s.jpg?v=<?= time() ?>" alt="Aucune image" style="max-width:200px; border-radius:8px;">
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label>Nouvelle image</label>
                <input type="file" name="image">
            </div>

            <div class="admin-actions">
                <a href="index.php" class="btn btn-secondary">← Retour</a>
                <button type="submit" class="btn">💾 Enregistrer</button>
            </div>

        </form>
    </div>
</section>


