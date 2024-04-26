import TranslatableInput from '@components/translatable-input';

$(document).ready(() => {
  new TranslatableInput();

  const handleChange = (e) => {
    const $checkbox = $(e.currentTarget);
    const name = $checkbox.data('target');
    const $checkboxes = $(`input[name="${name}"]`);

    $checkboxes.prop('checked', $checkbox.prop('checked'));
  }

  const handleDropdownPreventClose = (e) => {
    if (!e.clickEvent) {
      return true;
    }

    const $clickTarget = $(e.clickEvent.target);

    if ($clickTarget.closest('.dropdown-menu').length) {
      return false;
    }
  }

  $('.js-inpostizi-accept-all-options-checkbox').on('change', handleChange);
  $('[data-prevent-close-on-inside-click="true"]').on('hide.bs.dropdown', handleDropdownPreventClose);
});
