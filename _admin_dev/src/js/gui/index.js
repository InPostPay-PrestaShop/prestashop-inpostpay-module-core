import { DOMReady } from '../helpers/DOM';

const SELECTORS = {
  form: '[name="gui_configuration"]',
  selects: '.js-widget-attribute-provider',
  previewContents: '.js-inpostizi-btn-preview-content'
};

const replaceRelatedContent = (responseHtml, content) => {
  const type = content.getAttribute('data-type');

  const previewRelatedContent = responseHtml.querySelector(`${SELECTORS.previewContents}[data-type="${type}"]`);

  if (previewRelatedContent) {
    content.replaceWith(previewRelatedContent);
  }

  if (typeof window.handleInpostIziButtons === 'function') {
    window.handleInpostIziButtons();
  }
}

const handeAjaxRequest = (response) => {
  const parser = new DOMParser();
  const responseHtml = parser.parseFromString(response, 'text/html');

  if (!responseHtml) {
    return;
  }

  const previewContents = document.querySelectorAll(SELECTORS.previewContents);

  previewContents.forEach((content) => {
    replaceRelatedContent(responseHtml, content);
  });
}

const handleStylesChanges = () => {
  const form = document.querySelector(SELECTORS.form);
  const selects = document.querySelectorAll(SELECTORS.selects);

  const handleSelectChange = () => {
    const formData = new FormData(form);

    fetch("", {
      "headers": {
        "content-type": "application/x-www-form-urlencoded; charset=UTF-8",
        "x-requested-with": "XMLHttpRequest"
      },
      "body": new URLSearchParams(formData).toString(),
      "method": "POST",
    }).then(response => response.text()).then(handeAjaxRequest);
  };

  selects.forEach((select) => {
    select.addEventListener('change', handleSelectChange)
  })
}


DOMReady(handleStylesChanges);
