<?php
/*
 * Schadensformular: prüft die Eingaben, schickt die Meldung per SMTP ans Büro
 * und auf Wunsch eine Eingangsbestätigung an den Absender.
 * Antwortet mit JSON (fetch aus js/main.js) oder leitet ohne JavaScript auf danke.html bzw. fehler.html weiter.
 */
declare(strict_types=1);

use PHPMailer\PHPMailer\Exception as MailException;
use PHPMailer\PHPMailer\PHPMailer;

// Warnungen und Hinweise nie in die Antwort schreiben (sie würden das JSON für das Formular zerstören),
// sondern nur ins Fehlerlog; verirrte Ausgaben werden gepuffert und vor der Antwort verworfen.
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ob_start();

$wantsJson = strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false;
$debug = false;

function antworten(bool $ok, string $fehler = '', bool $bestaetigung = false, string $ursache = ''): void
{
    global $wantsJson, $debug;
    if ($ursache !== '') {
        error_log('Schadensformular: ' . $ursache);
        // Diagnosemodus ('debug' => true in config.php): Ursache auch im Formular anzeigen
        if ($debug) {
            $fehler .= ' [Diagnose: ' . $ursache . ']';
        }
    }
    $streuausgabe = trim((string) ob_get_clean());
    if ($streuausgabe !== '') {
        error_log('Schadensformular: unerwartete Ausgabe verworfen: ' . substr($streuausgabe, 0, 500));
    }
    if ($wantsJson) {
        http_response_code($ok ? 200 : 422);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => $ok, 'fehler' => $fehler, 'bestaetigung' => $bestaetigung], JSON_UNESCAPED_UNICODE);
    } else {
        header('Location: ' . ($ok ? 'danke.html' : 'fehler.html'), true, 303);
    }
    exit;
}

// Jeder unerwartete Fehler wird zur normalen Fehlermeldung, im Diagnosemodus mit Ursache
set_exception_handler(function (Throwable $e) {
    antworten(false, 'Die Meldung konnte nicht gesendet werden. Bitte rufen Sie uns an: 06103 / 22011.', false,
        get_class($e) . ': ' . $e->getMessage() . ' in ' . basename($e->getFile()) . ':' . $e->getLine());
});

/** Text aus dem POST holen: getrimmt, ohne Steuerzeichen, gekürzt. */
function feld(string $name, int $max, bool $mehrzeilig = false): string
{
    $wert = is_string($_POST[$name] ?? null) ? trim($_POST[$name]) : '';
    $wert = $mehrzeilig
        ? preg_replace('/[^\P{C}\n\t]/u', '', str_replace("\r\n", "\n", $wert))
        : preg_replace('/\p{C}/u', ' ', $wert);
    // ohne mbstring-Abhängigkeit auf $max Zeichen kürzen
    return preg_replace('/^(.{0,' . $max . '}).*$/us', '$1', (string) $wert);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: ./#schaden-melden', true, 303);
    exit;
}

$nichtErreichbar = 'Das Formular ist gerade nicht erreichbar. Bitte rufen Sie uns an: 06103 / 22011.';
// config/ und vendor/ liegen eine Ebene über dem Webroot; liegen sie (notgedrungen) daneben, geht es auch
$basis = is_dir(__DIR__ . '/../config') || is_dir(__DIR__ . '/../vendor') ? __DIR__ . '/..' : __DIR__;
$configFile = $basis . '/config/config.php';
if (!is_file($configFile)) {
    // Ursache nur ins Fehlerlog: Serverpfade gehen Besucher nichts an
    antworten(false, $nichtErreichbar, false, 'config.php fehlt (erwartet unter ' . realpath($basis) . '/config/config.php)');
}
$config = require $configFile;
$debug = !empty($config['debug']);

$autoload = $basis . '/vendor/autoload.php';
if (!is_file($autoload)) {
    antworten(false, $nichtErreichbar, false, 'vendor/autoload.php fehlt – composer install --no-dev ausführen und vendor/ hochladen');
}
require $autoload;

// Nur Formulare von der eigenen Website annehmen: Schickt der Browser einen Origin mit,
// muss er zum eigenen Host passen (verhindert das Abschicken von fremden Seiten aus).
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin !== '' && parse_url($origin, PHP_URL_HOST) !== parse_url('//' . ($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST)) {
    antworten(false, $nichtErreichbar, false, 'fremder Origin abgewiesen: ' . substr($origin, 0, 100));
}

