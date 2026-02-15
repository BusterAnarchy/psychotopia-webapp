function applyChartDefaults() {
  if (typeof window === 'undefined') {
    return false;
  }

  const chart = window.Chart;
  if (!chart || !chart.defaults) {
    return false;
  }

  chart.defaults.color = '#000';

  if (!chart.defaults.plugins) {
    chart.defaults.plugins = {};
  }

  if (!chart.defaults.plugins.legend) {
    chart.defaults.plugins.legend = {};
  }

  if (!chart.defaults.plugins.legend.labels) {
    chart.defaults.plugins.legend.labels = {};
  }

  chart.defaults.plugins.legend.labels.color = '#000';

  chart.defaults.colorPalette = [
  '#4E79A7', // Blue
  '#F28E2B', // Orange
  '#E15759', // Red
  '#76B7B2', // Teal
  '#59A14F', // Green
  '#EDC948', // Yellow
  '#B07AA1', // Purple
  '#FF9DA7', // Pink
  '#9C755F', // Brown
  '#BAB0AC', // Grey
  ];

  return true;
}

if (!applyChartDefaults() && typeof window !== 'undefined') {
  window.addEventListener('load', applyChartDefaults, { once: true });
}
