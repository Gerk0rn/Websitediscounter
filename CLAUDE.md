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
| `prices.php` | Liefert Preise, Abo-Konditionen und Pixel-ID als JSON an die Website. |
| `admin/` | Backend unter `/wd/admin/`: einmalige Registrierung per Mail-Code, Login, Preise/Abo/Pixel-ID/Passwort ändern. |
| `data/` | Vom Backend auf dem Server angelegt (Preise, Zugang, Einstellungen) – per `.gitignore` **nie im Repo**, damit Deploys nichts überschreiben. Dateien beginnen mit `<?php exit; ?>`, plus `.htaccess`-Sperre. |
| `impressum.html`, `datenschutz.html` | Rechtstexte, eigenes Inline-CSS. |
| `img/` | Logo (schwarz/weiß), Porträt, Referenz-Screenshots (800 px breit, JPEG). |

Neue externe Dateien mit Versions-Query einbinden (`consent.js?v=1`) und bei Änderung hochzählen.

## Design

Gerken-Medien-Stil: **nur Schwarz, Weiß, Grau** (`#000`, `#fff`, `#f2f2f2`, Text-Grau `#555`),
System-Grotesk, keine externen Schriften/Ressourcen, keine Rundungen, 2px-Linien, eckige Buttons mit
invertierendem Hover. Hero, Designbeispiele-Sektion, Kontakt und Footer schwarz. Motion über `data-r`
(Reveal), `data-r="clip"` (Überschriften), `data-stagger`, `data-to` (Hochzählen); immer
`prefers-reduced-motion` und No-JS berücksichtigen. Mobil (390 px) ohne horizontales Scrollen testen.

## Preise und Abo

- Einmalkauf (Standard): Start 500 €, Business 1.200 €, Premium 1.650 € – im Admin änderbar.
- Abo je Paket (im Admin änderbar, intern in **Cent**): monatlich 39,99 / 89,99 / 109,99 €,
  Einrichtung 99,99 / 129,99 / 159,99 €, Mindestlaufzeit 12 Monate, jährlich −20 % ohne Einrichtung.
  Jahrespreis = 12 × Monatsbeitrag × (1 − Rabatt).
- Abo-Bedingungen (fest im Text): Pflege + bis zu 2 kleinere Änderungen/Monat inklusive, größere nach
  Aufwand, Hosting nicht enthalten, nach 12 Monaten monatliche Verlängerung, Kündigungsfrist 1 Monat,
  nach Ende des Abos keine Änderungen mehr.
- Preishinweis: **„Alle Preise sind Endpreise – es kommt keine Mehrwertsteuer hinzu.“**
  Nicht „netto“, nicht „inkl. MwSt.“, kein „§ 19 UStG“ auf der Website (Kunde will das nicht).
- Rechner: Punkte aus 3 Fragen → bis 2 Start, bis 6 Business, ab 7 Premium.

## Referenzen & Beispiele

Nie erfundene Projekte als echte Referenzen ausgeben (irreführende Werbung).
Aktuell: **Thai Yoga Massage** (Start, Referenz, Telefonnummer im Screenshot verpixelt, nicht verlinkt),
**Sportsfreund** (Business, Referenz, Personal Trainer Pascal Stempfle – Daten dürfen sichtbar sein).

## Recht & Datenschutz

- Firmendaten: Gerken Medien – Kreativagentur für Medien, Inhaber Dennis Gerken,
  August-Wegmann-Str. 2, 49413 Dinklage, Telefon +49 152 219 50840, info@grkn.net, www.grkn.net,
  W-IdNr. DE430947120-00001. Hosting: STRATO.
- Datenschutz enthält: Hosting/Logfiles, Anfrageformular, Cookies/Einwilligung (TDDDG), Meta-Pixel
  (Art. 26 DSGVO, Drittland), E-Mail/Telefon, **WhatsApp Business** (ohne Adressbuchzugriff – nur so
  lassen, solange das stimmt).
- Bei neuen Tracking-/Drittanbieter-Diensten immer Datenschutzerklärung und Cookie-Banner anpassen.

## Testen

Lokal: `php -S 127.0.0.1:8000` im Projektordner; Mailversand mit
`php -d sendmail_path=<skript, das stdin in eine Datei schreibt>` abfangen. Browser-Tests mit
Playwright (Desktop 1280 px, Handy 390 px). Die Umgebung blockiert gerken-medien.de und Bildportale –
Screenshots/Dateien vom Nutzer schicken lassen.
