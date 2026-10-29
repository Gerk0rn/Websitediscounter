<?php
// Liefert die aktuellen Paketpreise für die Website (im Admin-Backend änderbar).

require __DIR__ . '/admin/lib.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
// "pixel": Meta-Pixel-ID – wird von consent.js nur nach Einwilligung genutzt
$abo = [];
foreach (get_abo() as $key => $a) {
    $abo[$key] = ['monthly' => $a['monthly'] / 100, 'setup' => $a['setup'] / 100, 'term' => $a['term'], 'discount' => $a['discount']];
}
echo json_encode(get_prices() + ['abo' => $abo, 'pixel' => get_pixel_id()]);
