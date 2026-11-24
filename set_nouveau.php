<?php
require 'includes/db.php';

// Mark the latest product (ID 72 - Badee Al Oud Noble Blush) as Nouveau
$stmt = $conn->prepare("UPDATE produits SET nouveau = 1 WHERE id = 72");
$stmt->execute();

// Also mark any products where id > 68 as nouveau (Badee products)
$stmt = $conn->prepare("UPDATE produits SET nouveau = 1 WHERE id >= 69");
$stmt->execute();

echo "✅ Produits marqués comme Nouveau : " . $stmt->rowCount() . " produits mis à jour.";
header('Location: produits.php');
exit;

