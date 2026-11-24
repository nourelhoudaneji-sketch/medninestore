<?php
// register.php
session_start();

// Le client ne doit pas s’inscrire
if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
    header('Location: ../medninestore/admin/index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include 'includes/db.php';
    
    $nom = trim($_POST['nom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['mot_de_passe'] ?? '';
    $confirm = $_POST['confirm_mot_de_passe'] ?? '';
    
    if (empty($nom) || empty($email) || empty($password)) {
        $error = 'Veuillez remplir tous les champs.';
    } elseif ($password !== $confirm) {
        $error = 'Les mots de passe ne correspondent pas.';
    } elseif (strlen($password) < 6) {
        $error = 'Le mot de passe doit contenir au moins 6 caractères.';
    } else {
        // Vérifier si l'email existe déjà
        $stmt = $conn->prepare("SELECT id FROM utilisateurs WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            $error = 'Cet email est déjà utilisé.';
        } else {
            $stmt = $conn->prepare("INSERT INTO utilisateurs (nom, email, mot_de_passe, role) VALUES (?, ?, ?, 'client')");
            $stmt->execute([
                $nom,
                $email,
                password_hash($password, PASSWORD_DEFAULT)
            ]);
            $success = 'Compte créé avec succès ! Vous pouvez maintenant vous connecter.';
        }
    }
}

include 'includes/header.php';
?>

<section class="auth-section">
    <div class="auth-container">
        <div class="auth-header">
            <h2>Inscription</h2>
            <p>Créez votre compte Mednine Store</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <form method="post" class="auth-form">
            <div class="form-group">
                <label for="nom">Nom complet</label>
                <input type="text" id="nom" name="nom" required placeholder="Votre nom">
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required placeholder="votre@email.com">
            </div>
            
            <div class="form-group">
                <label for="mot_de_passe">Mot de passe</label>
                <input type="password" id="mot_de_passe" name="mot_de_passe" required placeholder="••••••••" minlength="6">
            </div>
            
            <div class="form-group">
                <label for="confirm_mot_de_passe">Confirmer le mot de passe</label>
                <input type="password" id="confirm_mot_de_passe" name="confirm_mot_de_passe" required placeholder="••••••••">
            </div>
            
            <button type="submit" class="btn btn-full">S'inscrire</button>
        </form>
 
    </div>
</section>

<?php include 'includes/footer.php'; ?>
