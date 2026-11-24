
<?php
require_once 'protection.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    die('Identifiant invalide.');
}

$stmt = $conn->prepare(
    "SELECT * FROM utilisateurs WHERE id = ? AND role = 'admin'"
);
$stmt->execute([$id]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    die('Administrateur introuvable.');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!hash_equals($_SESSION['csrf_token'],
                     $_POST['csrf_token'] ?? '')) {
        die('Requête invalide.');
    }

    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['mot_de_passe'] ?? '';

    if ($nom === '' || $prenom === '' ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        ($password !== '' && strlen($password) < 8)) {

        $error = 'Informations invalides.';

    } else {

        try {
            if ($password !== '') {

                $hash = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $conn->prepare(
                    "UPDATE utilisateurs
                     SET nom=?, prenom=?, email=?, mot_de_passe=?
                     WHERE id=? AND role='admin'"
                );

                $stmt->execute([
                    $nom, $prenom, $email, $hash, $id
                ]);

            } else {

                $stmt = $conn->prepare(
                    "UPDATE utilisateurs
                     SET nom=?, prenom=?, email=?
                     WHERE id=? AND role='admin'"
                );

                $stmt->execute([
                    $nom, $prenom, $email, $id
                ]);
            }

            header('Location: liste_admin.php');
            exit;

        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $error = 'Cet email est déjà utilisé.';
            } else {
                throw $e;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier administrateur</title>
</head>
<body>

<h2>Modifier un administrateur</h2>

<?php if ($error): ?>
    <p style="color:red"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<form method="POST">

    <input type="hidden" name="csrf_token"
           value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

    <label>Nom :</label><br>
    <input type="text" name="nom"
           value="<?= htmlspecialchars($admin['nom']) ?>" required><br><br>

    <label>Prénom :</label><br>
    <input type="text" name="prenom"
           value="<?= htmlspecialchars($admin['prenom']) ?>" required><br><br>

    <label>Email :</label><br>
    <input type="email" name="email"
           value="<?= htmlspecialchars($admin['email']) ?>" required><br><br>

    <label>Nouveau mot de passe (facultatif) :</label><br>
    <input type="password" name="mot_de_passe" minlength="8"><br><br>

    <button type="submit">Enregistrer les modifications</button>

</form>

<a href="liste_admin.php">Retour à la liste</a>

</body>
</html>
