<?php
// Admin-Backend: einmalige Registrierung (mit Code per E-Mail), Login, Preise und Passwort ändern.

require __DIR__ . '/lib.php';

start_session();

$action = $_GET['a'] ?? '';
$post = $_SERVER['REQUEST_METHOD'] === 'POST';
$user = get_user();

if ($post && !csrf_ok()) {
    flash('Die Sitzung ist abgelaufen. Bitte noch einmal versuchen.', 'err');
    redirect();
}

// ---------- Abmelden ----------
if ($post && $action === 'logout') {
    $_SESSION = [];
    session_regenerate_id(true);
    flash('Du bist abgemeldet.');
    redirect();
}

// ---------- Einrichtung (nur solange es noch kein Konto gibt) ----------
if (!$user) {
    $setup = data_read('setup');

    if ($post && $action === 'code') {
        if ($setup && $setup['sent'] > time() - 60) {
            flash('Bitte warte eine Minute, bevor du einen neuen Code anforderst.', 'err');
            redirect();
        }
        $code = (string) random_int(100000, 999999);
        data_write('setup', ['hash' => hash('sha256', $code), 'expires' => time() + CODE_SECONDS, 'sent' => time(), 'tries' => 0]);
        $ok = send_mail('Dein Einrichtungscode für den Website Discounter',
            "Hallo,\n\njemand möchte das Admin-Konto für den Website Discounter einrichten.\n\n"
            . "Dein Einrichtungscode: $code\n\nDer Code gilt 30 Minuten. Wenn du das nicht warst, ignoriere diese E-Mail.\n");
        flash($ok ? 'Der Code ist unterwegs an ' . ADMIN_MAIL . '.' : 'Die E-Mail konnte nicht verschickt werden. Bitte später erneut versuchen.', $ok ? 'ok' : 'err');
        redirect();
    }

    if ($post && $action === 'setup') {
        $code = trim($_POST['code'] ?? '');
        $name = trim($_POST['username'] ?? '');
        $pass = $_POST['password'] ?? '';
        $error = null;

        if (!$setup || $setup['expires'] < time() || $setup['tries'] >= 5) {
            $error = 'Der Code ist abgelaufen. Bitte fordere einen neuen an.';
        } elseif (!hash_equals($setup['hash'], hash('sha256', $code))) {
            $setup['tries']++;
            data_write('setup', $setup);
            $error = 'Der Code stimmt nicht.';
        } elseif (!preg_match('/^[A-Za-z0-9._@-]{3,40}$/', $name)) {
            $error = 'Der Benutzername muss 3–40 Zeichen lang sein (Buchstaben, Zahlen, . _ @ -).';
        } else {
            $error = password_problem($pass, $_POST['password2'] ?? '');
        }

        if ($error) {
            flash($error, 'err');
            redirect();
        }
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        data_write('user', ['username' => $name, 'password' => $hash, 'created' => date('c')]);
        data_delete('setup');
        session_regenerate_id(true);
        $_SESSION['user'] = $name;
        $_SESSION['pw'] = substr($hash, -16);
        flash('Dein Konto ist eingerichtet. Willkommen!');
        redirect();
    }

    $codeSent = $setup && $setup['expires'] > time();
    page('Einrichten', '
<h1>Backend einrichten</h1>
<p class="lead">Lege einmalig deinen Zugang fest. Danach ist die Registrierung geschlossen.</p>
<div class="card">
  <h2>1. Code anfordern</h2>
  <p class="muted">Zur Sicherheit bekommst du einen Einrichtungscode an <strong>' . h(ADMIN_MAIL) . '</strong>. So kann niemand anderes das Konto anlegen.</p>
  <form method="post" action="./?a=code">' . csrf_field() . '<button class="btn' . ($codeSent ? ' ghost' : '') . '">' . ($codeSent ? 'Neuen Code senden' : 'Code per E-Mail senden') . '</button></form>
</div>
<div class="card">
  <h2>2. Zugang festlegen</h2>
  <form method="post" action="./?a=setup">' . csrf_field() . '
    <label>Einrichtungscode<input name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" required></label>
    <label>Benutzername<input name="username" autocomplete="username" maxlength="40" required></label>
    <label>Passwort <span class="hint">(mindestens 10 Zeichen)</span><input type="password" name="password" autocomplete="new-password" required></label>
    <label>Passwort wiederholen<input type="password" name="password2" autocomplete="new-password" required></label>
    <button class="btn">Konto anlegen</button>
  </form>
</div>');
}

// ---------- Anmelden ----------
if (!logged_in()) {
    if ($post && $action === 'login') {
        if (login_locked()) {
            flash('Zu viele Fehlversuche. Bitte warte 15 Minuten.', 'err');
            redirect();
        }
        $name = trim($_POST['username'] ?? '');
        $pass = $_POST['password'] ?? '';
        if (hash_equals($user['username'], $name) && password_verify($pass, $user['password'])) {
            if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
                $user['password'] = password_hash($pass, PASSWORD_DEFAULT);
                data_write('user', $user);
            }
            login_reset_fails();
            session_regenerate_id(true);
            $_SESSION['user'] = $user['username'];
            $_SESSION['pw'] = substr($user['password'], -16);
            redirect();
        }
        login_failed();
        flash('Benutzername oder Passwort ist falsch.', 'err');
        redirect();
    }

    page('Anmelden', '
<h1>Anmelden</h1>
<p class="lead">Hier verwaltest du die Preise des Website Discounters.</p>
<div class="card">
  <form method="post" action="./?a=login">' . csrf_field() . '
    <label>Benutzername<input name="username" autocomplete="username" required autofocus></label>
    <label>Passwort<input type="password" name="password" autocomplete="current-password" required></label>
    <button class="btn">Anmelden</button>
  </form>
</div>
<p class="muted">Passwort vergessen? Lösche per FTP die Datei <code>data/user.php</code> im Website-Ordner – danach kannst du den Zugang hier neu einrichten.</p>');
}

// ---------- Angemeldet: Preise speichern ----------
if ($post && $action === 'prices') {
    $new = ['abo' => []];
    foreach (PACKAGES as $key => $label) {
        $raw = str_replace(['.', ' ', '€'], '', trim($_POST[$key] ?? ''));
        if (!preg_match('/^\d{1,6}$/', $raw) || (int) $raw < 1) {
            flash("Bitte für $label einen Kaufpreis in ganzen Euro eingeben (z. B. 1200).", 'err');
            redirect();
        }
        $new[$key] = (int) $raw;

        $monthly = parse_cents($_POST["abo_{$key}_monthly"] ?? '');
        $setup = parse_cents($_POST["abo_{$key}_setup"] ?? '');
        $term = trim($_POST["abo_{$key}_term"] ?? '');
        $discount = trim($_POST["abo_{$key}_discount"] ?? '');
        if ($monthly === null || $monthly < 1 || $setup === null) {
            flash("Bitte für $label Abo-Preis und Einrichtung als Betrag eingeben (z. B. 39,99).", 'err');
            redirect();
        }
        if (!preg_match('/^\d{1,2}$/', $term) || (int) $term < 1 || (int) $term > 60) {
            flash("Bitte für $label eine Mindestlaufzeit von 1 bis 60 Monaten eingeben.", 'err');
            redirect();
        }
        if (!preg_match('/^\d{1,2}$/', $discount) || (int) $discount > 90) {
            flash("Bitte für $label einen Rabatt von 0 bis 90 % eingeben.", 'err');
            redirect();
        }
        $new['abo'][$key] = ['monthly' => $monthly, 'setup' => $setup, 'term' => (int) $term, 'discount' => (int) $discount];
    }
    if (data_write('prices', $new + ['updated' => date('c')])) {
        flash('Gespeichert. Die neuen Preise sind sofort auf der Website zu sehen.');
    } else {
        flash('Speichern fehlgeschlagen – der Ordner data/ ist nicht beschreibbar.', 'err');
    }
    redirect();
}

// ---------- Angemeldet: Meta-Pixel-ID speichern ----------
if ($post && $action === 'tracking') {
    $id = preg_replace('/\s+/', '', $_POST['pixel'] ?? '');
    if ($id !== '' && !preg_match('/^\d{5,20}$/', $id)) {
        flash('Die Pixel-ID besteht nur aus Ziffern (z. B. 123456789012345).', 'err');
        redirect();
    }
    $settings = data_read('settings') ?? [];
    $settings['pixel'] = $id;
    if (data_write('settings', $settings)) {
        flash($id === '' ? 'Meta-Pixel deaktiviert.' : 'Pixel-ID gespeichert. Das Pixel lädt bei Besuchern, die zustimmen.');
    } else {
        flash('Speichern fehlgeschlagen – der Ordner data/ ist nicht beschreibbar.', 'err');
    }
    redirect();
}

// ---------- Angemeldet: Passwort ändern ----------
if ($post && $action === 'password') {
    if (!password_verify($_POST['current'] ?? '', $user['password'])) {
        flash('Das aktuelle Passwort ist falsch.', 'err');
        redirect();
    }
    $error = password_problem($_POST['password'] ?? '', $_POST['password2'] ?? '');
    if ($error) {
        flash($error, 'err');
        redirect();
    }
    $user['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
    data_write('user', $user);
    session_regenerate_id(true);
    $_SESSION['pw'] = substr($user['password'], -16);
    flash('Dein Passwort wurde geändert.');
    redirect();
}

// ---------- Angemeldet: Übersicht ----------
$prices = get_prices();
$abo = get_abo();
$saved = data_read('prices');
$fields = '';
foreach (PACKAGES as $key => $label) {
    $a = $abo[$key];
    $yearly = abo_yearly($a);
    $fields .= '<fieldset class="pkg"><legend>' . h($label) . '</legend>'
        . '<label>Kaufpreis (einmalig)<span class="euro"><input name="' . $key . '" value="' . (int) $prices[$key] . '" inputmode="numeric" pattern="[0-9.]*" required></span></label>'
        . '<p class="sub">Abo</p>'
        . '<label>Monatlich<span class="euro small"><input name="abo_' . $key . '_monthly" value="' . money($a['monthly']) . '" inputmode="decimal" pattern="[0-9]+([,.][0-9]{1,2})?" required></span></label>'
        . '<label>Einrichtung (einmalig)<span class="euro small"><input name="abo_' . $key . '_setup" value="' . money($a['setup']) . '" inputmode="decimal" pattern="[0-9]+([,.][0-9]{1,2})?" required></span></label>'
        . '<div class="two"><label>Laufzeit<span class="unit" data-unit="Monate"><input name="abo_' . $key . '_term" value="' . $a['term'] . '" inputmode="numeric" pattern="[0-9]{1,2}" required></span></label>'
        . '<label>Rabatt bei jährlicher Zahlung<span class="unit" data-unit="%"><input name="abo_' . $key . '_discount" value="' . $a['discount'] . '" inputmode="numeric" pattern="[0-9]{1,2}" required></span></label></div>'
        . '<p class="calc">Jährlich: <strong>' . money($yearly) . ' €</strong> (ohne Einrichtung)<br>'
        . 'Monatlich gesamt ' . $a['term'] . ' Mon.: ' . money($a['monthly'] * $a['term'] + $a['setup']) . ' €</p>'
        . '</fieldset>';
}
$stand = $saved && isset($saved['updated'])
    ? 'Zuletzt geändert am ' . date('d.m.Y \u\m H:i', strtotime($saved['updated'])) . ' Uhr.'
    : 'Noch keine eigenen Preise gespeichert – es gelten die Standardpreise.';

page('Preise', '
<h1>Hallo ' . h($user['username']) . '!</h1>
<p class="lead">Ändere hier Kaufpreise und Abo-Konditionen deiner drei Pakete. Sie werden überall auf der Website übernommen.</p>
<div class="card">
  <h2>Paketpreise</h2>
  <form method="post" action="./?a=prices">' . csrf_field() . '
    <div class="prices">' . $fields . '</div>
    <p class="muted">' . h($stand) . '</p>
    <div class="row"><button class="btn">Preise speichern</button><a class="btn ghost" href="../#pakete" target="_blank" rel="noopener">Website ansehen ↗</a></div>
  </form>
</div>
<div class="card">
  <h2>Meta-Pixel (Facebook)</h2>
  <form method="post" action="./?a=tracking">' . csrf_field() . '
    <label>Pixel-ID <span class="hint">(leer lassen = Pixel aus)</span><input name="pixel" value="' . h(get_pixel_id()) . '" inputmode="numeric" pattern="[0-9 ]*" maxlength="25" placeholder="z. B. 123456789012345"></label>
    <p class="muted">Du findest die ID im Meta Events Manager unter „Datenquellen“. Das Pixel lädt nur bei Besuchern, die im Cookie-Banner zustimmen. Gemessen werden Seitenaufrufe und abgeschickte Anfragen (ohne Formularinhalte).</p>
    <button class="btn ghost">Pixel-ID speichern</button>
  </form>
</div>
<div class="card">
  <h2>Passwort ändern</h2>
  <form method="post" action="./?a=password">' . csrf_field() . '
    <input type="text" name="username" value="' . h($user['username']) . '" autocomplete="username" hidden>
    <label>Aktuelles Passwort<input type="password" name="current" autocomplete="current-password" required></label>
    <label>Neues Passwort <span class="hint">(mindestens 10 Zeichen)</span><input type="password" name="password" autocomplete="new-password" required></label>
    <label>Neues Passwort wiederholen<input type="password" name="password2" autocomplete="new-password" required></label>
    <button class="btn ghost">Passwort ändern</button>
  </form>
</div>', true);
