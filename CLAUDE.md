# Komp OnePager – Projektkontext für Claude

OnePager für das **Ingenieur-Büro Komp**, KFZ-Sachverständige in Langen (Hessen). Umgesetzt von der Agentur Anders & Seim nach dem Design „Komp OnePager“ (Claude-Design-Artifact https://claude.ai/code/artifact/b4386f5d-192d-49c1-a5d3-b513cb65ea29) mit dem Designsystem „KFZ-Ingenieur-Büro Komp“. Zielgruppen, Tonalität und Gestaltungsprinzipien: `PRODUCT.md`. Aufbau und Einrichtung: `README.md`.

Sprache mit dem Auftraggeber: Deutsch.

## Arbeitsweise

- **Version 5 ist gewählt.** Gearbeitet wird nur noch im Branch `live` (Standard-Branch auf GitHub), entstanden aus `design-v5` (Stand `18dafbe`).
- Lokale Arbeitskopien beim Auftraggeber, getrennt nebeneinander:
  - `C:\Temp\Komp\live` = Branch `live` (**Arbeitsversion**, alle Änderungen nur hier)
  - `C:\Temp\Komp\version-5` = Branch `design-v5` (Archiv, nicht mehr ändern)
  - `C:\Temp\Komp\version-4` = Branch `design-v4` (in Abnahme; nur auf ausdrücklichen Wunsch ändern, dann nur dort)
  - `C:\Temp\Komp\version-3` = Branch `design-v3` (eingefroren; nur auf ausdrücklichen Wunsch ändern, dann nur dort)
  Änderungen im Ordner der jeweiligen Version vornehmen, nie zwischen den Versionen mischen.
- Jede abgeschlossene Änderung ohne Rückfrage auf den Branch der jeweiligen Version committen und pushen (Commit-Nachricht auf Deutsch). Vorher die Tests unter „Testen“ laufen lassen; schlägt etwas fehl, nicht pushen, sondern melden.

## Technik

- Statisches HTML/CSS in `public/` (Webroot), ein PHP-Skript `public/senden.php` für das Schadensformular (PHPMailer per SMTP, Eingangsbestätigung an den Absender).
- `config/config.php` (nicht im Repo, Vorlage `config/config.example.php`) und `vendor/` liegen außerhalb des Webroots; `senden.php` findet sie notfalls auch daneben.
- Server: Apache, PHP 8.5, SMTP vorhanden. Code bleibt kompatibel ab PHP 7.4.
- Klassen: `k-*` = Designsystem (`public/css/komp.css`, nicht seitenspezifisch ändern), `op-*` = Seite (`public/css/onepager.css`).
- Schriften (Barlow, Barlow Condensed, Material Symbols, Icon-Subset) liegen lokal in `public/fonts/`; neue Icons müssen ins Subset (`public/css/fonts.css`, woff2 neu erzeugen).

## Versionen

| Branch | Inhalt |
|---|---|
| `live` | **Arbeitsversion und Standard-Branch**: aus `design-v5` erstellt, Version 5 ist gewählt. Alle Änderungen nur hier |
| `design-v5` | **Archiv** (Ausgangsstand von `live`): Version 4 plus feine Micro-Interactions „zeichnen und messen“ (Fahrzeugzeichnung zeichnet sich erst, wenn sie fast ganz im Bild ist; Ablauf-Maßlinie mit nacheinander erscheinenden Schritten; Zeitleiste als Lineal mit gestaffelten Einträgen; Messlinie im Formular; Telefonnummer; FAQ-Symbol; Textlinks; einheitlicher leiser Hover für alle Buttons, nie unterstrichen). Ebene „Version 5“ am Ende von `public/css/onepager.css`, Auslöser in `public/js/main.js` (`data-reveal`, Klasse `op-motion`). Nur transform, opacity, stroke-dashoffset, background-size; bei „Bewegung reduzieren“ und ohne JavaScript alles sofort sichtbar |
| `design-v4` | **In Abnahme, nicht ändern**: gleiche Inhalte wie `design-v3`, Gestaltung „Ingenieur-Zeichnung“ (Haarlinien statt Karten, präzise Ecken, Maßlinien, technische Fahrzeugzeichnung). Gestaltungsebene am Ende von `public/css/onepager.css` |
| `design-v3` | **Eingefroren, 1:1 erhalten**: diesen Stand hat der Kunde gesehen. Nicht mehr ändern. Hero in Komp-Dunkelblau mit Konstruktionsraster |
| `main` | Version 1: Umsetzung des ursprünglichen Designs, nur noch Archiv |
| `design-audit` | Version 2: Barrierefreiheit (WCAG 2.2 AA), Leistungen als nummerierte Liste, nur noch Archiv |

**Abnahme:** `design-v3` (Stand `a225b10`; danach auf Wunsch des Auftraggebers nur: mobil Formular vor Einsatzgebiet) und `design-v4` (Stand `51c2b19`; danach auf Wunsch des Auftraggebers nur: mobil Formular vor Einsatzgebiet) liegen beim Kunden zur Abnahme. Bis zur Rückmeldung an der Website nichts ändern, nur auf ausdrücklichen Wunsch des Auftraggebers.

Tag `v1` = Stand von Version 1 vor den inhaltlichen Korrekturen (liegt evtl. noch nicht auf GitHub). Änderungen nur noch in `live`; `design-v5`, `design-v4`, `design-v3`, `main` und `design-audit` bleiben unverändert erhalten.

## Inhaltliche Festlegungen (vom Auftraggeber)

- **Öffentlich bestellt und vereidigt ist nur Dipl.-Ing. Werner Thiel** (IHK Offenbach am Main, Kraftfahrzeugschäden und -bewertung), nicht Dietmar Komp. Formulierungen wie „IHK-Sachverständiger“ (Hero) oder „ö.b.u.v. Sachverständiger im Team“ (Siegel, Version 5), nie so, dass Herr Komp oder das ganze Büro bestellt wirkt. Angaben zur Bestellung stehen im Impressum; auf der Startseite wird Herr Thiel in Version 5 nicht mehr namentlich genannt.
- **Dieter Komp ist verstorben.** Nie als aktiv tätig nennen. Heute leitet **Dipl.-Ing. Dietmar Komp** das Büro. In der Firmengeschichte bleibt „1977: Dieter Komp wird Partner … prägt das Büro über Jahrzehnte“.
- **„Seit den 1950er Jahren“ nicht im Hero und nicht in der Meta-Description** (wirkt altbacken). In der Firmengeschichte ist es in Ordnung.
- Hero-Kicker: „Unabhängige KFZ-Sachverständige im Großraum Rhein-Main“ (Version 5; Version 3/4: „im Rhein-Main-Gebiet“).
- **Kundenänderungen November (nur Version 5):** überall „Großraum Rhein-Main“ statt „Rhein-Main-Gebiet“; Öffnungszeiten Mo–Fr 07:30–17:00 Uhr (auch JSON-LD und Eingangsbestätigung in `senden.php`); „Wertermittlung & Oldtimer“ mit Plakette „GTÜ-Classic-Partner“; HU-Termin „in einer unserer Partnerwerkstätten“; „Beweiskräftig – Unsere Gutachten halten jeder Prüfung stand.“; „Sie sprechen direkt mit uns“; Ablauf Schritt 2 „begutachten“; FAQ „Zulassungsbescheinigung Teil I (Fahrzeugschein)“; kein Foto von Dietmar Komp, Zitat steht links unter der Einleitung; Impressum ohne Aufsichtsbehörde.
- Einsatzgebiet neben dem Formular (Version 5): Langen, Egelsbach, Dreieich, Neu-Isenburg, Rödermark, Rodgau, Mörfelden-Walldorf, Groß-Gerau, Darmstadt, Frankfurt am Main, Offenbach, Hanau (Offenbach und Hanau ergänzt am 24.11.2026; im JSON-LD `areaServed` „Offenbach am Main“ und „Hanau“). Footer (Version 5): „Kreise Offenbach, Groß-Gerau, Darmstadt-Dieburg, Main-Taunus und Main-Kinzig sowie Frankfurt und Hanau“ (vom Kunden bestätigt).
- Logos in der Vertrauensleiste: BVSK, ADAC Vertragssachverständiger, GTÜ Vertragspartner (`public/img/*.webp`); IHK nur als Textsiegel.
- Inhalte in `design-v4` identisch zu `design-v3` halten. `design-v5` hat die Kundenänderungen vom November und weicht inhaltlich ab.
- Keine Stockfotos von Unfallautos; keine Optik von Unfall-Portalen, Behörden, Baukasten-Templates oder Autohaus-Werbung (siehe `PRODUCT.md`).

## Offene Punkte

- Platzhalter im Code mit `[…]` markiert: `[DOMAIN]` (Canonical, JSON-LD), `[BEWERTUNGEN]` und `[GOOGLE-PROFIL]` (Bewertungs-Sektion ist per `hidden` ausgeblendet, bis echte Google-Bewertungen vorliegen), Impressum und Datenschutz sind aus der bisherigen Website übernommen (Stand Datenschutz Dez. 2024), die Datenschutzerklärung ist an diese Seite angepasst (keine Cookies, Schadensformular, Spamschutz, lokale Schriften, Google Maps erst nach Klick). Hoster bleibt Mittwald (bestätigt). Adresse der IHK Offenbach im Impressum bestätigt. Herr Thiel wird in Version 5 nur im Impressum genannt.
- Das Porträt von Dietmar Komp ist in Version 5 auf Kundenwunsch entfallen (Version 3/4 haben noch den Platzhalter `[FOTO]`). Echte Fotos von Büro oder Besichtigung wären weiterhin ein möglicher Gestaltungsschritt.
- Formular auf dem Testserver: Die vorgeschaltete Firewall hat eine Ausnahme für `/senden.php` (erledigt). Bei erneutem HTTP 403 zuerst diese Ausnahme prüfen.
- Servertest erledigt: Formularversand (Meldung und Eingangsbestätigung), HTTPS-Umleitung, `.htaccess` und Karte funktionieren auf dem Testserver. Bei Formularproblemen `'debug' => true` in `config/config.php` setzen, `[Diagnose: …]` auswerten, danach wieder `false`.

## Spam- und Sicherheitsschutz (nicht abschwächen)

Honeypot-Feld `website`, Mindest-Ausfüllzeit 3 s (`t`), max. 5 Meldungen pro IP und Stunde, Link-Spam wird still verworfen, Formulare fremder Origins abgewiesen, Eingangsbestätigung max. 1 pro Empfänger und Stunde und 20 pro Stunde insgesamt, Bestätigung enthält bewusst keine Formularinhalte. Keine Cookies, kein Tracking, keine externen Schriften, Google Maps erst nach Klick.

## Testen

Werkzeuge im Ordner `werkzeuge/` (Anleitung: `werkzeuge/README.md`), unter Windows wie Linux:

- `npm test`: Pflicht vor jedem Commit. WCAG 2.2 AA mit axe-core auf allen fünf Seiten (Desktop 1280 px, Handy 390 px), kein seitliches Scrollen, keine Skriptfehler, Menü und Sprungmarken, Formularhinweis, Karte erst nach Klick, Formular vor Einsatzgebiet (mobil), Logoleiste im ersten Bildschirm, „Bewegung reduzieren“ und ohne JavaScript alles sichtbar, JSON-LD gültig, `senden.php` ohne Syntaxfehler.
- `npm run test:formular`: nach Änderungen an `senden.php` oder am Formular. Echte Meldung und alle Spamschutz-Regeln mit eingebautem Test-Mailserver; sichert und restauriert eine vorhandene `config/config.php`.
- `npm run paket`: Upload-ZIP aus dem committeten Stand nach `werkzeuge/ausgabe/`.
- `npm run vorschau`: lokale Vorschau unter http://127.0.0.1:8080/.

Schlägt ein Test fehl, nicht pushen, sondern melden.

## Lokale Einrichtung beim Auftraggeber

Git, PHP 8.x, Node.js (LTS), Composer installieren. `werkzeuge/komp-einrichten.bat` legt `C:\Temp\Komp\live` und `C:\Temp\Komp\version-3/-4/-5` an. Je Arbeitskopie einmal `composer install --no-dev`, dann in `werkzeuge`: `npm install` und `npx playwright install chromium`. `config/config.php` (SMTP) liegt nicht im Repository; für echte Mailtests vom Server kopieren.

## Livegang und danach

1. ✔ Version 5 ist gewählt (13.11.2026).
2. ✔ Branch `live` aus `design-v5` erstellt und gepusht, auf GitHub als Standard-Branch eingestellt, Arbeitskopie `C:\Temp\Komp\live` angelegt. `main` bleibt unverändert als Archiv von Version 1.
3. Upload-Paket mit `npm run paket` bauen, hochladen, auf dem Server prüfen (Formular mit `debug`, http→https, Karte), danach `debug` wieder `false`.
4. Den veröffentlichten Stand markieren: `git tag live-JJJJ-MM-TT` und `git push origin live-JJJJ-MM-TT`. Zurück auf einen früheren Stand: Tag auschecken, Paket bauen, hochladen.
5. Ab dann gilt: Arbeitskopie `C:\Temp\Komp\live` (`git clone -b live …`), alle Änderungen nur in `live`, jede Änderung testen, committen, pushen; jede Veröffentlichung bekommt einen neuen Tag. `design-v3/-v4/-v5` bleiben als Archiv unverändert.
6. Beim Livegang `[DOMAIN]` prüfen (Canonical und JSON-LD zeigen bereits auf https://www.kfz-sachverstaendiger-komp.de/).

## Entscheidungen und Begründungen

- **Kein Captcha.** Unfallopfer sollen ohne Hürde melden können. Spamschutz in Stufen (siehe oben). Falls im Betrieb Spam ankommt, in dieser Reihenfolge nachrüsten: (1) Zeitfeld `t` zur Pflicht machen (schließt Direktversand ohne Browser aus; ohne JavaScript dann nur Telefon), (2) Tageslimit insgesamt, (3) datenschutzfreundliches Captcha (ALTCHA selbst gehostet oder Friendly Captcha). Bisher nicht umgesetzt, Entscheidung des Auftraggebers offen.
- **`senden.php` liegt im Webroot**, weil das Formular eine erreichbare Adresse braucht; Geheimnisse liegen in `config/` und `vendor/` außerhalb. Auf dem Testserver blockierte eine vorgeschaltete Firewall `.php`-Aufrufe (HTTP 403 „Anders und Seim stopped this request“); dafür gibt es eine Ausnahme für `/senden.php`.
- **GTÜ-Hinweis im HU-Block bleibt** („Im Namen und auf Rechnung der GTÜ …“), obwohl das Impressum keine Aufsichtsbehörde mehr nennt: Prüfstellen-Partner brauchen diesen Hinweis in der Regel.
- **Datenschutz:** keine Cookies, kein Tracking, Schriften lokal, Google Maps erst nach Klick. Deshalb kein Cookie-Banner. Hoster Mittwald (Auftragsverarbeitung). Wer Dienste ergänzt (Analyse, Maps ohne Klick, externe Schriften), muss die Datenschutzerklärung anpassen.
- **Logoleiste im ersten Bildschirm** (Desktop): Hero-Höhe nicht vergrößern, ohne das zu prüfen (`npm test`).
- **Mobil Formular vor Einsatzgebiet:** Die Sprungmarke „Schaden melden“ landet eindeutig beim Formular. Umgesetzt über die Reihenfolge im HTML (nicht per CSS `order`), damit auch Tastatur und Screenreader das Formular zuerst erreichen; Desktop-Anordnung per Grid.
- **Mobiles Menü:** Hamburger mit Navigation, „Schaden melden“, Anrufen, E-Mail, Adresse, Öffnungszeiten; Impressum und Datenschutz nur im Footer (Kundenwunsch). Unterseiten verlinken auf Sprungmarken der Startseite (`./#faq`).
- **Bewegung (Version 5):** nur transform, opacity, stroke-dashoffset, background-size; keine Layout-Verschiebung; Einblendungen nur einmal und erst im Bild (`data-reveal`, `data-reveal="late"` für die Zeichnung). Bei „Bewegung reduzieren“, ohne JavaScript und im Druck alles sofort sichtbar.
- **Einsatzgebiet:** seit 24.11.2026 zwölf Orte in drei Zeilen auf Desktop und Handy. Die Schriftgröße folgt der Spaltenbreite (`cqi`, Desktop 15–19 px, Handy 15–17 px); bei neuen Orten Zeilenzahl bei 360, 390, 920, 1024 und 1280 px prüfen. Vorher Variante A: Orte 16 px, Desktop zwei Zeilen. Variante B mit weiteren Städten (Offenbach, Dietzenbach, Seligenstadt, Rüsselsheim, Dieburg, Hofheim, Hanau, Maintal) wurde vorgeschlagen, aber nicht gewählt.
- **Footer-Einsatzgebiet** formal korrekt („Kreise … sowie Frankfurt und Hanau“): Hanau gehört zum Main-Kinzig-Kreis, Frankfurt ist kreisfrei.
- **Kein übernommener Fremdtext:** Abgleich mit gutachter-kiwi.de (Nov. 2026) ergab nur branchenübliche Wendungen, keine kopierten Sätze.
- **Upload-Pakete** mit Dateirechten 644/755 und ohne `.git` in `vendor/` (gruppen-schreibbare Dateien können auf Servern zu Fehlern führen).
- **Version 3 und 4 eingefroren**, weil der Kunde diese Stände gesehen hat; Version 3/4 bekamen nach der Abnahme nur die mobile Umstellung Formular/Einsatzgebiet.
