<?php
// Liefert die aktuellen Paketpreise für die Website (im Admin-Backend änderbar).

require __DIR__ . '/admin/lib.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
echo json_encode(get_prices());
