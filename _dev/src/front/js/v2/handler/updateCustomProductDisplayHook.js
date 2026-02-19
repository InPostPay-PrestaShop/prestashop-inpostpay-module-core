import selectorsMap from '../../shared/map/selectorsMap';
import parseToHtml from "../../shared/utils/parseToHtml";
import widgetGetRequest from "../http/widgetGetRequest";

/**
 * @param {object} event
 * @param {int} event.id_product_attribute
 */
const updateCustomProductDisplayHook = async (event) => {
  const { id_product_attribute: idProductAttribute = 0} = event;
  const selectorAppendix = '[data-hook="displayIziProductButton"]';
  const blockSelector = `${selectorsMap().inpostIziProductButtonWrapper}${selectorAppendix}`;

  const oldBlock = document.querySelector(blockSelector);
  const idProduct = oldBlock?.dataset.idProduct;

  if (!oldBlock) {
    return Promise.resolve();
  }

  if (!idProduct) {
    return Promise.reject(`No data-id-product attribute found for block ${blockSelector}`);
  }

  const response = await widgetGetRequest('displayIziProductButton', parseInt(idProduct), parseInt(idProductAttribute));

  if (!response.ok) {
    return Promise.reject(`Failed to fetch widget for product ${idProduct} and attribute ${idProductAttribute}`);
  }

  const { content } = await response.json();

  const newBlock = parseToHtml(content);

  oldBlock.replaceWith(newBlock);

  return Promise.resolve();
}

export default updateCustomProductDisplayHook;
