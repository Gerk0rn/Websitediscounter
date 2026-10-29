<?php
// Gemeinsame Funktionen für das Admin-Backend des Website Discounters.
// Daten liegen als JSON in ../data/ – jede Datei beginnt mit einer PHP-Exit-Zeile,
// damit sie über den Browser nie lesbar ist (zusätzlich sperrt data/.htaccess den Ordner).

const ADMIN_MAIL = 'info@grkn.net';
const DATA_DIR = __DIR__ . '/../data';
const DEFAULT_PRICES = ['start' => 500, 'business' => 1200, 'premium' => 1650];
const PACKAGES = ['start' => 'Start', 'business' => 'Business', 'premium' => 'Premium'];
// Abo je Paket: monthly/setup in Cent, term in Monaten, discount in Prozent bei jährlicher Zahlung
const DEFAULT_ABO = [
    'start'    => ['monthly' => 3999,  'setup' => 9999,  'term' => 12, 'discount' => 20],
    'business' => ['monthly' => 8999,  'setup' => 12999, 'term' => 12, 'discount' => 20],
    'premium'  => ['monthly' => 10999, 'setup' => 15999, 'term' => 12, 'discount' => 20],
];
const MAX_FAILS = 5;            // Fehlversuche ...
const LOCK_SECONDS = 15 * 60;   // ... innerhalb dieser Zeit sperren den Login
const CODE_SECONDS = 30 * 60;   // so lange gilt der Einrichtungscode

date_default_timezone_set('Europe/Berlin');

// ---------- Speicher ----------

function data_path($name) {
    return DATA_DIR . '/' . $name . '.php';
}

function data_read($name) {
    $file = data_path($name);
    if (!is_file($file)) {
        return null;
    }
    $raw = file_get_contents($file);
    $json = substr($raw, strpos($raw, "\n") + 1);
    $data = json_decode($json, true);
    return is_array($data) ? $data : null;
}

function data_write($name, array $data) {
    if (!is_dir(DATA_DIR) && !mkdir(DATA_DIR, 0755, true)) {
        return false;
    }
    $file = data_path($name);
    $tmp = $file . '.' . bin2hex(random_bytes(4)) . '.tmp';
    $content = "<?php exit; ?>\n" . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if (file_put_contents($tmp, $content, LOCK_EX) === false) {
        return false;
    }
    return rename($tmp, $file);
}

function data_delete($name) {
    $file = data_path($name);
    if (is_file($file)) {
        unlink($file);
    }
}

function get_prices() {
    $saved = data_read('prices') ?? [];
    $prices = DEFAULT_PRICES;
    foreach ($prices as $key => $value) {
        if (isset($saved[$key]) && is_int($saved[$key])) {
            $prices[$key] = $saved[$key];
        }
    }
    return $prices;
}

function get_abo() {
    $saved = (data_read('prices') ?? [])['abo'] ?? [];
    $abo = DEFAULT_ABO;
    foreach ($abo as $key => $fields) {
        foreach ($fields as $field => $value) {
            if (isset($saved[$key][$field]) && is_int($saved[$key][$field])) {
                $abo[$key][$field] = $saved[$key][$field];
            }
        }
    }
    return $abo;
}

// Jährlicher Abo-Preis in Cent: 12 Monatsbeiträge abzüglich Rabatt
function abo_yearly(array $a) {
    return (int) round($a['monthly'] * 12 * (100 - $a['discount']) / 100);
}

// "39,99" / "39.99" / "40" → 3999 Cent, sonst null
function parse_cents($raw) {
    $raw = str_replace([' ', '€'], '', trim((string) $raw));
    if (!preg_match('/^(\d{1,6})(?:[.,](\d{1,2}))?$/', $raw, $m)) {
        return null;
    }
    return (int) $m[1] * 100 + (int) str_pad($m[2] ?? '0', 2, '0');
}

function money($cents) {
    return number_format($cents / 100, 2, ',', '.');
}

