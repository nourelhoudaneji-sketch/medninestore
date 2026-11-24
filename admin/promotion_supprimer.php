<?php
session_start();
require __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/header.php';

if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
    die('Accès refusé');
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: promotions.php');
    exit;
}

$stmtPromo = $conn->prepare('SELECT id FROM promotions WHERE id = ?');
$stmtPromo->execute([$id]);
$exists = $stmtPromo->fetchColumn();
if (!$exists) {
    header('Location: promotions.php');
    exit;
}

// Suppression sûre (on supprime aussi les produits liés)
try {
    $stmtD = $conn->prepare('DELETE FROM promotion_produits WHERE promotion_id = ?');
    $stmtD->execute([$id]);

    $stmt = $conn->prepare('DELETE FROM promotions WHERE id = ?');
    $stmt->execute([$id]);
} catch (Throwable $e) {
    die('Erreur suppression promotion: ' . htmlspecialchars($e->getMessage()));
}

header('Location: promotions.php');
exit;


