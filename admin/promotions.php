<?php
session_start();
require __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/header.php';

if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
    die('Accès refusé');
}

$success = null;
$error = null;

// Create promotion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_promotion'])) {
    $titre = trim($_POST['titre'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $type = $_POST['type_promo'] ?? 'mix'; // prix_fixe | pourcentage | mix


    $pourcentage = trim($_POST['pourcentage'] ?? '');
    $prixFixe = trim($_POST['prix_fixe'] ?? '');

    $dateDebut = trim($_POST['date_debut'] ?? '');
    $dateFin = trim($_POST['date_fin'] ?? '');

    // Products selection (multi)
    $selectedProductIds = $_POST['produits'] ?? [];
    $selectedProductIds = array_values(array_filter(array_map('intval', (array)$selectedProductIds), fn($x) => $x > 0));

    if ($titre === '' || count($selectedProductIds) === 0) {
        $error = 'Titre requis et au moins un produit sélectionné.';
    } else {
        // Determine values to store (prix fixe prioritaire when both exist)
        $pourcentageVal = null;
        $prixFixeVal = null;

        if ($type === 'pourcentage') {
            $pourcentageVal = ($pourcentage === '') ? null : (float)$pourcentage;
        } elseif ($type === 'mix') {
            if ($prixFixe !== '') $prixFixeVal = (float)$prixFixe;
            if ($prixFixeVal === null && $pourcentage !== '') $pourcentageVal = (float)$pourcentage;
        } else {
            // prix_fixe
            $prixFixeVal = ($prixFixe === '') ? null : (float)$prixFixe;
        }

        // If mix and prixFixe not provided, allow percentage
        if ($type === 'mix' && $prixFixeVal === null && $pourcentageVal === null) {
            $error = 'Pour le type mix, fournissez au moins un champ (prix fixe ou pourcentage).';
        } else {
            // Insert promotion
            $stmt = $conn->prepare(
                "INSERT INTO promotions (titre, description, pourcentage, prix_fixe, type_promo, date_debut, date_fin, actif, date_creation)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW())"
            );

            $stmt->execute([
                $titre,
                $description,
                $pourcentageVal,
                $prixFixeVal,
                $type,
                $dateDebut ?: null,
                $dateFin ?: null,
            ]);

            $promotionId = (int)$conn->lastInsertId();

            $stmtP = $conn->prepare(
                "INSERT INTO promotion_produits (promotion_id, produit_id) VALUES (?, ?)"
            );
            foreach ($selectedProductIds as $pid) {
                $stmtP->execute([$promotionId, $pid]);
            }

            $success = 'Promotion créée avec succès.';
        }
    }
}

// List promotions
$promotions = [];
$stmtPromo = $conn->query("SELECT * FROM promotions ORDER BY date_creation DESC");
$promotions = $stmtPromo->fetchAll(PDO::FETCH_ASSOC);

// Products list for selector
$products = [];
$stmtProd = $conn->query("SELECT id, nom, prix FROM produits ORDER BY nom ASC");
$products = $stmtProd->fetchAll(PDO::FETCH_ASSOC);

?>

<section class="section">
    <h1>🏷️ Promotions</h1>

    <?php if ($success): ?>
        <div class="alert alert-success" style="max-width:900px; margin:20px auto;">✅ <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error" style="max-width:900px; margin:20px auto;">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="admin-form" style="margin:30px auto; max-width:900px;">
        <h3 style="margin-bottom:20px; color: var(--secondary);">Créer une promotion</h3>

        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="create_promotion" value="1">

            <div class="form-group">
                <label>Titre (ex: 🌸 SOLDES D'ÉTÉ)</label>
                <input type="text" name="titre" required>
            </div>

            <div class="form-group">
                <label>Description (ex: Jusqu'à -25% ...)</label>
                <textarea name="description" rows="3"></textarea>
            </div>

            <div class="form-group" style="display:grid; grid-template-columns: 1fr 1fr; gap:16px;">
                <div>
                    <label>Date début</label>
                    <input type="date" name="date_debut">
                </div>
                <div>
                    <label>Date fin</label>
                    <input type="date" name="date_fin">
                </div>
            </div>

            <div class="form-group">
                <label>Type de remise</label>
                <select name="type_promo" id="type_promo">
                    <option value="prix_fixe">Prix fixe (prioritaire)</option>
                    <option value="pourcentage">Pourcentage</option>
                    <option value="mix" selected>Mix (prix fixe prioritaire)</option>
                </select>
            </div>

            <div class="form-group" style="display:grid; grid-template-columns: 1fr 1fr; gap:16px;">
                <div>
                    <label>Pourcentage (%)</label>
                    <input type="number" name="pourcentage" step="0.01" placeholder="ex: 25">
                </div>
                <div>
                    <label>Prix fixe (DT)</label>
                    <input type="number" name="prix_fixe" step="0.01" placeholder="ex: 120">
                </div>
            </div>

            <div class="form-group">
                <label>Produits concernés (choisir 1 ou plusieurs)</label>
                <div style="max-height:260px; overflow:auto; border:1px solid var(--border); border-radius:8px; padding:12px;">
                    <?php foreach ($products as $p): ?>
                        <label style="display:flex; align-items:center; gap:10px; padding:8px 6px;">
                            <input type="checkbox" name="produits[]" value="<?= (int)$p['id'] ?>">
                            <span style="flex:1;">
                                <?= htmlspecialchars($p['nom']) ?>
                                <small style="color: var(--text-light);">(<?= number_format((float)$p['prix'], 2) ?> DT)</small>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="admin-actions">
                <button type="submit" class="btn">➕ Créer la promotion</button>
                <a href="index.php" class="btn btn-outline">Retour</a>
            </div>
        </form>
    </div>

    <div class="admin-card" style="max-width:900px; margin:0 auto;">
        <h3 style="margin-bottom:20px; color: var(--secondary);">Liste des promotions</h3>
        <?php if (empty($promotions)): ?>
            <p>Aucune promotion.</p>
        <?php else: ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Titre</th>
                        <th>Remise</th>
                        <th>Dates</th>
                        <th>Actif</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($promotions as $pr): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($pr['titre'] ?? '') ?></strong></td>
                        <td>
                            <?php
                            $pf = $pr['prix_fixe'] ?? null;
                            $pc = $pr['pourcentage'] ?? null;
                            if ($pf !== null && $pf !== '') {
                                echo 'Prix fixe: ' . number_format((float)$pf, 2) . ' DT';
                            } elseif ($pc !== null && $pc !== '') {
                                echo 'Pourcentage: -' . number_format((float)$pc, 2) . '%';
                            } else {
                                echo '—';
                            }
                            ?>
                        </td>
                        <td>
                            <?= htmlspecialchars($pr['date_debut'] ?? '') ?> → <?= htmlspecialchars($pr['date_fin'] ?? '') ?>
                        </td>
                        <td><?= !empty($pr['actif']) ? 'Oui' : 'Non' ?></td>
                        <td>
                            <a class="btn btn-small" href="promotion_modifier.php?id=<?= (int)$pr['id'] ?>" style="margin:0 6px; display:inline-block;">✏️ Modifier</a>
                            <a class="btn btn-small btn-outline" href="promotion_supprimer.php?id=<?= (int)$pr['id'] ?>" onclick="return confirm('Supprimer cette promotion ?');" style="margin:0 6px; display:inline-block;">🗑️ Supprimer</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

        <?php endif; ?>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>

