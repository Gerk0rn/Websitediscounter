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
- `admin/` – Backend zum Ändern der Preise (Login unter `/wd/admin/`).
- `prices.php` – liefert die aktuellen Preise an die Website.
- `data/` – vom Backend angelegte Daten (Preise, Zugang). Liegt nur auf dem Server, ist per `.gitignore`
  vom Repository ausgeschlossen und wird deshalb von Deploys nie überschrieben.

## Backend

1. `/wd/admin/` aufrufen und „Code per E-Mail senden“ klicken – der Einrichtungscode geht an `info@grkn.net`.
2. Code, Benutzername und Passwort (mind. 10 Zeichen) eintragen. Danach ist die Registrierung geschlossen.
3. Nach dem Login die drei Preise ändern und speichern – die Website zeigt sie sofort an.

Passwort vergessen: per FTP `data/user.php` löschen, dann neu einrichten.

## Veröffentlichen

Jeder Push auf `main` lädt die Seite per GitHub Actions per SFTP nach `/wd/` auf STRATO.
