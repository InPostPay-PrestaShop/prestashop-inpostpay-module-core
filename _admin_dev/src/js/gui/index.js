import { DOMReady } from '../helpers/DOM';
import ChoiceTree from '@components/form/choice-tree';

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
    formData.append('gui_configuration[invalid_extra_field]', 'temporary valid form submission countermeasure');

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

const initChoiceTree = () => {
  const choiceTreeContainer = document.querySelector('.js-choice-tree-container');

  if (null !== choiceTreeContainer) {
    new ChoiceTree(choiceTreeContainer).enableAutoCheckChildren();
  }
}

const initGui = () => {
  initChoiceTree();
}

DOMReady(initGui);
DOMReady(handleStylesChanges);
