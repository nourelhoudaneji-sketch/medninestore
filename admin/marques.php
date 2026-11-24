<?php
include __DIR__ . '/../includes/db.php';
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    die('Accès refusé');
}

// Catégories (Homme/Femme) = categories actives, parent_id NULL ou 0.
$stmtCats = $conn->query("SELECT id, nom FROM categories ORDER BY nom");
$categories = $stmtCats->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $categorie_id = (int)($_POST['categorie_id'] ?? 0);

if ($nom !== '' && $categorie_id > 0) {
        // Upload logo
        $logoName = null;
        if (!empty($_FILES['logo']['name'])) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            $type = $_FILES['logo']['type'] ?? '';
            if (!in_array($type, $allowedTypes, true)) {
                $error = "Format de logo non autorisé.";
            } else {
                $logoName = time() . '_' . basename($_FILES['logo']['name']);
                move_uploaded_file(
                    $_FILES['logo']['tmp_name'],
                    __DIR__ . '/../img/marques/' . $logoName
                );
            }
        }

        $stmt = $conn->prepare("INSERT INTO marques (nom, categorie_id, actif, logo) VALUES (?, ?, 1, ?)");
        $stmt->execute([$nom, $categorie_id, $logoName]);
        header('Location: ajouter_produit.php?categorie_id=' . (int)$categorie_id);
        exit();
    }
}

// Migration légère: ajouter la colonne `logo` si elle n'existe pas
// (évite d'avoir à exécuter une commande SQL externe)
$columns = $conn->query("SHOW COLUMNS FROM marques")->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('logo', $columns, true)) {
    $conn->exec("ALTER TABLE marques ADD COLUMN logo VARCHAR(255) NULL AFTER nom");
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Gestion des marques</title>
  <link rel="stylesheet" href="../style.css">
</head>
<body>
<h1 style="margin: 0 0 10px 0;">Gestion des marques</h1>
<p style="margin: 0 0 20px 0;">
  <a href="ajouter_produit.php" class="btn btn-small btn-outline">← Ajouter un produit</a>
</p>

<h2>Ajouter une marque</h2>
  <form method="post" enctype="multipart/form-data">
    Nom marque : <input name="nom" required><br>
    Catégorie (Homme/Femme) :
    <select name="categorie_id" required>
        <?php foreach ($categories as $cat): ?>
          <option value="<?= (int)$cat['id'] ?>"><?= htmlspecialchars($cat['nom']) ?></option>
        <?php endforeach; ?>
    </select>

    <div style="margin-top:10px;">
      Logo : <input type="file" name="logo" accept="image/*">
    </div>

    <button type="submit" style="margin-top:10px;">Ajouter</button>
  </form>

  <h2>Marques par catégorie (et leurs parfums)</h2>

  <?php
  $marquesList = $conn->query("SELECT m.id AS marque_id, m.nom AS marque, c.nom AS categorie
                                FROM marques m
                                LEFT JOIN categories c ON c.id = m.categorie_id
                                ORDER BY c.nom, m.nom")->fetchAll();

  $produitsByMarque = [];
  $marqueIds = array_map(fn($x) => (int)$x['marque_id'], $marquesList);
  if (!empty($marqueIds)) {
      $in = implode(',', array_fill(0, count($marqueIds), '?'));
      $stmtP = $conn->prepare("SELECT id, nom, description, prix, image, marque_id
                               FROM produits
                               WHERE marque_id IN ($in)");
      $stmtP->execute($marqueIds);
      while ($p = $stmtP->fetch()) {
          $mid = (int)$p['marque_id'];
          $produitsByMarque[$mid][] = $p;
      }
  }

  $marquesGrouped = [];
  foreach ($marquesList as $m) {
      $catName = $m['categorie'] ?? '—';
      $marquesGrouped[$catName][] = $m;
  }
  ?>

  <?php if (empty($marquesGrouped)): ?>
      <p style="color: var(--text-medium);">Aucune marque trouvée.</p>
  <?php else: ?>
      <?php foreach ($marquesGrouped as $catName => $marquesCat): ?>
          <div style="margin: 18px 0; padding: 12px; border: 1px solid rgba(0,0,0,0.08); border-radius: 10px;">
              <h3 style="margin: 0 0 10px;">Catégorie : <?= htmlspecialchars($catName) ?></h3>

              <?php foreach ($marquesCat as $m): ?>
                  <div style="margin: 12px 0; padding: 10px; border: 1px dashed rgba(0,0,0,0.15); border-radius: 10px;">
                      <div style="display:flex; align-items:center; justify-content:space-between; gap:10px;">
<div style="display:flex; align-items:center; gap:10px;">
                            <?php $logo = $m['logo'] ?? null; ?>
                            <?php if (!empty($logo)): ?>
                                <img src="../img/marques/<?= htmlspecialchars($logo) ?>" alt="" style="width:40px; height:40px; object-fit:contain; border-radius:8px; background:rgba(0,0,0,0.04);">
                            <?php endif; ?>
                            <strong>Marque : <?= htmlspecialchars($m['marque']) ?></strong>
                        </div>
                        <div>
                            <a class="btn btn-small btn-outline" href="modifier_marque.php?id=<?= (int)$m['marque_id'] ?>">✏️ Modifier</a>
                        </div>
                      </div>

                      <?php $mid = (int)$m['marque_id']; ?>
                      <?php $plist = $produitsByMarque[$mid] ?? []; ?>

                      <?php if (empty($plist)): ?>
                          <p style="color: var(--text-medium); margin: 8px 0 0;">Aucun parfum pour cette marque.</p>
                      <?php else: ?>
                          <table cellpadding="8" style="width: 100%; border-collapse: collapse; margin-top: 10px;" border="1">
                              <tr>
                                  <th>Parfum</th>
                                  <th>Description</th>
                                  <th>Prix</th>
                              </tr>
                              <?php foreach ($plist as $p): ?>
                                  <tr>
                                      <td><?= htmlspecialchars($p['nom'] ?? '') ?></td>
                                      <td><?= htmlspecialchars($p['description'] ?? '') ?></td>
                                      <td><?= number_format((float)$p['prix'], 2) ?> DT</td>
                                  </tr>
                              <?php endforeach; ?>
                          </table>
                      <?php endif; ?>
                  </div>
              <?php endforeach; ?>
          </div>
      <?php endforeach; ?>
  <?php endif; ?>
</body>
</html>


