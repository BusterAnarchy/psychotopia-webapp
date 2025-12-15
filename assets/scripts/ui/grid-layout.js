const GRID_COLUMNS_STORAGE_KEY = 'psychotopia:grid-columns';
const GRID_COLUMN_CONFIG = {
  2: { chartHeight: 600 },
  3: { chartHeight: 400 },
  4: { chartHeight: 300 },
};
let pendingChartResizeFrame = null;

export function initGridLayoutControls() {
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
