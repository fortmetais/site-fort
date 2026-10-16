(() => {
  const widget = document.querySelector('#fort-whatsapp-widget');
  if (!widget) return;
  const launcher = widget.querySelector('#fort-whatsapp-launcher');
  const panel = widget.querySelector('#fort-whatsapp-panel');
  const closeButton = widget.querySelector('.fort-whatsapp-close');
  if (!launcher || !panel || !closeButton) return;

  // The anchor remains a useful direct WhatsApp link if JavaScript is unavailable.
  launcher.setAttribute('role', 'button');
  const setOpen = (open, restoreFocus = false) => {
    panel.hidden = !open;
    launcher.setAttribute('aria-expanded', String(open));
    launcher.setAttribute('aria-label', open ? launcher.dataset.closeLabel : launcher.dataset.openLabel);
    if (open) closeButton.focus({ preventScroll: true });
    else if (restoreFocus) launcher.focus({ preventScroll: true });
  };

  launcher.addEventListener('click', event => {
    if (event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
    event.preventDefault();
    setOpen(panel.hidden);
  });
  launcher.addEventListener('keydown', event => {
    if (event.key !== ' ' || event.repeat) return;
    event.preventDefault();
    setOpen(panel.hidden);
  });
  closeButton.addEventListener('click', () => setOpen(false, true));
  document.addEventListener('keydown', event => {
    if (event.key !== 'Escape' || panel.hidden) return;
    event.preventDefault();
    setOpen(false, true);
  });
  document.addEventListener('pointerdown', event => {
    if (!panel.hidden && !widget.contains(event.target)) setOpen(false);
  });
  document.addEventListener('click', event => {
    if (!panel.hidden && !widget.contains(event.target)) setOpen(false);
  });
  document.addEventListener('focusin', event => {
    if (!panel.hidden && !widget.contains(event.target)) setOpen(false);
  });
})();
