import assert from 'node:assert/strict';

// Read-only checks. This script never signs in, publishes posts or submits forms.
const base = new URL(process.env.FORT_URL || 'http://localhost:8080');
const local = ['localhost', '127.0.0.1', '[::1]'].includes(base.hostname);
assert.ok(local || base.protocol === 'https:', 'Use localhost ou um destino HTTPS explícito em FORT_URL.');
let checks = 0;
const checkedImages = new Set();
function passed(label) { checks++; console.log(`OK ${label}`); }
function decode(value = '') {
  return value.replace(/&(#x[\da-f]+|#\d+|amp|quot|apos|nbsp|ndash|mdash|hellip);/gi, (whole, code) => {
    if (code.startsWith('#')) return String.fromCodePoint(code[1].toLowerCase() === 'x' ? parseInt(code.slice(2), 16) : Number(code.slice(1)));
    return { amp: '&', quot: '"', apos: "'", nbsp: ' ', ndash: '–', mdash: '—', hellip: '…' }[code] ?? whole;
  });
}
function text(value = '') { return decode(value.replace(/<[^>]*>/g, '')).replace(/\s+/g, ' ').trim(); }
function attrs(tag) {
  return Object.fromEntries([...tag.matchAll(/([\w:-]+)\s*=\s*(?:"([^"]*)"|'([^']*)')/g)].map(([, key, double, single]) => [key, decode(double ?? single ?? '')]));
}
function tags(html, tag) { return [...html.matchAll(new RegExp(`<${tag}\\b[^>]*>`, 'gi'))].map(match => attrs(match[0])); }
function classHas(attributes, className) { return (attributes.class || '').split(/\s+/).includes(className); }
function localized(path, language) { const url = new URL(path, base); url.searchParams.set('lang', language); return url; }
async function request(path) {
  let url = new URL(path, base);
  for (let redirects = 0; redirects < 8; redirects++) {
    assert.equal(url.origin, base.origin, 'A conferência deve permanecer no site selecionado.');
    const response = await fetch(url, { redirect: 'manual', signal: AbortSignal.timeout(45000) });
    const target = response.headers.get('location');
    if (![301, 302, 303, 307, 308].includes(response.status) || !target) return response;
    await response.arrayBuffer();
    url = new URL(target, url);
  }
  throw new Error('Redirecionamentos excessivos.');
}
async function page(path) {
  const response = await request(path);
  assert.equal(response.status, 200, `Página disponível: ${path}`);
  const html = await response.text();
  assert.doesNotMatch(html, /Fatal error:|Há um erro crítico|Failed opening required|<b>(?:Warning|Notice|Deprecated)<\/b>/i, 'Página sem erro de PHP.');
  if (!local) assert.doesNotMatch(html, /https?:\/\/(?:localhost|127\.0\.0\.1)(?::\d+)?\//i, 'O site hospedado não deve apontar ao localhost.');
  return html;
}
function cards(html) {
  return [...html.matchAll(/(<article\b[^>]*>)([\s\S]*?)<\/article>/gi)]
    .map(([, tag, body]) => ({ attrs: attrs(tag), body }))
    .filter(card => classHas(card.attrs, 'fort-news-card'))
    .map(card => ({
      ...card,
      link: tags(card.body, 'a').find(a => classHas(a, 'fort-news-read'))?.href,
      date: tags(card.body, 'time')[0]?.datetime,
      image: tags(card.body, 'img')[0]?.src,
    }));
}
function assertOrdered(items) {
  const dates = items.map(item => {
    assert.ok(item.link && item.date, 'Cada notícia possui link e data.');
    assert.equal(new URL(item.link, base).origin, base.origin);
    const date = Date.parse(item.date);
    assert.ok(Number.isFinite(date), 'A data é válida e inclui horário/fuso.');
    return date;
  });
  for (let index = 1; index < dates.length; index++) assert.ok(dates[index] <= dates[index - 1], 'As notícias seguem da mais recente para a mais antiga.');
  assert.equal(new Set(items.map(item => item.link)).size, items.length, 'O destaque não duplica uma notícia da grade.');
}
async function imageAvailable(url) {
  if (!url || checkedImages.has(url)) return;
  const target = new URL(url, base);
  assert.equal(target.origin, base.origin, 'A foto da notícia pertence ao próprio site.');
  const response = await request(target);
  assert.equal(response.status, 200, 'A imagem da notícia está disponível.');
  assert.match(response.headers.get('content-type') || '', /^image\//i);
  await response.body?.cancel();
  checkedImages.add(url);
}

const home = await page(localized('/', 'pt-br'));
const nav = home.match(/<nav\b[^>]*id="main-nav"[^>]*>([\s\S]*?)<\/nav>/i)?.[1] || '';
assert.ok(tags(nav, 'a').some(a => new URL(a.href, base).pathname === '/noticias/'), 'O menu principal inclui Notícias.');
passed('Notícias disponível no menu principal');

for (const [language, htmlLanguage, heading] of [['pt-br', 'pt-BR', 'Notícias'], ['en', 'en', 'News'], ['es', 'es', 'Noticias']]) {
  const listing = await page(localized('/noticias/', language));
  assert.equal(tags(listing, 'html')[0]?.lang, htmlLanguage);
  assert.ok(tags(listing, 'main').some(a => classHas(a, 'fort-news-listing')));
  assert.equal(text(listing.match(/<h1\b[^>]*>([\s\S]*?)<\/h1>/i)?.[1]), heading);
  assert.ok(tags(listing, 'link').some(a => /\/assets\/news\.css(?:\?|$)/.test(a.href || '')), 'A seção carrega seu estilo.');
  const items = cards(listing);
  assert.ok(items.length <= 9, 'A primeira página exibe no máximo nove notícias.');
  assertOrdered(items);
  assert.equal(items.filter(card => classHas(card.attrs, 'fort-news-featured')).length, items.length ? 1 : 0, 'Somente a primeira notícia ocupa o destaque.');
  if (!items.length) {
    assert.match(listing, /fort-news-empty/);
    passed(`Listagem e estado vazio em ${language}`);
    continue;
  }
  const first = items[0];
  const single = await page(first.link);
  assert.equal(tags(single, 'html')[0]?.lang, htmlLanguage);
  assert.ok(tags(single, 'main').some(a => classHas(a, 'fort-news-single')));
  assert.equal((single.match(/<h1\b/gi) || []).length, 1, 'A notícia tem um título principal.');
  assert.ok(tags(single, 'time').some(time => time.datetime === first.date), 'A notícia mantém a data exibida no card.');
  assert.match(single, /class="fort-news-body prose"/);
  const back = tags(single, 'a').find(a => new URL(a.href || '/', base).pathname === '/noticias/');
  assert.ok(back, 'A notícia tem um caminho de volta à listagem.');
  if (language !== 'pt-br') assert.equal(new URL(back.href, base).searchParams.get('lang'), language);
  const switches = tags(single, 'a').filter(a => Object.hasOwn(a, 'data-fort-language'));
  assert.equal(switches.length, 3);
  for (const switcher of switches) assert.equal(new URL(switcher.href, base).pathname, new URL(first.link, base).pathname, 'Trocar idioma mantém a notícia.');
  await imageAvailable(first.image);
  const next = tags(listing, 'a').find(a => classHas(a, 'page-numbers') && (/\/page\/2\/?$/.test(new URL(a.href || '/', base).pathname) || new URL(a.href || '/', base).searchParams.get('paged') === '2'));
  if (next) {
    const second = cards(await page(next.href));
    assert.ok(second.length > 0);
    assertOrdered(second);
    assert.ok(second.every(item => !classHas(item.attrs, 'fort-news-featured')));
    assert.ok(second.every(item => !items.some(firstPage => firstPage.link === item.link)), 'As páginas não repetem os mesmos posts.');
    assert.ok(Date.parse(second[0].date) <= Date.parse(items.at(-1).date));
    if (language !== 'pt-br') assert.equal(new URL(next.href, base).searchParams.get('lang'), language);
  }
  passed(`Listagem, destaque, data, notícia e idioma ${language}${next ? ', incluindo página 2' : ''}`);
}

console.log(`\n${checks} grupos de verificação concluídos; ${checkedImages.size} imagem(ns) conferida(s). Nenhum conteúdo foi criado ou alterado.`);
