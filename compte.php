<?php
// compte.php
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

require_once 'includes/db.php';

$id = (int) $_SESSION['user']['id'];

$stmt = $conn->prepare(
    "SELECT * FROM utilisateurs WHERE id = ?"
);
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit();
}

$error = '';
$success = '';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// MODIFICATION DU COMPTE
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['modifier_compte'])) {

    if (!hash_equals(
        $_SESSION['csrf_token'],
        $_POST['csrf_token'] ?? ''
    )) {
        die('Requête invalide.');
    }

    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $ancien_mdp = $_POST['ancien_mdp'] ?? '';
    $nouveau_mdp = $_POST['nouveau_mdp'] ?? '';

    if (
        $nom === '' ||
        $prenom === '' ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        !password_verify($ancien_mdp, $user['mot_de_passe'])
    ) {
        $error = "Informations invalides ou ancien mot de passe incorrect.";

    } elseif ($nouveau_mdp !== '' && strlen($nouveau_mdp) < 8) {
        $error = "Le nouveau mot de passe doit contenir 8 caractères minimum.";

    } else {
        try {
            if ($nouveau_mdp !== '') {
                $hash = password_hash($nouveau_mdp, PASSWORD_DEFAULT);

                $stmt = $conn->prepare(
                    "UPDATE utilisateurs
                     SET nom=?, prenom=?, email=?, mot_de_passe=?
                     WHERE id=?"
                );

                $stmt->execute([
                    $nom, $prenom, $email, $hash, $id
                ]);

            } else {
                $stmt = $conn->prepare(
                    "UPDATE utilisateurs
                     SET nom=?, prenom=?, email=?
                     WHERE id=?"
                );

                $stmt->execute([
                    $nom, $prenom, $email, $id
                ]);
            }

            $_SESSION['user']['nom'] = $nom;
            $_SESSION['user']['prenom'] = $prenom;
            $_SESSION['user']['email'] = $email;

            header('Location: compte.php?modifie=1');
            exit();

        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $error = "Cette adresse email est déjà utilisée.";
            } else {
                $error = "Erreur lors de la modification du compte.";
            }
        }
    }
}

// SUPPRESSION DU COMPTE
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['supprimer_compte'])) {

    if (!hash_equals(
        $_SESSION['csrf_token'],
        $_POST['csrf_token'] ?? ''
    )) {
        die('Requête invalide.');
    }

    $password = $_POST['confirmation_mdp'] ?? '';

    if (!password_verify($password, $user['mot_de_passe'])) {
        $error = "Mot de passe incorrect.";

    } else {

        // Empêcher la suppression du dernier administrateur
        if ($user['role'] === 'admin') {
            $stmt = $conn->prepare(
                "SELECT COUNT(*) FROM utilisateurs WHERE role = 'admin'"
            );
            $stmt->execute();

            if ((int)$stmt->fetchColumn() <= 1) {
                $error = "Impossible de supprimer le dernier administrateur.";
            }
        }

        if ($error === '') {
            $stmt = $conn->prepare(
                "DELETE FROM utilisateurs WHERE id = ?"
            );
            $stmt->execute([$id]);

            session_unset();
            session_destroy();

            header('Location: index.php?compte_supprime=1');
            exit();
        }
    }
}

$user['nom'] = $user['nom'] ?? '';
$user['prenom'] = $user['prenom'] ?? '';
$user['email'] = $user['email'] ?? '';
$user['role'] = $user['role'] ?? '';

include 'includes/header.php';
?>



<section class="section">
    <div class="compte-container">
        <div class="compte-sidebar">
            <div class="compte-avatar">
                <span><?php echo strtoupper(substr($user['nom'], 0, 1)); ?></span>
            </div>
            <h3><?php echo htmlspecialchars($user['nom']); ?></h3>
            <p><?php echo htmlspecialchars($user['email']); ?></p>
            
            <nav class="compte-nav">
             

                <a href="panier.php">Mon panier</a>
                <?php if (isset($user['role']) && $user['role'] === 'admin'): ?>
                    <a href="/medninestore/admin/index.php">Administration</a>

                <?php endif; ?>
                <a href="logout.php" class="logout-link">Déconnexion</a>
            </nav>
        </div>
        
        <div class="compte-content">
            <div class="compte-card">
                <h3>Bienvenue, <?php echo htmlspecialchars($user['nom']); ?> !</h3>
                <p>Merci de faire confiance à Mednine Store pour vos achats de parfums.</p>
            </div>
            
            <div class="compte-card">
                <h4>Informations personnelles</h4>
                <div class="info-grid">
                    <div class="info-item">
                        <label>Nom</label>
                        <span><?php echo htmlspecialchars($user['nom']); ?></span>
                    </div>
                    <div class="info-item">
                        <label>Email</label>
                        <span><?php echo htmlspecialchars($user['email']); ?></span>
                    </div>
                    <div class="info-item">
                        <label>Type de compte</label>
                        <span><?php echo ucfirst($user['role'] ?? 'admin'); ?></span>
                    </div>
                </div>
            </div>
            
<!-- MODIFICATION DU COMPTE -->
<div class="compte-card">

    <h4>✏️ Modifier mon compte</h4>

    <?php if ($error): ?>
        <div class="alert alert-error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['modifie'])): ?>
        <div class="alert alert-success">
            Votre compte a été modifié avec succès.
        </div>
    <?php endif; ?>

    <form method="POST">

        <input type="hidden" name="csrf_token"
               value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

        <div class="form-group">
            <label>Nom</label>
            <input type="text" name="nom"
                   value="<?= htmlspecialchars($user['nom']) ?>" required>
        </div>

        <div class="form-group">
            <label>Prénom</label>
            <input type="text" name="prenom"
                   value="<?= htmlspecialchars($user['prenom']) ?>" required>
        </div>

        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email"
                   value="<?= htmlspecialchars($user['email']) ?>" required>
        </div>

        <div class="form-group">
            <label>Ancien mot de passe</label>
            <input type="password" name="ancien_mdp" required>
        </div>

        <div class="form-group">
            <label>Nouveau mot de passe (facultatif)</label>
            <input type="password" name="nouveau_mdp" minlength="8">
        </div>

        <button type="submit" name="modifier_compte" class="btn">
            Enregistrer les modifications
        </button>

    </form>

</div>


<!-- SUPPRESSION DU COMPTE -->
<div class="compte-card">

    <h4>🗑️ Supprimer mon compte</h4>

    <p>
        Attention : cette action est définitive.
        Votre compte sera supprimé de la base de données.
    </p>

    <form method="POST"
          onsubmit="return confirm('Attention ! Voulez-vous vraiment supprimer définitivement votre compte ?');">

        <input type="hidden" name="csrf_token"
               value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

        <div class="form-group">
            <label>Confirmez votre mot de passe</label>
            <input type="password" name="confirmation_mdp" required>
        </div>

        <button type="submit" name="supprimer_compte"
                class="btn"
                style="background:#b42318;">
            Supprimer définitivement mon compte
        </button>

    </form>

</div>

            <div class="compte-actions">
    <a href="produits.php" class="btn">Voir nos produits</a>
    <a href="panier.php" class="btn btn-outline">Mon panier</a>
   <?php if ($user['role'] === 'admin'): ?>
  
    <a href="admin/index.php" class="btn">Administration</a>
<?php endif; ?>


</div>

        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
