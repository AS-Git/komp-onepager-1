// Startet die Seite lokal: http://127.0.0.1:8080/ (Beenden mit Strg+C)
import { starteServer } from './lib.mjs';
const server = await starteServer(Number(process.env.PORT) || 8080);
console.log(`Vorschau läuft: ${server.url}  (Beenden mit Strg+C)`);
process.on('SIGINT', () => { server.stop(); process.exit(0); });
setInterval(() => {}, 1 << 30);
