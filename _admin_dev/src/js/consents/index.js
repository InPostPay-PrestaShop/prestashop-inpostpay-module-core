import ConsentCollection from './components/ConsentCollection';
import TranslatableInput from '@components/translatable-input';

$(document).ready(() => {
  new ConsentCollection(document.getElementById('consents_configuration_consents'));

  new TranslatableInput();
});
