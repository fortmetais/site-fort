import assert from 'node:assert/strict';
import { createHash } from 'node:crypto';

// Read-only pages plus rejected quotation requests. No valid form is submitted.
const base = new URL(process.env.FORT_URL || 'http://localhost:8080');
assert.ok(['localhost', '127.0.0.1', '[::1]'].includes(base.hostname), 'Use somente o WordPress local');
const productPath = '/produtos/torneira-opala-black-para-bancada-cuba/';
const legacyPath = '/produtos/torneira-opala-black-bica-movel-slim/';
const modelTitle = 'Torneira Opala para bancada — cuba';
const finishes = new Map([
  ['cromado', 'Cromado'], ['black', 'Black'], ['aco-corten', 'Aço Corten'],
  ['rose', 'Rose'], ['grafite', 'Grafite'],
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
function withId(html, id, tag = '[a-z]+') {
  const element = [...html.matchAll(new RegExp(`<${tag}\\b[^>]*>`, 'g'))].map(match => attributes(match[0])).find(attrs => attrs.id === id);
  assert.ok(element, `Elemento ${id} disponível`);
  return element;
}
function inputs(html) {
  return [...html.matchAll(/<input\b[^>]*>/g)].map(match => attributes(match[0]));
}
function hidden(html, name) {
  const input = inputs(html).find(attrs => attrs.type === 'hidden' && attrs.name === name);
  assert.ok(input, `Campo oculto ${name}`);
  return input.value;
}
function selectedProduct(html) {
  return html.match(/<div class="selected-product">([\s\S]*?)<\/div>/)?.[1] || '';
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

const initial = await page(productPath);
assert.match(initial, new RegExp(`<h1>${modelTitle}</h1>`));
const options = inputs(initial).filter(attrs => attrs.type === 'radio' && attrs.name === 'acabamento');
assert.equal(options.length, finishes.size, 'Cinco opções de acabamento');
assert.deepEqual(options.map(option => option.value), [...finishes.keys()]);
assert.deepEqual(options.filter(option => Object.hasOwn(option, 'checked')).map(option => option.value), ['black']);
assert.ok(Object.hasOwn(withId(initial, 'fort-finish-demo', 'p'), 'hidden'), 'Black original sem aviso de simulação visível');
passed('Ficha com nome do modelo, cinco cores e Black como acabamento inicial');

const images = new Set();
for (const option of options) {
  const response = await request(option['data-image']);
  assert.equal(response.status, 200, `Foto ${option.value} disponível`);
  assert.match(response.headers.get('content-type') || '', /^image\/(jpeg|png|webp)/i);
  const bytes = new Uint8Array(await response.arrayBuffer());
  assert.ok(bytes.length > 5000, `Foto ${option.value} com conteúdo`);
  const signature = Buffer.from(bytes.subarray(0, 12));
  assert.ok((signature[0] === 0xff && signature[1] === 0xd8) || signature.subarray(1, 4).toString() === 'PNG' || (signature.subarray(0, 4).toString() === 'RIFF' && signature.subarray(8, 12).toString() === 'WEBP'), `Arquivo ${option.value} é imagem real`);
  images.add(createHash('sha256').update(bytes).digest('hex'));
}
assert.equal(images.size, finishes.size, 'Cada acabamento tem uma imagem diferente');
passed('Cinco fotografias distintas, válidas e disponíveis');

for (const [key, label] of finishes) {
  const html = await page(`${productPath}?acabamento=${key}`);
  assert.match(html, new RegExp(`<h1>${modelTitle}</h1>`));
  const radios = inputs(html).filter(attrs => attrs.type === 'radio' && attrs.name === 'acabamento');
  assert.deepEqual(radios.filter(option => Object.hasOwn(option, 'checked')).map(option => option.value), [key]);
  const selected = radios.find(option => option.value === key);
  assert.equal(selected['data-label'], label);
  assert.equal(selected['data-demo'], key === 'black' ? '0' : '1');
  assert.equal(withId(html, 'fort-product-photo', 'img').src, selected['data-image']);
  assert.equal(withId(html, 'fort-product-photo', 'img').alt, `${modelTitle} — acabamento ${label}`);
  assert.match(html, new RegExp(`<dd id="fort-finish-spec">${label}</dd>`));
  assert.equal(Object.hasOwn(withId(html, 'fort-finish-demo', 'p'), 'hidden'), key === 'black');
  const quote = new URL(withId(html, 'fort-product-quote', 'a').href);
  assert.equal(quote.searchParams.get('produto'), '9');
  assert.equal(quote.searchParams.get('acabamento'), key);
  const whatsapp = new URL(withId(html, 'fort-product-whatsapp', 'a').href).searchParams.get('text');
  assert.ok(whatsapp.includes(modelTitle) && whatsapp.includes(`Acabamento: ${label}.`));
  assert.ok(whatsapp.includes(`${productPath}?acabamento=${key}`), 'WhatsApp compartilha o acabamento selecionado');
  assert.equal(whatsapp.includes('Simulação para teste.'), key !== 'black');
  const quoteHtml = await page(quote);
  const summary = selectedProduct(quoteHtml);
  assert.ok(summary.includes(modelTitle), 'Orçamento usa o nome genérico do modelo');
  assert.ok(summary.includes(`Acabamento: <strong>${label}</strong>`));
  assert.equal(summary.includes('Simulação de acabamento para teste.'), key !== 'black');
  assert.equal(hidden(quoteHtml, 'produto'), '9');
  assert.equal(hidden(quoteHtml, 'acabamento'), key);
  assert.ok(hidden(quoteHtml, 'fort_quote_nonce'), 'Nonce preservado no formulário');
  const formWhatsapp = decode(quoteHtml.match(/class="form-footnote">[\s\S]*?<a href="([^"]+)"/)?.[1] || '');
  const formMessage = new URL(formWhatsapp).searchParams.get('text');
  assert.ok(formMessage.includes(modelTitle) && formMessage.includes(`Acabamento: ${label}.`));
  assert.equal(formMessage.includes('simulação de acabamento'), key !== 'black');
}
passed('Cinco links de cor atualizam foto, ficha, orçamento e mensagem do WhatsApp');

for (const query of ['acabamento=nao-existe', 'acabamento%5B%5D=rose', 'acabamento=javascript%3Aalert(1)']) {
  const html = await page(`${productPath}?${query}`);
  const checked = inputs(html).filter(attrs => attrs.name === 'acabamento' && Object.hasOwn(attrs, 'checked'));
  assert.deepEqual(checked.map(option => option.value), ['black']);
  assert.equal(new URL(withId(html, 'fort-product-quote', 'a').href).searchParams.get('acabamento'), 'black');
}
for (const query of ['produto=9&acabamento=nao-existe', 'produto=9&acabamento%5B%5D=rose', 'produto=9']) {
  const html = await page(`/fale-com-a-gente/?${query}`);
  assert.equal(hidden(html, 'produto'), '9');
  assert.equal(hidden(html, 'acabamento'), '');
  assert.ok(selectedProduct(html).includes(modelTitle));
  assert.doesNotMatch(selectedProduct(html), /Acabamento:|Simulação de acabamento/);
}
passed('Consultas inválidas têm retorno seguro e orçamento antigo permanece disponível');

for (const query of ['', '?acabamento=rose']) {
  const legacy = await page(legacyPath + query);
  assert.match(legacy, /<h1>Torneira Opala Black bica móvel Slim<\/h1>/);
  assert.doesNotMatch(legacy, /id="fort-finish-picker"|type="radio" name="acabamento"/);
  const quote = new URL(withId(legacy, 'fort-product-quote', 'a').href);
  assert.equal(quote.searchParams.get('produto'), '17');
  assert.equal(quote.searchParams.get('acabamento'), null);
}
const legacyQuote = await page('/fale-com-a-gente/?produto=17&acabamento=rose');
assert.equal(hidden(legacyQuote, 'produto'), '17');
assert.equal(hidden(legacyQuote, 'acabamento'), '');
assert.ok(selectedProduct(legacyQuote).includes('Torneira Opala Black bica móvel Slim'));
assert.doesNotMatch(selectedProduct(legacyQuote), /Acabamento:|Simulação de acabamento/);
const general = await page('/fale-com-a-gente/?produto=999999999&acabamento=rose');
assert.equal(hidden(general, 'produto'), '0');
assert.equal(hidden(general, 'acabamento'), '');
assert.equal(selectedProduct(general), '');
passed('Produto sem variações e contato geral preservados');

const quoteNonce = hidden(general, 'fort_quote_nonce');
for (const [product, finish] of [['9', '__invalid_finish__'], ['17', 'rose']]) {
  const body = new URLSearchParams({
    action: 'fort_orcamento', fort_quote_nonce: quoteNonce, produto: product, acabamento: finish,
    nome: 'Validação local de acabamento inválido', email: 'teste@example.invalid',
    telefone: '(44) 99999-0000', cidade: 'Teste local / PR', mensagem: 'Solicitação inválida para testar rejeição, sem cadastro.',
    website: '', consentimento: '1',
  });
  const response = await fetch(new URL('/wp-admin/admin-post.php', base), {
    method: 'POST', body, redirect: 'manual', signal: AbortSignal.timeout(45000),
  });
  assert.ok([302, 303].includes(response.status), `Acabamento inválido ${finish} deve ser rejeitado com redirecionamento`);
  const location = new URL(response.headers.get('location'), base);
  assert.equal(location.origin, base.origin);
  assert.equal(location.pathname, '/fale-com-a-gente/');
  assert.equal(location.searchParams.get('envio'), 'invalido', `Não salvar solicitação com produto ${product} e acabamento ${finish}`);
  await response.arrayBuffer();
}
passed('POSTs com chave inválida e acabamento de outro produto são rejeitados antes do salvamento');

console.log(`\n${checks} verificações de acabamentos concluídas. Nenhum orçamento válido foi enviado.`);
