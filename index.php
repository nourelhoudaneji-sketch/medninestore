<?php
// index.php
include 'includes/db.php';
include 'includes/header.php';
?>

<section class="hero">

    <div class="hero-content">
        <h1>L'Art du <span>Parfum</span></h1>
        <p>Découvrez notre collection exclusive de fragrances d'exception</p>
        <a href="produits.php" class="btn">Explorer la Collection</a>
    </div>
</section>
<section class="features-section">
    <div class="features-grid">
        <div class="feature-card">
            <div class="feature-icon">✨</div>
            <h4>Authenticité Garantie</h4>
            <p> Des produits originaux </p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">🚚</div>
            <h4>Livraison Rapide</h4>
            <p>Partout en Tunisie sous 24h </p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">💎</div>
            <h4>Qualité Premium</h4>
            <p>Les meilleures marques internationales</p>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>

