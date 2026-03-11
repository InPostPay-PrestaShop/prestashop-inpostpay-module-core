import selectorsMap from '../../shared/map/selectorsMap';
import parseToHtml from '../../shared/utils/parseToHtml';

/**
 * @param {object} event
 * @param {string} event.product_add_to_cart
 */
const updateProductActionsBlock = (event) => {
  const { product_add_to_cart: productAddToCartNewContent = '' } = event;
  const selectorAppendix = '[data-hook="displayProductActions"]';
  const blockSelector = `${selectorsMap().inpostIziProductButtonWrapper}${selectorAppendix}`;

  const addToCartDOM = parseToHtml(productAddToCartNewContent);
  const inpostPayContent = addToCartDOM.querySelector(blockSelector);

  const oldBlock = document.querySelector(blockSelector);

  oldBlock?.replaceWith(inpostPayContent);
};

export default updateProductActionsBlock;
