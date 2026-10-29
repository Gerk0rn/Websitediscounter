<?php
// Nimmt Anfragen aus dem Preisrechner entgegen und schickt sie per E-Mail an Gerken Medien.

$empfaenger = 'info@grkn.net';
$absender   = 'info@grkn.net';

header('Content-Type: application/json; charset=utf-8');

function antwort($ok, $status = 200) {
    http_response_code($status);
    echo json_encode(['success' => $ok]);
    exit;
}

function einzeilig($wert, $max) {
    $wert = trim(preg_replace('/[\r\n\t]+/', ' ', (string) $wert));
    return mb_substr($wert, 0, $max);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    antwort(false, 405);
}

$daten = json_decode(file_get_contents('php://input'), true);
if (!is_array($daten)) {
    antwort(false, 400);
}

// Honeypot: echte Besucher sehen dieses Feld nicht
if (!empty($daten['website'])) {
    antwort(true);
}

$name    = einzeilig($daten['name'] ?? '', 100);
$email   = filter_var(einzeilig($daten['email'] ?? '', 150), FILTER_VALIDATE_EMAIL);
$telefon = einzeilig($daten['telefon'] ?? '', 50);
$firma   = einzeilig($daten['firma'] ?? '', 150);
$paket   = einzeilig($daten['paket'] ?? '', 100);
$zahlweise = einzeilig($daten['zahlweise'] ?? '', 300);
$antworten = mb_substr(trim((string) ($daten['antworten'] ?? '')), 0, 2000);
$nachricht = mb_substr(trim((string) ($daten['nachricht'] ?? '')), 0, 3000);

if ($name === '' || !$email || $paket === '') {
    antwort(false, 400);
}

$text = "Neue Anfrage über den Website Discounter\n\n"
      . "Paket: $paket\n"
      . "Zahlweise: " . ($zahlweise !== '' ? $zahlweise : '–') . "\n\n"
      . "Name: $name\n"
      . "E-Mail: $email\n"
      . "Telefon: " . ($telefon !== '' ? $telefon : '–') . "\n"
      . "Firma/Projekt: " . ($firma !== '' ? $firma : '–') . "\n\n"
      . "Antworten im Rechner:\n" . ($antworten !== '' ? $antworten : '–') . "\n\n"
      . "Nachricht:\n" . ($nachricht !== '' ? $nachricht : '–') . "\n";

$betreff = '=?UTF-8?B?' . base64_encode("Website-Anfrage: $paket – $name") . '?=';
$header  = "From: Website Discounter <$absender>\r\n"
         . "Reply-To: $email\r\n"
         . "MIME-Version: 1.0\r\n"
         . "Content-Type: text/plain; charset=UTF-8\r\n"
         . "Content-Transfer-Encoding: 8bit";

$ok = mail($empfaenger, $betreff, $text, $header, '-f' . $absender);
antwort($ok, $ok ? 200 : 500);
