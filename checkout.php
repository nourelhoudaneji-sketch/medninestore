<?php
session_start();
include 'includes/db.php';
include 'includes/header.php';

if(empty($_SESSION['panier'])){
    header('Location: panier.php');
    exit;
}

// Validation commande : côté valider_commande.php, l’utilisateur doit être connecté (FK).
$utilisateur_id = isset($_SESSION['user']) ? (int)$_SESSION['user']['id'] : null;
$total = 0;
foreach($_SESSION['panier'] as $id => $qty){
    $stmt = $conn->prepare("SELECT prix FROM produits WHERE id=?");
    $stmt->execute([$id]);
    $produit = $stmt->fetch(PDO::FETCH_ASSOC);
    if($produit) $total += $produit['prix']*$qty;
}

?>

<section class="page-header">
    <h1>Validation de <span>Commande</span></h1>
</section>

<section class="section">
    <form method="post" action="valider_commande.php" id="checkoutForm">
        <div class="compte-card">
            <h3>Vos informations</h3>

            <div class="info-item">
                <label>Nom</label>
                <input type="text" name="nom" required>
            </div>

            <div class="info-item">
                <label>Téléphone</label>
                <input type="text" name="telephone" required placeholder="8 chiffres">
            </div>

            <div class="info-item">
                <label>Adresse</label>
                <textarea name="adresse" required></textarea>
            </div>

            <p>Total : <?= number_format($total+8,2) ?> DT (incluant livraison)</p>
            <button type="submit" class="btn btn-full">Valider la commande</button>
        </div>
    </form>
</section>



<?php include 'includes/footer.php'; ?>
