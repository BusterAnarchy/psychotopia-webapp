import { initNav, initDrawers } from './ui/navigation';
import { initTabs } from './ui/tabs';
import { initGridLayoutControls } from './ui/grid-layout';
import { initCardDescriptions } from './ui/cards';
import { initCardModal } from './ui/card-modal';
import { initChartTables } from './ui/chart-tables';

document.addEventListener('DOMContentLoaded', () => {
  initNav();
  initDrawers();
  initTabs();
  initGridLayoutControls();
  initChartTables();
  initCardDescriptions();
  initCardModal();
});
