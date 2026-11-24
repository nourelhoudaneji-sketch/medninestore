<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Notifications (flash)
// - Côté client : $_SESSION['notification']
// - Côté admin : $_SESSION['admin_notification']
$notification = null;
$role = $_SESSION['user']['role'] ?? '';
$nbNouvelles = 0;

if ($role === 'admin') {
    $stmtNotif = $conn->query("
        SELECT COUNT(*) AS total
        FROM commandes
        WHERE nom_statut = 'en_attente'
    ");

    $nbNouvelles = (int)$stmtNotif->fetch(PDO::FETCH_ASSOC)['total'];
}
if ($role === 'admin') {
    $notification = $_SESSION['admin_notification'] ?? null;
    unset($_SESSION['admin_notification']);
} else {
    $notification = $_SESSION['notification'] ?? null;
    unset($_SESSION['notification']);
}


?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Mednine Store - Parfums et cosmétiques de luxe">
    <title>Mednine Store | Parfumerie de Luxe</title>
   <link rel="stylesheet" href="/medninestore/style.css">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@300;400;500;600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>
<body>

    <header>
        <div class="header-container">
            <!-- Logo avec image de luxe -->
            <a href="index.php" class="logo-container">
                <img src="/medninestore/img/m_s.jpg" alt="Mednine Store" class="logo-img">
                <span class="logo-text">
                    <span class="logo-title">Mednine</span>
                    <span class="logo-subtitle">Store</span>
                </span>
            </a>
            
            <!-- Navigation -->
            <nav>
            <a href="/medninestore/index.php"> 🏠 Accueil</a>
            <a href="/medninestore/produits.php"> 🌸 Nos Parfums</a>
            <a href="/medninestore/promotion.php"> 🔥 Promo</a>
            <a href="/medninestore/panier.php"> 🛒 Panier</a>
            
            
           



                
                    <?php if (!empty($_SESSION['panier'])): ?>
                        <span class="cart-badge"><?php echo array_sum($_SESSION['panier']); ?></span>
                    <?php endif; ?>
                </a>
                <?php if (isset($_SESSION['user']) && ($_SESSION['user']['role'] ?? '') === 'admin'): ?>
                    

                    
                    <a href="/medninestore/admin/dashboard.php">dashboard</a>
                    <a href="/medninestore/compte.php">Compte</a>
                    <a href="/medninestore/logout.php">Déconnexion</a>
                <?php else: ?>
                    <a href="/medninestore/login.php">Connexion</a>
                <?php endif; ?>

            </nav>
        </div>
    </header>
    <main>

    <?php if (!empty($notification)): ?>
        <div class="alert alert-success" style="margin:20px auto; max-width:900px;">
            <?= htmlspecialchars($notification['message'] ?? '') ?>
        </div>
    <?php endif; ?>

