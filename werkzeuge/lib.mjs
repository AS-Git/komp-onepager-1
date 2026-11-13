// Gemeinsame Hilfen: Projektpfade und lokaler PHP-Server
import { spawn, execFileSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import path from 'node:path';
import net from 'node:net';

export const WERKZEUGE = path.dirname(fileURLToPath(import.meta.url));
export const PROJEKT = path.resolve(WERKZEUGE, '..');
export const PUBLIC = path.join(PROJEKT, 'public');

export function php(args, opts = {}) {
  return execFileSync('php', args, { cwd: PROJEKT, encoding: 'utf8', ...opts });
}

function portFrei(port) {
  return new Promise((resolve) => {
    const s = net.connect(port, '127.0.0.1');
    s.on('connect', () => { s.destroy(); resolve(false); });
    s.on('error', () => resolve(true));
  });
}

// Startet `php -S` auf dem Webroot und wartet, bis der Server antwortet
export async function starteServer(port = 8090) {
  if (!(await portFrei(port))) throw new Error(`Port ${port} ist belegt. Bitte den anderen Server beenden.`);
  const proc = spawn('php', ['-S', `127.0.0.1:${port}`, '-t', PUBLIC], { cwd: PROJEKT, stdio: 'ignore' });
  proc.on('error', (e) => { console.error('PHP konnte nicht gestartet werden. Ist PHP installiert und im PATH?', e.message); process.exit(1); });
  for (let i = 0; i < 50; i++) {
    if (!(await portFrei(port))) return { url: `http://127.0.0.1:${port}/`, stop: () => proc.kill() };
    await new Promise((r) => setTimeout(r, 100));
  }
  proc.kill();
  throw new Error('Der PHP-Server ist nicht gestartet.');
}

// Einfache Ausgabe mit Gesamtergebnis
export function protokoll() {
  let fehler = 0;
  return {
    ok: (text) => console.log('  ✔ ' + text),
    fehler: (text) => { fehler++; console.log('  ✘ ' + text); },
    pruefe: (bedingung, text, detail = '') => {
      if (bedingung) console.log('  ✔ ' + text);
      else { fehler++; console.log('  ✘ ' + text + (detail ? ' → ' + detail : '')); }
    },
    abschnitt: (t) => console.log('\n' + t),
    get anzahlFehler() { return fehler; },
  };
}
