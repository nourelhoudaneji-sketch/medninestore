<?php
/**
 * notify.php — Notifie l'admin (email + WhatsApp) quand une commande est passée.
 *
 * Utilisation (dans valider_commande.php, après l'INSERT) :
 *     require_once __DIR__ . '/notify.php';
 *     notifierAdminCommande([
 *         'id'        => 123,
 *         'client'    => 'Nom Prénom',
 *         'telephone' => '+216 20 000 000',
 *         'email'     => 'client@exemple.com',
 *         'adresse'   => 'Adresse de livraison',
 *         'total'     => 245.500,
 *         'articles'  => [
 *             ['nom' => 'Oud Royal 100ml', 'quantite' => 2, 'prix' => 89.0],
 *         ],
 *     ]);
 *
 * PHPMailer : deux façons de l'installer (une seule suffit)
 *   A) Composer :  composer require phpmailer/phpmailer
 *   B) Sans Composer : télécharger PHPMailer, copier le dossier "src" dans
 *      votre site sous le nom  PHPMailer/src/  (PHPMailer.php, SMTP.php, Exception.php)
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

/* =====================================================================
   RÉGLAGES : modifiez uniquement cette fonction
   ===================================================================== */
function notif_config(): array
{
    return [
        // ---------- EMAIL (SMTP) ----------
        'mail' => [
            'actif'      => true,
            'host'       => 'smtp.gmail.com',
            'port'       => 587,
            'secure'     => 'tls',                      // 'tls' (587) ou 'ssl' (465)
            'username'   => 'medninestore1@gmail.com',
            'password'   => 'pvtpjrogqbbaubgv', // Gmail : mot de passe d'application
            'from_email' => 'medninestore1@gmail.com',
            'from_name'  => 'Mednine Store',
            'admin_to'   => 'medninestore1@gmail.com',        // l'adresse qui reçoit les commandes
        ],

        // ---------- WHATSAPP (CallMeBot) ----------
        'whatsapp' => [
            'actif'  => true,
            'phone'  => '+21629546699',      // numéro de l'admin, format international
            'apikey' => 'VOTRE_CLE_CALLMEBOT',
        ],

        'log_file' => __DIR__ . '/notifications.log',
        'devise'   => 'TND',
    ];
}

/* ===================================================================== */

function notif_log(string $message, array $config): void
{
    $ligne = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
    @file_put_contents($config['log_file'], $ligne, FILE_APPEND | LOCK_EX);
}

function notif_e($valeur): string
{
    return htmlspecialchars((string) $valeur, ENT_QUOTES, 'UTF-8');
}

function notif_prix($montant, string $devise): string
{
    return number_format((float) $montant, 2, ',', ' ') . ' ' . $devise;
}

/** Message texte (WhatsApp et version texte de l'email) */
function notif_message_texte(array $c, string $devise): string
{
    $lignes = [];
    $lignes[] = '🛍️ NOUVELLE COMMANDE #' . ($c['id'] ?? '?');
    $lignes[] = '';
    $lignes[] = '👤 ' . ($c['client'] ?? 'Client');
    $lignes[] = '📞 ' . ($c['telephone'] ?? '-');
    if (!empty($c['email']))   { $lignes[] = '✉️ ' . $c['email']; }
    if (!empty($c['adresse'])) { $lignes[] = '📍 ' . $c['adresse']; }
    $lignes[] = '';
    $lignes[] = 'Articles :';
    foreach (($c['articles'] ?? []) as $a) {
        $lignes[] = '• ' . ($a['nom'] ?? 'Produit') . ' x' . ($a['quantite'] ?? 1)
                  . ' — ' . notif_prix(($a['prix'] ?? 0) * ($a['quantite'] ?? 1), $devise);
    }
    $lignes[] = '';
    $lignes[] = '💰 TOTAL : ' . notif_prix($c['total'] ?? 0, $devise);

    return implode("\n", $lignes);
}

/** Message HTML pour l'email */
function notif_message_html(array $c, string $devise): string
{
    $rows = '';
    foreach (($c['articles'] ?? []) as $a) {
        $sous = ($a['prix'] ?? 0) * ($a['quantite'] ?? 1);
        $rows .= '<tr>'
            . '<td style="padding:8px;border-bottom:1px solid #eee">' . notif_e($a['nom'] ?? 'Produit') . '</td>'
            . '<td style="padding:8px;border-bottom:1px solid #eee;text-align:center">' . notif_e($a['quantite'] ?? 1) . '</td>'
            . '<td style="padding:8px;border-bottom:1px solid #eee;text-align:right">' . notif_e(notif_prix($sous, $devise)) . '</td>'
            . '</tr>';
    }

    return '
    <div style="font-family:Arial,sans-serif;max-width:560px;margin:auto;border:1px solid #e7ded2;border-radius:10px;overflow:hidden">
      <div style="background:#6B1E35;color:#fff;padding:20px;text-align:center">
        <h2 style="margin:0;font-weight:500">Nouvelle commande #' . notif_e($c['id'] ?? '?') . '</h2>
      </div>
      <div style="padding:20px;color:#1c1714">
        <p><strong>Client :</strong> ' . notif_e($c['client'] ?? '-') . '<br>
           <strong>Téléphone :</strong> ' . notif_e($c['telephone'] ?? '-') . '<br>
           <strong>Email :</strong> ' . notif_e($c['email'] ?? '-') . '<br>
           <strong>Adresse :</strong> ' . notif_e($c['adresse'] ?? '-') . '</p>
        <table style="width:100%;border-collapse:collapse;font-size:14px">
          <tr style="background:#f1e9de"><th style="padding:8px;text-align:left">Article</th><th style="padding:8px">Qté</th><th style="padding:8px;text-align:right">Prix</th></tr>
          ' . $rows . '
        </table>
        <p style="text-align:right;font-size:18px;color:#6B1E35"><strong>Total : ' . notif_e(notif_prix($c['total'] ?? 0, $devise)) . '</strong></p>
      </div>
    </div>';
}

