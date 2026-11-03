<?php
// Nimmt Anfragen aus dem Preisrechner entgegen und schickt sie per E-Mail an Gerken Medien.
// Spamschutz: Herkunft, Größe, Honeypot, Mindest-Ausfüllzeit, Limit pro Absender, Link-Filter, Doppel-Sperre.

require __DIR__ . '/admin/lib.php';

$empfaenger = 'info@grkn.net';
$absender   = 'info@grkn.net';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

const MIN_DAUER_MS = 3000;          // schneller füllt kein Mensch das Formular aus
const LIMITS = [600 => 3, 86400 => 10]; // Sekunden => maximale Anfragen pro Absender

function antwort($ok, $status = 200) {
    http_response_code($status);
    echo json_encode(['success' => $ok]);
    exit;
}

function einzeilig($wert, $max) {
    $wert = trim(preg_replace('/[\r\n\t]+/', ' ', (string) $wert));
    return mb_substr($wert, 0, $max);
}

// Schlüssel für die gekürzte IP (Hash statt Klartext), einmalig erzeugt
function spam_schluessel() {
    $s = data_read('secret');
    if (!$s || empty($s['key'])) {
        $s = ['key' => bin2hex(random_bytes(32))];
        data_write('secret', $s);
    }
    return $s['key'];
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    antwort(false, 405);
}

// Nur Anfragen von der eigenen Seite
$host = strtolower($_SERVER['HTTP_HOST'] ?? '');
$herkunft = $_SERVER['HTTP_ORIGIN'] ?? ($_SERVER['HTTP_REFERER'] ?? '');
if ($host === '' || strtolower((string) parse_url($herkunft, PHP_URL_HOST)) !== preg_replace('/:\d+$/', '', $host)) {
    antwort(false, 403);
}
if (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== 0) {
    antwort(false, 415);
}
if ((int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 20000) {
    antwort(false, 413);
}

$daten = json_decode(file_get_contents('php://input', false, null, 0, 20000), true);
if (!is_array($daten)) {
    antwort(false, 400);
}

// Honeypot und Mindest-Ausfüllzeit: Bots bekommen eine Erfolgsmeldung, es geht aber nichts raus
if (!empty($daten['website']) || (int) ($daten['dauer'] ?? 0) < MIN_DAUER_MS) {
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

// Link-Filter: Spam enthält fast immer viele Links oder BBCode
$alles = "$name $firma $telefon $nachricht";
$links = preg_match_all('~(https?://|www\.)~i', $alles);
if ($links > 2 || preg_match('~https?://|www\.~i', $name) || preg_match('~\[(url|link)[=\]]|<a\s~i', $alles)) {
    antwort(false, 422);
}

// Limit pro Absender (nur ein Hash der IP, Einträge verfallen nach 24 Stunden) und Doppel-Sperre
$jetzt = time();
$absender_id = hash_hmac('sha256', $_SERVER['REMOTE_ADDR'] ?? '', spam_schluessel());
$inhalt_id = hash('sha256', strtolower("$email|$nachricht|$paket"));
$log = data_read('spam-limit') ?? ['ips' => [], 'inhalte' => []];
foreach (['ips', 'inhalte'] as $teil) {
    foreach ($log[$teil] ?? [] as $id => $zeiten) {
        $log[$teil][$id] = array_values(array_filter($zeiten, function ($t) use ($jetzt) { return $t > $jetzt - 86400; }));
        if (!$log[$teil][$id]) {
            unset($log[$teil][$id]);
        }
    }
}
$meine = $log['ips'][$absender_id] ?? [];
foreach (LIMITS as $fenster => $max) {
    if (count(array_filter($meine, function ($t) use ($jetzt, $fenster) { return $t > $jetzt - $fenster; })) >= $max) {
        antwort(false, 429);
    }
}
if (array_filter($log['inhalte'][$inhalt_id] ?? [], function ($t) use ($jetzt) { return $t > $jetzt - 600; })) {
    antwort(true); // gleiche Anfrage gerade erst gesendet
}
$log['ips'][$absender_id][] = $jetzt;
$log['inhalte'][$inhalt_id][] = $jetzt;
data_write('spam-limit', $log);

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
