<?php
/**
 * Fichier de diagnostic. À placer à côté de notify.php, puis ouvrir dans le navigateur :
 * http://localhost/medninestore/test-notif.php
 * SUPPRIMEZ-LE une fois le problème réglé.
 */
header('Content-Type: text/html; charset=utf-8');
ini_set('display_errors', '1');
error_reporting(E_ALL);

function ligne($ok, $texte)
{
    echo '<p style="font-family:Arial;margin:6px 0">' . ($ok ? '✅' : '❌') . ' ' . htmlspecialchars($texte) . '</p>';
}

echo '<h2 style="font-family:Arial">Diagnostic des notifications</h2>';

// 1. Fichiers
$notify = __DIR__ . '/notify.php';
ligne(file_exists($notify), 'notify.php trouvé dans ' . __DIR__);
ligne(
    file_exists(__DIR__ . '/vendor/autoload.php') || file_exists(__DIR__ . '/PHPMailer/src/PHPMailer.php'),
    'PHPMailer installé (vendor/autoload.php ou PHPMailer/src/PHPMailer.php) — sinon aucun email ne peut partir'
);
ligne(function_exists('curl_init'), 'Extension cURL active — sinon aucun message WhatsApp ne peut partir');
ligne(extension_loaded('openssl'), 'Extension OpenSSL active — nécessaire pour Gmail (SMTP sécurisé)');

if (!file_exists($notify)) {
    exit;
}

require_once $notify;
$config = notif_config();

// 2. Réglages encore par défaut ?
ligne($config['mail']['password'] !== 'pvtpjrogqbbaubgv', 'Mot de passe email modifié (pas la valeur par défaut)');
ligne($config['mail']['admin_to'] !== 'medninestore1@gmail.com', 'Adresse admin email modifiée');
ligne($config['whatsapp']['apikey'] !== 'VOTRE_CLE_CALLMEBOT', 'Clé CallMeBot modifiée');
ligne($config['whatsapp']['phone'] !== '+21629546699', 'Numéro WhatsApp modifié');

// 3. Test d'envoi
@unlink($config['log_file']);
$res = notifierAdminCommande([
    'id'        => 'TEST',
    'client'    => 'Client Test',
    'telephone' => '+216 20 000 000',
    'email'     => 'test@exemple.com',
    'adresse'   => 'Adresse test',
    'total'     => 100,
    'articles'  => [['nom' => 'Produit test', 'quantite' => 1, 'prix' => 100]],
]);

ligne($res['email'], 'Email envoyé');
ligne($res['whatsapp'], 'WhatsApp envoyé');

// 4. Erreurs détaillées
echo '<h3 style="font-family:Arial">Erreurs notées</h3>';
if (file_exists($config['log_file'])) {
    echo '<pre style="background:#f5f5f5;padding:12px;white-space:pre-wrap">' . htmlspecialchars(file_get_contents($config['log_file'])) . '</pre>';
} else {
    echo '<p style="font-family:Arial">Aucune erreur enregistrée.</p>';
}