/** Charge PHPMailer (Composer ou dossier PHPMailer/src). Retourne false s'il est introuvable. */
function notif_charger_phpmailer(): bool
{
    if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        return true;
    }

    $autoload = __DIR__ . '/vendor/autoload.php';
    if (file_exists($autoload)) {
        require_once $autoload;
        return class_exists('PHPMailer\\PHPMailer\\PHPMailer');
    }

    $src = __DIR__ . '/PHPMailer/src/';
    if (file_exists($src . 'PHPMailer.php')) {
        require_once $src . 'Exception.php';
        require_once $src . 'PHPMailer.php';
        require_once $src . 'SMTP.php';
        return true;
    }

    return false;
}

function notif_envoyer_email(array $c, array $config): bool
{
    $m = $config['mail'];
    if (empty($m['actif'])) {
        return false;
    }

    if (!notif_charger_phpmailer()) {
        notif_log('EMAIL: PHPMailer introuvable (ni vendor/autoload.php, ni PHPMailer/src/PHPMailer.php)', $config);
        return false;
    }

    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = $m['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $m['username'];
        $mail->Password   = $m['password'];
        $mail->SMTPSecure = $m['secure'] === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = (int) $m['port'];
        $mail->CharSet    = 'UTF-8';
        $mail->Timeout    = 10;

        $mail->setFrom($m['from_email'], $m['from_name']);
        $mail->addAddress($m['admin_to']);
        if (!empty($c['email']) && filter_var($c['email'], FILTER_VALIDATE_EMAIL)) {
            $mail->addReplyTo($c['email'], $c['client'] ?? '');
        }

        $mail->isHTML(true);
        $mail->Subject = 'Nouvelle commande #' . ($c['id'] ?? '?') . ' - ' . notif_prix($c['total'] ?? 0, $config['devise']);
        $mail->Body    = notif_message_html($c, $config['devise']);
        $mail->AltBody = notif_message_texte($c, $config['devise']);

        $mail->send();
        return true;
    } catch (MailException $e) {
        notif_log('EMAIL ERREUR: ' . $e->getMessage(), $config);
        return false;
    } catch (Throwable $e) {
        notif_log('EMAIL ERREUR: ' . $e->getMessage(), $config);
        return false;
    }
}

function notif_envoyer_whatsapp(array $c, array $config): bool
{
    $w = $config['whatsapp'];
    if (empty($w['actif'])) {
        return false;
    }

    if (!function_exists('curl_init')) {
        notif_log('WHATSAPP: extension cURL désactivée dans php.ini', $config);
        return false;
    }

    $url = 'https://api.callmebot.com/whatsapp.php?' . http_build_query([
        'phone'  => $w['phone'],
        'text'   => notif_message_texte($c, $config['devise']),
        'apikey' => $w['apikey'],
    ]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_CONNECTTIMEOUT => 5,
    ]);
    $reponse = curl_exec($ch);
    $code    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $erreur  = curl_error($ch);
    curl_close($ch);

    if ($reponse === false || $code < 200 || $code >= 300) {
        notif_log('WHATSAPP ERREUR (HTTP ' . $code . '): ' . ($erreur ?: substr((string) $reponse, 0, 200)), $config);
        return false;
    }
    return true;
}

/**
 * Point d'entrée : à appeler juste après l'enregistrement de la commande.
 * Ne lève jamais d'exception : une panne de notification ne doit pas bloquer la commande.
 *
 * @return array ['email' => bool, 'whatsapp' => bool]
 */
function notifierAdminCommande(array $commande): array
{
    $config = notif_config();

    $resultat = ['email' => false, 'whatsapp' => false];
    try {
        $resultat['email']    = notif_envoyer_email($commande, $config);
        $resultat['whatsapp'] = notif_envoyer_whatsapp($commande, $config);
    } catch (Throwable $e) {
        notif_log('ERREUR GLOBALE: ' . $e->getMessage(), $config);
    }
    return $resultat;
}
