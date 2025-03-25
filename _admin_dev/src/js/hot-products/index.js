import TomSelect from 'tom-select';

const select = document.getElementById('create_hot_product_productId');
delete select.dataset.toggle; // w domyślnym theme'ie we wczesnych PS 1.7 do list wyboru dodawany jest data atrybut `data-toggle="select2"` 😨

/**
 * @type {HTMLElement}
 */

$(() => {
  select.classList.remove('custom-select');
  const combinationChoiceWrapper = document.getElementById('combination_choice_wrapper');

  // zerżnięte z UX autocomplete
  const tomSelectInstance = new TomSelect('#create_hot_product_productId', {
    items: [select.value],
    plugins: {
      virtual_scroll: {}
    },
    closeAfterSelect: true,
    firstUrl: (query) => {
      return `${select.dataset.url}&query=${encodeURIComponent(query)}`;
    },
    // VERY IMPORTANT: use 'function (query, callback) { ... }' instead of the
    // '(query, callback) => { ... }' syntax because, otherwise,
    // the 'this.XXX' calls inside this method fail
    load: function (query, callback) {
      const url = this.getUrl(query);
      fetch(url)
        .then((response) => response.json())
        // important: next_url must be set before invoking callback()
        .then((json) => {
          this.setNextUrl(query, json.next_page);
          callback(json.results);
        })
        .catch(() => callback([], []));
    },
    shouldLoad: (...query) => {
      if ('undefined' !== typeof select.dataset.minCharacters) {
        return query.length >= select.dataset.minCharacters;
      }

      if (tomSelectInstance.hasLoadedChoicesPreviously) {
        return true;
      }

      // mark that the choices have loaded (but avoid initial load)
      if (query.length > 0) {
        tomSelectInstance.hasLoadedChoicesPreviously = true;
      }

      return query.length >= 3;
    },
    // avoid extra filtering after results are returned
    score: (search) => (item) => 1,
    render: {
      option: (item) => `<div>${item['text']}</div>`,
      item: (item) => `<div>${item['text']}</div>`,
      loading_more: () => {
        return `<div class="loading-more-results">${select.dataset.loadingMoreText}</div>`;
      },
      no_more_results: () => {
        return `<div class="no-more-results">${select.dataset.noMoreResultsText}</div>`;
      },
      no_results: () => {
        return `<div class="no-results">${select.dataset.noResultsFoundText}</div>`;
      },
    },
    preload: false,
  });

  select.addEventListener('change', (event) => {
    /**
     * @type {HTMLSelectElement}
     */
    const select = event.target;
    const form = select.closest('form');

    const formData = new FormData();
    formData.append(select.name, select.value);

    fetch(form.action, {
      method: form.method,
      body: new URLSearchParams(formData).toString(),
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'charset': 'utf-8'
      },
    })
      .then(response => response.text())
      .then(text => {
        const parser = new DOMParser();

        return parser.parseFromString(text, 'text/html');
      })
      .then(html => {
        combinationChoiceWrapper.innerHTML = html.getElementById('combination_choice_wrapper').innerHTML;
      });
  });

  const $datePickers = $('.js-hot-products-datepicker-input');
  $.each($datePickers, (i, picker) => {
    const $input = $(picker);

    $input.datetimepicker({
      format: $input.data('format') ? $input.data('format') : 'YYYY-MM-DD',
      sideBySide: true,
      icons: {
        time: 'time',
        date: 'date',
        up: 'up',
        down: 'down',
      },
    });
  });
});
