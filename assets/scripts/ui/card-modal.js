import { lockCardDescriptionForModal, restoreCardDescriptionAfterModal } from './cards';
import { downloadTableAsCsv, getChartTableState } from './chart-tables';

export function initCardModal() {
  const cards = Array.from(document.querySelectorAll('.analysis-card, .chart-card'));
  if (!cards.length) return;

  const modal = document.querySelector('[data-card-modal]');
  if (!modal) return;

  const modalContent = modal.querySelector('[data-card-modal-content]');
  const modalTitle = modal.querySelector('[data-card-modal-title]');
  const modalDescription = modal.querySelector('[data-card-modal-description]');
  const modalShareButton = modal.querySelector('[data-card-modal-share-button]');
  const modalShareIframeButton = modal.querySelector('[data-card-modal-share-iframe-button]');
  const modalExportButton = modal.querySelector('[data-card-modal-export-button]');
  const dialog = modal.querySelector('.card-modal__dialog');
  if (!modalContent || !modalTitle || !dialog) return;

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
      restoreCardDescriptionAfterModal(state.card);
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
    lockCardDescriptionForModal(card);
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
    button.innerHTML = '<span aria-hidden="true">⤢</span>';
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

  setupCopyButton(modalShareButton, () => (modalShareButton ? modalShareButton.dataset.copyValue || '' : ''));
  setupCopyButton(modalShareIframeButton, () => (modalShareIframeButton ? modalShareIframeButton.dataset.copyValue || '' : ''));
  if (modalExportButton) {
    modalExportButton.addEventListener('click', () => {
      if (modalExportButton.disabled) return;
      if (!state.card) return;
      const tableContainer = state.card.querySelector('[data-chart-table]');
      if (!tableContainer) return;
      const tableState = getChartTableState(tableContainer);
      if (!tableState) return;
      downloadTableAsCsv(tableState.headers, tableState.rows, tableState.fileName);
    });
  }

  window.addEventListener('hashchange', handleHashNavigation);
  handleHashNavigation();

  function clearModalSidebar() {
    if (modalDescription) {
      modalDescription.innerHTML = '';
    }
    if (modalShareButton) {
      modalShareButton.dataset.copyValue = '';
      modalShareButton.disabled = true;
      resetCopyButton(modalShareButton);
    }
    if (modalShareIframeButton) {
      modalShareIframeButton.dataset.copyValue = '';
      modalShareIframeButton.disabled = true;
      resetCopyButton(modalShareIframeButton);
    }
    if (modalExportButton) {
      modalExportButton.disabled = true;
    }
  }

  function populateModalDescription(card) {
    if (!modalDescription) return;
    const content = card.querySelector('[data-card-description-content]');
    if (content && content.innerHTML.trim()) {
      modalDescription.innerHTML = content.innerHTML;
    } else {
      modalDescription.innerHTML = '<p class="card-modal__muted">Aucune description disponible.</p>';
    }
  }

  function updateModalShare(card) {
    const url = buildShareUrl(card);
    if (modalShareButton) {
      modalShareButton.dataset.copyValue = url || '';
      modalShareButton.disabled = !url;
      resetCopyButton(modalShareButton);
    }
    updateEmbedShare(card);
    updateExportAction(card);
  }

  function updateEmbedShare(card) {
    if (!modalShareIframeButton) return;
    const embedData = buildEmbedData(card);
    if (!embedData) {
      modalShareIframeButton.dataset.copyValue = '';
      modalShareIframeButton.disabled = true;
      resetCopyButton(modalShareIframeButton);
      return;
    }

    modalShareIframeButton.dataset.copyValue = embedData.code;
    modalShareIframeButton.disabled = false;
    resetCopyButton(modalShareIframeButton);
  }

  function updateExportAction(card) {
    if (!modalExportButton) return;
    const tableContainer = card ? card.querySelector('[data-chart-table]') : null;
    modalExportButton.disabled = !tableContainer;
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

  function buildEmbedUrl(card) {
    if (!card) return null;
    const chartKey = card.dataset ? card.dataset.embedChart : null;
    if (!chartKey) return null;
    const embedRoute = document.body && document.body.dataset ? document.body.dataset.embedRoute : null;
    if (!embedRoute) return null;
    const slug = document.body && document.body.dataset ? document.body.dataset.embedMolecule : '';
    const pageParams = new URLSearchParams(window.location.search);
    try {
      const base = window.location.origin || `${window.location.protocol}//${window.location.host}`;
      const url = new URL(embedRoute, base);
      pageParams.forEach((value, key) => {
        url.searchParams.set(key, value);
      });
      url.searchParams.set('chart', chartKey);
      if (slug) {
        url.searchParams.set('molecule', slug);
      }
      url.hash = '';
      return url.toString();
    } catch (error) {
      pageParams.set('chart', chartKey);
      if (slug) {
        pageParams.set('molecule', slug);
      }
      const query = pageParams.toString();
      return query ? `${embedRoute}?${query}` : embedRoute;
    }
  }

  function buildEmbedData(card) {
    const embedUrl = buildEmbedUrl(card);
    if (!embedUrl) return null;
    const title = card && card.dataset && card.dataset.cardTitle ? card.dataset.cardTitle.trim() : 'Visualisation';
    const code = `<iframe src="${escapeHtml(embedUrl)}" width="100%" height="480" loading="lazy" style="border:0;" title="${escapeHtml(title)}"></iframe>`;
    return { url: embedUrl, code };
  }

  function setupCopyButton(button, getValue) {
    if (!button) return;
    const status = button.querySelector('[data-tool-status]');
    const defaultLabel =
      button.dataset.copyLabel ||
      (status ? status.textContent.trim() : button.textContent.trim()) ||
      'Copier';
    button.dataset.copyLabel = defaultLabel;
    if (status) {
      status.textContent = defaultLabel;
    } else {
      button.textContent = defaultLabel;
    }
    button.addEventListener('click', () => {
      if (button.disabled) return;
      const value = typeof getValue === 'function' ? getValue() : '';
      if (!value) return;
      copyTextToClipboard(value, button);
    });
  }

  function copyTextToClipboard(value, button) {
    const fallbackCopy = () => {
      const textarea = document.createElement('textarea');
      textarea.value = value;
      textarea.setAttribute('readonly', '');
      textarea.style.position = 'absolute';
      textarea.style.left = '-9999px';
      document.body.appendChild(textarea);
      textarea.select();
      document.execCommand('copy');
      document.body.removeChild(textarea);
      showCopyFeedback(button);
    };

    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(value).then(() => showCopyFeedback(button)).catch(fallbackCopy);
    } else {
      fallbackCopy();
    }
  }

  function showCopyFeedback(button) {
    if (!button) return;
    button.classList.add('is-success');
    const status = button.querySelector('[data-tool-status]');
    if (status) {
      status.textContent = 'Copié !';
    } else {
      button.textContent = 'Copié !';
    }
    setTimeout(() => {
      button.classList.remove('is-success');
      const defaultLabel = button.dataset.copyLabel || 'Copier';
      if (status) {
        status.textContent = defaultLabel;
      } else {
        button.textContent = defaultLabel;
      }
    }, 1500);
  }

  function resetCopyButton(button) {
    if (!button) return;
    button.classList.remove('is-success');
    const status = button.querySelector('[data-tool-status]');
    const defaultLabel = button.dataset.copyLabel || 'Copier';
    if (status) {
      status.textContent = defaultLabel;
    } else {
      button.textContent = defaultLabel;
    }
  }

  function escapeHtml(value) {
    if (!value) return '';
    return value.replace(/[&<>"']/g, (char) => {
      const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;',
      };
      return map[char] || char;
    });
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
