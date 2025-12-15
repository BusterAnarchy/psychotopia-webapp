export function initTabs() {
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
      const panel = group.querySelector(selector) || document.querySelector(selector);
      if (!panel) return;

      button.addEventListener('click', () => activate(button, panel));
    });
  });
}
