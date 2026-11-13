// Baut das Upload-Paket (ZIP) aus dem zuletzt committeten Stand plus vendor/.
// Aufruf im Ordner werkzeuge: npm run paket
// Ergebnis: werkzeuge/ausgabe/komp-onepager-<version>-<commit>.zip
// Enthält public/, config/config.example.php, vendor/, composer.json/.lock und README.md,
// aber nie config/config.php mit Zugangsdaten. Dateirechte 644, Ordner 755.
import { execFileSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import archiver from 'archiver';
import { PROJEKT, WERKZEUGE } from './lib.mjs';

const git = (...args) => execFileSync('git', args, { cwd: PROJEKT });
const branch = git('rev-parse', '--abbrev-ref', 'HEAD').toString().trim();
const commit = git('rev-parse', '--short', 'HEAD').toString().trim();
const version = branch.replace(/^design-v(\d+)$/, 'version-$1');
const name = `komp-onepager-${version}`;

if (git('status', '--porcelain', '--', 'public', 'config', 'composer.json', 'composer.lock', 'README.md').toString().trim()) {
  console.log('⚠ Es gibt nicht committete Änderungen. Das Paket enthält nur den committeten Stand.');
}
const vendor = path.join(PROJEKT, 'vendor');
if (!fs.existsSync(path.join(vendor, 'autoload.php'))) {
  console.error('vendor/ fehlt. Bitte zuerst im Projektordner `composer install --no-dev` ausführen.');
  process.exit(1);
}

const ausgabe = path.join(WERKZEUGE, 'ausgabe');
fs.mkdirSync(ausgabe, { recursive: true });
const ziel = path.join(ausgabe, `${name}-${commit}.zip`);
const zip = archiver('zip', { zlib: { level: 9 } });
const fertig = new Promise((resolve, reject) => {
  const out = fs.createWriteStream(ziel);
  out.on('close', resolve);
  zip.on('error', reject);
  zip.pipe(out);
});

// Committete Dateien
const dateien = git('ls-files', '-z', '--', 'public', 'config', 'composer.json', 'composer.lock', 'README.md')
  .toString().split('\0').filter(Boolean).filter((f) => f !== 'config/config.php');
for (const f of dateien) zip.append(git('show', `HEAD:${f}`), { name: `${name}/${f}`, mode: 0o644 });

// vendor/ (ohne .git-Ordner)
const sammle = (dir) => fs.readdirSync(dir, { withFileTypes: true }).flatMap((e) => {
  if (e.name === '.git') return [];
  const p = path.join(dir, e.name);
  return e.isDirectory() ? sammle(p) : [p];
});
for (const f of sammle(vendor)) {
  const rel = path.relative(PROJEKT, f).split(path.sep).join('/');
  zip.file(f, { name: `${name}/${rel}`, mode: 0o644 });
}

await zip.finalize();
await fertig;
console.log(`✔ Paket erstellt: ${ziel}`);
console.log(`  ${dateien.length} Projektdateien + vendor/, Stand ${branch} @ ${commit}`);
