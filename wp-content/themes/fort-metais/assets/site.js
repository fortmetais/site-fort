(() => {
  const i18n = window.fortI18n || {};
  const menu = document.querySelector('.main-nav');
  const menuButton = document.querySelector('.menu-toggle');
  const search = document.querySelector('#header-search');
  const searchButton = document.querySelector('.search-toggle');
  const languageMenu = document.querySelector('.fort-language-switcher details');
  function closeLanguageMenu() { if (languageMenu) languageMenu.open = false; }
  function closeMenu() {
    menu?.classList.remove('is-open');
    menuButton?.setAttribute('aria-expanded', 'false');
    menuButton?.setAttribute('aria-label', i18n.openMenu || 'Abrir menu');
  }
  function closeSearch() { if (search) search.hidden = true; searchButton?.setAttribute('aria-expanded', 'false'); }
  menuButton?.addEventListener('click', () => {
    const open = menuButton.getAttribute('aria-expanded') !== 'true';
    menuButton.setAttribute('aria-expanded', String(open));
    menuButton.setAttribute('aria-label', open ? (i18n.closeMenu || 'Fechar menu') : (i18n.openMenu || 'Abrir menu'));
    menu?.classList.toggle('is-open', open);
    if (open) { closeSearch(); closeLanguageMenu(); menu?.querySelector('a')?.focus(); }
  });
  menu?.querySelectorAll('a').forEach(link => link.addEventListener('click', closeMenu));
  searchButton?.addEventListener('click', () => {
    const open = search?.hidden;
    closeMenu();
    closeLanguageMenu();
    if (search) search.hidden = !open;
    searchButton.setAttribute('aria-expanded', String(Boolean(open)));
    if (open) search?.querySelector('input')?.focus();
  });
  document.addEventListener('keydown', event => {
    if (event.key !== 'Escape') return;
    if (languageMenu?.open) { closeLanguageMenu(); languageMenu.querySelector('summary')?.focus(); return; }
    if (menuButton?.getAttribute('aria-expanded') === 'true') { closeMenu(); menuButton.focus(); }
    else if (searchButton?.getAttribute('aria-expanded') === 'true') { closeSearch(); searchButton.focus(); }
  });
  languageMenu?.addEventListener('toggle', () => { if (languageMenu.open) { closeMenu(); closeSearch(); updateLanguageLinks(); } });
  document.addEventListener('click', event => { if (languageMenu?.open && !languageMenu.contains(event.target)) closeLanguageMenu(); });
  function updateLanguageLinks() {
    document.querySelectorAll('[data-fort-language]').forEach(link => { const url = new URL(window.location.href); url.searchParams.set('lang', link.dataset.fortLanguage); link.href = url.href; });
  }
  updateLanguageLinks();
  document.querySelectorAll('[data-fort-language]').forEach(link => link.addEventListener('click', updateLanguageLinks));
  window.addEventListener('hashchange', updateLanguageLinks);
  const wideScreen = window.matchMedia('(min-width: 1024px)');
  wideScreen.addEventListener('change', () => { if (wideScreen.matches) closeMenu(); });
  document.querySelector('.contact-form')?.addEventListener('submit', event => {
    const button = event.currentTarget.querySelector('button[type="submit"]');
    if (button) { button.disabled = true; button.textContent = i18n.sending || 'Enviando…'; }
  });
})();