function get_pixel_id() {
    $settings = data_read('settings') ?? [];
    $id = (string) ($settings['pixel'] ?? '');
    return preg_match('/^\d{5,20}$/', $id) ? $id : '';
}

function get_user() {
    return data_read('user');
}

// ---------- Sitzung & Schutz ----------

function start_session() {
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_name('wd_admin');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/',
        'secure' => $https,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
    header('Cache-Control: no-store');
    header('X-Robots-Tag: noindex, nofollow');
}

function csrf_field() {
    return '<input type="hidden" name="csrf" value="' . h($_SESSION['csrf']) . '">';
}

function csrf_ok() {
    return isset($_POST['csrf']) && is_string($_POST['csrf']) && hash_equals($_SESSION['csrf'], $_POST['csrf']);
}

function logged_in() {
    $user = get_user();
    return $user && isset($_SESSION['user']) && $_SESSION['user'] === $user['username']
        && isset($_SESSION['pw']) && $_SESSION['pw'] === substr($user['password'], -16);
}

function client_ip() {
    return $_SERVER['REMOTE_ADDR'] ?? 'unbekannt';
}

function login_locked() {
    $fails = data_read('login-fails') ?? [];
    $ip = client_ip();
    if (!isset($fails[$ip])) {
        return false;
    }
    $recent = array_filter($fails[$ip], function ($t) { return $t > time() - LOCK_SECONDS; });
    return count($recent) >= MAX_FAILS;
}

function login_failed() {
    $fails = data_read('login-fails') ?? [];
    $cutoff = time() - LOCK_SECONDS;
    foreach ($fails as $ip => $times) {
        $fails[$ip] = array_values(array_filter($times, function ($t) use ($cutoff) { return $t > $cutoff; }));
        if (!$fails[$ip]) {
            unset($fails[$ip]);
        }
    }
    $fails[client_ip()][] = time();
    data_write('login-fails', $fails);
}

function login_reset_fails() {
    $fails = data_read('login-fails') ?? [];
    unset($fails[client_ip()]);
    data_write('login-fails', $fails);
}

function password_problem($password, $repeat) {
    if (mb_strlen($password) < 10) {
        return 'Das Passwort muss mindestens 10 Zeichen lang sein.';
    }
    if ($password !== $repeat) {
        return 'Die beiden Passwörter stimmen nicht überein.';
    }
    return null;
}

function send_mail($subject, $text) {
    $header = "From: Website Discounter <" . ADMIN_MAIL . ">\r\n"
            . "MIME-Version: 1.0\r\n"
            . "Content-Type: text/plain; charset=UTF-8\r\n"
            . "Content-Transfer-Encoding: 8bit";
    return mail(ADMIN_MAIL, '=?UTF-8?B?' . base64_encode($subject) . '?=', $text, $header, '-f' . ADMIN_MAIL);
}

// ---------- Ausgabe ----------

