
<?php
require_once 'protection.php';

$stmt = $conn->prepare(
    "SELECT id, nom, prenom, email, date_creation
     FROM utilisateurs
     WHERE role = 'admin'
     ORDER BY id DESC"
);
$stmt->execute();
$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des administrateurs</title>
</head>
<body>

<h2>Gestion des administrateurs</h2>

<a href="ajouter_admin.php">+ Ajouter un administrateur</a>

<table border="1" cellpadding="10">
    <tr>
        <th>ID</th>
        <th>Nom</th>
        <th>Prénom</th>
        <th>Email</th>
        <th>Date de création</th>
        <th>Actions</th>
    </tr>

    <?php foreach ($admins as $admin): ?>
    <tr>
        <td><?= (int)$admin['id'] ?></td>
        <td><?= htmlspecialchars($admin['nom']) ?></td>
        <td><?= htmlspecialchars($admin['prenom']) ?></td>
        <td><?= htmlspecialchars($admin['email']) ?></td>
        <td><?= htmlspecialchars($admin['date_creation']) ?></td>
        <td>
            <a href="modifier_admin.php?id=<?= (int)$admin['id'] ?>">
                Modifier
            </a>

            <form method="POST" action="supprimer_admin.php"
                  style="display:inline"
                  onsubmit="return confirm('Supprimer ce compte ?');">

                <input type="hidden" name="id"
                       value="<?= (int)$admin['id'] ?>">

                <input type="hidden" name="csrf_token"
                       value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                <button type="submit">Supprimer</button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>

</table>

</body>
</html>
