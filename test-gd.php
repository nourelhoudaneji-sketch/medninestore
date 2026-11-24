<?php

echo '<pre>';

echo 'PHP Version : ' . PHP_VERSION . "\n";

echo 'GD : ';
var_dump(extension_loaded('gd'));

echo 'imagewebp : ';
var_dump(function_exists('imagewebp'));

echo 'Imagick : ';
var_dump(extension_loaded('imagick'));

echo '</pre>';