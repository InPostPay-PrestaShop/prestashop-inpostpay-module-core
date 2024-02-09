import { DOMReady } from '../helpers/DOM';

const SELECTORS = {
  debugSwitch: '[name="advanced_configuration[debugEnabled]"]',
  form: '[name="advanced_configuration"]',
};

const handleResponse = (response) => {
  if (typeof window.showSuccessMessage === 'function') {
    window.showSuccessMessage(response.message)
  } else {
    alert(response.message);
  }
}

const handleError = (error) => {
  if (typeof window.showErrorMessage === 'function') {
    window.showErrorMessage(error.message)
  } else {
    alert(error.message);
  }
}

const handleSwitchChange = (e) => {
  const form = document.querySelector(SELECTORS.form);
  const action = form.getAttribute('action');
  const formData = new FormData(form);

  fetch(action, {
    "headers": {
      "content-type": "application/x-www-form-urlencoded; charset=UTF-8",
      "x-requested-with": "XMLHttpRequest"
    },
    "body": new URLSearchParams(formData).toString(),
    "method": "POST",
  }).then(response => response.json())
    .then(handleResponse)
    .catch(handleError);
}

const handleDebugSwitchChange = () => {
  const switches = document.querySelectorAll(SELECTORS.debugSwitch);

  switches.forEach((switchEl) => {
    switchEl.addEventListener('change', handleSwitchChange);
  });
}

DOMReady(handleDebugSwitchChange);