function h($s) {
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

function euro($n) {
    return number_format($n, 0, ',', '.');
}

function redirect($query = '') {
    header('Location: ./' . $query, true, 303);
    exit;
}

function flash($text, $type = 'ok') {
    $_SESSION['flash'] = [$text, $type];
}

function page($title, $body, $nav = false) {
    $flash = '';
    if (!empty($_SESSION['flash'])) {
        [$text, $type] = $_SESSION['flash'];
        unset($_SESSION['flash']);
        $flash = '<p class="flash ' . h($type) . '">' . h($text) . '</p>';
    }
    $links = $nav
        ? '<nav><a href="../" target="_blank" rel="noopener">Website ansehen ↗</a><form method="post" action="./?a=logout">' . csrf_field() . '<button class="link">Abmelden</button></form></nav>'
        : '<nav><a href="../">← Zur Website</a></nav>';
    ?><!doctype html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= h($title) ?> – Website Discounter Admin</title>
<link rel="icon" href="../favicon.svg" type="image/svg+xml">
<style>
*{box-sizing:border-box}
[hidden]{display:none!important}
body{margin:0;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI","Helvetica Neue",Arial,sans-serif;color:#000;background:#f2f2f2;line-height:1.5}
header{background:#000;color:#fff}
header .wrap{display:flex;align-items:center;justify-content:space-between;gap:16px;height:64px}
.brand{display:flex;align-items:center;gap:12px;color:#fff;text-decoration:none}
.brand img{width:90px;display:block}
.brand span{border-left:2px solid #fff;padding-left:12px;font-size:.7rem;font-weight:800;letter-spacing:.14em;line-height:1.2;text-transform:uppercase}
nav{display:flex;align-items:center;gap:20px}
nav a,.link{white-space:nowrap;color:#fff;font:inherit;font-weight:600;text-decoration:none;background:none;border:0;padding:0;cursor:pointer}
nav a:hover,.link:hover{text-decoration:underline}
nav form{margin:0}
.wrap{max-width:720px;margin:0 auto;padding:0 20px}
main{padding:48px 0 80px}
h1{font-size:clamp(2rem,6vw,2.8rem);letter-spacing:-.03em;line-height:1.05;margin:0 0 .3em}
h2{font-size:1.25rem;letter-spacing:-.01em;margin:0 0 1em}
.lead{color:#555;margin:0 0 32px}
.card{background:#fff;border:2px solid #000;padding:32px;margin-bottom:28px}
label{display:block;font-weight:700;font-size:.92rem;margin-bottom:16px}
input{display:block;width:100%;font:inherit;padding:13px 14px;border:2px solid #ccc;border-radius:0;margin-top:6px;background:#fff;color:#000}
input:focus{outline:none;border-color:#000}
.hint{font-weight:400;color:#555;font-size:.85rem}
.btn{display:inline-block;font:inherit;font-weight:700;padding:14px 26px;border:2px solid #000;background:#000;color:#fff;cursor:pointer;text-decoration:none}
.btn:hover{background:#fff;color:#000}
.btn.ghost{background:#fff;color:#000}
.btn.ghost:hover{background:#000;color:#fff}
.prices{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:8px}
.prices label{margin:0}
.euro{position:relative}
.euro input{padding-right:36px;font-size:1.5rem;font-weight:800}
.euro:after{content:"€";position:absolute;right:14px;bottom:13px;font-size:1.3rem;font-weight:800}
.pkg{border:2px solid #000;padding:16px 16px 4px;margin:0;min-width:0}
.pkg legend{font-weight:800;font-size:1.1rem;padding:0 6px}
.pkg label{margin-bottom:12px}
.pkg .sub{margin:14px 0 8px;padding-top:12px;border-top:1px solid #ddd;font-size:.75rem;font-weight:700;letter-spacing:.14em;text-transform:uppercase}
.pkg .two{display:block}
.pkg .calc{font-size:.82rem;color:#555;border-top:1px solid #ddd;padding-top:10px;margin:0 0 12px}
.euro.small input{font-size:1.1rem}
.unit{position:relative;display:block}
.unit input{padding-right:46px}
.unit:after{content:attr(data-unit);position:absolute;right:12px;bottom:14px;font-weight:700;color:#555}
.flash{padding:14px 18px;font-weight:700;margin:0 0 24px;border:2px solid #000}
.flash.ok{background:#000;color:#fff}
.flash.err{background:#fff;color:#b42318;border-color:#b42318}
.row{display:flex;gap:12px;flex-wrap:wrap;align-items:center}
.muted{color:#555;font-size:.9rem}
@media(max-width:600px){nav a[target]{display:none}.prices{grid-template-columns:1fr}.card{padding:22px 18px}nav{gap:14px;font-size:.9rem}.brand img{width:72px}}
</style>
</head>
<body>
<header><div class="wrap"><a class="brand" href="./"><img src="../img/logo-weiss.svg" alt="Gerken Medien" width="90" height="33"><span>Website Discounter<br>Admin</span></a><?= $links ?></div></header>
<main><div class="wrap"><?= $flash ?><?= $body ?></div></main>
</body>
</html>
<?php
    exit;
}
