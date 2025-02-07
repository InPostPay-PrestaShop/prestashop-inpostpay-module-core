import appController from './controller/appController';
import widget from './components/widget';

window.prestashop ??= {};
window.prestashop.inpostizi ??= {};
window.prestashop.inpostizi.widget ??= widget();

// Backward compatibility for widget v1 refresh handling
if (typeof window.handleInpostIziButtons !== 'function') {
  window.handleInpostIziButtons = window.prestashop.inpostizi.widget.refresh;
}

document.addEventListener('DOMContentLoaded', () => {
  const { init } = appController();

  init();
});
