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

1. In `assets/config.js` deine E-Mail-Adresse bei `email` eintragen.
2. `versand: "formsubmit"` schickt Anfragen direkt über [formsubmit.co](https://formsubmit.co) an dich – beim ersten Absenden kommt eine Bestätigungs-Mail, die du einmal bestätigen musst.
   Mit `versand: "mailto"` öffnet sich stattdessen das E-Mail-Programm des Kunden mit fertig ausgefüllter Anfrage.
3. `impressum.html` und `datenschutz.html` mit deinen Angaben füllen.
4. Alle Dateien auf deinen Webspace hochladen (oder GitHub Pages / Netlify nutzen) – kein Server oder Build nötig.

Lokal ansehen: `index.html` einfach im Browser öffnen.
