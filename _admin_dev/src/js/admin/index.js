import TranslatableInput from '@components/translatable-input';

$(document).ready(() => {
  new TranslatableInput();

  const handleChange = (e) => {
    const $checkbox = $(e.currentTarget);
    const name = $checkbox.data('target');
    const $checkboxes = $(`input[name="${name}"]`);

    $checkboxes.prop('checked', $checkbox.prop('checked'));
  }

  $('.js-inpostizi-accept-all-options-checkbox').on('change', handleChange);
});
