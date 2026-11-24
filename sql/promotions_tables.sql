-- Promotions + association aux produits
-- À exécuter dans phpMyAdmin (base: mednine_store)

CREATE TABLE IF NOT EXISTS promotions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  titre VARCHAR(255) NOT NULL,
  description TEXT NULL,

  -- Champs de remise (optionnel)
  -- Ex: pourcentage=25 signifie -25%
  pourcentage DECIMAL(6,2) NULL,

  -- Ex: prix_fixe=120 signifie nouveau prix = 120
  prix_fixe DECIMAL(10,2) NULL,

  -- prix_fixe | pourcentage | mix
  type_promo ENUM('prix_fixe','pourcentage','mix') NOT NULL DEFAULT 'mix',

  date_debut DATE NULL,
  date_fin DATE NULL,

  actif TINYINT(1) NOT NULL DEFAULT 1,
  date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS promotion_produits (
  id INT AUTO_INCREMENT PRIMARY KEY,
  promotion_id INT NOT NULL,
  produit_id INT NOT NULL,

  UNIQUE KEY uniq_promo_produit (promotion_id, produit_id),

  CONSTRAINT fk_promo
    FOREIGN KEY (promotion_id) REFERENCES promotions(id)
    ON DELETE CASCADE,

  CONSTRAINT fk_produit
    FOREIGN KEY (produit_id) REFERENCES produits(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

