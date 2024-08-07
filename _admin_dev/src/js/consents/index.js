import ConsentCollection from './components/ConsentCollection';
import CollectionForm from '../components/collection-form';
import TranslatableInput from '@components/translatable-input';

$(document).ready(() => {
  const consentsForm = document.getElementById('consents_configuration_consents');

  new ConsentCollection(consentsForm);
  consentsForm.addEventListener(CollectionForm.events.entryAdded, (event) => {
    $(event.detail).find('[data-toggle="popover"]').popover();
  });

  new TranslatableInput();
});
