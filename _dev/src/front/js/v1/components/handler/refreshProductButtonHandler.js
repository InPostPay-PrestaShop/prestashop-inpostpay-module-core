import widgetGetRequest from '../http/widgetGetRequest';
import selectorsMap from '../../../shared/map/selectorsMap';
import parseToHtml from '../../../shared/utils/parseToHtml';

/**
 * Refresh product button handler
 * @param {HTMLElement} buttonWrapper - button wrapper
 * @param {string} hookName - hook name
 * @param {number} idProduct - product id
 */
const refreshProductButtonHandler = async (buttonWrapper, hookName, idProduct) => {
  const { content = null } = await widgetGetRequest(hookName, idProduct);
  const { inpostIziButton } = selectorsMap();

  if (!content) {
    return;
  }

  buttonWrapper.querySelector(inpostIziButton)?.remove();
  buttonWrapper.prepend(parseToHtml(content));

  if (typeof window.handleInpostIziButtons === 'function') {
    window.handleInpostIziButtons();
  }
};

export default refreshProductButtonHandler;
