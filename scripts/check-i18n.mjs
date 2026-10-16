import assert from 'node:assert/strict';

// Local read-only checks, plus one deliberately rejected quotation request.
// Never send a valid request or contact an external service from this script.
const base = new URL(process.env.FORT_URL || 'http://localhost:8080');
assert.ok(['localhost', '127.0.0.1', '[::1]'].includes(base.hostname), 'Use somente o WordPress local');
const opalaPath = '/produtos/torneira-opala-black-para-bancada-cuba/';
const jadePath = '/produtos/4796-1-2-c70-torneira-jade-bancada-bica-movel-tubo-longo-3-4-arejador-1-4-de-volta/';
const legacyPath = '/produtos/torneira-opala-black-bica-movel-slim/';
let checks = 0;

const entities = {
  amp: '&', quot: '"', apos: "'", nbsp: ' ', ndash: '–', mdash: '—', hellip: '…',
  lsquo: '‘', rsquo: '’', ldquo: '“', rdquo: '”', laquo: '«', raquo: '»',
  aacute: 'á', eacute: 'é', iacute: 'í', oacute: 'ó', uacute: 'ú',
  Aacute: 'Á', Eacute: 'É', Iacute: 'Í', Oacute: 'Ó', Uacute: 'Ú',
  acirc: 'â', ecirc: 'ê', ocirc: 'ô', Acirc: 'Â', Ecirc: 'Ê', Ocirc: 'Ô',
  agrave: 'à', Agrave: 'À', atilde: 'ã', otilde: 'õ', Atilde: 'Ã', Otilde: 'Õ',
  ccedil: 'ç', Ccedil: 'Ç', ntilde: 'ñ', Ntilde: 'Ñ', uuml: 'ü', Uuml: 'Ü',
  iexcl: '¡', iquest: '¿', copy: '©', reg: '®', trade: '™',
};
function decode(value = '') {
  return value.replace(/&(#x[\da-f]+|#\d+|[a-z][a-z\d]+);/gi, (entity, code) => {
    if (code.startsWith('#')) return String.fromCodePoint(code[1].toLowerCase() === 'x' ? parseInt(code.slice(2), 16) : Number(code.slice(1)));
    return entities[code] ?? entity;
  });
}
function text(value) { return decode(value.replace(/<[^>]*>/g, '')).replace(/\s+/g, ' ').trim(); }
function attributes(tag) {
  return Object.fromEntries([...tag.matchAll(/([\w:-]+)(?:\s*=\s*(?:"([^"]*)"|'([^']*)'|([^\s>]+)))?/g)]
    .map(([, key, double, single, bare]) => [key.toLowerCase(), decode(double ?? single ?? bare ?? '')]));
}
function tags(html, tag) { return [...html.matchAll(new RegExp(`<${tag}\\b[^>]*>`, 'gi'))].map(match => attributes(match[0])); }
function withId(html, id, tag = '[a-z]+') {
  const element = tags(html, tag).find(item => item.id === id);
  assert.ok(element, `Elemento ${id} disponível`);
  return element;
}
function hidden(html, name) {
  const field = tags(html, 'input').find(item => item.type === 'hidden' && item.name === name);
  assert.ok(field, `Campo oculto ${name} disponível`);
  return field.value;
}
function forms(html) {
  return [...html.matchAll(/(<form\b[^>]*>)([\s\S]*?)<\/form>/gi)].map(([, tag, inner]) => ({ attrs: attributes(tag), inner }));
}
function htmlLanguage(html) { return tags(html, 'html')[0]?.lang; }
function reference(html) {
  const block = html.match(/<p\b[^>]*id="fort-product-reference"[^>]*>([\s\S]*?)<\/p>/)?.[1];
  assert.ok(block, 'Referência do produto disponível');
  return text(block.match(/<span>([\s\S]*?)<\/span>/)?.[1] || '');
}
function selected(html, name) { return tags(html, 'input').filter(item => item.name === name && Object.hasOwn(item, 'checked')).map(item => item.value); }
function passed(label) { checks++; console.log(`OK ${label}`); }

async function request(path, cookies = null, options = {}) {
  let url = new URL(path, base);
  for (let redirects = 0; redirects < 8; redirects++) {
    assert.equal(url.origin, base.origin, 'Nenhuma verificação pode sair do servidor local');
    const headers = { ...options.headers };
    if (cookies?.size) headers.cookie = [...cookies].map(([name, value]) => `${name}=${value}`).join('; ');
    const response = await fetch(url, { ...options, headers, redirect: 'manual', signal: AbortSignal.timeout(45000) });
    if (cookies) {
      for (const cookie of response.headers.getSetCookie()) {
        const match = cookie.match(/^([^=;]+)=([^;]*)/);
        if (match) cookies.set(match[1], match[2]);
      }
    }
    const location = response.headers.get('location');
    if (options.method === 'POST' || ![301, 302, 303, 307, 308].includes(response.status) || !location) return response;
    url = new URL(location, url);
    await response.arrayBuffer();
  }
  throw new Error('Redirecionamentos excessivos');
}
async function page(path, cookies = null) {
  const response = await request(path, cookies);
  assert.equal(response.status, 200, `Página disponível: ${path}`);
  const html = await response.text();
  assert.doesNotMatch(html, /(?:Fatal error|Warning|Notice|Deprecated):|<b>(?:Fatal error|Warning|Notice|Deprecated)<\/b>|Há um erro crítico|Failed opening required/i, `Sem avisos PHP: ${path}`);
  assert.match(html, /<html\b/i);
  return html;
}
function assertSelectors(html, currentPath) {
  const current = new URL(currentPath, base);
  const links = [...html.matchAll(/(<a\b[^>]*data-fort-language[^>]*>)([\s\S]*?)<\/a>/g)]
    .map(([, tag, inner]) => ({ ...attributes(tag), text: text(inner) }));
  assert.equal(links.length, 3, 'Três opções de idioma no menu');
  const autonyms = new Map([['pt-br', 'Português (BR)'], ['en', 'English'], ['es', 'Español']]);
  for (const [language, label] of autonyms) {
    const link = links.find(item => item['data-fort-language'] === language);
    assert.ok(link, `Opção de idioma ${language}`);
    assert.equal(link.text, label, 'Os nomes dos idiomas não devem ser traduzidos');
    const target = new URL(link.href);
    assert.equal(target.origin, base.origin);
    assert.equal(target.pathname, current.pathname, 'Trocar idioma mantém a página atual');
    assert.equal(target.searchParams.get('lang'), language);
    for (const [name, value] of current.searchParams) if (name !== 'lang') assert.equal(target.searchParams.get(name), value, `Parâmetro ${name} preservado na troca de idioma`);
  }
  assert.equal(new Set(links.map(item => item.href)).size, 3);
  const alternates = tags(html, 'link').filter(item => item.rel === 'alternate' && ['pt-BR', 'en', 'es'].includes(item.hreflang));
  assert.equal(alternates.length, 3, 'Hreflang para os três idiomas');
  assert.equal(new Set(alternates.map(item => item.href)).size, 3, 'Hreflang mantém destinos distintos');
  for (const item of alternates) assert.equal(new URL(item.href).searchParams.get('lang'), item.hreflang === 'pt-BR' ? 'pt-br' : item.hreflang);
}
function assertLocalizedForms(html, language) {
  for (const form of forms(html)) {
    assert.equal(hidden(form.inner, 'lang'), language, 'Formulários nativos preservam o idioma');
    if (form.attrs.method?.toLowerCase() === 'get') assert.equal(new URL(form.attrs.action, base).searchParams.get('lang'), language);
  }
}

const cookies = new Map();
const portuguese = await page('/', cookies);
assert.equal(htmlLanguage(portuguese), 'pt-BR');
assert.match(text(portuguese.match(/<h1\b[^>]*>([\s\S]*?)<\/h1>/)?.[1] || ''), /Desde 1999, inspirando inovação e praticidade/);
assertSelectors(portuguese, '/');
passed('Português do Brasil é o padrão sem cookie e o seletor possui três destinos distintos');

const english = await page('/?lang=en', cookies);
assert.equal(htmlLanguage(english), 'en');
assert.equal(cookies.get('fort_lang'), 'en', 'Escolha em inglês grava a preferência');
assert.match(text(english.match(/<title>([\s\S]*?)<\/title>/)?.[1] || ''), /Design and quality for every space/);
assert.match(tags(english, 'meta').find(item => item.name === 'description')?.content || '', /bathroom and kitchen fittings/);
const englishNav = english.match(/<nav\b[^>]*id="main-nav"[^>]*>([\s\S]*?)<\/nav>/)?.[1] || '';
assert.match(text(englishNav), /Home.*About us.*Products.*Spaces.*Representatives/);
assertLocalizedForms(english, 'en');
const internalAbout = tags(englishNav, 'a').find(item => new URL(item.href, base).hash === '#institucional');
assert.equal(new URL(internalAbout.href).searchParams.get('lang'), 'en');
const followed = await page(internalAbout.href, cookies);
assert.equal(htmlLanguage(followed), 'en');
const remembered = await page('/', cookies);
assert.equal(htmlLanguage(remembered), 'en', 'Navegação sem query respeita a preferência');
passed('Inglês traduz menu, título e descrição; links e preferência persistem durante a navegação');

const spanish = await page('/?lang=es', cookies);
assert.equal(htmlLanguage(spanish), 'es');
assert.equal(cookies.get('fort_lang'), 'es');
assert.match(text(spanish.match(/<h1\b[^>]*>([\s\S]*?)<\/h1>/)?.[1] || ''), /Desde 1999, inspirando innovación y practicidad/);
assertLocalizedForms(spanish, 'es');
const reset = await page('/?lang=pt-br', cookies);
assert.equal(htmlLanguage(reset), 'pt-BR');
assert.equal(cookies.get('fort_lang'), 'pt-br');
for (const invalid of ['/?lang=fr', '/?lang%5B%5D=en&lang%5B%5D=es']) {
  const html = await page(invalid, new Map([['fort_lang', 'en']]));
  assert.equal(htmlLanguage(html), 'pt-BR', 'Idioma inválido retorna ao português mesmo com preferência anterior');
}
passed('Espanhol, retorno ao português e parâmetros inválidos usam seleção segura');

const institutionalEnglish = await page('/institucional/?lang=en');
const institutionalSpanish = await page('/institucional/?lang=es');
assert.match(text(institutionalEnglish), /OUR ESSENCE.*Quality that spans generations/);
assert.match(text(institutionalEnglish), /Mission.*Vision.*Values/);
assert.match(text(institutionalSpanish), /NUESTRA ESENCIA.*Calidad que trasciende generaciones/);
assert.match(text(institutionalSpanish), /Misión.*Visión.*Valores/);
for (const [language, heading, body] of [
  ['en', 'Privacy', /information submitted through the form is recorded/],
  ['es', 'Privacidad', /información del formulario se registra/],
]) {
  const html = await page(`/privacidade/?lang=${language}`);
  assert.equal(htmlLanguage(html), language);
  assert.equal(text(html.match(/<h1\b[^>]*>([\s\S]*?)<\/h1>/)?.[1] || ''), heading);
  assert.match(text(html), body);
}
passed('Institucional, compromissos e privacidade têm conteúdo em inglês e espanhol');

const complexPath = '/fale-com-a-gente/?produto=9&acabamento=rose&versao=articulador&linha=opala-black&ambiente=banheiro&q=minha%20busca%20original&lang=en';
const contact = await page(complexPath);
assertSelectors(contact, complexPath);
assertLocalizedForms(contact, 'en');
const quoteForm = forms(contact).find(form => form.attrs.method === 'post');
assert.ok(quoteForm, 'Formulário de orçamento preservado');
assert.equal(new URL(quoteForm.attrs.action).pathname, '/wp-admin/admin-post.php');
assert.equal(new URL(quoteForm.attrs.action).searchParams.get('lang'), null, 'Endpoint administrativo não é reescrito');
assert.equal(hidden(quoteForm.inner, 'action'), 'fort_orcamento');
assert.equal(hidden(quoteForm.inner, 'produto'), '9');
assert.equal(hidden(quoteForm.inner, 'acabamento'), 'rose');
assert.equal(hidden(quoteForm.inner, 'versao'), '', 'Versão que não pertence ao produto permanece inválida');
assert.ok(hidden(quoteForm.inner, 'fort_quote_nonce'));
assert.match(text(quoteForm.inner), /Finish: Rose.*Reference: to be confirmed/);
passed('Troca de idioma preserva página, busca, filtros, produto e variantes; orçamento mantém chaves e nonce');

const opala = await page(`${opalaPath}?acabamento=rose&lang=en`);
const finishOptions = tags(opala, 'input').filter(item => item.name === 'acabamento' && item.type === 'radio');
assert.deepEqual(finishOptions.map(item => item.value), ['cromado', 'black', 'aco-corten', 'rose', 'grafite']);
assert.deepEqual(finishOptions.map(item => item['data-label']), ['Chrome', 'Black', 'Corten steel', 'Rose', 'Graphite']);
assert.deepEqual(selected(opala, 'acabamento'), ['rose']);
assert.equal(reference(opala), 'to be confirmed');
assert.equal(withId(opala, 'fort-finish-picker', 'form')['data-model-title'], 'Opala deck-mounted basin faucet');
for (const option of finishOptions) {
  assert.equal(option['data-reference'], '', 'Referência ainda não confirmada não é inventada');
  const quote = new URL(option['data-quote']);
  assert.equal(quote.searchParams.get('lang'), 'en');
  assert.equal(quote.searchParams.get('acabamento'), option.value);
  assert.equal(quote.searchParams.get('produto'), '9');
  const message = new URL(option['data-whatsapp']).searchParams.get('text');
  assert.match(message, /^Hello!/);
  assert.doesNotMatch(message, /Olá|Gostaria|Acabamento:|Referência:|Torneira/);
  const deepLink = new URL(message.match(/https?:\/\/\S+/)?.[0]);
  assert.equal(deepLink.origin, base.origin);
  assert.equal(deepLink.searchParams.get('lang'), 'en');
  assert.equal(deepLink.searchParams.get('acabamento'), option.value);
  assert.equal(new URL(option['data-image']).searchParams.get('lang'), null, 'Arquivo de imagem não recebe idioma');
}
assert.equal(new URL(withId(opala, 'fort-product-quote', 'a').href).searchParams.get('lang'), 'en');
passed('Opala traduz rótulos e mensagens das cinco cores e compartilha o idioma escolhido');

const jade = await page(`${jadePath}?versao=articulador&lang=es`);
const versions = tags(jade, 'input').filter(item => item.name === 'versao' && item.type === 'radio');
assert.deepEqual(versions.map(item => item.value), ['arejador', 'articulador']);
assert.deepEqual(versions.map(item => item['data-label']), ['Con aireador', 'Con aireador articulado']);
assert.deepEqual(versions.map(item => item['data-reference']), ['F-013161', '']);
assert.deepEqual(selected(jade, 'versao'), ['articulador']);
assert.equal(reference(jade), 'por confirmar');
assert.match(text(jade.match(/<h1\b[^>]*>([\s\S]*?)<\/h1>/)?.[1] || ''), /Grifo Jade de caño giratorio largo/);
for (const option of versions) {
  const quote = new URL(option['data-quote']);
  assert.equal(quote.searchParams.get('lang'), 'es');
  assert.equal(quote.searchParams.get('produto'), '28');
  assert.equal(quote.searchParams.get('versao'), option.value);
  const message = new URL(option['data-whatsapp']).searchParams.get('text');
  assert.match(message, /^¡Hola!/);
  assert.ok(message.includes(option.value === 'arejador' ? 'Referencia: F-013161' : 'Referencia: por confirmar'));
}
const jadeContact = await page(versions.find(item => item.value === 'articulador')['data-quote']);
assert.equal(hidden(jadeContact, 'versao'), 'articulador');
assert.equal(hidden(jadeContact, 'lang'), 'es');
assert.match(text(jadeContact), /Versión: Con aireador articulado.*Referencia: por confirmar/);
const legacy = await page(`${legacyPath}?lang=en`);
assert.doesNotMatch(legacy, /id="fort-(?:finish|version)-picker"/);
assert.equal(new URL(withId(legacy, 'fort-product-quote', 'a').href).searchParams.get('produto'), '17');
passed('Jade traduz versões e referências; produto sem variações continua sem seletor');

const userQuery = 'minha busca original — Torneira texto não catalogado';
const queryPage = await page(`/produtos/?q=${encodeURIComponent(userQuery)}&lang=en`);
assert.equal(withId(queryPage, 'catalog-q', 'input').value, userQuery, 'Texto digitado não deve ser traduzido');
assertLocalizedForms(queryPage, 'en');
for (const [language, query] of [['en', 'faucet'], ['es', 'grifo']]) {
  const html = await page(`/produtos/?q=${query}&lang=${language}`);
  const count = html.match(/<div class="catalog-summary">\s*<p><strong>(\d+)<\/strong>/)?.[1];
  assert.equal(count, '12', `Busca por ${query} encontra o catálogo traduzido`);
  assert.equal(withId(html, 'catalog-q', 'input').value, query);
}
passed('Busca aceita faucet/grifo e preserva texto personalizado sem tradução');

const originalAssetUrls = new Set([...tags(portuguese, 'img').map(item => item.src), ...tags(portuguese, 'script').map(item => item.src), ...tags(portuguese, 'link').filter(item => item.rel === 'stylesheet').map(item => item.href)].filter(Boolean));
const translatedAssetUrls = new Set([...tags(english, 'img').map(item => item.src), ...tags(english, 'script').map(item => item.src), ...tags(english, 'link').filter(item => item.rel === 'stylesheet').map(item => item.href)].filter(Boolean));
assert.deepEqual(translatedAssetUrls, originalAssetUrls, 'Imagens, fontes e scripts mantêm suas URLs originais');
const originalContacts = tags(portuguese, 'a').filter(item => /^(?:tel|mailto):/.test(item.href || '')).map(item => item.href);
assert.ok(originalContacts.length > 0);
assert.deepEqual(tags(english, 'a').filter(item => /^(?:tel|mailto):/.test(item.href || '')).map(item => item.href), originalContacts, 'Telefone e e-mail não são modificados');
passed('Arquivos estáticos e destinos de contato externos mantêm seus endereços');

const rejected = await request('/wp-admin/admin-post.php', null, {
  method: 'POST',
  body: new URLSearchParams({
    action: 'fort_orcamento', fort_quote_nonce: hidden(quoteForm.inner, 'fort_quote_nonce'),
    produto: '9', acabamento: '__invalid_finish__', versao: '', lang: 'es',
    nome: 'Verificação local inválida de idioma', email: 'teste@example.invalid',
    telefone: '(44) 99999-0000', cidade: 'Teste local / PR',
    mensagem: 'Solicitação deliberadamente inválida para verificar o retorno do idioma. Não cadastrar.',
    website: '', consentimento: '1',
  }),
});
assert.ok([302, 303].includes(rejected.status));
const rejectedLocation = new URL(rejected.headers.get('location'), base);
assert.equal(rejectedLocation.origin, base.origin);
assert.equal(rejectedLocation.pathname, '/fale-com-a-gente/');
assert.equal(rejectedLocation.searchParams.get('envio'), 'invalido', 'Acabamento inválido é rejeitado antes do salvamento');
assert.equal(rejectedLocation.searchParams.get('lang'), 'es', 'Redirecionamento do POST preserva espanhol');
await rejected.arrayBuffer();
passed('POST inválido é rejeitado e volta ao formulário no idioma selecionado');

console.log(`\n${checks} verificações de idiomas concluídas. Nenhum orçamento válido foi enviado.`);
