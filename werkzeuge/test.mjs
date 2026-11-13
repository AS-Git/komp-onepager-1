// Haupttest: Barrierefreiheit (WCAG 2.2 AA), Layout, Menü, Sprungmarken, Formularprüfung,
// „Bewegung reduzieren“, ohne JavaScript, strukturierte Daten und PHP-Syntax.
// Aufruf im Ordner werkzeuge: npm test
import { chromium } from 'playwright';
import { readFileSync, existsSync } from 'node:fs';
import { createRequire } from 'node:module';
import path from 'node:path';
import { PUBLIC, php, starteServer, protokoll } from './lib.mjs';

const require = createRequire(import.meta.url);
const axeQuelle = readFileSync(require.resolve('axe-core/axe.min.js'), 'utf8');
const SEITEN = ['', 'impressum.html', 'datenschutz.html', 'danke.html', 'fehler.html'];
const BREITEN = [[1280, 800], [390, 844]];
const p = protokoll();

p.abschnitt('PHP und strukturierte Daten');
try { php(['-l', path.join(PUBLIC, 'senden.php')]); p.ok('senden.php ohne Syntaxfehler'); }
catch (e) { p.fehler('senden.php: ' + (e.stdout || e.message)); }
const index = readFileSync(path.join(PUBLIC, 'index.html'), 'utf8');
for (const [, json] of index.matchAll(/<script type="application\/ld\+json">([\s\S]*?)<\/script>/g)) {
  try { const d = JSON.parse(json); p.ok('JSON-LD gültig: ' + [].concat(d['@type']).join(', ')); }
  catch (e) { p.fehler('JSON-LD ungültig: ' + e.message); }
}
const offen = [...new Set((index.match(/\[[A-ZÄÖÜ-]{4,}\]/g) || []))];
if (offen.length) console.log('  ℹ Offene Platzhalter: ' + offen.join(', '));

