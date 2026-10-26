#!/usr/bin/env node
// Gate S4 (specs/06_GATES.md): prueba de humo de producción, sin dependencias.
// Uso: node scripts/gates/smoke.mjs https://laravel.helx.page
// Imprime "smoke passed" solo si todo cumple; si no, lista cada falla y sale con 1.

const base = process.argv[2]?.replace(/\/+$/, '');
if (!base) {
  console.error('uso: node scripts/gates/smoke.mjs <url-base>');
  process.exit(2);
}

const fallas = [];
const falla = (msg) => fallas.push(msg);

if (!base.startsWith('https://')) falla(`la URL base no es HTTPS: ${base}`);

async function pedir(ruta) {
  try {
    // redirect manual: un 302 en /login o en la API es una falla, no algo a seguir.
    return await fetch(base + ruta, { redirect: 'manual', signal: AbortSignal.timeout(15000) });
  } catch (e) {
    falla(`GET ${ruta}: sin respuesta (${e.cause?.code ?? e.message})`);
    return null;
  }
}

// 1. /up y /login responden 200.
const up = await pedir('/up');
if (up && up.status !== 200) falla(`GET /up respondió ${up.status}, se esperaba 200`);

const login = await pedir('/login');
if (login && login.status !== 200) falla(`GET /login respondió ${login.status}, se esperaba 200`);

// 2. Las cinco cabeceras de 05 §5 en /login.
if (login) {
  const h = login.headers;
  const csp = (h.get('content-security-policy') ?? '').split(';').map((d) => d.trim().replace(/\s+/g, ' '));
  for (const directiva of ["default-src 'self'", "script-src 'self'", "img-src 'self' data:", "frame-ancestors 'none'"]) {
    if (!csp.includes(directiva)) falla(`CSP sin la directiva exacta "${directiva}" (recibida: "${h.get('content-security-policy')}")`);
  }
  const exactas = {
    'x-content-type-options': 'nosniff',
    'x-frame-options': 'DENY',
    'referrer-policy': 'strict-origin-when-cross-origin',
    'strict-transport-security': 'max-age=31536000',
  };
  for (const [nombre, valor] of Object.entries(exactas)) {
    if (h.get(nombre) !== valor) falla(`${nombre}: se esperaba "${valor}", llegó "${h.get(nombre)}"`);
  }

  // 3. Cookies: todas Secure y SameSite=Lax; la de sesión además HttpOnly.
  const cookies = h.getSetCookie().map((linea) => {
    const [par, ...attrs] = linea.split(';').map((p) => p.trim());
    return { nombre: par.split('=')[0], attrs: attrs.map((a) => a.toLowerCase()), linea };
  });
  const xsrf = cookies.find((c) => c.nombre === 'XSRF-TOKEN');
  const sesion = cookies.find((c) => c.nombre !== 'XSRF-TOKEN');
  if (!xsrf) falla('GET /login no envió la cookie XSRF-TOKEN');
  if (!sesion) falla('GET /login no envió la cookie de sesión');
  for (const c of cookies) {
    if (!c.attrs.includes('secure')) falla(`la cookie ${c.nombre} no lleva Secure`);
    if (!c.attrs.includes('samesite=lax')) falla(`la cookie ${c.nombre} no lleva SameSite=Lax`);
  }
  if (sesion && !sesion.attrs.includes('httponly')) falla(`la cookie de sesión ${sesion.nombre} no lleva HttpOnly`);
}

// 4. La API sin token y sin Accept (fetch manda */*, igual que Postman) da 401 en JSON.
const api = await pedir('/api/v1/artists');
if (api) {
  if (api.status !== 401) falla(`GET /api/v1/artists sin token respondió ${api.status}, se esperaba 401`);
  const tipo = api.headers.get('content-type') ?? '';
  if (!tipo.startsWith('application/json')) falla(`GET /api/v1/artists sin token respondió con Content-Type "${tipo}", se esperaba JSON`);
  try {
    JSON.parse(await api.text());
  } catch {
    falla('GET /api/v1/artists sin token: el cuerpo no es JSON válido');
  }
}

if (fallas.length > 0) {
  console.error(`smoke failed (${fallas.length}):`);
  for (const f of fallas) console.error(`- ${f}`);
  process.exit(1);
}
console.log('smoke passed');
