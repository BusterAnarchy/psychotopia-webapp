const chartTableState = new WeakMap();

export function initChartTables() {
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

export function getChartTableState(container) {
  if (!container) return null;
  return chartTableState.get(container) || null;
}

export function downloadTableAsCsv(headers, rows, fileName) {
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

function escapeCsvValue(value) {
  const text = value === null || typeof value === 'undefined' ? '' : String(value);
  if (text.includes('"') || text.includes(';') || text.includes('\n')) {
    return `"${text.replace(/"/g, '""')}"`;
  }
  return text;
}