const server = await starteServer(8090);
const browser = await chromium.launch();
const axe = async (seite) => {
  await seite.addScriptTag({ content: axeQuelle });
  return seite.evaluate(async () => {
    const r = await window.axe.run(document, { runOnly: { type: 'tag', values: ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa'] } });
    return r.violations.map((v) => `${v.id} (${v.nodes.length}×): ${v.nodes[0].target.join(' ')}`);
  });
};

try {
  p.abschnitt('Barrierefreiheit und Layout (alle Seiten, Desktop 1280 px und Handy 390 px)');
  for (const s of SEITEN) {
    for (const [w, h] of BREITEN) {
      const seite = await browser.newPage({ viewport: { width: w, height: h } });
      const fehler = [];
      seite.on('pageerror', (e) => fehler.push(e.message));
      seite.on('requestfailed', (r) => { if (!r.url().includes('google')) fehler.push('Nicht geladen: ' + r.url()); });
      await seite.goto(server.url + s, { waitUntil: 'networkidle' });
      const verstoesse = await axe(seite);
      const sw = await seite.evaluate(() => document.documentElement.scrollWidth);
      const name = (s || 'index.html') + ' ' + w + ' px';
      p.pruefe(verstoesse.length === 0, `${name}: keine WCAG-Verstöße`, verstoesse.join(' | '));
      p.pruefe(sw <= w, `${name}: kein seitliches Scrollen`, `Seitenbreite ${sw} px`);
      p.pruefe(fehler.length === 0, `${name}: keine Skript- oder Ladefehler`, fehler.join(' | '));
      await seite.close();
    }
  }

  p.abschnitt('Navigation und mobiles Menü');
  {
    const seite = await browser.newPage({ viewport: { width: 1280, height: 800 } });
    await seite.goto(server.url + 'impressum.html', { waitUntil: 'networkidle' });
    p.pruefe(await seite.isVisible('.k-nav'), 'Desktop: Navigation sichtbar');
    p.pruefe(!(await seite.isVisible('.op-menu-toggle')), 'Desktop: Menü-Button ausgeblendet');
    await seite.click('.k-nav a[href="./#faq"]');
    await seite.waitForURL(/#faq$/);
    p.pruefe(seite.url().endsWith('/#faq'), 'Unterseite → Sprungmarke „FAQ“ auf der Startseite');
    await seite.close();

    const handy = await browser.newPage({ viewport: { width: 390, height: 844 } });
    await handy.goto(server.url + 'datenschutz.html', { waitUntil: 'networkidle' });
    const zu = async () => (await handy.getAttribute('#op-menu', 'hidden')) !== null;
    p.pruefe(await zu(), 'Handy: Menü anfangs geschlossen');
    await handy.click('.op-menu-toggle');
    await handy.waitForTimeout(400);
    p.pruefe(!(await zu()) && (await handy.getAttribute('.op-menu-toggle', 'aria-expanded')) === 'true', 'Handy: Menü öffnet, aria-expanded = true');
    const v = await axe(handy);
    p.pruefe(v.length === 0, 'Handy: offenes Menü ohne WCAG-Verstöße', v.join(' | '));
    await handy.keyboard.press('Escape');
    p.pruefe(await zu(), 'Handy: Escape schließt das Menü');
    p.pruefe(await handy.evaluate(() => document.activeElement.classList.contains('op-menu-toggle')), 'Handy: Fokus kehrt zum Menü-Button zurück');
    await handy.click('.op-menu-toggle');
    await handy.click('.op-menu-links a[href="./#leistungen"]');
    await handy.waitForURL(/#leistungen$/);
    p.pruefe(seiteEndet(handy.url(), '#leistungen') && (await zu()), 'Handy: Menülink springt zur Startseite und schließt das Menü');
    await handy.close();
  }

  p.abschnitt('Startseite: Formular, Reihenfolge, erster Bildschirm');
  {
    const seite = await browser.newPage({ viewport: { width: 1440, height: 900 } });
    await seite.goto(server.url, { waitUntil: 'networkidle' });
    const logo = await seite.evaluate(() => Math.round(document.querySelector('.op-trustbar').getBoundingClientRect().bottom));
    p.pruefe(logo <= 900, 'Logoleiste im ersten Bildschirm (1440 × 900)', `unterer Rand bei ${logo} px`);
    await seite.click('#schadenformular button[type=submit]');
    await seite.waitForSelector('.op-form-status:not([hidden])');
    p.pruefe((await seite.textContent('.op-form-status')).includes('Bitte prüfen'), 'Leeres Formular zeigt Hinweis auf Pflichtfelder');
    await seite.click('[data-map-load]');
    await seite.waitForTimeout(300);
    p.pruefe((await seite.locator('.op-map iframe').count()) === 1, 'Karte lädt erst nach Klick');
    await seite.close();

    const handy = await browser.newPage({ viewport: { width: 390, height: 844 } });
    await handy.goto(server.url, { waitUntil: 'networkidle' });
    const y = (sel) => handy.evaluate((s) => document.querySelector(s)?.getBoundingClientRect().top ?? null, sel);
    const form = await y('.k-form-panel'), gebiet = await y('.op-area');
    if (gebiet !== null) p.pruefe(form < gebiet, 'Handy: Formular steht vor dem Einsatzgebiet');
    await handy.close();
  }

  p.abschnitt('Bewegung: „Bewegung reduzieren“ und ohne JavaScript ist alles sichtbar');
  for (const [name, opt] of [['Bewegung reduzieren', { reducedMotion: 'reduce' }], ['ohne JavaScript', { javaScriptEnabled: false }]]) {
    const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 }, ...opt });
    const seite = await ctx.newPage();
    await seite.goto(server.url, { waitUntil: 'networkidle' });
    const unsichtbar = await seite.evaluate(() => [...document.querySelectorAll('[data-reveal], [data-reveal] > *')]
      .filter((e) => getComputedStyle(e).opacity !== '1').map((e) => e.className || e.tagName));
    p.pruefe(unsichtbar.length === 0, `${name}: alle animierten Bereiche sofort sichtbar`, unsichtbar.join(', '));
    await ctx.close();
  }
} finally {
  await browser.close();
  server.stop();
}

console.log(p.fehler ? `\n✘ ${p.fehler} Prüfung(en) fehlgeschlagen. Bitte nicht veröffentlichen.` : '\n✔ Alle Prüfungen bestanden.');
process.exit(p.fehler ? 1 : 0);

function seiteEndet(url, anker) { return url.endsWith('/' + anker); }
