
<?php
session_start();

if (!isset($_SESSION['user']) ||
    $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

require '../includes/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['mot_de_passe'] ?? '';

    if ($nom === '' || $prenom === '' ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        strlen($password) < 8) {

        $error = "Veuillez vérifier les informations. Mot de passe : 8 caractères minimum.";

    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt = $conn->prepare(
                "INSERT INTO utilisateurs
                (nom, prenom, email, mot_de_passe, role, date_creation)
                VALUES (?, ?, ?, ?, 'admin', NOW())"
            );

            $stmt->execute([
                $nom, $prenom, $email, $hash
            ]);

            header('Location: index.php?success=1');
            exit;

        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $error = "Cette adresse email existe déjà.";
            } else {
                $error = "Une erreur est survenue.";
            }
        }
    }
}
?>

<?php include '../includes/header.php'; ?>

<section class="section">
    <h2>➕ Ajouter un administrateur</h2>

    <?php if ($error): ?>
        <div class="alert alert-error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST">

        <div class="form-group">
            <label>Nom</label>
            <input type="text" name="nom" required>
        </div>

        <div class="form-group">
            <label>Prénom</label>
            <input type="text" name="prenom" required>
        </div>

        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" required>
        </div>

        <div class="form-group">
            <label>Mot de passe</label>
            <input type="password" name="mot_de_passe"
                   minlength="8" required>
        </div>

        <button class="btn" type="submit">
            Ajouter
        </button>

        <a href="index.php" class="btn btn-outline">
            Retour
        </a>

    </form>
</section>

<?php include '../includes/footer.php'; ?>
