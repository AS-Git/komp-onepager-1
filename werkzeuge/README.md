# Werkzeuge: Vorschau, Tests, Upload-Paket

Läuft unter Windows, macOS und Linux. Alle Befehle im Ordner `werkzeuge` ausführen.

## Einmalig einrichten

Voraussetzungen: Git, PHP 8.x, Node.js (LTS) und Composer.

```
cd ..                       # Projektordner, z. B. C:\Temp\Komp\version-5
composer install --no-dev   # PHPMailer nach vendor/
cd werkzeuge
npm install
npx playwright install chromium
```

`komp-einrichten.bat` legt unter Windows die Arbeitskopien `C:\Temp\Komp\version-3`, `-4` und `-5` an.

## Befehle

| Befehl | Zweck |
|---|---|
| `npm run vorschau` | Seite lokal unter http://127.0.0.1:8080/ (Strg+C beendet) |
| `npm test` | Haupttest vor jedem Commit: WCAG 2.2 AA (axe-core) auf allen Seiten, Desktop 1280 px und Handy 390 px, kein seitliches Scrollen, keine Skriptfehler, Menü, Sprungmarken, Formularhinweis, Karte erst nach Klick, Formular vor Einsatzgebiet (mobil), Logoleiste im ersten Bildschirm, „Bewegung reduzieren“ und ohne JavaScript alles sichtbar, JSON-LD gültig, `senden.php` ohne Syntaxfehler |
| `npm run test:formular` | Formular und Spamschutz mit eingebautem Test-Mailserver: echte Meldung (Büro-Mail und Bestätigung), Fangfeld, Mindestzeit, Link-Spam, fremde Website, eingeschleuste Empfänger, Limit pro Absender. Es geht keine echte Mail raus; eine vorhandene `config/config.php` wird vorher gesichert und danach unverändert wiederhergestellt |
| `npm run paket` | Upload-ZIP aus dem committeten Stand nach `werkzeuge/ausgabe/` (mit `vendor/`, ohne `config/config.php`, Dateirechte 644) |

Schlägt ein Test fehl (Ausgabe mit ✘, Rückgabewert 1), nicht committen oder veröffentlichen.
