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

  window.createChartHtmlLegendPlugin = function createChartHtmlLegendPlugin() {
    const getOrCreateLegendList = (containerId) => {
      const legendContainer = document.getElementById(containerId);
      if (!legendContainer) {
        return null;
      }

      let listContainer = legendContainer.querySelector('ul');
      if (!listContainer) {
        listContainer = document.createElement('ul');
        listContainer.className = 'chart-html-legend__list';
        legendContainer.appendChild(listContainer);
      }

      return listContainer;
    };

    return {
      id: 'htmlLegend',
      afterUpdate(chartInstance, args, options) {
        const containerId = options && options.containerID;
        if (!containerId) {
          return;
        }

        const legendContainer = document.getElementById(containerId);
        const listContainer = getOrCreateLegendList(containerId);
        if (!legendContainer || !listContainer) {
          return;
        }

        while (listContainer.firstChild) {
          listContainer.firstChild.remove();
        }

        const legendOptions = chartInstance.options.plugins.legend || {};
        const labelOptions = legendOptions.labels || {};
        const generateLabels = labelOptions.generateLabels || chart.defaults.plugins.legend.labels.generateLabels;

        let items = generateLabels(chartInstance);

        if (typeof labelOptions.filter === 'function') {
          items = items.filter((item) => labelOptions.filter(item, chartInstance.data));
        }

        if (typeof labelOptions.sort === 'function') {
          items = items.slice().sort((a, b) => labelOptions.sort(a, b, chartInstance.data));
        }

        legendContainer.hidden = items.length === 0;

        items.forEach((item) => {
          const listItem = document.createElement('li');
          listItem.className = 'chart-html-legend__entry';

          const button = document.createElement('button');
          button.type = 'button';
          button.className = 'chart-html-legend__item';
          button.setAttribute('aria-pressed', String(!item.hidden));
          button.onclick = () => {
            const chartType = chartInstance.config.type;

            if (chartType === 'pie' || chartType === 'doughnut' || chartType === 'polarArea') {
              chartInstance.toggleDataVisibility(item.index);
            } else if (typeof item.datasetIndex === 'number') {
              chartInstance.setDatasetVisibility(item.datasetIndex, !chartInstance.isDatasetVisible(item.datasetIndex));
            }

            chartInstance.update();
          };

          const marker = document.createElement('span');
          marker.className = 'chart-html-legend__marker';
          marker.style.background = item.fillStyle || 'transparent';
          marker.style.borderColor = item.strokeStyle || item.fillStyle || 'transparent';
          marker.style.borderWidth = `${item.lineWidth || 1}px`;

          const label = document.createElement('span');
          label.className = 'chart-html-legend__label';
          label.textContent = item.text;

          if (item.hidden) {
            button.classList.add('is-hidden');
          }

          button.appendChild(marker);
          button.appendChild(label);
          listItem.appendChild(button);
          listContainer.appendChild(listItem);
        });
      }
    };
  };

  return true;
}

if (!applyChartDefaults() && typeof window !== 'undefined') {
  window.addEventListener('load', applyChartDefaults, { once: true });
}
