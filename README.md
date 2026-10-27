# Website Discounter

Landingpage mit Preisrechner für Gerken Medien. Kunden beantworten 3 kurze Fragen, sehen ihren Festpreis und senden die Anfrage direkt ab.

| Paket    | Preis   |
|----------|---------|
| Start    | 500 €   |
| Business | 1.200 € |
| Premium  | 1.650 € |

## Dateien

- `index.html` – komplette Seite, CSS und JavaScript stehen direkt in der Datei (Pakete, Preise und Punkte-Logik oben im Script).
- `send.php` – schickt Anfragen per E-Mail an `info@grkn.net`.
- `impressum.html`, `datenschutz.html` – Rechtstexte.
- `img/` – Logo und Foto.

## Veröffentlichen

Jeder Push auf `main` lädt die Seite per GitHub Actions per SFTP nach `/wd/` auf STRATO.
