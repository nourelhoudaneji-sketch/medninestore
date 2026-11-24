<?php
session_start();
require __DIR__ . '/../includes/db.php';
include __DIR__ . '/../includes/header.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    die('Accès refusé');
}

$id = (int)($_GET['id'] ?? 0);

$stmt = $conn->prepare("
    SELECT * FROM commandes
    WHERE id = ?
");
$stmt->execute([$id]);
$commande = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$commande) {
    die('Commande introuvable');
}

$stmt = $conn->prepare("
    SELECT  p.image, p.nom, cp.quantite, cp.prix
    FROM commande_produits cp
    JOIN produits p ON p.id = cp.produit_id
    WHERE cp.commande_id = ?
");
$stmt->execute([$id]);
$produits = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<section class="section">
    <?php
    $statut = $commande['nom_statut'] ?? '';

    // Classe appliquée à la zone/ligne statut
    $rowClasse = 'status-row-unknown';
    $badgeClasse = 'status-badge-unknown';
    $statutTexte = $statut;

    if ($statut === 'en_cours') {
        $rowClasse = 'status-row-en-cours';
        $badgeClasse = 'status-badge-en-cours';
        $statutTexte = '🕒 En cours';
    } elseif ($statut === 'livree') {
        $rowClasse = 'status-row-livree';
        $badgeClasse = 'status-badge-livree';
        $statutTexte = '✅ Livrée';
    } elseif ($statut === 'prete') {
        $rowClasse = 'status-row-prete';
        $badgeClasse = 'status-badge-prete';
        $statutTexte = '🟡 Prête';
    }
    ?>

    <h1>🧾 Commande #<?= $commande['id'] ?></h1>

    <p class="<?= $rowClasse ?>" style="display:inline-block;">
        <strong>Statut :</strong>
        <span class="<?= $badgeClasse ?>"><?= $statutTexte ?></span>
    </p>



    <table class="admin-table">
        <tr>
            <th>Produit</th>
            <th>Qté</th>
            <th>Prix</th>
            <th>Total</th>
        </tr>

        <?php foreach ($produits as $p): ?>
        <tr>
        <td>
        <?php if (!empty($p['image'])): ?>
    <img src="/medninestore/img/<?= htmlspecialchars($p['image']) ?>" 
         alt="<?= htmlspecialchars($p['nom']) ?>" 
         style="width:70px; height:auto; margin-right:0px; vertical-align:middle;">
<?php endif; ?>

        </td>
            <td><?= $p['quantite'] ?></td>
            <td><?= number_format($p['prix'],2) ?> DT</td>
            <td><?= number_format($p['prix']*$p['quantite'],2) ?> DT</td>
        </tr>
        <?php endforeach; ?>
    </table>

    <h3>Total : <?= number_format($commande['total'],2) ?> DT</h3>

    <a href="commandes.php" class="btn">⬅ Retour</a>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
