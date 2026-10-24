// Mide la razón de contraste WCAG 2.x de los pares texto/fondo del brief.
// Lee los tokens del :root de prototype.html (fuente medida), convierte OKLCH -> OKLab -> sRGB lineal
// (matrices de Björn Ottosson), avisa si un color cae fuera del gamut sRGB y calcula
// (L1 + 0.05) / (L2 + 0.05). Uso: node specs/design/contrast.mjs [--hex]
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';

const html = readFileSync(fileURLToPath(new URL('./prototype.html', import.meta.url)), 'utf8');
const root = html.match(/:root\s*\{([\s\S]*?)\n\}/)[1];
const raw = Object.fromEntries([...root.matchAll(/--([\w-]+):\s*([^;]+);/g)].map(m => [m[1], m[2].trim()]));
const resolve = v => { const m = v.match(/^var\(--([\w-]+)\)$/); return m ? resolve(raw[m[1]]) : v; };

function oklchToLinear(str) {
  const m = str.match(/oklch\(\s*([\d.]+)%\s+([\d.]+)\s+([\d.]+)/);
  if (!m) throw new Error(`No es OKLCH: ${str}`);
  const L = +m[1] / 100, C = +m[2], h = (+m[3] * Math.PI) / 180;
  const a = C * Math.cos(h), b = C * Math.sin(h);
  const l = (L + 0.3963377774 * a + 0.2158037573 * b) ** 3;
  const mm = (L - 0.1055613458 * a - 0.0638541728 * b) ** 3;
  const s = (L - 0.0894841775 * a - 1.291485548 * b) ** 3;
  return [
    4.0767416621 * l - 3.3077115913 * mm + 0.2309699292 * s,
    -1.2684380046 * l + 2.6097574011 * mm - 0.3413193965 * s,
    -0.0041960863 * l - 0.7034186147 * mm + 1.707614701 * s,
  ];
}
const inGamut = lin => lin.every(c => c >= -1e-4 && c <= 1 + 1e-4);
const clamp = c => Math.min(1, Math.max(0, c));
const lum = lin => { const [r, g, b] = lin.map(clamp); return 0.2126 * r + 0.7152 * g + 0.0722 * b; };
const enc = c => { c = clamp(c); return c <= 0.0031308 ? 12.92 * c : 1.055 * c ** (1 / 2.4) - 0.055; };
const hex = lin => '#' + lin.map(c => Math.round(enc(c) * 255).toString(16).padStart(2, '0')).join('');
const color = name => { const v = resolve(raw[name]); if (!v) throw new Error(`Token inexistente: --${name}`); return oklchToLinear(v); };
const ratio = (fg, bg) => { const [hi, lo] = [lum(color(fg)), lum(color(bg))].sort((x, y) => y - x); return (hi + 0.05) / (lo + 0.05); };

// [texto o elemento, fondo, mínimo exigido, uso]
const pairs = [
  ['color-ink', 'color-canvas', 7, 'Texto principal sobre el fondo de página'],
  ['color-ink', 'color-surface', 7, 'Texto principal en tablas, formularios y paneles'],
  ['color-ink', 'color-sunken', 4.5, 'Texto sobre hover de fila/botón secundario'],
  ['color-ink-2', 'color-canvas', 4.5, 'Metadatos, ayudas, info de DataTables'],
  ['color-ink-2', 'color-surface', 4.5, 'Encabezados de tabla, texto secundario en paneles'],
  ['color-ink-2', 'color-sunken', 4.5, 'Texto de botón deshabilitado (informativo)'],
  ['color-ink', 'color-accent', 4.5, 'Texto del botón primario y página actual'],
  ['color-ink', 'color-accent-strong', 4.5, 'Botón primario en hover/active'],
  ['color-ink', 'color-accent-soft', 7, 'Token de API mostrado una vez'],
  ['color-on-vinyl', 'color-vinyl', 7, 'Texto del riel lateral (nombre, item activo)'],
  ['color-on-vinyl-2', 'color-vinyl', 4.5, 'Items del menú en reposo, correo del usuario'],
  ['color-on-vinyl', 'color-vinyl-2', 4.5, 'Item del menú activo / hover'],
  ['color-ink', 'color-accent', 4.5, 'Iniciales en la galleta (avatar)'],
  ['color-danger', 'color-surface', 4.5, 'Enlace "Eliminar" y errores de campo'],
  ['color-danger', 'color-canvas', 4.5, 'Errores sobre el fondo de página'],
  ['color-danger', 'color-danger-soft', 4.5, 'Resumen de errores / flash de error'],
  ['color-surface', 'color-danger', 4.5, 'Texto del botón Eliminar'],
  ['color-surface', 'color-danger-strong', 4.5, 'Botón Eliminar en hover/active'],
  ['color-success', 'color-success-soft', 4.5, 'Flash de éxito'],
  ['color-success', 'color-surface', 4.5, 'Texto de éxito sobre panel'],
  ['color-control', 'color-surface', 3, 'Borde de inputs y botón secundario (no texto)'],
  ['color-control', 'color-canvas', 3, 'Borde de inputs sobre el fondo de página (no texto)'],
  ['color-focus', 'color-canvas', 3, 'Anillo de foco sobre fondo de página (no texto)'],
  ['color-focus', 'color-surface', 3, 'Anillo de foco sobre panel (no texto)'],
  ['color-focus-on-vinyl', 'color-vinyl', 3, 'Anillo de foco y marca activa en el riel (no texto)'],
  ['color-ink', 'color-surface', 3, 'Borde del botón primario sobre panel (no texto)'],
  ['color-ink-2', 'color-surface', 3, 'Barras de Chart.js sobre el panel (no texto)'],
  ['color-rule', 'color-surface', 1, 'Separador de filas (decorativo, sin mínimo)'],
];

const rows = pairs.map(([fg, bg, min, use]) => {
  const r = ratio(fg, bg);
  return { fg, bg, r, min, pass: r >= min, use };
});

if (process.argv.includes('--hex')) {
  for (const name of Object.keys(raw)) {
    const v = resolve(raw[name]);
    if (!/^oklch/.test(v)) continue;
    const lin = oklchToLinear(v);
    console.log(`--${name.padEnd(22)} ${v.padEnd(26)} ${hex(lin)}${inGamut(lin) ? '' : '  (FUERA DE GAMUT sRGB, se recorta)'}`);
  }
  process.exit(0);
}

const out = [];
for (const name of Object.keys(raw)) {
  const v = resolve(raw[name]);
  if (/^oklch/.test(v) && !inGamut(oklchToLinear(v))) out.push(`--${name}`);
}
if (out.length) console.log(`Fuera de gamut sRGB: ${out.join(', ')}`);
console.log('| Texto / elemento | Fondo | Razón | Mínimo | Resultado | Uso |');
console.log('| :--- | :--- | ---: | ---: | :--- | :--- |');
for (const x of rows) console.log(`| \`--${x.fg}\` | \`--${x.bg}\` | ${x.r.toFixed(2)}:1 | ${x.min === 1 ? 'n/a' : x.min + ':1'} | ${x.pass ? 'PASA' : 'FALLA'} | ${x.use} |`);
const fails = rows.filter(x => !x.pass);
console.log(`\n${rows.length} pares medidos, ${fails.length} fallas.`);
process.exit(fails.length ? 1 : 0);
