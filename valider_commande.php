<?php
session_start();
require 'includes/db.php';

// PHPMailer (votre dossier lib/) : chargé une seule fois, notify.php le réutilise
require_once __DIR__ . '/lib/Exception.php';
require_once __DIR__ . '/lib/PHPMailer.php';
require_once __DIR__ . '/lib/SMTP.php';

// Notifications admin (email + WhatsApp) : réglages dans notify.php
require_once __DIR__ . '/notify.php';

// Vérification panier
if (empty($_SESSION['panier'])) {
    die("Panier vide");
}

// Le client peut passer commande sans connexion.
// Ta contrainte FK utilisateur_id -> utilisateurs.id oblige un id existant.
// On crée / utilise un utilisateur "client" (id récupéré en base) sans forcer le login.
if (!isset($_SESSION['user'])) {
    $stmtU = $conn->query("SELECT id FROM utilisateurs WHERE role = 'client' LIMIT 1");
    $utilisateur_id = (int) ($stmtU->fetchColumn() ?: 0);

    // Si aucun utilisateur client n'existe, on crée un utilisateur placeholder.
    if ($utilisateur_id === 0) {
        $defaultEmail = 'client_auto@local';
        $defaultNom = 'Client';
        $defaultPass = password_hash('client_auto', PASSWORD_DEFAULT);

        $stmtIns = $conn->prepare("INSERT INTO utilisateurs (nom, email, mot_de_passe, role) VALUES (?, ?, ?, 'client')");
        $stmtIns->execute([$defaultNom, $defaultEmail, $defaultPass]);
        $utilisateur_id = (int)$conn->lastInsertId();
    }
} else {
    $utilisateur_id = (int) $_SESSION['user']['id'];
}

// Données formulaire
$nom = trim($_POST['nom'] ?? '');
$telephone = trim($_POST['telephone'] ?? '');
$adresse = trim($_POST['adresse'] ?? '');

if (!$nom || !$telephone || !$adresse) {
    die("Informations manquantes");
}

// Condition : téléphone numérique uniquement et exactement 8 chiffres
if (!preg_match('/^\d{8}$/', $telephone)) {
    die("Numéro de téléphone invalide : il doit contenir exactement 8 chiffres numériques.");
}

/* =========================
   1️⃣ Calcul du total
========================= */
$total = 0;
$produits = [];

foreach ($_SESSION['panier'] as $produit_id => $quantite) {
    $stmt = $conn->prepare("SELECT id, nom, prix FROM produits WHERE id = ?");
    $stmt->execute([$produit_id]);
    $produit = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($produit) {
        $total += $produit['prix'] * $quantite;
        $produits[] = [
            'id'       => $produit['id'],
            'nom'      => $produit['nom'],
            'quantite' => $quantite,
            'prix'     => $produit['prix']
        ];
    }
}

/* =========================
   ➕ Frais de livraison
========================= */
$frais_livraison = 8; // 8 DT fixe
$total_final = $total + $frais_livraison;

/* =========================
   2️⃣ Insertion commande
========================= */
$statut = 'en_attente';

$stmt = $conn->prepare(
    "INSERT INTO commandes (utilisateur_id, nom, telephone, adresse, total, statut, date_commande)
     VALUES (?, ?, ?, ?, ?, ?, NOW())"
);

$stmt->execute([$utilisateur_id, $nom, $telephone, $adresse, $total_final, $statut]);
$commande_id = $conn->lastInsertId();

/* =========================
   3️⃣ Insertion produits
========================= */
$stmtProd = $conn->prepare("
    INSERT INTO commande_produits (commande_id, produit_id, quantite, prix)
    VALUES (?, ?, ?, ?)
");

$articlesNotif = []; // pour la notification admin
foreach ($produits as $p) {
    $stmtProd->execute([
        $commande_id,
        $p['id'],
        $p['quantite'],
        $p['prix']
    ]);

    $articlesNotif[] = [
        'nom'      => $p['nom'] ?: ("Produit #" . $p['id']),
        'quantite' => $p['quantite'],
        'prix'     => $p['prix'],
    ];
}

// Ligne « livraison » pour que le détail corresponde au total
$articlesNotif[] = [
    'nom'      => 'Frais de livraison',
    'quantite' => 1,
    'prix'     => $frais_livraison,
];

/* =========================
   3b. Notification admin (email + WhatsApp)
   Ne bloque jamais la commande en cas d'échec.
========================= */
notifierAdminCommande([
    'id'        => $commande_id,
    'client'    => $nom,
    'telephone' => '+216 ' . $telephone,
    'email'     => '',
    'adresse'   => $adresse,
    'total'     => $total_final,
    'articles'  => $articlesNotif,
]);

/* =========================
   4️⃣ Nettoyage panier
========================= */
unset($_SESSION['panier']);

// Notification admin : nombre de commandes aujourd'hui
$todayStmt = $conn->query(
    "SELECT COUNT(*) AS nb
     FROM commandes
     WHERE DATE(date_commande) = CURDATE()"
);
$nbCommandesJour = (int)($todayStmt->fetch(PDO::FETCH_ASSOC)['nb'] ?? 0);

$_SESSION['admin_notification'] = [
    'type' => 'success',
    'message' => 'Commandes aujourd\'hui : ' . $nbCommandesJour
];

header("Location: confirmation.php");
exit;