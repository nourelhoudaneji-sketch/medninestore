<?php
session_start();
require __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/header.php';

if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
    die('Accès refusé');
}


$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: promotions.php');
    exit;
}

$promo = null;
$stmt = $conn->prepare('SELECT * FROM promotions WHERE id = ?');
$stmt->execute([$id]);
$promo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$promo) {
    header('Location: promotions.php');
    exit;
}

// produits liés
$linkedProductIds = [];
$stmtLP = $conn->prepare('SELECT produit_id FROM promotion_produits WHERE promotion_id = ?');
$stmtLP->execute([$id]);
$linkedProductIds = $stmtLP->fetchAll(PDO::FETCH_COLUMN);
$linkedProductIds = array_map('intval', $linkedProductIds);

// liste produits
$products = [];
$stmtProd = $conn->query('SELECT id, nom, prix FROM produits ORDER BY nom ASC');
$products = $stmtProd->fetchAll(PDO::FETCH_ASSOC);

$success = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_promotion'])) {
    $titre = trim($_POST['titre'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $type = $_POST['type_promo'] ?? 'mix';

    $pourcentage = trim($_POST['pourcentage'] ?? '');
    $prixFixe = trim($_POST['prix_fixe'] ?? '');

    $dateDebut = trim($_POST['date_debut'] ?? '');
    $dateFin = trim($_POST['date_fin'] ?? '');

    $actif = isset($_POST['actif']) ? 1 : 0;




    $selectedProductIds = $_POST['produits'] ?? [];

    $selectedProductIds = array_values(array_filter(array_map('intval', (array)$selectedProductIds), fn($x) => $x > 0));


    if ($titre === '' || count($selectedProductIds) === 0) {

        $error = 'Titre requis et au moins un produit sélectionné.';
    } else {
        $pourcentageVal = null;
        $prixFixeVal = null;

        if ($type === 'pourcentage') {
            $pourcentageVal = ($pourcentage === '') ? null : (float)$pourcentage;
        } elseif ($type === 'mix') {
            if ($prixFixe !== '') $prixFixeVal = (float)$prixFixe;
            if ($prixFixeVal === null && $pourcentage !== '') $pourcentageVal = (float)$pourcentage;
        } else {
            $prixFixeVal = ($prixFixe === '') ? null : (float)$prixFixe;
        }

        if ($type === 'mix' && $prixFixeVal === null && $pourcentageVal === null) {
            $error = 'Pour le type mix, fournissez au moins un champ (prix fixe ou pourcentage).';
        } else {
            $stmtU = $conn->prepare(
                "UPDATE promotions
                 SET titre = ?, description = ?, pourcentage = ?, prix_fixe = ?, type_promo = ?, date_debut = ?, date_fin = ?, actif = ?, date_creation = date_creation
                 WHERE id = ?"

            );

            $stmtU->execute([
                $titre,
                $description,
                $pourcentageVal,
                $prixFixeVal,
                $type,
                $dateDebut ?: null,
                $dateFin ?: null,
                $id
            ]);

            // update linked products
            $stmtD = $conn->prepare('DELETE FROM promotion_produits WHERE promotion_id = ?');
            $stmtD->execute([$id]);

            $stmtP = $conn->prepare('INSERT INTO promotion_produits (promotion_id, produit_id) VALUES (?, ?)');
            foreach ($selectedProductIds as $pid) {
                $stmtP->execute([$id, $pid]);
            }

            $success = 'Promotion modifiée avec succès.';
        }
    }
}
?>

<section class="section">
    <h1>✏️ Modifier la promotion</h1>

    <?php if ($success): ?>
        <div class="alert alert-success" style="max-width:900px; margin:20px auto;">✅ <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error" style="max-width:900px; margin:20px auto;">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="admin-form" style="margin:30px auto; max-width:900px;">
        <h3 style="margin-bottom:20px; color: var(--secondary);">Détails</h3>

        <form method="post">
            <input type="hidden" name="update_promotion" value="1">
            <input type="hidden" name="id" value="<?= (int)$id ?>">

            <div class="form-group">
                <label>Titre</label>
                <input type="text" name="titre" required value="<?= htmlspecialchars($promo['titre'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="3"><?= htmlspecialchars($promo['description'] ?? '') ?></textarea>
            </div>

            <div class="form-group" style="display:grid; grid-template-columns: 1fr 1fr; gap:16px;">
                <div>
                    <label>Date début</label>
                    <input type="date" name="date_debut" value="<?= htmlspecialchars($promo['date_debut'] ?? '') ?>">
                </div>
                <div>
                    <label>Date fin</label>
                    <input type="date" name="date_fin" value="<?= htmlspecialchars($promo['date_fin'] ?? '') ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Type de remise</label>
                <select name="type_promo" id="type_promo">
                    <?php
                    $currentType = $promo['type_promo'] ?? 'mix';
                    $opts = ['prix_fixe' => 'Prix fixe (prioritaire)', 'pourcentage' => 'Pourcentage', 'mix' => 'Mix (prix fixe prioritaire)'];
                    foreach ($opts as $val => $label) {
                        $sel = $currentType === $val ? 'selected' : '';
                        echo "<option value=\"$val\" $sel>$label</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="form-group" style="display:grid; grid-template-columns: 1fr 1fr; gap:16px;">
                <div>
                    <label>Pourcentage (%)</label>
                    <input type="number" name="pourcentage" step="0.01" placeholder="ex: 25" value="<?= htmlspecialchars($promo['pourcentage'] ?? '') ?>">
                </div>
                <div>
                    <label>Prix fixe (DT)</label>
                    <input type="number" name="prix_fixe" step="0.01" placeholder="ex: 120" value="<?= htmlspecialchars($promo['prix_fixe'] ?? '') ?>">
                </div>
            </div>

            <div class="form-group">
                <label style="display:flex; align-items:center; gap:10px;">
                    <input type="checkbox" name="actif" value="1" <?= !empty($promo['actif']) ? 'checked' : '' ?> style="width:18px; height:18px;">
                    Promotion active
                </label>
            </div>

            <div class="form-group">
                <label>Produits concernés</label>
                <div style="max-height:260px; overflow:auto; border:1px solid var(--border); border-radius:8px; padding:12px;">

                    <?php foreach ($products as $p):
                        $checked = in_array((int)$p['id'], $linkedProductIds, true);
                        ?>
                        <label style="display:flex; align-items:center; gap:10px; padding:8px 6px;">
                            <input type="checkbox" name="produits[]" value="<?= (int)$p['id'] ?>" <?= $checked ? 'checked' : '' ?>>
                            <span style="flex:1;">
                                <?= htmlspecialchars($p['nom']) ?>
                                <small style="color: var(--text-light);">(<?= number_format((float)$p['prix'], 2) ?> DT)</small>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="admin-actions">
                <button type="submit" class="btn">💾 Enregistrer</button>
                <a href="promotions.php" class="btn btn-outline">Retour</a>
            </div>
        </form>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>

