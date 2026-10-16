(() => {
  const i18n = window.fortI18n || {};
  const photo = document.querySelector('#fort-product-photo');
  if (!photo) return;
  const reference = document.querySelector('#fort-product-reference');
  const quote = document.querySelector('#fort-product-quote');
  const whatsapp = document.querySelector('#fort-product-whatsapp');
  const imageBox = document.querySelector('#fort-product-image');
  const errors = document.querySelectorAll('#fort-finish-error, #fort-version-error');
  photo.addEventListener('load', () => { imageBox?.removeAttribute('aria-busy'); errors.forEach(error => { error.hidden = true; }); });
  photo.addEventListener('error', () => { imageBox?.removeAttribute('aria-busy'); errors.forEach(error => { error.hidden = false; }); });
  const groups = [
    {id:'fort-finish-picker', name:'acabamento', label:'fort-selected-finish', spec:'fort-finish-spec', demo:'fort-finish-demo', alt:i18n.finishPrefix || 'acabamento '},
    {id:'fort-version-picker', name:'versao', label:'fort-selected-version', spec:'fort-version-spec', demo:'fort-version-demo', alt:''},
  ];
  groups.forEach(group => {
    const picker = document.getElementById(group.id);
    if (!picker) return;
    const selectedLabel = document.getElementById(group.label);
    const specification = document.getElementById(group.spec);
    const demo = document.getElementById(group.demo);
    picker.addEventListener('change', event => {
      const input = event.target;
      if (!(input instanceof HTMLInputElement) || input.name !== group.name || !input.checked) return;
      selectedLabel.textContent = input.dataset.label;
      if (specification) specification.textContent = input.dataset.label;
      if (reference) {
        reference.hidden = false;
        reference.querySelector('span').textContent = input.dataset.reference || i18n.unconfirmed || 'a confirmar';
      }
      if (demo) demo.hidden = input.dataset.demo !== '1';
      errors.forEach(error => { error.hidden = true; });
      quote.href = input.dataset.quote;
      whatsapp.href = input.dataset.whatsapp;
      imageBox?.setAttribute('aria-busy', 'true');
      photo.removeAttribute('srcset');
      photo.removeAttribute('sizes');
      photo.alt = `${picker.dataset.modelTitle} — ${group.alt}${input.dataset.label}`;
      photo.src = input.dataset.image;
      const url = new URL(window.location.href);
      url.searchParams.set(group.name, input.value);
      window.history.replaceState(null, '', url);
    });
  });
})();
