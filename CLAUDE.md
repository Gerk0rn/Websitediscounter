# Website Discounter – Projektnotizen für Claude

Landingpage mit Preisrechner für **Gerken Medien** (Inhaber Dennis Gerken, Kleinunternehmer).
Zielgruppe: Unternehmen, Selbstständige und Vereine. Sprache der Website: Deutsch, **du-Form**
(Datenschutz/Impressum in Sie-Form).

## Veröffentlichen

- Jeder Push auf `main` lädt das Repo per GitHub Actions (`.github/workflows/deploy.yml`, SFTP) nach
  **`/wd/`** auf STRATO. Der Upload überschreibt nur, er löscht nie Dateien auf dem Server.
- Entwickelt wird auf dem Arbeits-Branch; auf `main` erst, wenn der Nutzer „online stellen“ sagt.
  Will er „erst einen Entwurf sehen“: nur auf den Arbeits-Branch pushen und Screenshots zeigen.
- Nach jedem Push auf `main` den Deploy-Run prüfen (GitHub Actions) und das Ergebnis melden.
- Vom Upload ausgeschlossen: `.git/`, `.github/`, `CLAUDE.md`.

## Aufbau

| Datei | Inhalt |
|---|---|
| `index.html` | Komplette Startseite – **CSS und JS inline** (verhindert Cache-Mischmasch alter/neuer Dateien, das hat die Seite einmal zerschossen). |
| `consent.js` | Cookie-Banner + Meta-Pixel (lädt nur nach Einwilligung, meldet `PageView` und `Lead`). |
| `whatsapp.js` | Schwebender WhatsApp-Button (`wa.me/4915221950840`), reiner Link. |
| `send.php` | Anfrageformular → Mail an `info@grkn.net` (PHP `mail()`, Honeypot `website`). |
| `prices.php` | Liefert Preise und Pixel-ID als JSON an die Website. |
| `admin/` | Backend unter `/wd/admin/`: einmalige Registrierung per Mail-Code, Login, Preise/Pixel-ID/Passwort ändern. |
| `data/` | Vom Backend auf dem Server angelegt (Preise, Zugang, Einstellungen) – per `.gitignore` **nie im Repo**, damit Deploys nichts überschreiben. Dateien beginnen mit `<?php exit; ?>`, plus `.htaccess`-Sperre. |
| `impressum.html`, `datenschutz.html` | Rechtstexte, eigenes Inline-CSS. |
| `img/` | Logo (schwarz/weiß), Porträt, Referenz-Screenshots (800 px breit, JPEG), `hero/f01–f37.jpg` (Laptop klappt auf: Einzelbilder aus einem Picsart-Video, Graustufen 800×680, Ränder reines Schwarz). |

Neue externe Dateien mit Versions-Query einbinden (`consent.js?v=1`) und bei Änderung hochzählen.

## Design

Gerken-Medien-Stil: **nur Schwarz, Weiß, Grau** (`#000`, `#fff`, `#f2f2f2`, Text-Grau `#555`),
System-Grotesk, keine externen Schriften/Ressourcen, keine Rundungen, 2px-Linien, eckige Buttons mit
invertierendem Hover. Hero, Designbeispiele-Sektion, Kontakt und Footer schwarz. Startbereich: Laptop klappt beim Scrollen auf (Canvas zeichnet `img/hero/f01–f37`; ab 901 px bleibt der Hero dafür stehen – `.hero-track`/`.hero-space` 70vh; mobil öffnet er beim Hereinscrollen; ohne JS/bei reduzierter Bewegung steht das offene Bild `f37`). Motion über `data-r`
(Reveal), `data-r="clip"` (Überschriften), `data-stagger`, `data-to` (Hochzählen); immer
`prefers-reduced-motion` und No-JS berücksichtigen. Mobil (390 px) ohne horizontales Scrollen testen.

## Preise

