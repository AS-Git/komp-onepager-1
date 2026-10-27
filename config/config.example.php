<?php
/*
 * Vorlage für die Formular-Konfiguration.
 * Als config/config.php kopieren und ausfüllen. config.php nie ins Repository einchecken.
 * Der Ordner config/ liegt außerhalb des Webroots (public/) und ist nicht aus dem Netz erreichbar.
 */
return [
    'smtp' => [
        'host'     => 'smtp.example.de',
        'port'     => 587,
        'secure'   => 'tls',          // 'tls' (STARTTLS, Port 587), 'ssl' (Port 465) oder 'none' (nur lokale Tests)
        'user'     => 'formular@kfz-ing.de',
        'password' => '',
    ],

    // Absender aller Mails; muss zum SMTP-Konto passen, sonst landen Mails im Spam
    'from_email' => 'formular@kfz-ing.de',
    'from_name'  => 'Ingenieur-Büro Komp',

    // Empfänger der Schadensmeldungen
    'to_email' => 'komp@kfz-ing.de',
    'to_name'  => 'Ingenieur-Büro Komp',

    // Eingangsbestätigung an den Absender, wenn er eine E-Mail-Adresse angibt
    'confirmation' => true,

    // Diagnose: true zeigt den Fehlergrund im Formular an und schreibt den SMTP-Dialog ins PHP-Fehlerlog.
    // Nur zur Einrichtung einschalten, danach wieder auf false.
    'debug' => false,

    // Spamschutz: höchstens so viele Meldungen pro IP-Adresse und Stunde
    'rate_limit_per_hour' => 5,

    // Schutz vor Missbrauch der Eingangsbestätigung: höchstens so viele Bestätigungen pro Stunde
    // insgesamt (zusätzlich gilt: höchstens eine pro Empfängeradresse und Stunde)
    'confirmation_limit_per_hour' => 20,
];
