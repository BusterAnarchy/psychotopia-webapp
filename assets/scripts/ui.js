const GRID_COLUMNS_STORAGE_KEY = 'psychotopia:grid-columns';
const GRID_COLUMN_CONFIG = {
  2: { chartHeight: 600 },
  3: { chartHeight: 400 },
  4: { chartHeight: 300 },
};
let pendingChartResizeFrame = null;
const chartTableState = new WeakMap();

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

function getStoredGridColumns() {
  try {
    const value = localStorage.getItem(GRID_COLUMNS_STORAGE_KEY);
    if (!value) return null;
    const parsed = Number(value);
    return Number.isFinite(parsed) ? parsed : null;
  } catch (error) {
    return null;
  }
}

function setStoredGridColumns(value) {
  try {
    localStorage.setItem(GRID_COLUMNS_STORAGE_KEY, String(value));
  } catch (error) {
    // Ignore storage errors (private mode, etc.)
  }
}

function clearStoredGridColumns() {
  try {
    localStorage.removeItem(GRID_COLUMNS_STORAGE_KEY);
  } catch (error) {
    // Ignore storage errors (private mode, etc.)
  }
}

function scheduleChartResize() {
  if (typeof window === 'undefined') return;
  if (pendingChartResizeFrame !== null) return;
  pendingChartResizeFrame = window.requestAnimationFrame(() => {
    pendingChartResizeFrame = null;
    window.dispatchEvent(new Event('resize'));
  });
}

function applyGridColumnPreference(columns) {
  const root = document.documentElement;
  if (!root) return;
  if (Number.isFinite(columns) && columns > 0) {
    root.style.setProperty('--analysis-grid-columns', String(columns));
    const settings = GRID_COLUMN_CONFIG[columns];
    if (settings && settings.chartHeight) {
      root.style.setProperty('--analysis-chart-height', `${settings.chartHeight}px`);
    } else {
      root.style.removeProperty('--analysis-chart-height');
    }
  } else {
    root.style.removeProperty('--analysis-grid-columns');
    root.style.removeProperty('--analysis-chart-height');
  }
}

function initGridLayoutControls() {
  const controls = document.querySelectorAll('[data-grid-layout]');
  if (!controls.length) return;

  const buttons = document.querySelectorAll('[data-grid-layout] [data-grid-columns]');
  if (!buttons.length) return;

  const updateActiveButtons = (columns) => {
    buttons.forEach((button) => {
      const value = Number(button.dataset.gridColumns || '');
      const isActive = Number.isFinite(value) && value === columns;
      button.classList.toggle('is-active', isActive);
    });
  };

  const storedValue = getStoredGridColumns();
  const hasStoredOption =
    storedValue !== null &&
    Array.from(buttons).some((button) => {
      const value = Number(button.dataset.gridColumns || '');
      return Number.isFinite(value) && value === storedValue;
    });

  if (storedValue && hasStoredOption) {
    applyGridColumnPreference(storedValue);
    updateActiveButtons(storedValue);
    scheduleChartResize();
  } else if (storedValue && !hasStoredOption) {
    clearStoredGridColumns();
  }

  buttons.forEach((button) => {
    button.addEventListener('click', () => {
      const value = Number(button.dataset.gridColumns || '');
      if (!Number.isFinite(value) || value <= 0) return;
      applyGridColumnPreference(value);
      updateActiveButtons(value);
      setStoredGridColumns(value);
      scheduleChartResize();
    });
  });
}

