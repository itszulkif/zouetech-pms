<?php
/**
 * PWA Icon Generator - outputs PNG icon for Zouetech-PMS
 * Usage: icon.php?size=192 or icon.php?size=512
 */
$size = isset($_GET['size']) ? (int)$_GET['size'] : 192;
if (!in_array($size, [192, 512])) $size = 192;

header('Content-Type: image/png');
header('Cache-Control: public, max-age=86400');

if (extension_loaded('gd')) {
    $img = imagecreatetruecolor($size, $size);
    if ($img) {
        imagealphablending($img, true);
        imagesavealpha($img, true);
        $trans = imagecolorallocatealpha($img, 0, 0, 0, 127);
        imagefill($img, 0, 0, $trans);
        $cyan = imagecolorallocate($img, 6, 182, 212);
        $indigo = imagecolorallocate($img, 99, 102, 241);
        $border = imagecolorallocate($img, 255, 255, 255);
        imagefilledellipse($img, $size/2, $size/2, $size-4, $size-4, $cyan);
        imageellipse($img, $size/2, $size/2, $size-4, $size-4, $indigo);
        imagepng($img);
        imagedestroy($img);
        exit;
    }
}
// Fallback: minimal 1x1 PNG if GD unavailable
echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
