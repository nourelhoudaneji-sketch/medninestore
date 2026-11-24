<?php
/**
 * notify-sms.php — Envoie un SMS à l'admin quand une commande est passée (via Twilio).
 *
 * 1. Créez un compte sur twilio.com
 * 2. Récupérez : Account SID, Auth Token, et un numéro Twilio (ou un "Messaging Service")
 * 3. Remplissez les réglages dans sms_config() ci-dessous
 *
 * Utilisation (dans valider_commande.php) :
 *     require_once __DIR__ . '/notify-sms.php';
 *     notifierSmsAdmin($commande_id, $nom, $telephone, $total_final);
 */

/* =====================================================================
   RÉGLAGES : modifiez uniquement cette fonction
   ===================================================================== */
function sms_config(): array
{
    return [
        'actif'       => true,
        'account_sid' => 'ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',  // Twilio : Account SID
        'auth_token'  => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',    // Twilio : Auth Token
        'from'        => '+1XXXXXXXXXX',                        // numéro expéditeur fourni par Twilio
        'to'          => '+21600000000',                        // numéro de l'admin (format international)
        'devise'      => 'DT',
        'log_file'    => __DIR__ . '/notifications.log',
    ];
}

/* ===================================================================== */

function sms_log(string $message, array $config): void
{
    @file_put_contents(
        $config['log_file'],
        '[' . date('Y-m-d H:i:s') . '] SMS: ' . $message . PHP_EOL,
        FILE_APPEND | LOCK_EX
    );
}

/**
 * Envoie le SMS. Ne lève jamais d'exception et ne bloque jamais la commande.
 * Texte volontairement court et sans accents/emojis : un SMS reste ainsi à 1 segment (moins cher).
 */
function notifierSmsAdmin($commandeId, string $client, string $telephone, $total): bool
{
    $c = sms_config();
    if (empty($c['actif'])) {
        return false;
    }

    if (!function_exists('curl_init')) {
        sms_log('extension cURL désactivée dans php.ini', $c);
        return false;
    }

    // Nettoyage : on évite les caractères qui font passer le SMS en Unicode (plus long)
    $client = preg_replace('/[^A-Za-z0-9 \-\.]/', '', iconv('UTF-8', 'ASCII//TRANSLIT', $client) ?: $client);
    $client = substr(trim($client), 0, 25);

    $texte = 'Nouvelle commande #' . $commandeId
           . ' - ' . $client
           . ' - Tel ' . $telephone
           . ' - Total ' . number_format((float) $total, 2, ',', '') . ' ' . $c['devise'];

    $url = 'https://api.twilio.com/2010-04-01/Accounts/' . rawurlencode($c['account_sid']) . '/Messages.json';

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query([
            'From' => $c['from'],
            'To'   => $c['to'],
            'Body' => $texte,
        ]),
        CURLOPT_USERPWD        => $c['account_sid'] . ':' . $c['auth_token'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_CONNECTTIMEOUT => 5,
    ]);
    $reponse = curl_exec($ch);
    $code    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $erreur  = curl_error($ch);
    curl_close($ch);

    if ($reponse === false || $code < 200 || $code >= 300) {
        sms_log('ERREUR (HTTP ' . $code . '): ' . ($erreur ?: substr((string) $reponse, 0, 300)), $c);
        return false;
    }
    return true;
}