document.addEventListener('DOMContentLoaded', () => {
  initNav();
  initDrawers();
  initTabs();
  initGridLayoutControls();
  initChartTables();
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

function initCardModal() {
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

  setupCopyButton(modalShareButton, () => (modalShareButton ? modalShareButton.dataset.copyValue || '' : ''));
  setupCopyButton(modalShareIframeButton, () => (modalShareIframeButton ? modalShareIframeButton.dataset.copyValue || '' : ''));
  if (modalExportButton) {
    modalExportButton.addEventListener('click', () => {
      if (modalExportButton.disabled) return;
      if (!state.card) return;
      const tableContainer = state.card.querySelector('[data-chart-table]');
      if (!tableContainer) return;
      const tableState = chartTableState.get(tableContainer);
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
      modalDescription.innerHTML = '<p class=\"card-modal__muted\">Aucune description disponible.</p>';
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

function initChartTables() {
  const containers = document.querySelectorAll('[data-chart-table]');
  if (!containers.length) return;

  containers.forEach((container) => {
    const payload = parseJsonAttribute(container.dataset.chartPayload);
    const config = parseJsonAttribute(container.dataset.chartConfig);
    const table = container.querySelector('table');
    if (!payload || !config || !table) return;

    const tableData = buildChartTableData(payload, config);
    if (!tableData) return;

    renderChartTable(table, tableData.headers, tableData.rows);
    const fileName = buildExportFileName(container, config);
    chartTableState.set(container, {
      headers: tableData.headers,
      rows: tableData.rows,
      fileName,
    });
    setupChartTableToggle(container);
  });
}

function parseJsonAttribute(value) {
  if (!value) return null;
  try {
    return JSON.parse(value);
  } catch (error) {
    return null;
  }
}

function buildChartTableData(data, config) {
  const type = config && config.type ? config.type : 'categorical';
  switch (type) {
    case 'timeseries':
      return buildTimeseriesTable(data, config);
    case 'scatter':
      return buildScatterTable(data, config);
    case 'map':
      return buildMapTable(data, config);
    case 'categorical':
    default:
      return buildCategoricalTable(data, config);
  }
}

function buildCategoricalTable(data, config) {
  const labelsKey = config.labelsKey || 'labels';
  const dataKey = config.dataKey || 'data';
  const labels = Array.isArray(data[labelsKey]) ? data[labelsKey] : [];
  const values = Array.isArray(data[dataKey]) ? data[dataKey] : [];
  const headers = [
    config.labelHeading || 'Catégorie',
    config.valueHeading || 'Valeur',
  ];

  const length = Math.max(labels.length, values.length);
  const rows = [];
  for (let i = 0; i < length; i += 1) {
    rows.push([
      formatCellValue(labels[i]),
      formatCellValue(values[i]),
    ]);
  }

  return { headers, rows };
}

function buildTimeseriesTable(data, config) {
  const labelsKey = config.labelsKey || 'labels';
  const datasetsKey = config.datasetsKey || 'datasets';
  const labels = Array.isArray(data[labelsKey]) ? data[labelsKey] : [];
  const datasets = Array.isArray(data[datasetsKey]) ? data[datasetsKey] : [];
  const headers = [
    config.labelHeading || 'Période',
    config.datasetHeading || 'Série',
    config.valueHeading || 'Valeur',
  ];

  const rows = [];
  datasets.forEach((dataset, datasetIndex) => {
    if (!dataset || !Array.isArray(dataset.data)) {
      return;
    }
    const seriesLabel = dataset.label && dataset.label.trim()
      ? dataset.label.trim()
      : `Série ${datasetIndex + 1}`;

    const points = dataset.data;
    const length = Math.max(points.length, labels.length);
    for (let i = 0; i < length; i += 1) {
      const point = points[i];
      const labelValue = labels[i] !== undefined
        ? labels[i]
        : (point && typeof point === 'object' && 'x' in point ? point.x : i + 1);
      let value = point;
      if (point && typeof point === 'object') {
        if (typeof point.y !== 'undefined') {
          value = point.y;
        } else if (typeof point.value !== 'undefined') {
          value = point.value;
        }
      }
      rows.push([
        formatCellValue(labelValue),
        formatCellValue(seriesLabel),
        formatCellValue(value),
      ]);
    }
  });

  return { headers, rows };
}

function buildScatterTable(data, config) {
  const datasets = Array.isArray(data.datasets) ? data.datasets : [];
  const headers = [
    config.datasetHeading || 'Série',
    config.xHeading || 'Valeur X',
    config.yHeading || 'Valeur Y',
  ];
  const rows = [];

  datasets.forEach((dataset, datasetIndex) => {
    if (!dataset || !Array.isArray(dataset.data)) return;
    const seriesLabel = dataset.label && dataset.label.trim()
      ? dataset.label.trim()
      : `Série ${datasetIndex + 1}`;

    dataset.data.forEach((point, pointIndex) => {
      const safePoint = point && typeof point === 'object' ? point : null;
      const xValue = safePoint ? safePoint.x : pointIndex + 1;
      const yValue = safePoint && typeof safePoint.y !== 'undefined'
        ? safePoint.y
        : '';
      rows.push([
        formatCellValue(seriesLabel),
        formatCellValue(xValue),
        formatCellValue(yValue),
      ]);
    });
  });

  return { headers, rows };
}

function buildMapTable(data, config) {
  const entries = Object.entries(data || {}).filter(([, value]) => (
    typeof value === 'number' ||
    typeof value === 'string'
  ));

  const headers = [
    config.labelHeading || 'Territoire',
    config.valueHeading || 'Valeur',
  ];

  const rows = entries.map(([region, value]) => [
    formatCellValue(region),
    formatCellValue(value),
  ]);

  return { headers, rows };
}

function formatCellValue(value) {
  if (value === null || typeof value === 'undefined') {
    return '';
  }
  if (typeof value === 'number') {
    return Number.isFinite(value) ? String(value) : '';
  }
  if (typeof value === 'string') {
    return value;
  }
  if (typeof value === 'boolean') {
    return value ? 'Oui' : 'Non';
  }
  return JSON.stringify(value);
}

function renderChartTable(table, headers, rows) {
  const thead = table.querySelector('thead');
  const tbody = table.querySelector('tbody');
  if (!thead || !tbody) return;

  thead.innerHTML = '';
  const headerRow = document.createElement('tr');
  headers.forEach((header) => {
    const th = document.createElement('th');
    th.scope = 'col';
    th.textContent = header;
    headerRow.appendChild(th);
  });
  thead.appendChild(headerRow);

  tbody.innerHTML = '';
  if (!rows.length) {
    const tr = document.createElement('tr');
    const td = document.createElement('td');
    td.colSpan = headers.length;
    td.textContent = 'Aucune donnée disponible pour le moment.';
    tr.appendChild(td);
    tbody.appendChild(tr);
    return;
  }

  rows.forEach((row) => {
    const tr = document.createElement('tr');
    row.forEach((cell) => {
      const td = document.createElement('td');
      td.textContent = cell;
      tr.appendChild(td);
    });
    tbody.appendChild(tr);
  });
}

function setupChartTableToggle(container) {
  const toggle = container.querySelector('[data-chart-table-toggle]');
  const panel = container.querySelector('[data-chart-table-panel]');
  if (!toggle || !panel) return;

  const defaultState = toggle.dataset.chartTableDefault === 'closed' ? false : true;
  setChartTablePanelState(panel, toggle, defaultState);

  toggle.addEventListener('click', (event) => {
    event.preventDefault();
    const nextState = !panel.classList.contains('is-open');
    setChartTablePanelState(panel, toggle, nextState);
  });
}

function setChartTablePanelState(panel, toggle, isOpen) {
  if (!panel || !toggle) return;
  panel.classList.toggle('is-open', Boolean(isOpen));
  toggle.classList.toggle('is-open', Boolean(isOpen));
  if (isOpen) {
    panel.removeAttribute('hidden');
  } else {
    panel.setAttribute('hidden', '');
  }
  toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
  const labelEl = toggle.querySelector('[data-chart-table-toggle-label]');
  const openLabel = toggle.dataset.labelOpen || 'Masquer le tableau';
  const closedLabel = toggle.dataset.labelClosed || 'Afficher le tableau';
  if (labelEl) {
    labelEl.textContent = isOpen ? openLabel : closedLabel;
  }
}

function buildExportFileName(container, config) {
  const fallback = container.dataset.chartId
    ? `donnees-${container.dataset.chartId}`
    : 'donnees-graphique';
  const explicit = typeof config.exportFileName === 'string'
    ? config.exportFileName.trim()
    : '';
  const base = (explicit || fallback || 'donnees').toLowerCase();
  const sanitized = base.replace(/[^\w.-]+/g, '-');
  return sanitized.endsWith('.csv') ? sanitized : `${sanitized}.csv`;
}

function downloadTableAsCsv(headers, rows, fileName) {
  const csvRows = [headers, ...rows];
  const csvContent = csvRows
    .map((row) => row.map(escapeCsvValue).join(';'))
    .join('\n');
  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  link.href = URL.createObjectURL(blob);
  link.download = fileName;
  document.body.appendChild(link);
  link.click();
  setTimeout(() => {
    URL.revokeObjectURL(link.href);
    link.remove();
  }, 0);
}

function escapeCsvValue(value) {
  const text = value === null || typeof value === 'undefined' ? '' : String(value);
  if (text.includes('"') || text.includes(';') || text.includes('\n')) {
    return `"${text.replace(/"/g, '""')}"`;
  }
  return text;
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
