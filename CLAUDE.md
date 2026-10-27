# Komp OnePager – Projektkontext für Claude

OnePager für das **Ingenieur-Büro Komp**, KFZ-Sachverständige in Langen (Hessen). Umgesetzt von der Agentur Anders & Seim nach dem Design „Komp OnePager“ (Claude-Design-Artifact https://claude.ai/code/artifact/b4386f5d-192d-49c1-a5d3-b513cb65ea29) mit dem Designsystem „KFZ-Ingenieur-Büro Komp“. Zielgruppen, Tonalität und Gestaltungsprinzipien: `PRODUCT.md`. Aufbau und Einrichtung: `README.md`.

Sprache mit dem Auftraggeber: Deutsch.

## Technik

- Statisches HTML/CSS in `public/` (Webroot), ein PHP-Skript `public/senden.php` für das Schadensformular (PHPMailer per SMTP, Eingangsbestätigung an den Absender).
- `config/config.php` (nicht im Repo, Vorlage `config/config.example.php`) und `vendor/` liegen außerhalb des Webroots; `senden.php` findet sie notfalls auch daneben.
- Server: Apache, PHP 8.5, SMTP vorhanden. Code bleibt kompatibel ab PHP 7.4.
- Klassen: `k-*` = Designsystem (`public/css/komp.css`, nicht seitenspezifisch ändern), `op-*` = Seite (`public/css/onepager.css`).
- Schriften (Barlow, Barlow Condensed, Material Symbols, Icon-Subset) liegen lokal in `public/fonts/`; neue Icons müssen ins Subset (`public/css/fonts.css`, woff2 neu erzeugen).

## Versionen (alle drei parallel behalten, nichts verwerfen)

| Branch | Inhalt |
|---|---|
| `main` | Version 1: Umsetzung des ursprünglichen Designs |
| `design-audit` | Version 2: Barrierefreiheit (WCAG 2.2 AA), Leistungen als nummerierte Liste, weniger Kartenraster |
| `design-v3` | Version 3, aktuell bevorzugt: Hero in Komp-Dunkelblau mit Konstruktionsraster, große Überschrift und Telefonnummer |

Tag `v1` = Stand von Version 1 vor den inhaltlichen Korrekturen (liegt evtl. noch nicht auf GitHub). Sachliche Korrekturen und Sicherheits-Fixes immer in **alle drei** Branches übernehmen.

## Inhaltliche Festlegungen (vom Auftraggeber)

- **Dieter Komp ist verstorben.** Nie als aktiv tätig nennen. Heute leitet **Dipl.-Ing. Dietmar Komp** das Büro. In der Firmengeschichte bleibt „1977: Dieter Komp wird Partner … prägt das Büro über Jahrzehnte“.
- **„Seit den 1950er Jahren“ nicht im Hero** (wirkt altbacken). In der Firmengeschichte ist es in Ordnung.
- Hero-Kicker: „Unabhängige KFZ-Sachverständige im Rhein-Main-Gebiet“.
- Logos in der Vertrauensleiste: BVSK, ADAC Vertragssachverständiger, GTÜ Vertragspartner (`public/img/*.webp`); IHK nur als Textsiegel.
- Keine Stockfotos von Unfallautos; keine Optik von Unfall-Portalen, Behörden, Baukasten-Templates oder Autohaus-Werbung (siehe `PRODUCT.md`).

## Offene Punkte

- Meta-Description enthält noch „seit den 1950er Jahren“. Ob ersetzen: Entscheidung des Auftraggebers offen. Vorschlag: „Unabhängige KFZ-Gutachter in Langen (Hessen): Unfallgutachten, Wertermittlung, Leasingrückgabe, HU. Persönlich in zweiter Generation. Tel. 06103 / 22011“.
- Platzhalter im Code mit `[…]` markiert: `[DOMAIN]` (Canonical, JSON-LD), `[FOTO]` (Porträt Dietmar Komp), `[BEWERTUNGEN]` und `[GOOGLE-PROFIL]` (Bewertungs-Sektion ist per `hidden` ausgeblendet, bis echte Google-Bewertungen vorliegen), `[IMPRESSUM]`, `[DATENSCHUTZ]` (Rechtstexte fehlen noch; ohne sie nicht live schalten).
- Echte Fotos (Büro, Besichtigung, Dietmar Komp) wären der nächste große Gestaltungsschritt.
- Formular auf dem Live-Server: Beim ersten Test kam „Die Meldung konnte nicht gesendet werden“. Die Ursache ist nicht geklärt. Zur Diagnose `'debug' => true` in `config/config.php` setzen, die Meldung `[Diagnose: …]` auswerten und danach wieder auf `false`.
- `.htaccess` (HTTPS-Umleitung, HSTS, Content-Security-Policy, Zugriffssperren) ist nicht auf einem echten Apache getestet. Nach dem Upload prüfen: Seite lädt, http→https, Karte öffnet sich nach Klick. Bei „500 Internal Server Error“ das Fehlerlog ansehen.
- GitHub: Default-Branch auf `main` stellen; Hilfs-Branch `claude/quirky-goodall-nc14bh` kann danach weg.

## Spam- und Sicherheitsschutz (nicht abschwächen)

Honeypot-Feld `website`, Mindest-Ausfüllzeit 3 s (`t`), max. 5 Meldungen pro IP und Stunde, Link-Spam wird still verworfen, Formulare fremder Origins abgewiesen, Eingangsbestätigung max. 1 pro Empfänger und Stunde und 20 pro Stunde insgesamt, Bestätigung enthält bewusst keine Formularinhalte. Keine Cookies, kein Tracking, keine externen Schriften, Google Maps erst nach Klick.

## Testen

`php -S 127.0.0.1:8080 -t public`; für Mailtests einen lokalen SMTP-Sink in `config.php` eintragen (`'secure' => 'none'`). Barrierefreiheit wurde mit axe-core (WCAG 2.2 AA) geprüft: auf Desktop 1280 px und Mobil 390 px ohne Verstöße, ohne horizontales Scrollen.