- Nur Einmalkauf zum Festpreis: Start 499 €, Business 999 €, Premium 1.499 € – im Admin änderbar.
- **Kein Abo-Modell mehr** (monatlich/jährlich wurde auf Wunsch des Kunden entfernt) – nicht wieder einbauen.
- Gespeicherte Admin-Preise gelten nur mit `"v": PRICES_VERSION` (`admin/lib.php`); ältere Dateien in
  `data/prices.php` werden ignoriert, dann gelten die Standardpreise. Bei neuen Standardpreisen, die
  sofort live gelten sollen, `PRICES_VERSION` hochzählen.
- Preishinweis: **„Alle Preise sind Endpreise – es kommt keine Mehrwertsteuer hinzu.“**
  Nicht „netto“, nicht „inkl. MwSt.“, kein „§ 19 UStG“ auf der Website (Kunde will das nicht).
- Rechner: Punkte aus 3 Fragen → bis 2 Start, bis 6 Business, ab 7 Premium.

## Referenzen & Beispiele

Nie erfundene Projekte als echte Referenzen ausgeben (irreführende Werbung).
Aktuell nur: **Sportsfreund** (Business, Referenz, Personal Trainer Pascal Stempfle – Daten dürfen sichtbar sein).

## Recht & Datenschutz

- Firmendaten: Gerken Medien – Kreativagentur für Medien, Inhaber Dennis Gerken,
  August-Wegmann-Str. 2, 49413 Dinklage, Telefon +49 4443 6362962 (WhatsApp weiterhin mobil: +49 152 219 50840), info@grkn.net, www.grkn.net,
  W-IdNr. DE430947120-00001. Hosting: STRATO.
- Datenschutz enthält: Hosting/Logfiles, Anfrageformular, Cookies/Einwilligung (TDDDG), Meta-Pixel
  (Art. 26 DSGVO, Drittland), E-Mail/Telefon, **WhatsApp Business** (ohne Adressbuchzugriff – nur so
  lassen, solange das stimmt).
- Bei neuen Tracking-/Drittanbieter-Diensten immer Datenschutzerklärung und Cookie-Banner anpassen.

## Sicherheit

- `.htaccess` (Wurzel von `/wd/`): HTTPS-Zwang, Sicherheits-Header (HSTS, CSP, X-Frame-Options,
  nosniff, Referrer-, Permissions-Policy), keine Verzeichnislisten, versteckte Dateien und `*.md`
  gesperrt. CSP erlaubt nur eigene Dateien + `connect.facebook.net`/`www.facebook.com` (Meta-Pixel).
  **Neue externe Dienste müssen in die CSP**, sonst blockiert der Browser sie.
- `send.php` Spamschutz: Herkunft (Origin/Referer = eigener Host), JSON-only, ≤ 20 KB, Honeypot,
  Mindest-Ausfüllzeit 3 s (`dauer` aus index.html), max. 3 Anfragen/10 min und 10/Tag pro
  IP-Hash (`data/spam-limit.php`, 24 h), Link-/BBCode-Filter, Doppel-Sperre 10 min.
- Admin: CSRF-Token, Login-Sperre nach 5 Fehlversuchen, Passwort-Hash, Session-Cookie HttpOnly/SameSite.
- Testen: Apache lokal (`apt-get install apache2 libapache2-mod-php`, `a2enmod headers rewrite`,
  AllowOverride All, Kopie unter `/var/www`), HTTPS per Header `X-Forwarded-Proto: https` simulieren.

## Testen

Lokal: `php -S 127.0.0.1:8000` im Projektordner; Mailversand mit
`php -d sendmail_path=<skript, das stdin in eine Datei schreibt>` abfangen. Browser-Tests mit
Playwright (Desktop 1280 px, Handy 390 px). Die Umgebung blockiert gerken-medien.de und Bildportale –
Screenshots/Dateien vom Nutzer schicken lassen.
