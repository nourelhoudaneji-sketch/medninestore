<?php

include '../includes/db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

if (!isset($_GET['id'])) {
    header('Location: produits.php');
    exit;
}

$id = (int)$_GET['id'];

// Vérifier si le produit est présent dans des commandes
$stmt = $conn->prepare("SELECT COUNT(*) FROM commande_produits WHERE produit_id = ?");
$stmt->execute([$id]);
$count = $stmt->fetchColumn();

if ($count > 0) {
    echo "Impossible de supprimer ce produit car il est présent dans une commande.";
    exit;
}

// Supprimer le produit (pas de suppression de logo)
$stmt = $conn->prepare("DELETE FROM produits WHERE id = ?");
$stmt->execute([$id]);

header('Location: produits.php');
exit;
?>
