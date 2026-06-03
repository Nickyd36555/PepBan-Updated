<?php
// Generates the PepBan favicon as a PNG — served at /favicon.ico and /favicon.png
header('Content-Type: image/png');
header('Cache-Control: public, max-age=604800');

$size = 64;
$img  = imagecreatetruecolor($size, $size);
imagealphablending($img, false);
imagesavealpha($img, true);

$transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
imagefill($img, 0, 0, $transparent);

$red  = imagecolorallocate($img, 220, 38, 38);
$dark = imagecolorallocate($img, 185, 28, 28);
$bg   = imagecolorallocate($img, 12, 12, 30);
$white = imagecolorallocate($img, 255, 255, 255);

// Rounded square background
imagefilledrectangle($img, 4, 4, 59, 59, $bg);

// Shield body — filled polygon
$shield = [
    32, 8,   // top center
    52, 16,  // top right
    56, 36,  // right
    32, 58,  // bottom
    8,  36,  // left
    12, 16,  // top left
];
imagefilledpolygon($img, $shield, $red);

// Inner shield highlight
$inner = [
    32, 14,
    48, 21,
    51, 36,
    32, 53,
    13, 36,
    16, 21,
];
imagefilledpolygon($img, $inner, $dark);

// "PB" text centered — use built-in font
$font = 5; // largest built-in font (9x15)
$fw = imagefontwidth($font);
$fh = imagefontheight($font);
$text = 'PB';
$tx = (int)(($size - strlen($text) * $fw) / 2);
$ty = (int)(($size - $fh) / 2);
imagestring($img, $font, $tx, $ty, $text, $white);

imagepng($img);
imagedestroy($img);
