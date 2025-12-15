function setCardDescriptionState(panel, isOpen) {
  if (!panel) return;
  panel.classList.toggle('is-open', Boolean(isOpen));
  const toggle = panel.querySelector('[data-card-description-toggle]');
  if (!toggle) return;
  toggle.classList.toggle('is-open', Boolean(isOpen));
  toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
  const label = toggle.querySelector('[data-card-description-toggle-label]');
  const openLabel = toggle.dataset.labelOpen || 'Masquer la description';
  const closedLabel = toggle.dataset.labelClosed || 'Afficher la description';
  if (label) {
    label.textContent = isOpen ? openLabel : closedLabel;
  }
}

export function initCardDescriptions() {
  const panels = document.querySelectorAll('[data-card-description]');
  if (!panels.length) return;

  const mobileQuery = window.matchMedia('(max-width: 767px)');

  const applyResponsiveState = () => {
    panels.forEach((panel) => {
      const defaultState = panel.dataset.cardDescriptionDefault === 'open';
      const desiredState = mobileQuery.matches ? false : defaultState;
      setCardDescriptionState(panel, desiredState);
    });
  };

  panels.forEach((panel) => {
    const toggle = panel.querySelector('[data-card-description-toggle]');
    if (!toggle) return;
    const defaultState = panel.classList.contains('is-open');
    panel.dataset.cardDescriptionDefault = defaultState ? 'open' : 'closed';
    const initialState = mobileQuery.matches ? false : defaultState;
    setCardDescriptionState(panel, initialState);

    toggle.addEventListener('click', (event) => {
      event.preventDefault();
      const nextState = !panel.classList.contains('is-open');
      setCardDescriptionState(panel, nextState);
    });
  });

  const mediaHandler = () => applyResponsiveState();
  if (mobileQuery.addEventListener) {
    mobileQuery.addEventListener('change', mediaHandler);
  } else {
    mobileQuery.addListener(mediaHandler);
  }
}

export function lockCardDescriptionForModal(card) {
  if (!card) return;
  const panel = card.querySelector('[data-card-description]');
  if (!panel) return;
  panel.dataset.cardPrevState = panel.classList.contains('is-open') ? 'open' : 'closed';
  setCardDescriptionState(panel, true);
}

export function restoreCardDescriptionAfterModal(card) {
  if (!card) return;
  const panel = card.querySelector('[data-card-description]');
  if (!panel) return;
  const wasOpen = panel.dataset.cardPrevState === 'open';
  setCardDescriptionState(panel, wasOpen);
  delete panel.dataset.cardPrevState;
}
