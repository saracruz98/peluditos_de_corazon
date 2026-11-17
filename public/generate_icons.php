<?php
// Script para generar íconos PNG para la PWA
// Ejecutar una sola vez: php generate_icons.php

function makeIcon($size, $outPath) {
    // Crear imagen
    $img = imagecreatetruecolor($size, $size);
    
    // Fondo verde #198754
    $bg = imagecolorallocate($img, 25, 135, 84);
    imagefill($img, 0, 0, $bg);
    
    // Blanco para la pata
    $white = imagecolorallocate($img, 255, 255, 255);
    
    $cx = $size / 2;
    $cy = $size / 2 + $size * 0.05;
    $r  = $size * 0.22;  // radio pata principal
    
    // Pata principal (elipse)
    imagefilledellipse($img, (int)$cx, (int)$cy, (int)($r * 2.2), (int)($r * 2.5), $white);
    
    // 4 dedos encima
    $dw = (int)($r * 0.75);
    $dh = (int)($r * 1.0);
    $oy = (int)($cy - $r * 1.6);
    
    imagefilledellipse($img, (int)($cx - $r * 1.0), $oy, $dw, $dh, $white);
    imagefilledellipse($img, (int)($cx - $r * 0.33), (int)($oy - $r * 0.2), $dw, $dh, $white);
    imagefilledellipse($img, (int)($cx + $r * 0.33), (int)($oy - $r * 0.2), $dw, $dh, $white);
    imagefilledellipse($img, (int)($cx + $r * 1.0), $oy, $dw, $dh, $white);
    
    // Crear directorio si no existe
    $dir = dirname($outPath);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    
    imagepng($img, $outPath);
    imagedestroy($img);
    echo "Creado: $outPath ({$size}x{$size})\n";
}

makeIcon(192, __DIR__ . '/icons/icon-192.png');
makeIcon(512, __DIR__ . '/icons/icon-512.png');
makeIcon(180, __DIR__ . '/icons/apple-touch-icon.png');
echo "¡Iconos generados correctamente!\n";
