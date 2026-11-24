<?php
session_start();
require __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    die('Accès refusé');
}

if (isset($_POST['id_commande'], $_POST['nom_statut'])) {
    $id = (int)$_POST['id_commande'];
    $nom_statut = $_POST['nom_statut'];

    $valid_status = ['en_cours', 'prete', 'livree']; // Ajouter tous les statuts possibles
    if (!in_array($nom_statut, $valid_status)) {
        die('Statut invalide');
    }

    $stmt = $conn->prepare("UPDATE commandes SET nom_statut = :nom_statut, date_modification = NOW() WHERE id = :id");
    $stmt->execute(['nom_statut' => $nom_statut, 'id' => $id]);

// Notification admin (flash) : nombre de commandes (pas le nombre de produits)
$countStmt = $conn->query("SELECT COUNT(*) AS nb FROM commandes");
$nbCommandes = (int)($countStmt->fetch(PDO::FETCH_ASSOC)['nb'] ?? 0);

$_SESSION['admin_notification'] = [
    'type' => 'success',
    'message' => 'Commandes mises à jour ✅ Total commandes : ' . $nbCommandes
];


    header('Location: commandes.php');
    exit;

} else {
    die('Données manquantes');
}
