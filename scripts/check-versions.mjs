import assert from 'node:assert/strict';
import { createHash } from 'node:crypto';

// Local GET requests and deliberately invalid POST requests only.
const base = new URL(process.env.FORT_URL || 'http://localhost:8080');
assert.ok(['localhost', '127.0.0.1', '[::1]'].includes(base.hostname), 'Use somente o WordPress local');
const jadePath = '/produtos/4796-1-2-c70-torneira-jade-bancada-bica-movel-tubo-longo-3-4-arejador-1-4-de-volta/';
const blackPath = '/produtos/torneira-opala-black-para-bancada-cuba/';
const legacyPath = '/produtos/torneira-opala-black-bica-movel-slim/';
const modelTitle = '4796 C70 Torneira Jade bica móvel tubo longo 3/4';
const definitions = new Map([
  ['arejador', { label: 'Com arejador', reference: 'F-013161', demo: false }],
  ['articulador', { label: 'Com articulador', reference: 'a confirmar', demo: true }],
]);
let checks = 0;

function decode(value) {
  return value.replace(/&#(x[\da-f]+|\d+);/gi, (_, code) => String.fromCodePoint(code[0].toLowerCase() === 'x' ? parseInt(code.slice(1), 16) : Number(code)))
    .replaceAll('&amp;', '&').replaceAll('&quot;', '"').replaceAll('&#039;', "'");
}
function attributes(tag) {
  return Object.fromEntries([...tag.matchAll(/([\w:-]+)(?:\s*=\s*(?:"([^"]*)"|'([^']*)'|([^\s>]+)))?/g)]
    .map(([, key, double, single, bare]) => [key, decode(double ?? single ?? bare ?? '')]));
}
function inputs(html) { return [...html.matchAll(/<input\b[^>]*>/g)].map(match => attributes(match[0])); }
function withId(html, id, tag = '[a-z]+') {
  const element = [...html.matchAll(new RegExp(`<${tag}\\b[^>]*>`, 'g'))].map(match => attributes(match[0])).find(attrs => attrs.id === id);
  assert.ok(element, `Elemento ${id} disponível`);
  return element;
}
function hidden(html, name) {
  const input = inputs(html).find(attrs => attrs.type === 'hidden' && attrs.name === name);
  assert.ok(input, `Campo oculto ${name}`);
  return input.value;
}
function selectedProduct(html) { return html.match(/<div class="selected-product">([\s\S]*?)<\/div>/)?.[1] || ''; }
function reference(html) {
  const block = html.match(/<p\b[^>]*id="fort-product-reference"[^>]*>([\s\S]*?)<\/p>/)?.[1];
  assert.ok(block, 'Referência do produto disponível');
  return decode(block.match(/<span>([\s\S]*?)<\/span>/)?.[1] || '');
}
function whatsappMessage(html, id = 'fort-product-whatsapp') {
  return new URL(withId(html, id, 'a').href).searchParams.get('text');
}
function contactWhatsapp(html) {
  const href = decode(html.match(/class="form-footnote">[\s\S]*?<a href="([^"]+)"/)?.[1] || '');
  return new URL(href).searchParams.get('text');
}
async function request(path) {
  let url = new URL(path, base);
  for (let redirects = 0; redirects < 8; redirects++) {
    assert.equal(url.origin, base.origin, 'As verificações não devem acessar serviços externos');
    const response = await fetch(url, { method: 'GET', redirect: 'manual', signal: AbortSignal.timeout(45000) });
    const location = response.headers.get('location');
    if (![301, 302, 303, 307, 308].includes(response.status) || !location) return response;
    url = new URL(location, url);
    await response.arrayBuffer();
  }
  throw new Error('Redirecionamentos excessivos');
}
async function page(path) {
  const response = await request(path);
  assert.equal(response.status, 200, `Página disponível: ${path}`);
  const html = await response.text();
  assert.doesNotMatch(html, /Fatal error:|Há um erro crítico|Failed opening required/, `PHP sem erros: ${path}`);
  return html;
}
function passed(label) { checks++; console.log(`OK ${label}`); }

const initial = await page(jadePath);
assert.ok(initial.includes(`<h1>${modelTitle}</h1>`), 'Título do modelo sem fixar uma versão de bica');
const options = inputs(initial).filter(attrs => attrs.type === 'radio' && attrs.name === 'versao');
assert.deepEqual(options.map(option => option.value), [...definitions.keys()]);
assert.deepEqual(options.filter(option => Object.hasOwn(option, 'checked')).map(option => option.value), ['arejador']);
assert.equal(reference(initial), 'F-013161');
assert.ok(Object.hasOwn(withId(initial, 'fort-version-demo', 'p'), 'hidden'));
assert.equal(new URL(withId(initial, 'fort-product-quote', 'a').href).searchParams.get('versao'), 'arejador');
passed('Jade com duas versões, nome do modelo e arejador inicial com referência confirmada');

const imageHashes = new Set();
for (const option of options) {
  const response = await request(option['data-image']);
  assert.equal(response.status, 200, `Foto da versão ${option.value} disponível`);
  assert.match(response.headers.get('content-type') || '', /^image\/(jpeg|png|webp)/i);
  const bytes = new Uint8Array(await response.arrayBuffer());
  assert.ok(bytes.length > 5000, `Foto da versão ${option.value} com conteúdo`);
  const signature = Buffer.from(bytes.subarray(0, 12));
  assert.ok((signature[0] === 0xff && signature[1] === 0xd8) || signature.subarray(1, 4).toString() === 'PNG' || (signature.subarray(0, 4).toString() === 'RIFF' && signature.subarray(8, 12).toString() === 'WEBP'), 'Arquivo recebido é uma imagem real');
  imageHashes.add(createHash('sha256').update(bytes).digest('hex'));
}
assert.equal(imageHashes.size, 2, 'As versões usam imagens diferentes');
passed('Fotografias das duas versões válidas, distintas e disponíveis');

for (const [key, definition] of definitions) {
  const html = await page(`${jadePath}?versao=${key}`);
  assert.ok(html.includes(`<h1>${modelTitle}</h1>`));
  const radios = inputs(html).filter(attrs => attrs.type === 'radio' && attrs.name === 'versao');
  assert.deepEqual(radios.filter(option => Object.hasOwn(option, 'checked')).map(option => option.value), [key]);
  const selected = radios.find(option => option.value === key);
  assert.equal(selected['data-label'], definition.label);
  assert.equal(selected['data-reference'], definition.reference === 'a confirmar' ? '' : definition.reference);
  assert.equal(selected['data-demo'], definition.demo ? '1' : '0');
  assert.equal(withId(html, 'fort-product-photo', 'img').src, selected['data-image']);
  assert.equal(withId(html, 'fort-product-photo', 'img').alt, `${modelTitle} — ${definition.label}`);
  assert.ok(html.includes(`<dd id="fort-version-spec">${definition.label}</dd>`));
  assert.equal(reference(html), definition.reference, 'Referência pertence à versão selecionada');
  assert.equal(Object.hasOwn(withId(html, 'fort-version-demo', 'p'), 'hidden'), !definition.demo);
  const quote = new URL(withId(html, 'fort-product-quote', 'a').href);
  assert.equal(quote.searchParams.get('produto'), '28');
  assert.equal(quote.searchParams.get('versao'), key);
  const message = whatsappMessage(html);
  assert.ok(message.includes(modelTitle) && message.includes(`Versão: ${definition.label}.`));
  assert.ok(message.includes(`Referência: ${definition.reference}.`));
  assert.ok(message.includes(`${jadePath}?versao=${key}`));
  assert.equal(message.includes('Imagem ilustrativa para teste.'), definition.demo);
  const quoteHtml = await page(quote);
  const summary = selectedProduct(quoteHtml);
  assert.ok(summary.includes(modelTitle));
  assert.ok(summary.includes(`Versão: <strong>${definition.label}</strong>`));
  assert.ok(summary.includes(`Referência: ${definition.reference}`));
  assert.equal(summary.includes('Simulação de versão para teste.'), definition.demo);
  assert.equal(hidden(quoteHtml, 'produto'), '28');
  assert.equal(hidden(quoteHtml, 'versao'), key);
  assert.equal(hidden(quoteHtml, 'acabamento'), '');
  assert.ok(hidden(quoteHtml, 'fort_quote_nonce'));
  const formMessage = contactWhatsapp(quoteHtml);
  assert.ok(formMessage.includes(modelTitle) && formMessage.includes(`Versão: ${definition.label}.`));
  assert.ok(formMessage.includes(`Referência: ${definition.reference}.`));
  assert.equal(formMessage.includes('simulação de versão para teste'), definition.demo);
  if (definition.demo) {
    assert.doesNotMatch(summary, /F-013161/, 'Articulador não herda o SKU do arejador');
    assert.doesNotMatch(formMessage, /F-013161/);
  }
}
passed('Versões atualizam foto, ficha, referência, aviso, orçamento e WhatsApp sem herdar SKU');

for (const query of ['versao=nao-existe', 'versao%5B%5D=articulador']) {
  const html = await page(`${jadePath}?${query}`);
  const checked = inputs(html).filter(attrs => attrs.name === 'versao' && Object.hasOwn(attrs, 'checked'));
  assert.deepEqual(checked.map(option => option.value), ['arejador']);
  assert.equal(reference(html), 'F-013161');
}
for (const query of ['produto=28&versao=nao-existe', 'produto=28&versao%5B%5D=articulador', 'produto=28']) {
  const html = await page(`/fale-com-a-gente/?${query}`);
  assert.equal(hidden(html, 'produto'), '28');
  assert.equal(hidden(html, 'versao'), '');
  assert.ok(selectedProduct(html).includes(modelTitle));
  assert.doesNotMatch(selectedProduct(html), /Versão:|Simulação de versão/);
}
const legacy = await page(`${legacyPath}?versao=articulador`);
assert.match(legacy, /<h1>Torneira Opala Black bica móvel Slim<\/h1>/);
assert.doesNotMatch(legacy, /id="fort-version-picker"|type="radio" name="versao"/);
assert.equal(new URL(withId(legacy, 'fort-product-quote', 'a').href).searchParams.get('versao'), null);
const legacyQuote = await page('/fale-com-a-gente/?produto=17&versao=articulador');
assert.equal(hidden(legacyQuote, 'produto'), '17');
assert.equal(hidden(legacyQuote, 'versao'), '');
assert.doesNotMatch(selectedProduct(legacyQuote), /Versão:|Simulação de versão/);
passed('Consultas inválidas têm retorno seguro e produtos sem versões continuam funcionando');

const colors = await page(`${blackPath}?acabamento=rose`);
const colorOptions = inputs(colors).filter(attrs => attrs.type === 'radio' && attrs.name === 'acabamento');
assert.deepEqual(colorOptions.map(option => option.value), ['cromado', 'black', 'aco-corten', 'rose', 'grafite']);
assert.deepEqual(colorOptions.filter(option => Object.hasOwn(option, 'checked')).map(option => option.value), ['rose']);
assert.equal(reference(colors), 'a confirmar');
assert.doesNotMatch(colors, /id="fort-version-picker"/);
assert.ok(whatsappMessage(colors).includes('Referência: a confirmar.'));
for (const option of colorOptions) {
  assert.equal(option['data-reference'], '', 'Referência deve permanecer vazia até confirmação de cada acabamento');
  assert.ok(new URL(option['data-whatsapp']).searchParams.get('text').includes('Referência: a confirmar.'));
  assert.equal(new URL(option['data-quote']).searchParams.get('acabamento'), option.value);
}
const colorQuote = await page('/fale-com-a-gente/?produto=9&acabamento=rose');
assert.equal(hidden(colorQuote, 'produto'), '9');
assert.equal(hidden(colorQuote, 'acabamento'), 'rose');
assert.equal(hidden(colorQuote, 'versao'), '');
assert.ok(selectedProduct(colorQuote).includes('Acabamento: <strong>Rose</strong>'));
assert.ok(selectedProduct(colorQuote).includes('Referência: a confirmar'));
assert.ok(contactWhatsapp(colorQuote).includes('Referência: a confirmar.'));
passed('Cinco acabamentos anteriores preservados e referências não confirmadas identificadas');

const nonce = hidden(legacyQuote, 'fort_quote_nonce');
for (const test of [
  { product: '28', key: 'versao', value: '__invalid_version__' },
  { product: '28', key: 'versao[]', value: 'articulador' },
  { product: '17', key: 'versao', value: 'articulador' },
]) {
  const body = new URLSearchParams({
    action: 'fort_orcamento', fort_quote_nonce: nonce, produto: test.product,
    nome: 'Validação local de versão inválida', email: 'teste@example.invalid', telefone: '(44) 99999-0000',
    cidade: 'Teste local / PR', mensagem: 'Solicitação inválida para validar rejeição, sem cadastro.', website: '', consentimento: '1',
  });
  body.set(test.key, test.value);
  const response = await fetch(new URL('/wp-admin/admin-post.php', base), { method: 'POST', body, redirect: 'manual', signal: AbortSignal.timeout(45000) });
  assert.ok([302, 303].includes(response.status), 'Versão inválida rejeitada com redirecionamento');
  const location = new URL(response.headers.get('location'), base);
  assert.equal(location.origin, base.origin);
  assert.equal(location.pathname, '/fale-com-a-gente/');
  assert.equal(location.searchParams.get('envio'), 'invalido', `Rejeitar ${test.key}=${test.value} para produto ${test.product}`);
  await response.arrayBuffer();
}
passed('POSTs com versão desconhecida, array ou produto incorreto rejeitados antes do salvamento');

console.log(`\n${checks} verificações de versões concluídas. Nenhum orçamento válido foi enviado.`);
