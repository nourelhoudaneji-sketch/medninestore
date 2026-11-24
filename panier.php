<?php
session_start();
include 'includes/db.php';
include 'includes/header.php';

if (!isset($_SESSION['panier'])) $_SESSION['panier'] = [];

// Supprimer un produit
if(isset($_GET['supprimer'])){
    $id = (int)$_GET['supprimer'];
    unset($_SESSION['panier'][$id]);
    header('Location: panier.php'); exit;
}

// Mettre à jour quantités
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['quantites'])){
    foreach($_POST['quantites'] as $id=>$qty){
        $qty = (int)$qty;
        if($qty>0) $_SESSION['panier'][$id]=$qty;
        else unset($_SESSION['panier'][$id]);
    }
    header('Location: panier.php'); exit;
}

// Calcul total
$total = 0; $items = [];
foreach($_SESSION['panier'] as $id=>$qty){
    $stmt = $conn->prepare("SELECT * FROM produits WHERE id = ?");
    $stmt->execute([$id]);
    $p = $stmt->fetch(PDO::FETCH_ASSOC);
    if($p){
        $sous_total = $p['prix']*$qty;
        $total += $sous_total;
        $items[] = ['produit'=>$p,'quantite'=>$qty,'sous_total'=>$sous_total];
    }
}
?>

<section class="page-header">
<h1>Votre <span>Panier</span></h1>
</section>

<section class="section">
<?php if(!empty($items)): ?>
<form method="post">
<div class="panier-container">
    <div class="panier-items">
        <?php foreach($items as $item): ?>
        <div class="panier-item">
            <div class="panier-item-image">
                <img loading="lazy" decoding="async" fetchpriority="low" src="/medninestore/img/<?= rawurlencode($item['produit']['image']); ?>" alt="<?= htmlspecialchars($item['produit']['nom']) ?>">
            </div>
            <div class="panier-item-info">
                <h3><?= htmlspecialchars($item['produit']['nom']) ?></h3>
                <p><?= number_format($item['produit']['prix'],2) ?> DT</p>
            </div>
            <div class="panier-item-qty">
                <input type="number" name="quantites[<?= $item['produit']['id'] ?>]" value="<?= $item['quantite'] ?>" min="1">
            </div>
            <div class="panier-item-total"><?= number_format($item['sous_total'],2) ?> DT</div>
            <a href="panier.php?supprimer=<?= $item['produit']['id'] ?>" class="panier-item-remove">×</a>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="panier-summary">
        <h3>Récapitulatif</h3>
        <p>Sous-total: <?= number_format($total,2) ?> DT</p>
        <p>Livraison: 8.00 DT</p>
        <strong>Total: <?= number_format($total+8,2) ?> DT</strong>

        <button type="submit" class="btn btn-outline btn-full">Mettre à jour</button>
        <!-- Le client peut commander directement (saisie Nom/Téléphone/Adresse) -->
        <a href="checkout.php" class="btn btn-full">Commander</a>
    </div>
</div>
</form>
<?php else: ?>
<div class="empty-state">
    <h2>Votre panier est vide</h2>
    <a href="produits.php" class="btn">Voir les produits</a>
</div>
<?php endif; ?>
</section>

<?php include 'includes/footer.php'; ?>

