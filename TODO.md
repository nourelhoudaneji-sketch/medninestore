# TODOs - Correction Badee Al Oud

## Problèmes
1. Images Badee Al Oud ne s'affichent pas (noms de fichiers contiennent `#`)
2. Page produit.php cassée (variable `$produit` jamais définie)
3. Description du produit non affichée dans les cartes

## Étapes
- [x] Analyser le problème
- [x] Étape 1: Réparer la récupération du produit dans `produit.php` (description + prix + catégorie)
- [x] Étape 2: Ajouter `rawurlencode()` dans `produits.php` pour les images produits et logos marques
- [x] Étape 3: Ajouter `rawurlencode()` dans `marque.php` pour les images produits et logo marque
- [x] Étape 4: Ajouter `rawurlencode()` dans `panier.php` pour les images
- [x] Étape 5: Ajouter `rawurlencode()` dans `marques.php` pour les logos
- [x] Étape 6: Ajouter `rawurlencode()` dans `promotion.php` (détail + liste)
- [x] Étape 7: Ajouter `rawurlencode()` dans `includes/promotions_widget.php`
- [x] Étape 8: Ajouter `rawurlencode()` dans `includes/promo_hero_widget.php`
- [x] Étape 9: Nettoyage des noms de fichiers lors de l'upload dans `admin/ajouter_produit.php`

