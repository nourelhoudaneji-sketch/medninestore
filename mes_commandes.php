<?php
session_start();
require 'includes/db.php';
include 'includes/header.php';
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    die('Accès refusé');
}

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
$utilisateur_id = (int) $_SESSION['user']['id'];
$stmt = $conn->prepare("
    SELECT * FROM commandes
    WHERE utilisateur_id = ?
    ORDER BY date_commande DESC
");
$stmt->execute([$_SESSION['user']['id']]);
$commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<section class="section">
    <h1>📦 Mes commandes</h1>

    <?php if (!$commandes): ?>
        <p>Aucune commande pour le moment.</p>
    <?php else: ?>
        <table class="admin-table">
            <tr>
                <th>ID</th>
                <th>Date</th>
                <th>Total</th>
                <th>Statut</th>
                <th>Détails</th>
            </tr>

            <?php foreach ($commandes as $c): ?>
            <tr>
                <td>#<?= $c['id'] ?></td>
                <td><?= date('d/m/Y H:i', strtotime($c['date_commande']))?></td>
                <td><?= number_format($c['total'],2) ?> DT</td>
                <td>
                    <?php switch($c['statut']){
                    case 'en_cours': echo "🕒 En cours"; break;
                    case 'livree': echo "✅ Livrée"; break;
                    case 'annulee': echo "❌ Annulée"; break;
                    default: echo $c['statut'];} ?>
                        
                    
                        
                    
                </td>
                <td>
                    <a href="commande_detail.php?id=<?= $c['id'] ?>" class="btn">Voir</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</section>

<?php include 'includes/footer.php'; ?>
