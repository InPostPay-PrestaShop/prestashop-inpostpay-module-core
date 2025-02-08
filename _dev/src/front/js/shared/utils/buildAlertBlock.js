/**
 * @param message {string} - alert message
 * @param type {string} - alert type (success, danger, warning, info)
 * @param extraClass {string|array} - additional classes
 * @returns {HTMLElement}
 */
const buildAlertBlock = (message, type = 'success', extraClass = '') => {
  const alert = document.createElement('div');

  alert.classList.add('alert', `alert-${type}`, `w-100`);

  if (typeof extraClass === 'string') {
    alert.classList.add(extraClass);
  } else if (Array.isArray(extraClass)) {
    extraClass.forEach((className) => {
      alert.classList.add(className);
    });
  }

  alert.textContent = message;

  return alert;
};

export default buildAlertBlock;
