import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const base = process.env.FORT_URL || 'http://localhost:8080';
let checks = 0;
const cookies = new Map();
async function request(path, options = {}) {
  let url = new URL(path, base);
  let method = options.method || 'GET';
  let body = options.body;
  for (let redirects = 0; redirects < 10; redirects++) {
    assert.equal(url.origin, new URL(base).origin, 'O teste deve permanecer no ambiente local');
    const headers = new Headers(options.headers);
    if (cookies.size) headers.set('cookie', [...cookies].map(([key, value]) => `${key}=${value}`).join('; '));
    const response = await fetch(url, { ...options, headers, method, body, redirect:'manual', signal:AbortSignal.timeout(45000) });
    for (const value of response.headers.getSetCookie()) {
      const pair = value.split(';', 1)[0]; const separator = pair.indexOf('=');
      cookies.set(pair.slice(0, separator), pair.slice(separator + 1));
    }
    const location = response.headers.get('location');
    if (![301,302,303,307,308].includes(response.status) || !location) return response;
    url = new URL(location, url);
    if (response.status === 303 || ((response.status === 301 || response.status === 302) && method === 'POST')) { method = 'GET'; body = undefined; }
    await response.arrayBuffer();
  }
  throw new Error('Redirecionamentos excessivos no ambiente local');
}
async function page(path, expectedStatus = 200) {
  const response = await request(path);
  assert.equal(response.status, expectedStatus, `${path}: HTTP ${response.status}`);
  const body = await response.text();
  assert.ok(!/Fatal error:|Há um erro crítico|Failed opening required/.test(body), `Falha de PHP em ${path}`);
  return body;
}
function passed(label) { checks++; console.log(`OK ${label}`); }
const home = await page('/');
assert.match(home, /id="hero-title"/);
assert.equal((home.match(/<article class="product-card"/g) || []).length, 4);
assert.doesNotMatch(home, /href="#"/);
passed('Página inicial, quatro destaques e links definidos');
const all = await page('/produtos/');
assert.ok((all.match(/<article class="product-card"/g) || []).length >= 7);
passed('Catálogo com os produtos cadastrados');
const filtered = await page('/produtos/?linha=opala-black&ambiente=banheiro');
assert.match(filtered, /produtos? encontrados?/);
assert.match(filtered, /Torneira Opala Black para bancada/);
assert.doesNotMatch(filtered, /<h3>.*bica móvel Slim/);
passed('Filtros combinados de linha e ambiente');
const search = await page('/produtos/?q=Ametista');
assert.ok(Number(search.match(/<strong>(\d+)<\/strong>/)?.[1]) >= 1);
assert.match(search, /Torneira Ametista/);
passed('Busca por nome de produto');
const noResults = await page('/produtos/?q=produto-inexistente-fort-98765');
assert.match(noResults, /Nenhum produto foi encontrado/);
assert.doesNotMatch(noResults, /<article class="product-card"/);
passed('Busca sem resultados');
const detailMatch = all.match(/class="product-image" href="([^"]+)"/);
assert.ok(detailMatch, 'Link de ficha do produto');
const detail = await page(detailMatch[1]);
assert.match(detail, /Solicitar orçamento/);
const quote = detail.match(/id="fort-product-quote" href="([^"]+)"/);
assert.ok(quote, 'Orçamento associado ao produto');
const quoteUrl = quote[1].replace(/&amp;|&#0*38;|&#x26;/gi, '&');
assert.ok(new URL(quoteUrl, base).searchParams.get('produto'), 'O orçamento deve identificar o produto');
const quotePage = await page(quoteUrl);
assert.match(quotePage, /Produto de interesse/);
assert.match(quotePage, /name="fort_quote_nonce"/);
passed('Ficha de produto e orçamento com o item selecionado');
await page('/institucional/'); await page('/representantes/?uf=PR'); await page('/privacidade/');
passed('Institucional, representantes e privacidade');
const environments = await page('/ambientes/');
assert.match(environments, /Ambientes que inspiram/);
assert.ok((environments.match(/class="fort-environment-card"/g) || []).length >= 5);
const sceneReferences = [
  ['lavabo-contemporaneo-opala-black', 'F-400137'],
  ['texturas-naturais-rubi-aco-corten', 'F-400135'],
  ['leveza-e-praticidade-ametista', 'F-350002'],
  ['elegancia-em-tons-quentes-opala-rose', 'F-400146'],
];
for (const [slug, reference] of sceneReferences) {
  const scene = await page(`/inspiracao/${slug}/`);
  assert.match(scene, /class="fort-scene-pin"/);
  assert.match(scene, new RegExp(reference));
  assert.match(scene, /Conheça o produto/);
  const match = scene.match(/class="text-link" href="([^"]+)">Conheça o produto/);
  assert.ok(match, `Produto vinculado em ${slug}`);
  const linkedProduct = await page(match[1]);
  assert.match(linkedProduct, new RegExp(reference));
}
const homeScene = await page('/inspiracao/sofisticacao-em-tons-escuros-opala-black-slim/');
assert.match(homeScene, /id="scene-product-17"/);
assert.match(homeScene, /Torneira Opala Black bica móvel Slim/);
passed('Cinco ambientes com marcadores e produtos corretamente vinculados');
const rubiScenes = await page('/ambientes/?linha=rubi&ambiente=banheiro');
assert.equal((rubiScenes.match(/class="fort-environment-card"/g) || []).length, 1);
assert.match(rubiScenes, /Texturas naturais/);
const emptyScenes = await page('/ambientes/?linha=linha-inexistente-fort');
assert.match(emptyScenes, /Ainda não há ambientes publicados/);
assert.doesNotMatch(emptyScenes, /class="fort-environment-card"/);
await page('/ambientes/banheiro/');
for (const reference of sceneReferences.map(([, reference]) => reference)) {
  assert.match(await page(`/produtos/?q=${reference}`), /<strong>1<\/strong>/);
}
passed('Filtros de ambientes, busca por referência e classificação anterior preservada');
const access = JSON.parse(readFileSync(new URL('../.runtime/acesso-local.json', import.meta.url), 'utf8'));
await request('/wp-login.php');
const loginResponse = await request('/wp-login.php', {method:'POST',body:new URLSearchParams({log:access.usuario,pwd:access.senha,'wp-submit':'Acessar',redirect_to:`${base}/wp-admin/edit.php?post_type=fort_produto`,testcookie:'1'})});
assert.equal(loginResponse.status, 200);
const admin = await loginResponse.text();
assert.match(admin, /Adicionar produto/);
passed('Painel de produtos disponível');
await page('/pagina-inexistente-fort-98765/', 404);
passed('Resposta 404 para endereços inexistentes');
const invalid = await request('/wp-admin/admin-post.php', { method:'POST', body:new URLSearchParams({action:'fort_orcamento',fort_quote_nonce:'invalido'}) });
assert.equal(invalid.status, 403);
passed('Formulário rejeita solicitações sem nonce válido');
const media = [...new Set([...home.matchAll(/(?:src|href)="(http:\/\/localhost:\d+\/(?:wp-content\/themes\/fort-metais\/assets|wp-content\/uploads)\/[^"?]+)(?:\?[^\"]*)?"/g)].map(m => m[1]))];
for (const url of media) { const response = await request(url); assert.equal(response.status, 200, `Recurso indisponível: ${url}`); }
passed(`${media.length} arquivos de imagem, estilo e interação disponíveis`);
console.log(`\n${checks} verificações concluídas.`);
