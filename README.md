# OnePager KFZ-Ingenieur-Büro Komp

Statischer OnePager (HTML/CSS) mit PHP-Formularversand per SMTP, umgesetzt nach dem Design „Komp OnePager“ und dem Designsystem „KFZ-Ingenieur-Büro Komp“.

## Aufbau

```
public/              Webroot (Document Root des Hostings hierauf zeigen lassen)
  index.html         OnePager
  danke.html         Erfolgsseite (Formular ohne JavaScript)
  fehler.html        Fehlerseite (Formular ohne JavaScript)
  impressum.html     Platzhalter – Texte einsetzen
  datenschutz.html   Platzhalter – Texte einsetzen
  senden.php         Formularversand (PHPMailer, SMTP)
  css/komp.css       Designsystem: Tokens + Komponenten (k-*)
  css/onepager.css   Seitenspezifische Styles (op-*)
  css/fonts.css      Barlow, Barlow Condensed, Material Symbols – lokal, ohne Google-Verbindung
  js/main.js         Sticky-Header, Karte nach Klick, Formular per fetch
config/
  config.example.php Vorlage für SMTP und Empfänger
vendor/              PHPMailer (per Composer, nicht im Repository)
```

`config/` und `vendor/` liegen bewusst außerhalb des Webroots.

## Einrichten

1. `composer install --no-dev` (PHP ≥ 7.4)
2. `cp config/config.example.php config/config.php` und SMTP-Daten, Absender und Empfänger eintragen
3. Alles hochladen und den Document Root der Domain auf `public/` setzen

Kann der Document Root nicht geändert werden, den Inhalt von `public/` in den Webroot legen und
`config/` und `vendor/` eine Ebene darüber. Geht auch das nicht, dürfen beide Ordner direkt neben
`senden.php` liegen; `senden.php` findet sie dort ebenfalls.

## Fehlersuche Formular

Meldet das Formular „Die Meldung konnte nicht gesendet werden“, in `config/config.php`
`'debug' => true` setzen und erneut senden: Das Formular zeigt dann den Grund an
(z. B. falsches SMTP-Passwort, falscher Port, Absender nicht erlaubt), und der SMTP-Dialog
landet im PHP-Fehlerlog des Hostings. Danach wieder auf `false` stellen.

## Lokal testen

```
php -S 127.0.0.1:8080 -t public
```

Für Tests ohne echten Mailserver in `config.php` einen lokalen SMTP-Testserver eintragen (`'secure' => 'none'`).

## Formular

- Meldung als Text-Mail ans Büro, Reply-To auf den Absender
- Eingangsbestätigung an den Absender, wenn eine E-Mail-Adresse angegeben ist (`confirmation`). Sie enthält bewusst keine Formularinhalte, damit das Formular nicht zum Versand fremder Texte missbraucht werden kann.
- Spamschutz ohne Captcha: Honeypot-Feld, Mindest-Ausfülldauer, Limit pro IP und Stunde (IP nur gehasht, temporär)
- Keine Speicherung in einer Datenbank

## Offene Platzhalter

Im Code mit `[…]` markiert:

- `[DOMAIN]` Canonical und JSON-LD-URL in `index.html`
- `[FOTO]` Porträt Dietmar Komp
- `[BEWERTUNGEN]`, `[GOOGLE-PROFIL]` echte Google-Bewertungen und Link zum Unternehmensprofil
- `[IMPRESSUM]`, `[DATENSCHUTZ]` Rechtstexte
