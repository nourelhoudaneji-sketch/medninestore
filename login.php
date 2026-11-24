<?php
// login.php
session_start();

// login.php : page de connexion pour l'admin
// Si l'admin est déjà connecté, on le redirige
if (isset($_SESSION['user']) && ($_SESSION['user']['role'] ?? '') === 'admin') {
    header('Location: admin/index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include 'includes/db.php';
    
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['mot_de_passe'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Veuillez remplir tous les champs.';
    } else {
        $stmt = $conn->prepare("SELECT * FROM utilisateurs WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['mot_de_passe'])) {
         $_SESSION['user'] = [
         'id'    => $user['id'],
         'nom'   => $user['nom'],   // ✅ AJOUT ICI
         'email' => $user['email'],
         'role'  => $user['role']
];

            header('Location: compte.php');
            exit();
        } else {
            $error = 'Email ou mot de passe incorrect.';
        }
    }
}

include 'includes/header.php';
?>

<section class="auth-section">
    <div class="auth-container">
        <div class="auth-header">
            <h2>Connexion</h2>
            <p>Accédez à votre espace personnel</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <form method="post" class="auth-form">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required placeholder="votre@email.com">
            </div>
            
            <div class="form-group">
                <label for="mot_de_passe">Mot de passe</label>
                <input type="password" id="mot_de_passe" name="mot_de_passe" required placeholder="••••••••">
            </div>
            
            <button type="submit" class="btn btn-full">Se connecter</button>
        </form>

    </div>
</section>

<?php include 'includes/footer.php'; ?>
