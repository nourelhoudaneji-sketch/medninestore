-- Restauration de la table `marques`, extraite directement de marques.ibd
-- À exécuter dans phpMyAdmin, base medninestore (onglet SQL).
-- Les identifiants (20 à 29) sont ceux d'origine : les produits retrouvent leur marque.

DROP TABLE IF EXISTS `marques`;

CREATE TABLE `marques` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) NOT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `categorie_id` int(11) DEFAULT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `marques` (`id`, `nom`, `logo`, `categorie_id`, `actif`) VALUES
(20, 'Lattafa', '1782765311_Lattafa Mahasin Crystal Deodorant Spray for Women, 6_8 Oz (200 ml).jpeg', 8, 1),
(21, 'sheglam', '1782765516_Contour and highlighter Primer Lip kits.jpeg', 1, 1),
(22, 'Maison Alhambra', 'Maison Alhambra.jpeg', 8, 1),
(24, 'Armaf', '1783710994_1783458352_ARMAF Logo PNG Vector (AI) Free Download.jpeg', 8, 1),
(25, 'Afnan', 'Afnan copy.jpeg', 8, 1),
(26, 'Maison Rassasi', '1790021447_Maison_rassasi.jpeg', 8, 1),
(27, 'Maison French Avenu', '1790270499_French_Avenue.jpeg', 8, 1),
(28, 'Smart Collection', '1792788678_smart_collection_1.png', 8, 1),
(29, 'Frank Olivier', '1792843820_frank_olivier_paris.jpg', 8, 1);

ALTER TABLE `marques` AUTO_INCREMENT = 30;
