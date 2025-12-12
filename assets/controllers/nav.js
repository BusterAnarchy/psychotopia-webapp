document.addEventListener('DOMContentLoaded', function(){

  const btn = document.querySelector('.site-nav__toggle');
  const menu = document.querySelector('.site-nav__menu');
  if (!btn || !menu) return;
  
  btn.addEventListener('click', () => {
    
    const open = menu.getAttribute('data-open') === 'true';
    
    menu.setAttribute('data-open', !open);
    menu.style.display = !open ? 'flex' : 'none';
    btn.setAttribute('aria-expanded', String(!open));
  });
});
