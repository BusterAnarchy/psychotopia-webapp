function initNav() {
  const navToggle = document.querySelector('[data-nav-toggle]');
  const navMenu = document.querySelector('[data-nav-menu]');

  if (navToggle && navMenu) {
    navToggle.addEventListener('click', () => {
      const isOpen = navMenu.classList.toggle('is-open');
      navToggle.setAttribute('aria-expanded', String(isOpen));
    });
  }

  const dropdownButtons = document.querySelectorAll('[data-nav-dropdown]');
  const closeAllDropdowns = () => {
    dropdownButtons.forEach((button) => {
      const target = document.querySelector(button.dataset.navDropdown);
      const parent = button.closest('.site-nav__item');
      button.setAttribute('aria-expanded', 'false');
      parent && parent.classList.remove('is-open');
      target && target.classList.remove('is-open');
    });
  };

  dropdownButtons.forEach((button) => {
    const target = document.querySelector(button.dataset.navDropdown);
    const parent = button.closest('.site-nav__item');
    if (!target || !parent) return;

    button.addEventListener('click', (event) => {
      event.preventDefault();
      const expanded = button.getAttribute('aria-expanded') === 'true';
      closeAllDropdowns();
      if (!expanded) {
        button.setAttribute('aria-expanded', 'true');
        parent.classList.add('is-open');
        target.classList.add('is-open');
      }
    });
  });

  document.addEventListener('click', (event) => {
    if (!(event.target instanceof Element)) return;
    const nav = event.target.closest('.site-nav');
    if (!nav) {
      closeAllDropdowns();
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      closeAllDropdowns();
    }
  });
}

function initDrawers() {
  const triggers = document.querySelectorAll('[data-drawer-target]');
  if (!triggers.length) return;

  const body = document.body;

  triggers.forEach((trigger) => {
    const selector = trigger.dataset.drawerTarget;
    if (!selector) return;
    const drawer = document.querySelector(selector);
    if (!drawer) return;
    const closeButtons = drawer.querySelectorAll('[data-drawer-close]');

    const openDrawer = () => {
      drawer.setAttribute('aria-hidden', 'false');
      trigger.setAttribute('aria-expanded', 'true');
      body.classList.add('has-open-drawer');
    };

    const closeDrawer = () => {
      drawer.setAttribute('aria-hidden', 'true');
      trigger.setAttribute('aria-expanded', 'false');
      body.classList.remove('has-open-drawer');
    };

    trigger.addEventListener('click', (event) => {
      event.preventDefault();
      const isHidden = drawer.getAttribute('aria-hidden') !== 'false';
      if (isHidden) {
        openDrawer();
      } else {
        closeDrawer();
      }
    });

    closeButtons.forEach((btn) => btn.addEventListener('click', closeDrawer));

    drawer.addEventListener('click', (event) => {
      if (event.target === drawer) {
        closeDrawer();
      }
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && drawer.getAttribute('aria-hidden') === 'false') {
        closeDrawer();
      }
    });
  });
}

function initTabs() {
  const tabGroups = document.querySelectorAll('[data-tab-group]');
  if (!tabGroups.length) return;

  tabGroups.forEach((group) => {
    const buttons = group.querySelectorAll('[data-tab-target]');
    const panels = group.querySelectorAll('[data-tab-panel]');

    const activate = (button, panel) => {
      buttons.forEach((btn) => {
        btn.classList.remove('is-active');
        btn.setAttribute('aria-selected', 'false');
      });
      panels.forEach((pnl) => pnl.classList.remove('is-active'));

      button.classList.add('is-active');
      button.setAttribute('aria-selected', 'true');
      panel.classList.add('is-active');

      document.dispatchEvent(
        new CustomEvent('psychotopia:tab-changed', {
          detail: { group: group.dataset.tabGroup || null, panelId: panel.id },
        })
      );
    };

    buttons.forEach((button) => {
      const selector = button.dataset.tabTarget;
      if (!selector) return;
      const panel =
        group.querySelector(selector) ||
        document.querySelector(selector);
      if (!panel) return;

      button.addEventListener('click', () => activate(button, panel));
    });
  });
}

document.addEventListener('DOMContentLoaded', () => {
  initNav();
  initDrawers();
  initTabs();
});
