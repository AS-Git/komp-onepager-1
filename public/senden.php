<?php
/*
 * Schadensformular: prüft die Eingaben, schickt die Meldung per SMTP ans Büro
 * und auf Wunsch eine Eingangsbestätigung an den Absender.
 * Antwortet mit JSON (fetch aus js/main.js) oder leitet ohne JavaScript auf danke.html bzw. fehler.html weiter.
 */
declare(strict_types=1);

use PHPMailer\PHPMailer\Exception as MailException;
use PHPMailer\PHPMailer\PHPMailer;

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
    if ($wantsJson) {
        http_response_code($ok ? 200 : 422);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => $ok, 'fehler' => $fehler, 'bestaetigung' => $bestaetigung], JSON_UNESCAPED_UNICODE);
    } else {
        header('Location: ' . ($ok ? 'danke.html' : 'fehler.html'), true, 303);
    }
    exit;
}

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
    $debug = true; // ohne Config gibt es nichts Geheimes zu verraten
    antworten(false, $nichtErreichbar, false, 'config.php fehlt (erwartet unter ' . realpath($basis) . '/config/config.php)');
}
$config = require $configFile;
$debug = !empty($config['debug']);

$autoload = $basis . '/vendor/autoload.php';
if (!is_file($autoload)) {
    antworten(false, $nichtErreichbar, false, 'vendor/autoload.php fehlt – composer install --no-dev ausführen und vendor/ hochladen');
}
require $autoload;

// Spamschutz: Honeypot gefüllt oder in unter 3 Sekunden ausgefüllt -> still verwerfen.
// t = Ausfülldauer in ms, von js/main.js gesetzt; ohne JavaScript leer.
$t = feld('t', 12);
if (feld('website', 200) !== '' || ($t !== '' && (int) $t < 3000)) {
    antworten(true);
}

// Spamschutz: Meldungen pro IP und Stunde begrenzen (IP nur als Hash, Datei verfällt nach einer Stunde)
$limit = (int) ($config['rate_limit_per_hour'] ?? 5);
$ipFile = sys_get_temp_dir() . '/komp-formular-' . hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . __DIR__);
$zeiten = is_file($ipFile) ? array_filter(
    array_map('intval', explode(',', (string) file_get_contents($ipFile))),
    fn (int $z) => $z > time() - 3600
) : [];
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

$zeiten[] = time();
@file_put_contents($ipFile, implode(',', $zeiten), LOCK_EX);

// 2. Eingangsbestätigung an den Absender. Bewusst ohne Formularinhalte,
// damit das Formular nicht missbraucht werden kann, um fremden Text an beliebige Adressen zu schicken.
$bestaetigt = false;
if ($email !== '' && !empty($config['confirmation'])) {
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
