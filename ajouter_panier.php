<?php
session_start();
include 'includes/db.php';

if (!isset($_GET['id'])) {
    header('Location: produits.php');
    exit;
}

$id = (int)$_GET['id'];

// Vérifier produit
$stmt = $conn->prepare("SELECT id FROM produits WHERE id = ?");
$stmt->execute([$id]);
if (!$stmt->fetch()) {
    header('Location: produits.php');
    exit;
}

// Initialiser panier
if(!isset($_SESSION['panier'])) $_SESSION['panier'] = [];

// Ajouter ou incrémenter
$_SESSION['panier'][$id] = ($_SESSION['panier'][$id] ?? 0) + 1;

// Notification (flash) :
// - Client (interface panier) => $_SESSION['notification']
// - Admin (interface commandes) => $_SESSION['admin_notification']
$role = $_SESSION['user']['role'] ?? '';
if ($role === 'admin') {
    $_SESSION['admin_notification'] = [
        'type' => 'success',
        'message' => 'Produit ajouté au panier ✅'
    ];
} else {
    $_SESSION['notification'] = [
        'type' => 'success',
        'message' => 'Produit ajouté au panier ✅'
    ];
}



// Retour à produits.php
header('Location: produits.php');
exit;

