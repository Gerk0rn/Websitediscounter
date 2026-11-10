<?php
// Liefert die aktuellen Paketpreise für die Website (im Admin-Backend änderbar).

require __DIR__ . '/admin/lib.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
// "pixel": Meta-Pixel-ID – wird von consent.js nur nach Einwilligung genutzt
echo json_encode(get_prices() + ['pixel' => get_pixel_id()]);
