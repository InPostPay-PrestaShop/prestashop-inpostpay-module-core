import ConsentCollection from './components/ConsentCollection';

$(document).ready(() => {
  new ConsentCollection(document.getElementById('consents_configuration_consents'));

  window.prestashop.component.initComponents([
    'TranslatableInput',
  ]);
});