// Spamschutz: Honeypot gefüllt oder in unter 3 Sekunden ausgefüllt -> still verwerfen.
// t = Ausfülldauer in ms, von js/main.js gesetzt; ohne JavaScript leer.
$t = feld('t', 12);
if (feld('website', 200) !== '' || ($t !== '' && (int) $t < 3000)) {
    antworten(true);
}

/**
 * Zähler für die Spambremse: eine Datei pro Schlüssel im Temp-Verzeichnis, nur Zeitstempel,
 * Schlüssel nur als Hash (keine IP- oder E-Mail-Adresse im Klartext), Einträge verfallen nach einer Stunde.
 */
function zaehlerDatei(string $schluessel): string
{
    return sys_get_temp_dir() . '/komp-formular-' . hash('sha256', $schluessel . __DIR__);
}
function zaehlerLesen(string $datei): array
{
    return is_file($datei) ? array_values(array_filter(
        array_map('intval', explode(',', (string) file_get_contents($datei))),
        fn (int $z) => $z > time() - 3600
    )) : [];
}
function zaehlerErhoehen(string $datei, array $zeiten): void
{
    $zeiten[] = time();
    @file_put_contents($datei, implode(',', $zeiten), LOCK_EX);
}

// Spamschutz: Meldungen pro IP und Stunde begrenzen
$limit = (int) ($config['rate_limit_per_hour'] ?? 5);
$ipFile = zaehlerDatei('ip:' . ($_SERVER['REMOTE_ADDR'] ?? ''));
$zeiten = zaehlerLesen($ipFile);
if (count($zeiten) >= $limit) {
    antworten(false, 'Sie haben in kurzer Zeit mehrere Meldungen gesendet. Bitte rufen Sie uns an: 06103 / 22011.');
}

$schuldOptionen = [
    'gegner'     => 'Der Unfallgegner',
    'teilschuld' => 'Teilschuld',
    'selbst'     => 'Ich selbst / Kasko',
    'unklar'     => 'Noch unklar',
];

$name        = feld('name', 120);
$telefon     = feld('telefon', 40);
$email       = feld('email', 160);
$datum       = feld('datum', 10);
$fahrzeug    = feld('fahrzeug', 120);
$kennzeichen = feld('kennzeichen', 20);
$schuld      = $schuldOptionen[feld('schuld', 20)] ?? 'Keine Angabe';
$nachricht   = feld('nachricht', 5000, true);

if ($name === '' || $telefon === '' || ($_POST['einwilligung'] ?? '') !== '1') {
    antworten(false, 'Bitte füllen Sie Name und Telefon aus und bestätigen Sie die Einwilligung.');
}
if ($email !== '' && !PHPMailer::validateAddress($email)) {
    antworten(false, 'Bitte prüfen Sie Ihre E-Mail-Adresse.');
}
if ($datum !== '') {
    $d = DateTimeImmutable::createFromFormat('!Y-m-d', $datum);
    $datum = $d ? $d->format('d.m.Y') : '';
}

// Spamschutz: Link-Spam still verwerfen. Echte Schadensmeldungen enthalten in Name, Telefon,
// Fahrzeug und Kennzeichen nie Links und im Freitext höchstens einen.
$linkMuster = '~(https?://|www\.|\[url|<a\s)~i';
foreach ([$name, $telefon, $fahrzeug, $kennzeichen] as $wert) {
    if (preg_match($linkMuster, $wert)) {
        antworten(true);
    }
}
if (preg_match_all($linkMuster, $nachricht) > 1) {
    antworten(true);
}

function mailer(array $config): PHPMailer
{
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = $config['smtp']['host'];
    $mail->Port       = (int) $config['smtp']['port'];
    $secure = [
        'ssl'  => PHPMailer::ENCRYPTION_SMTPS,
        'none' => '',
    ];
    $mail->SMTPSecure = $secure[$config['smtp']['secure']] ?? PHPMailer::ENCRYPTION_STARTTLS;
    $mail->SMTPAutoTLS = $config['smtp']['secure'] !== 'none';
    $mail->SMTPAuth   = $config['smtp']['user'] !== '';
    $mail->Username   = $config['smtp']['user'];
    $mail->Password   = $config['smtp']['password'];
    $mail->CharSet    = PHPMailer::CHARSET_UTF8;
    $mail->Timeout    = 20;
    if (!empty($config['debug'])) {
        // SMTP-Dialog ins PHP-Fehlerlog schreiben (ohne Passwort im Klartext)
        $mail->SMTPDebug   = 2;
        $mail->Debugoutput = 'error_log';
    }
    $mail->setFrom($config['from_email'], $config['from_name']);
    return $mail;
}

