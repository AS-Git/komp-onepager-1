// Formular- und Spamschutztest mit einem lokalen Test-Mailserver (es wird keine echte Mail verschickt).
// Aufruf im Ordner werkzeuge: npm run test:formular
// Voraussetzung: `composer install` im Projektordner (vendor/ mit PHPMailer).
// Eine vorhandene config/config.php wird für die Dauer des Tests beiseitegelegt und danach wiederhergestellt.
import net from 'node:net';
import fs from 'node:fs';
import path from 'node:path';
import { PROJEKT, php, starteServer, protokoll } from './lib.mjs';

const p = protokoll();
const CONFIG = path.join(PROJEKT, 'config', 'config.php');
const SICHERUNG = CONFIG + '.vor-formulartest';
const SMTP_PORT = 2526;

if (!fs.existsSync(path.join(PROJEKT, 'vendor', 'autoload.php'))) {
  console.error('vendor/ fehlt. Bitte zuerst im Projektordner `composer install --no-dev` ausführen.');
  process.exit(1);
}

// Minimaler SMTP-Empfänger: nimmt Mails an und merkt sie sich
const mails = [];
const smtp = net.createServer((sock) => {
  let daten = false, puffer = '', aktuell = '';
  sock.write('220 test\r\n');
  sock.on('data', (chunk) => {
    puffer += chunk.toString('utf8');
    let i;
    while ((i = puffer.indexOf('\r\n')) >= 0) {
      const zeile = puffer.slice(0, i); puffer = puffer.slice(i + 2);
      if (daten) {
        if (zeile === '.') { daten = false; mails.push(aktuell); aktuell = ''; sock.write('250 ok\r\n'); }
        else aktuell += zeile + '\n';
        continue;
      }
      const cmd = zeile.slice(0, 4).toUpperCase();
      if (cmd === 'EHLO' || cmd === 'HELO') sock.write('250 test\r\n');
      else if (cmd === 'DATA') { daten = true; sock.write('354 go\r\n'); }
      else if (cmd === 'QUIT') { sock.write('221 bye\r\n'); sock.end(); }
      else sock.write('250 ok\r\n');
    }
  });
  sock.on('error', () => {});
}).listen(SMTP_PORT, '127.0.0.1');

// Testkonfiguration schreiben (echte config.php vorher sichern)
const hatteConfig = fs.existsSync(CONFIG);
if (hatteConfig) fs.renameSync(CONFIG, SICHERUNG);
const vorlage = fs.readFileSync(path.join(PROJEKT, 'config', 'config.example.php'), 'utf8')
  .replace(/'host'\s*=>\s*'[^']*'/, "'host'     => '127.0.0.1'")
  .replace(/'port'\s*=>\s*\d+/, `'port'     => ${SMTP_PORT}`)
  .replace(/'secure'\s*=>\s*'[^']*'/, "'secure'   => 'none'")
  .replace(/'user'\s*=>\s*'[^']*'/, "'user'     => ''");
fs.writeFileSync(CONFIG, vorlage);

// Zähler der Spambremse leeren, damit der Test wiederholbar ist
const temp = php(['-r', 'echo sys_get_temp_dir();']).trim();
for (const f of fs.readdirSync(temp)) if (f.startsWith('komp-formular-')) fs.rmSync(path.join(temp, f), { force: true });

const server = await starteServer(8091);
const URL = server.url + 'senden.php';
const senden = async (felder, kopf = {}) => {
  const body = new URLSearchParams({ einwilligung: '1', t: '9000', telefon: '06103 1', ...felder });
  const r = await fetch(URL, { method: 'POST', body, headers: { Accept: 'application/json', ...kopf } });
  return r.json();
};
const warte = () => new Promise((r) => setTimeout(r, 300));

try {
  p.abschnitt('Echte Meldung');
  let n = mails.length;
  let a = await senden({ name: 'Test Echt', email: 'test@example.org', fahrzeug: 'VW Golf', kennzeichen: 'OF-AB 123', nachricht: 'Auffahrunfall' });
  await warte();
  p.pruefe(a.ok === true, 'Meldung angenommen', JSON.stringify(a));
  p.pruefe(mails.length - n === 2, 'Meldung ans Büro und Eingangsbestätigung verschickt', `${mails.length - n} Mail(s)`);
  const buero = mails.find((m) => /Schadensmeldung: Test Echt/.test(m)) || '';
  p.pruefe(/Kennzeichen:\s+OF-AB 123/.test(buero), 'Büro-Mail enthält die Angaben');
  const bestaetigung = mails.find((m) => /^To: test@example\.org/m.test(m)) || '';
  p.pruefe(bestaetigung && !/Auffahrunfall/.test(bestaetigung), 'Bestätigung enthält keine Formularinhalte');

  p.abschnitt('Spam wird verworfen (keine Mail)');
  const faelle = [
    ['Fangfeld ausgefüllt', { name: 'Bot', website: 'spam.example' }],
    ['Zu schnell ausgefüllt', { name: 'Bot', t: '800' }],
    ['Link im Namen', { name: 'https://spam.example' }],
    ['Zwei Links im Text', { name: 'Max', nachricht: 'www.a.de und https://b.de' }],
  ];
  for (const [titel, felder] of faelle) {
    n = mails.length; a = await senden(felder); await warte();
    p.pruefe(mails.length === n, titel + ': still verworfen');
  }
  n = mails.length;
  a = await senden({ name: 'Max' }, { Origin: 'https://fremde-seite.example' }); await warte();
  p.pruefe(a.ok === false && mails.length === n, 'Formular von fremder Website abgewiesen');
  n = mails.length;
  a = await senden({ name: 'Max\r\nBcc: opfer@example.org' }); await warte();
  p.pruefe(!mails.slice(n).some((m) => /^Bcc:/mi.test(m)), 'Kein eingeschleuster Empfänger (Bcc) möglich');
  a = await senden({ name: 'Max', email: 'a@b.de\r\nBcc: x@y.de' });
  p.pruefe(a.ok === false, 'Manipulierte E-Mail-Adresse abgelehnt');

  p.abschnitt('Limit pro Absender');
  let gesperrt = false;
  for (let i = 0; i < 6; i++) {
    a = await senden({ name: 'Serie ' + i });
    if (a.ok === false && /mehrere Meldungen/.test(a.fehler || '')) { gesperrt = true; break; }
  }
  p.pruefe(gesperrt, 'Nach 5 Meldungen pro Stunde wird gesperrt');
} finally {
  server.stop();
  smtp.close();
  fs.rmSync(CONFIG, { force: true });
  if (hatteConfig) fs.renameSync(SICHERUNG, CONFIG);
  for (const f of fs.readdirSync(temp)) if (f.startsWith('komp-formular-')) fs.rmSync(path.join(temp, f), { force: true });
}

console.log(p.anzahlFehler ? `\n✘ ${p.anzahlFehler} Prüfung(en) fehlgeschlagen.` : '\n✔ Formular und Spamschutz in Ordnung.');
process.exit(p.anzahlFehler ? 1 : 0);
