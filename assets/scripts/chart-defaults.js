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
    '#D3C2CD', // Lilac Grey
    '#849E15', // Spring Leaves
    '#92A2A6', // Good Surf
    '#B28622', // Gold Velvet
    '#F8CABA', // Brink of Pink
    '#D8560E', // Poppy
    '#EFCE7B', // Butter Yellow
    '#E1903E', // Florida Oranges
    '#6777B6', // Pea Flower
    '#2B2B23', // Night Forest
    '#D17089', // Dusty Berry
    '#CBD183', // Pistachio
    '#7E4F2F', // Walnut Brown (warmer dark neutral)
    '#A1B6D6', // Powder Blue (cool pastel)
    '#F4A6A0', // Coral Pink (soft warm)
  ];

  return true;
}

if (!applyChartDefaults() && typeof window !== 'undefined') {
  window.addEventListener('load', applyChartDefaults, { once: true });
}
