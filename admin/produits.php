<?php
// admin/produits.php
include '../includes/db.php';
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
  die('Accès refusé');
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Gestion des produits</title>
  <link rel="stylesheet" href="../style.css">

</head>
<body>
  <h1>Interface d'administration - Produits</h1>
  <p><a href="ajouter_produit.php">Ajouter un produit</a></p>
  <table cellpadding="10">
    <tr>
      <th>ID</th>
      <th>Nom</th>
      <th>Description</th>
      <th>Prix</th>
      <th>Actions</th>
    </tr>
    <?php
    $stmt = $conn->query("SELECT * FROM produits");
    while ($row = $stmt->fetch()) {
      echo "<tr>
              <td>{$row['id']}</td>
              <td>{$row['nom']}</td>
              <td>{$row['description']}</td>
              <td>{$row['prix']} dt</td>
              <td>
                <a href='modifier_produit.php?id={$row['id']}'>Modifier</a> |
                <a href='supprimer_produit.php?id={$row['id']}' onclick=\"return confirm('Supprimer ce produit ?');\">Supprimer</a>
              </td>
            </tr>";
    }
    ?>
  </table>
</body>
</html>