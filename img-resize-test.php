<?php
/**
 * Mini-service image resize + WebP conversion

 * Usage:
 *   /parfemerie000/img-resize.php?src=img/FILE.jpg&w=320
 *   /parfemerie000/img-resize.php?src=img/marques/LOGO.png&w=64
 *
 * Sécurité:
 * - src doit être relatif à /parfemerie000 et ne pas contenir ..
 */

declare(strict_types=1);

// --- Config ---
$BASE_DIR = __DIR__;
$DEFAULT_W = 640;
$QUALITY = 82;

$src = $_GET['src'] ?? '';
$w = isset($_GET['w']) ? (int)$_GET['w'] : $DEFAULT_W;

if ($w <= 0) {
    $w = $DEFAULT_W;
}

// Normaliser séparateurs
$src = str_replace('\\', '/', (string)$src);

// Refuser les chemins suspects
if ($src === '' || str_contains($src, '../') || str_starts_with($src, '/') || str_contains($src, "..")) {
    http_response_code(400);
    exit('Bad src');
}

$srcPath = $BASE_DIR . '/' . $src;
if (!is_file($srcPath)) {
    http_response_code(404);
    exit('Not found');
}

// Sortie cache: img/cache-webp/<w>/<original_name>.webp (garde structure)
$pathParts = pathinfo($src);
$ext = strtolower($pathParts['extension'] ?? '');
$baseName = $pathParts['filename'] ?? 'image';

// garder dossier
$srcDir = dirname($src);
if ($srcDir === '.' || $srcDir === '') {
    $srcDir = '';
}

$outDir = $BASE_DIR . '/img/cache-webp/' . $w . '/' . ($srcDir ? $srcDir : 'root');
if (!is_dir($outDir)) {
    @mkdir($outDir, 0775, true);
}

$outFile = $outDir . '/' . $baseName . '.webp';

// Si déjà converti
if (is_file($outFile) && filesize($outFile) > 0) {
header('Content-Type: image/webp');
header('Cache-Control: public, max-age=86400');
readfile($outFile);
exit;

}

// Taille originale
// (on évite getimagesize si possible, mais c’est ok)
$info = @getimagesize($srcPath);
if ($info === false) {
    http_response_code(415);
    exit('Unsupported image');
}
[$origW, $origH] = $info;

// Calcul nouvelle hauteur (garder ratio)
$newW = min($w, (int)$origW);
if ($newW <= 0) {
    $newW = $w;
}
$newH = (int)round(($newW / max(1, (int)$origW)) * (int)$origH);
$newH = max(1, $newH);

// --- Implémentation: Imagick si dispo, sinon GD ---
$used = false;

if (extension_loaded('imagick')) {
    try {
        $im = new Imagick($srcPath);
        $im->setImageOrientation(Imagick::ORIENTATION_TOPLEFT);
        $im->setImageAlphaChannel(Imagick::ALPHACHANNEL_REMOVE);
        $im->resizeImage($newW, $newH, Imagick::FILTER_LANCZOS, 1);
        $im->setImageFormat('webp');
        $im->setImageCompressionQuality($QUALITY);

        $im->writeImage($outFile);
        $im->clear();
        $im->destroy();

        $used = true;
    } catch (Throwable $e) {
        $used = false;
    }
}

if (!$used && function_exists('imagecreatefromstring') && extension_loaded('gd')) {
    $data = @file_get_contents($srcPath);
    if ($data !== false) {
        $img = @imagecreatefromstring($data);
        if ($img !== false) {
            $dst = imagecreatetruecolor($newW, $newH);

            // Conserver transparence si PNG/GIF
            imagealphablending($dst, false);
            imagesavealpha($dst, true);

            imagecopyresampled($dst, $img, 0, 0, 0, 0, $newW, $newH, $origW, $origH);

            // PNG/JPEG -> WebP (GD supporte imagewebp si extension >=)
            if (function_exists('imagewebp')) {
                imagewebp($dst, $outFile, $QUALITY);
                $used = true;
            }

            imagedestroy($dst);
            imagedestroy($img);
        }
    }
}

if (!is_file($outFile) || filesize($outFile) === 0) {
    http_response_code(500);
    exit('Conversion failed');
}

header('Content-Type: image/webp');
header('Cache-Control: public, max-age=86400');
readfile($outFile);
exit;

