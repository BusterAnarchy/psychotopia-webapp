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
  initCardDescriptions();
  initCardModal();
});

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

function initCardDescriptions() {
  const panels = document.querySelectorAll('[data-card-description]');
  if (!panels.length) return;

  panels.forEach((panel) => {
    const toggle = panel.querySelector('[data-card-description-toggle]');
    if (!toggle) return;
    const initialState = panel.classList.contains('is-open');
    setCardDescriptionState(panel, initialState);

    toggle.addEventListener('click', (event) => {
      event.preventDefault();
      const nextState = !panel.classList.contains('is-open');
      setCardDescriptionState(panel, nextState);
    });
  });
}

function initCardModal() {
  const cards = Array.from(document.querySelectorAll('.analysis-card, .chart-card'));
  if (!cards.length) return;

  const modal = document.querySelector('[data-card-modal]');
  if (!modal) return;

  const modalContent = modal.querySelector('[data-card-modal-content]');
  const modalTitle = modal.querySelector('[data-card-modal-title]');
  const modalDescription = modal.querySelector('[data-card-modal-description]');
  const modalShareInput = modal.querySelector('[data-card-modal-share-input]');
  const modalShareLink = modal.querySelector('[data-card-modal-share-link]');
  const modalShareCopy = modal.querySelector('[data-card-modal-share-copy]');
  const dialog = modal.querySelector('.card-modal__dialog');
  if (!modalContent || !modalTitle || !dialog) return;
  const copyDefaultLabel = modalShareCopy ? modalShareCopy.textContent.trim() : 'Copier';

  const cardById = new Map();
  cards.forEach((card) => {
    if (card.id) {
      cardById.set(card.id, card);
    }
  });

  const closeButtons = modal.querySelectorAll('[data-card-modal-close]');
  const state = {
    placeholder: null,
    card: null,
    trigger: null,
  };
  const hashState = { isUpdating: false };

  const closeModal = (options = {}) => {
    const { fromHashChange = false } = options;
    const previousId = state.card ? state.card.id : null;
    if (state.card) {
      clearModalSidebar();
      restoreDescriptionAfterModal(state.card);
      if (state.placeholder && state.placeholder.parentNode) {
        state.placeholder.parentNode.insertBefore(state.card, state.placeholder);
        state.placeholder.remove();
      }
      state.card.classList.remove('is-modal-open');
    }

    state.card = null;
    state.placeholder = null;

    if (state.trigger) {
      state.trigger.classList.remove('is-active');
      if (typeof state.trigger.focus === 'function') {
        state.trigger.focus();
      }
    }

    state.trigger = null;
    modalContent.innerHTML = '';
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('has-card-modal');
    if (!fromHashChange && previousId) {
      clearHashIfMatches(previousId);
    }
  };

  const getTitle = (card) => {
    const head =
      card.querySelector('.analysis-card__head') ||
      card.querySelector('.chart-card__head') ||
      card.querySelector('header');
    if (!head) return 'Visualisation';
    const clone = head.cloneNode(true);
    clone.querySelectorAll('button').forEach((btn) => btn.remove());
    const text = clone.textContent ? clone.textContent.trim() : '';
    return text || 'Visualisation';
  };

  const openModal = (card, trigger, options = {}) => {
    const { fromHash = false } = options;
    if (!card) return;

    if (state.card === card) {
      if (!fromHash) {
        closeModal();
      }
      return;
    }

    if (state.card) {
      closeModal();
    }

    const placeholder = document.createElement('div');
    placeholder.className = 'card-modal__placeholder';
    if (card.parentNode) {
      card.parentNode.insertBefore(placeholder, card);
    }

    state.placeholder = placeholder;
    state.card = card;
    state.trigger = trigger || null;

    if (state.trigger) {
      state.trigger.classList.add('is-active');
    }

    modalContent.innerHTML = '';
    modalContent.appendChild(card);
    card.classList.add('is-modal-open');
    lockDescriptionForModal(card);
    populateModalDescription(card);
    updateModalShare(card);

    modalTitle.textContent = getTitle(card);
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('has-card-modal');
    if (!fromHash) {
      setHashForCard(card);
    }

    requestAnimationFrame(() => {
      dialog.focus();
    });
  };

  cards.forEach((card) => {
    const head =
      card.querySelector('.analysis-card__head') ||
      card.querySelector('.chart-card__head') ||
      card.querySelector('header');
    if (!head || head.querySelector('[data-card-modal-trigger]')) return;

    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'card-zoom-button';
    button.innerHTML = '<span aria-hidden=\"true\">⤢</span>';
    button.setAttribute('aria-label', 'Agrandir la visualisation');
    button.dataset.cardModalTrigger = 'true';

    button.addEventListener('click', (event) => {
      event.preventDefault();
      event.stopPropagation();
      openModal(card, button);
    });

    head.appendChild(button);
  });

  closeButtons.forEach((btn) => btn.addEventListener('click', closeModal));

  modal.addEventListener('click', (event) => {
    if (event.target === modal) {
      closeModal();
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && modal.classList.contains('is-open')) {
      event.preventDefault();
      closeModal();
    }
  });

  if (modalShareCopy && modalShareInput) {
    modalShareCopy.dataset.label = copyDefaultLabel;
    modalShareCopy.addEventListener('click', () => {
      const value = modalShareInput.value.trim();
      if (!value) return;
      const fallbackCopy = () => {
        modalShareInput.select();
        modalShareInput.setSelectionRange(0, modalShareInput.value.length);
        document.execCommand('copy');
        showCopyFeedback();
      };
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(value).then(showCopyFeedback).catch(fallbackCopy);
      } else {
        fallbackCopy();
      }
    });
  }

  window.addEventListener('hashchange', handleHashNavigation);
  handleHashNavigation();

  function clearModalSidebar() {
    if (modalDescription) {
      modalDescription.innerHTML = '';
    }
    if (modalShareInput) {
      modalShareInput.value = '';
    }
    if (modalShareLink) {
      modalShareLink.removeAttribute('href');
    }
    if (modalShareCopy) {
      modalShareCopy.classList.remove('is-success');
      modalShareCopy.textContent = copyDefaultLabel;
    }
  }

  function populateModalDescription(card) {
    if (!modalDescription) return;
    const content = card.querySelector('[data-card-description-content]');
    if (content && content.innerHTML.trim()) {
      modalDescription.innerHTML = content.innerHTML;
    } else {
      modalDescription.innerHTML = '<p class=\"card-modal__muted\">Aucune description disponible.</p>';
    }
  }

  function updateModalShare(card) {
    if (!modalShareInput || !modalShareLink) return;
    const url = buildShareUrl(card);
    modalShareInput.value = url;
    modalShareLink.href = url;
  }

  function buildShareUrl(card) {
    try {
      const url = new URL(window.location.href);
      if (card.id) {
        url.hash = card.id;
      }
      return url.toString();
    } catch (error) {
      return card.id ? `${window.location.pathname}#${card.id}` : window.location.href;
    }
  }

  function showCopyFeedback() {
    if (!modalShareCopy) return;
    modalShareCopy.classList.add('is-success');
    modalShareCopy.textContent = 'Copié !';
    setTimeout(() => {
      modalShareCopy.classList.remove('is-success');
      modalShareCopy.textContent = copyDefaultLabel;
    }, 1500);
  }

  function setHashForCard(card) {
    if (!card || !card.id) return;
    hashState.isUpdating = true;
    history.replaceState(null, '', `${window.location.pathname}${window.location.search}#${card.id}`);
    setTimeout(() => {
      hashState.isUpdating = false;
    }, 0);
  }

  function clearHashIfMatches(cardId) {
    if (!cardId || !window.location.hash) return;
    const current = decodeURIComponent(window.location.hash.slice(1));
    if (current !== cardId) return;
    hashState.isUpdating = true;
    history.replaceState(null, '', `${window.location.pathname}${window.location.search}`);
    setTimeout(() => {
      hashState.isUpdating = false;
    }, 0);
  }

  function handleHashNavigation() {
    if (hashState.isUpdating) return;
    const targetId = getHashId();
    if (!targetId) {
      if (modal.classList.contains('is-open')) {
        closeModal({ fromHashChange: true });
      }
      return;
    }
    const targetCard = cardById.get(targetId);
    if (targetCard) {
      openModal(targetCard, null, { fromHash: true });
    }
  }

  function getHashId() {
    if (!window.location.hash) return null;
    return decodeURIComponent(window.location.hash.slice(1));
  }
}

function lockDescriptionForModal(card) {
  if (!card) return;
  const panel = card.querySelector('[data-card-description]');
  if (!panel) return;
  panel.dataset.cardPrevState = panel.classList.contains('is-open') ? 'open' : 'closed';
  setCardDescriptionState(panel, true);
}

function restoreDescriptionAfterModal(card) {
  if (!card) return;
  const panel = card.querySelector('[data-card-description]');
  if (!panel) return;
  const wasOpen = panel.dataset.cardPrevState === 'open';
  setCardDescriptionState(panel, wasOpen);
  delete panel.dataset.cardPrevState;
}
