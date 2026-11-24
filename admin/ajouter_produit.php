<?php
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

require __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/header.php';

$success = '';
$error = '';

//Récupérer les catégories
$categories = $conn->query("SELECT * FROM categories")->fetchAll();

function uploadLogoMarque(array $file): array {
    $result = ['ok' => false, 'name' => null, 'error' => null];

    if (empty($file['name'])) {
        $result['error'] = 'Logo obligatoire.';
        return $result;
    }

    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $type = $file['type'] ?? '';

    if (!in_array($type, $allowedTypes, true)) {
        $result['error'] = 'Format de logo non autorisé.';
        return $result;
    }

    // Nettoyer le nom du fichier pour éviter les problèmes d'URL (#, espaces, etc.)
    $originalName = pathinfo($file['name'], PATHINFO_FILENAME);
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $cleanName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $originalName);
    $cleanName = preg_replace('/_+/', '_', $cleanName);
    $cleanName = trim($cleanName, '_');
    if (empty($cleanName)) $cleanName = 'logo';
    $logoName = time() . '_' . $cleanName . '.' . $extension;
    $targetDir = __DIR__ . '/../img/marques/';
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    if (!move_uploaded_file($file['tmp_name'], $targetDir . $logoName)) {
        $result['error'] = 'Impossible d’envoyer le fichier logo.';
        return $result;
    }

    $result['ok'] = true;
    $result['name'] = $logoName;
    return $result;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $modeMarque = $_POST['mode_marque'] ?? 'existant';

    // Produit
    $nomProduit = trim($_POST['nom'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $prix = $_POST['prix'] ?? '';
    $categorieProduitId = (int)($_POST['categorie_id'] ?? 0);

    // Vérifs produit
    if ($nomProduit === '' || $description === '' || $prix === '' || $categorieProduitId <= 0) {
        $error = 'Veuillez remplir tous les champs obligatoires du produit.';
    } else {
            $marqueId = 0;

        // Déterminer si la catégorie sélectionnée est "Accessoires" / "Skincare"
        $categorieProduitNom = '';
        $stmtCat = $conn->prepare("SELECT nom FROM categories WHERE id = ?");
        $stmtCat->execute([$categorieProduitId]);
        $rowCat = $stmtCat->fetch(PDO::FETCH_ASSOC);
        if ($rowCat && isset($rowCat['nom'])) {
            $categorieProduitNom = (string)$rowCat['nom'];
        }

        $requiresMarque = !(stripos($categorieProduitNom, 'access') !== false || stripos($categorieProduitNom, 'skin') !== false || stripos($categorieProduitNom, 'skincare') !== false);

        // Déterminer marqueId
        if ($requiresMarque && $modeMarque === 'nouvelle') {
            $nomMarque = trim($_POST['nom_marque'] ?? '');
            $categorieMarqueId = (int)($_POST['categorie_id_marque'] ?? 0);

            if ($nomMarque === '' || $categorieMarqueId <= 0) {
                $error = 'Nom marque et catégorie de la marque sont obligatoires.';
            } else {
                $logoUpload = uploadLogoMarque($_FILES['logo_marque'] ?? []);
                if (!$logoUpload['ok']) {
                    $error = $logoUpload['error'];
                } else {
                    $stmtInsMarque = $conn->prepare("INSERT INTO marques (nom, categorie_id, actif, logo) VALUES (?, ?, 1, ?)");
                    $stmtInsMarque->execute([$nomMarque, $categorieMarqueId, $logoUpload['name']]);
                    $marqueId = (int)$conn->lastInsertId();
                }
            }
        } else {
            // Ne valider une marque existante que si elle est requise
            if ($requiresMarque) {
                $marqueId = (int)($_POST['marque_id'] ?? 0);
                if ($marqueId <= 0) {
                    $error = 'Veuillez sélectionner une marque existante.';
                }
            } else {
                $marqueId = 0;
            }
        }

        if (!$error) {
            // Upload image produit
            $imageName = null;
            if (!empty($_FILES['image']['name'])) {
                $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
                if (!in_array($_FILES['image']['type'], $allowedTypes, true)) {
                    $error = "Format d'image non autorisé.";
                } else {
                    // Nettoyer le nom du fichier pour éviter les problèmes d'URL (#, espaces, etc.)
                    $origName = pathinfo($_FILES['image']['name'], PATHINFO_FILENAME);
                    $origExt = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                    $cleanOrig = preg_replace('/[^a-zA-Z0-9_-]/', '_', $origName);
                    $cleanOrig = preg_replace('/_+/', '_', $cleanOrig);
                    $cleanOrig = trim($cleanOrig, '_');
                    if (empty($cleanOrig)) $cleanOrig = 'produit';
                    $imageName = time() . '_' . $cleanOrig . '.' . $origExt;
                    move_uploaded_file(
                        $_FILES['image']['tmp_name'],
                        __DIR__ . '/../img/' . $imageName
                    );
                }
            }
        }

        if (!$error) {
            $nouveau = isset($_POST['nouveau']) ? 1 : 0;
            $stmt = $conn->prepare(
                "INSERT INTO produits (nom, description, prix, image, categorie_id, marque_id, nouveau)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );

            $stmt->execute([
                $nomProduit,
                $description,
                $prix,
                $imageName,
                $categorieProduitId,
                $marqueId,
                $nouveau
            ]);

            header('Location: index.php?success=1');
            exit;
        }
    }
}
?>

<section class="page-header">
    <h1>Administration <span>Produits</span></h1>
    <p>Gestion de la collection Mednine Store</p>
</section>

<section class="section">
    <div class="compte-card">

        <h3>Ajouter un nouveau produit</h3>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" class="admin-form">

            <div class="form-group">
                <label>Nom du produit</label>
                <input type="text" name="nom" required>
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea name="description" required></textarea>
            </div>

            <div class="form-group">
                <label>Prix (DT)</label>
                <input type="number" name="prix" step="0.01" required>
            </div>

            <div class="form-group">
                <label>Catégorie</label>
                <select name="categorie_id" id="categorie_id" required>
                    <option value="">-- Sélectionner --</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= (int)$c['id'] ?>">
                            <?= htmlspecialchars($c['nom']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php
            // Toutes les marques, pour choisir facilement
            $stmtM = $conn->query("SELECT id, nom FROM marques ORDER BY nom");
            $marques = $stmtM->fetchAll(PDO::FETCH_ASSOC);
            ?>

            <div class="form-group" id="marque-wrap">
                <label>Marque (choisir ou créer)</label>

                <div style="margin-top:6px; display:flex; flex-direction:column; gap:10px;">
<label style="display:flex; align-items:center; gap:10px;">
                        <input type="radio" name="mode_marque" value="existant" checked>
                        <span>Marque existante</span>
                    </label>
                    <select name="marque_id" id="marque_id">
                        <option value="">-- Sélectionner --</option>
                        <?php foreach ($marques as $m): ?>
                            <option value="<?= (int)$m['id'] ?>"><?= htmlspecialchars($m['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label style="display:flex; align-items:center; gap:10px;">
                        <input type="radio" name="mode_marque" value="nouvelle">
                        <span>Créer une nouvelle marque</span>
                    </label>

                    <div id="nouvelle-marque" style="display:none;">
                        <div class="form-group" style="margin-top:0;">
                            <label>Nom de la marque</label>
                            <input type="text" name="nom_marque"/>
                        </div>

                        <div class="form-group">
                            <label>Catégorie de la marque</label>
                            <select name="categorie_id_marque" id="categorie_id_marque">
                                <?php foreach ($categories as $c): ?>
                                    <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Logo de la marque</label>
                            <input type="file" name="logo_marque" accept="image/*"/>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                (function(){
                    const radios = document.querySelectorAll('input[name="mode_marque"]');
                    const wrap = document.getElementById('nouvelle-marque');
                    const select = document.getElementById('marque_id');
                    const categorieSelect = document.getElementById('categorie_id');

                    function getCategorieName(){
                        const opt = categorieSelect ? categorieSelect.options[categorieSelect.selectedIndex] : null;
                        return opt ? (opt.textContent || '').toLowerCase() : '';
                    }

                    function shouldRequireMarque(){
                        const name = getCategorieName();
                        return !(name.includes('access') || name.includes('skin') || name.includes('skincare'));
                    }

                    function toggle(){
                        const valEl = document.querySelector('input[name="mode_marque"]:checked');
                        const val = valEl ? valEl.value : 'existant';
                        const show = val === 'nouvelle';
                        if (wrap) wrap.style.display = show ? 'block' : 'none';

                        const requireMarque = shouldRequireMarque();
                        // Si Accessoires/Skincare => pas de marque requise
                        if (select) select.required = requireMarque && !show;
                        // Si pas requise => on évite aussi de forcer "nouvelle" (optionnel)
                    }

                    radios.forEach(r => r.addEventListener('change', toggle));
                    if (categorieSelect) categorieSelect.addEventListener('change', toggle);
                    toggle();
                })();
            </script>

            



            <div class="form-group">
                <label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
                    <input type="checkbox" name="nouveau" value="1" id="check_nouveau" style="width:auto; margin:0;">
                    <span>✨ Marquer comme <strong>Nouveau</strong> (affiche un badge sur la fiche produit)</span>
                </label>
            </div>

            <div class="form-group">
                <label>Image du produit</label>
                <input type="file" name="image" accept="image/*">
            </div>


            <div class="admin-actions">
                <button type="submit" class="btn">➕ Ajouter le produit</button>
                <a href="/medninestore/admin/index.php" class="btn btn-outline">Retour</a>
            </div>

        </form>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
