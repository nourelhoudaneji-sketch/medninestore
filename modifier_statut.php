<?php
session_start();
require 'includes/db.php';

// Vérification admin ou utilisateur autorisé
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id_commande']);
    $nouveau_statut = $_POST['nouveau_statut'];

    // Sécuriser le statut pour éviter les injections
    $statuts_valides = ['en_cours', 'prete', 'livree', 'annulee'];
    if (!in_array($nouveau_statut, $statuts_valides)) {
        die('Statut invalide');
    }

    $stmt = $conn->prepare("UPDATE commandes SET statut = ? WHERE id = ?");
    $stmt->execute([$nouveau_statut, $id]);

    header('Location: admin_commandes.php'); // Retour à la liste
    exit;
}
?>
