import parseToHtml from '../../shared/utils/parseToHtml';
import widgetGetRequest from './widgetGetRequest';

/**
 * @param {string} hookName
 * @param {number} idProduct
 * @param {number} idProductAttribute
 * @return {Promise<HTMLElement>}
 */
const fetchWidgetFromEndpoint = async (hookName, idProduct, idProductAttribute) => {
  const response = await widgetGetRequest(hookName, idProduct, idProductAttribute);

  if (!response.ok) {
    throw new Error(
      `Failed to fetch widget for hook ${hookName}, product ${idProduct}, attribute ${idProductAttribute}`,
    );
  }

  const { content } = await response.json();

  return parseToHtml(content);
};

export default fetchWidgetFromEndpoint;
