<?php
declare(strict_types=1);

/**
 * Usage :
 * /parfemerie000/img-resize.php?src=img/marques/logo.jpg&w=64
 */

$BASE_DIR  = __DIR__;
$DEFAULT_W = 640;
$QUALITY   = 82;

$src = $_GET['src'] ?? '';
$w   = isset($_GET['w']) ? (int)$_GET['w'] : $DEFAULT_W;

if ($w <= 0) {
    $w = $DEFAULT_W;
}

// Sécurité
$src = str_replace('\\', '/', trim($src));

if (
    $src === '' ||
    str_contains($src, '..') ||
    str_starts_with($src, '/')
) {
    http_response_code(400);
    exit('Bad src');
}

$srcPath = realpath($BASE_DIR . '/' . $src);

if (
    $srcPath === false ||
    !is_file($srcPath) ||
    !str_starts_with($srcPath, realpath($BASE_DIR))
) {
    http_response_code(404);
    exit('Image not found');
}

// Infos image
$info = @getimagesize($srcPath);

if ($info === false) {
    http_response_code(415);
    exit('Unsupported image');
}

$origW = (int)$info[0];
$origH = (int)$info[1];

$newW = min($w, $origW);
$newH = (int)round(($newW / $origW) * $origH);

$pathInfo = pathinfo($src);

$baseName = preg_replace(
    '/[^a-zA-Z0-9_-]/',
    '_',
    $pathInfo['filename'] ?? 'image'
);

$srcDir = dirname($src);

if ($srcDir === '.') {
    $srcDir = 'root';
}

$outDir = $BASE_DIR . '/img/cache-webp/' . $w . '/' . $srcDir;

if (!is_dir($outDir)) {
    mkdir($outDir, 0775, true);
}

$outFile = $outDir . '/' . $baseName . '.webp';

// Cache
if (is_file($outFile) && filesize($outFile) > 0) {
    header('Content-Type: image/webp');
    header('Cache-Control: public, max-age=86400');
    readfile($outFile);
    exit;
}

$success = false;

/*
|--------------------------------------------------------------------------
| Imagick
|--------------------------------------------------------------------------
*/
if (extension_loaded('imagick')) {
    try {
        $img = new Imagick($srcPath);

        $img->setImageFormat('webp');
        $img->resizeImage(
            $newW,
            $newH,
            Imagick::FILTER_LANCZOS,
            1
        );

        $img->setImageCompressionQuality($QUALITY);

        $success = $img->writeImage($outFile);

        $img->clear();
        $img->destroy();
    } catch (Throwable $e) {
        $success = false;
    }
}

/*
|--------------------------------------------------------------------------
| GD
|--------------------------------------------------------------------------
*/
if (
    !$success &&
    extension_loaded('gd') &&
    function_exists('imagecreatefromstring') &&
    function_exists('imagewebp')
) {
    $data = @file_get_contents($srcPath);

    if ($data !== false) {
        $srcImg = @imagecreatefromstring($data);

        if ($srcImg !== false) {

            $dstImg = imagecreatetruecolor($newW, $newH);

            imagealphablending($dstImg, false);
            imagesavealpha($dstImg, true);

            imagecopyresampled(
                $dstImg,
                $srcImg,
                0,
                0,
                0,
                0,
                $newW,
                $newH,
                $origW,
                $origH
            );

            $success = imagewebp(
                $dstImg,
                $outFile,
                $QUALITY
            );

            imagedestroy($srcImg);
            imagedestroy($dstImg);
        }
    }
}

if (!$success || !is_file($outFile) || filesize($outFile) === 0) {
    http_response_code(500);
    echo "<h3>Erreur de conversion WebP</h3>";
    echo "<pre>";
    echo "Fichier : " . htmlspecialchars($srcPath) . "\n";
    echo "GD : " . (extension_loaded('gd') ? 'OUI' : 'NON') . "\n";
    echo "imagewebp : " . (function_exists('imagewebp') ? 'OUI' : 'NON') . "\n";
    echo "Imagick : " . (extension_loaded('imagick') ? 'OUI' : 'NON') . "\n";
    echo "</pre>";
    exit;
}

header('Content-Type: image/webp');
header('Cache-Control: public, max-age=86400');
readfile($outFile);
exit;

