# Website Discounter

Einladende Landingpage mit Preisrechner: Kunden beantworten 3 kurze Fragen, sehen sofort ihren Festpreis und senden die Anfrage direkt ab.

## Pakete

| Paket    | Preis   | Für wen |
|----------|---------|---------|
| Start    | 500 €   | einfache Website, 1–3 Seiten |
| Business | 1.200 € | Unternehmensauftritt, mittlerer Aufwand |
| Premium  | 1.650 € | große Website mit vielen Inhalten & Extras |

Jede Antwort im Rechner gibt Punkte (`value` in `index.html`), daraus wählt `packageForScore()` in `assets/app.js` das Paket:
0–2 Punkte → Start, 3–6 → Business, ab 7 → Premium. Der Kunde kann das Paket im Ergebnis auch noch selbst wechseln.

## Einrichten

- Anfragen gehen über `send.php` (PHP `mail()` auf dem STRATO-Webspace) an `info@grkn.net`. Empfänger und Absender stehen oben in `send.php`.
  Klappt der Versand nicht, bekommt der Kunde einen Link, der sein E-Mail-Programm mit fertiger Anfrage öffnet.
- Mit `versand: "mailto"` in `assets/config.js` wird immer direkt das E-Mail-Programm des Kunden geöffnet.
- Design im Stil von Gerken Medien (Schwarz/Weiß, Systemschriften, keine externen Ressourcen).

## Veröffentlichen

Jeder Push auf `main` lädt die Seite per GitHub Actions (`.github/workflows/deploy.yml`) per SFTP nach `/wd/` auf STRATO.

Lokal ansehen: `php -S localhost:8000` im Projektordner und http://localhost:8000 öffnen (oder `index.html` direkt im Browser – dann ohne Mailversand).
