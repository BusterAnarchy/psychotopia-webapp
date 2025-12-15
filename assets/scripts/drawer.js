// drawer.js
document.addEventListener('DOMContentLoaded', function () {
  const openBtn = document.getElementById('openFiltersBtn');
  const closeBtn = document.getElementById('closeFiltersBtn');
  const drawer = document.getElementById('drawerFilters');

  if (!drawer || !openBtn) return;

  function openDrawer() {
    drawer.setAttribute('aria-hidden', 'false');
    openBtn.setAttribute('aria-expanded', 'true');
    document.body.style.overflow = 'hidden'; // prevent background scroll
  }
  function closeDrawer() {
    drawer.setAttribute('aria-hidden', 'true');
    openBtn.setAttribute('aria-expanded', 'false');
    document.body.style.overflow = '';
  }

  openBtn.addEventListener('click', openDrawer);
  closeBtn && closeBtn.addEventListener('click', closeDrawer);

  // close on ESC
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && drawer.getAttribute('aria-hidden') === 'false') {
      closeDrawer();
    }
  });

  // close when clicking outside (simple)
  drawer.addEventListener('click', (e) => {
    if (e.target === drawer) closeDrawer();
  });

  // keep the handle clickable: allow dragging UX later
});
