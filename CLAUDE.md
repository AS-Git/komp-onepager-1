# Komp OnePager – Projektkontext für Claude

OnePager für das **Ingenieur-Büro Komp**, KFZ-Sachverständige in Langen (Hessen). Umgesetzt von der Agentur Anders & Seim nach dem Design „Komp OnePager“ (Claude-Design-Artifact https://claude.ai/code/artifact/b4386f5d-192d-49c1-a5d3-b513cb65ea29) mit dem Designsystem „KFZ-Ingenieur-Büro Komp“. Zielgruppen, Tonalität und Gestaltungsprinzipien: `PRODUCT.md`. Aufbau und Einrichtung: `README.md`.

Sprache mit dem Auftraggeber: Deutsch.

## Arbeitsweise

- Lokale Arbeitskopie beim Auftraggeber: `C:\Temp\Komp` (Repository direkt in diesem Ordner) (Branch `design-v4`). Änderungen dort vornehmen.
- Jede abgeschlossene Änderung ohne Rückfrage auf `design-v4` committen und pushen (Commit-Nachricht auf Deutsch). Vorher die Tests unter „Testen“ laufen lassen; schlägt etwas fehl, nicht pushen, sondern melden.

## Technik

- Statisches HTML/CSS in `public/` (Webroot), ein PHP-Skript `public/senden.php` für das Schadensformular (PHPMailer per SMTP, Eingangsbestätigung an den Absender).
- `config/config.php` (nicht im Repo, Vorlage `config/config.example.php`) und `vendor/` liegen außerhalb des Webroots; `senden.php` findet sie notfalls auch daneben.
- Server: Apache, PHP 8.5, SMTP vorhanden. Code bleibt kompatibel ab PHP 7.4.
- Klassen: `k-*` = Designsystem (`public/css/komp.css`, nicht seitenspezifisch ändern), `op-*` = Seite (`public/css/onepager.css`).
- Schriften (Barlow, Barlow Condensed, Material Symbols, Icon-Subset) liegen lokal in `public/fonts/`; neue Icons müssen ins Subset (`public/css/fonts.css`, woff2 neu erzeugen).

## Versionen

| Branch | Inhalt |
|---|---|
| `design-v4` | **Arbeitsversion**: gleiche Inhalte wie `design-v3`, Gestaltung „Ingenieur-Zeichnung“ (Haarlinien statt Karten, präzise Ecken, Maßlinien, technische Fahrzeugzeichnung). Gestaltungsebene am Ende von `public/css/onepager.css` |
| `design-v3` | **Eingefroren, 1:1 erhalten**: diesen Stand hat der Kunde gesehen. Nicht mehr ändern. Hero in Komp-Dunkelblau mit Konstruktionsraster |
| `main` | Version 1: Umsetzung des ursprünglichen Designs, nur noch Archiv |
| `design-audit` | Version 2: Barrierefreiheit (WCAG 2.2 AA), Leistungen als nummerierte Liste, nur noch Archiv |

**Abnahme:** `design-v3` (Stand `a225b10`) und `design-v4` (Stand `51c2b19`, danach nur `CLAUDE.md` geändert) liegen beim Kunden zur Abnahme. Bis zur Rückmeldung an der Website nichts ändern, nur auf ausdrücklichen Wunsch des Auftraggebers.

Tag `v1` = Stand von Version 1 vor den inhaltlichen Korrekturen (liegt evtl. noch nicht auf GitHub). Änderungen nur noch in `design-v4`; `design-v3`, `main` und `design-audit` bleiben unverändert erhalten.

## Inhaltliche Festlegungen (vom Auftraggeber)

- **Öffentlich bestellt und vereidigt ist nur Dipl.-Ing. Werner Thiel** (IHK Offenbach am Main, Kraftfahrzeugschäden und -bewertung), nicht Dietmar Komp. Formulierungen wie „IHK-Sachverständiger“ (Hero) oder „bestellter Sachverständiger im Team“ (Siegel), nie so, dass Herr Komp oder das ganze Büro bestellt wirkt. Angaben zur Bestellung stehen im Impressum.
- **Dieter Komp ist verstorben.** Nie als aktiv tätig nennen. Heute leitet **Dipl.-Ing. Dietmar Komp** das Büro. In der Firmengeschichte bleibt „1977: Dieter Komp wird Partner … prägt das Büro über Jahrzehnte“.
- **„Seit den 1950er Jahren“ nicht im Hero und nicht in der Meta-Description** (wirkt altbacken). In der Firmengeschichte ist es in Ordnung.
- Hero-Kicker: „Unabhängige KFZ-Sachverständige im Rhein-Main-Gebiet“.
- Einsatzgebiet: Langen, Egelsbach, Dreieich, Offenthal, Neu-Isenburg, Rödermark, Mörfelden-Walldorf, Darmstadt, Frankfurt am Main (Chips, Footer, JSON-LD `areaServed`).
- Logos in der Vertrauensleiste: BVSK, ADAC Vertragssachverständiger, GTÜ Vertragspartner (`public/img/*.webp`); IHK nur als Textsiegel.
- Inhalte (Texte, Reihenfolge der Abschnitte) in `design-v4` identisch zu `design-v3` halten, solange der Auftraggeber nichts anderes sagt.
- Keine Stockfotos von Unfallautos; keine Optik von Unfall-Portalen, Behörden, Baukasten-Templates oder Autohaus-Werbung (siehe `PRODUCT.md`).

## Offene Punkte

- Platzhalter im Code mit `[…]` markiert: `[DOMAIN]` (Canonical, JSON-LD), `[FOTO]` (Porträt Dietmar Komp), `[BEWERTUNGEN]` und `[GOOGLE-PROFIL]` (Bewertungs-Sektion ist per `hidden` ausgeblendet, bis echte Google-Bewertungen vorliegen), Impressum und Datenschutz sind aus der bisherigen Website übernommen (Stand Datenschutz Dez. 2024), die Datenschutzerklärung ist an diese Seite angepasst (keine Cookies, Schadensformular, Spamschutz, lokale Schriften, Google Maps erst nach Klick). Hoster bleibt Mittwald (bestätigt). Adresse der IHK Offenbach im Impressum bestätigt. Herr Thiel wird bewusst nur im Text genannt, nicht im Bereich „Über uns“.
- Echte Fotos (Büro, Besichtigung, Dietmar Komp) wären der nächste große Gestaltungsschritt.
- Formular auf dem Testserver: Die vorgeschaltete Firewall hat eine Ausnahme für `/senden.php` (erledigt). Bei erneutem HTTP 403 zuerst diese Ausnahme prüfen.
- Servertest erledigt: Formularversand (Meldung und Eingangsbestätigung), HTTPS-Umleitung, `.htaccess` und Karte funktionieren auf dem Testserver. Bei Formularproblemen `'debug' => true` in `config/config.php` setzen, `[Diagnose: …]` auswerten, danach wieder `false`.

## Spam- und Sicherheitsschutz (nicht abschwächen)

Honeypot-Feld `website`, Mindest-Ausfüllzeit 3 s (`t`), max. 5 Meldungen pro IP und Stunde, Link-Spam wird still verworfen, Formulare fremder Origins abgewiesen, Eingangsbestätigung max. 1 pro Empfänger und Stunde und 20 pro Stunde insgesamt, Bestätigung enthält bewusst keine Formularinhalte. Keine Cookies, kein Tracking, keine externen Schriften, Google Maps erst nach Klick.

## Testen

`php -S 127.0.0.1:8080 -t public`; für Mailtests einen lokalen SMTP-Sink in `config.php` eintragen (`'secure' => 'none'`). Barrierefreiheit wurde mit axe-core (WCAG 2.2 AA) geprüft: auf Desktop 1280 px und Mobil 390 px ohne Verstöße, ohne horizontales Scrollen.