$eingang = (new DateTimeImmutable('now', new DateTimeZone('Europe/Berlin')))->format('d.m.Y, H:i');
$leer = fn (string $s) => $s === '' ? '–' : $s;

// 1. Meldung ans Büro
try {
    $mail = mailer($config);
    $mail->addAddress($config['to_email'], $config['to_name']);
    if ($email !== '') {
        $mail->addReplyTo($email, $name);
    }
    $mail->Subject = 'Schadensmeldung: ' . $name . ($kennzeichen !== '' ? ' (' . $kennzeichen . ')' : '');
    $mail->Body =
        "Neue Schadensmeldung über die Website ($eingang Uhr)\n\n"
        . "Name:           $name\n"
        . "Telefon:        $telefon\n"
        . 'E-Mail:         ' . $leer($email) . "\n"
        . 'Schadensdatum:  ' . $leer($datum) . "\n"
        . 'Fahrzeug:       ' . $leer($fahrzeug) . "\n"
        . 'Kennzeichen:    ' . $leer($kennzeichen) . "\n"
        . "Verursacher:    $schuld\n\n"
        . "Was ist passiert?\n" . $leer($nachricht) . "\n\n"
        . "--\nDer Absender hat der Verarbeitung seiner Angaben zur Bearbeitung der Anfrage zugestimmt.\n";
    $mail->send();
} catch (MailException $e) {
    antworten(false, 'Die Meldung konnte nicht gesendet werden. Bitte rufen Sie uns an: 06103 / 22011.', false,
        'Versand ans Büro fehlgeschlagen: ' . $e->getMessage());
}

zaehlerErhoehen($ipFile, $zeiten);

// 2. Eingangsbestätigung an den Absender. Bewusst ohne Formularinhalte,
// damit das Formular nicht missbraucht werden kann, um fremden Text an beliebige Adressen zu schicken.
// Zusätzlich gedrosselt, damit niemand fremde Postfächer mit Bestätigungen fluten kann:
// höchstens eine pro Empfänger und Stunde und insgesamt höchstens 'confirmation_limit_per_hour'.
$empfaengerFile = zaehlerDatei('mail:' . strtolower($email));
$empfaengerZeiten = $email !== '' ? zaehlerLesen($empfaengerFile) : [];
$gesamtFile = zaehlerDatei('bestaetigungen');
$gesamtZeiten = zaehlerLesen($gesamtFile);
$bestaetigt = false;
if ($email !== '' && !empty($config['confirmation'])
    && count($empfaengerZeiten) < 1
    && count($gesamtZeiten) < (int) ($config['confirmation_limit_per_hour'] ?? 20)) {
    zaehlerErhoehen($empfaengerFile, $empfaengerZeiten);
    zaehlerErhoehen($gesamtFile, $gesamtZeiten);
    try {
        $mail = mailer($config);
        $mail->addAddress($email);
        $mail->addReplyTo($config['to_email'], $config['to_name']);
        $mail->Subject = 'Ihre Schadensmeldung beim Ingenieur-Büro Komp';
        $mail->Body =
            "Guten Tag,\n\n"
            . "vielen Dank für Ihre Schadensmeldung, die am $eingang Uhr bei uns eingegangen ist.\n\n"
            . "Wir melden uns innerhalb unserer Öffnungszeiten (Montag bis Freitag, 07:30 bis 17:30 Uhr) telefonisch bei Ihnen "
            . "und vereinbaren einen Besichtigungstermin. Fotos vom Schaden können Sie gern als Antwort auf diese E-Mail senden.\n\n"
            . "Bitte unterschreiben Sie bis dahin nichts, was Ihnen die Versicherung der Gegenseite vorlegt.\n\n"
            . "In dringenden Fällen erreichen Sie uns unter 06103 / 22011.\n\n"
            . "Mit freundlichen Grüßen\n"
            . "Ingenieur-Büro Komp\n"
            . "KFZ-Sachverständige\n"
            . "Gartenstraße 115, 63225 Langen (Hessen)\n"
            . "Tel. 06103 / 22011 · Fax 06103 / 22090\n"
            . "komp@kfz-ing.de\n\n"
            . "Sie erhalten diese E-Mail, weil diese Adresse im Schadensformular auf unserer Website angegeben wurde.\n";
        $mail->send();
        $bestaetigt = true;
    } catch (MailException $e) {
        // Die Meldung ist angekommen; eine fehlende Bestätigung ist kein Fehler für den Absender
        error_log('Schadensformular: Bestätigung fehlgeschlagen: ' . $e->getMessage());
    }
}

antworten(true, '', $bestaetigt);
