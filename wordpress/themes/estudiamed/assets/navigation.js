(() => {
  const toggle = document.querySelector('.menu-toggle');
  const nav = document.getElementById('primary-nav');
  if (!toggle || !nav) return;

  const narrow = matchMedia('(max-width: 970px)');
  function setOpen(open) {
    nav.hidden = narrow.matches && !open;
    toggle.setAttribute('aria-expanded', String(open));
  }

  function sync() {
    toggle.hidden = !narrow.matches;
    setOpen(false);
  }

  toggle.addEventListener('click', () => setOpen(nav.hidden));
  nav.addEventListener('click', event => {
    if (narrow.matches && event.target.closest('a')) {
      setOpen(false);
      toggle.focus();
    }
  });
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && narrow.matches && !nav.hidden) {
      setOpen(false);
      toggle.focus();
    }
  });
  narrow.addEventListener('change', sync);
  sync();
})();
