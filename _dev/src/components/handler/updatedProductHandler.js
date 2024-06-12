import updateInpostButtonsHandler from "./updateInpostButtonsHandler";
import parseToHtml from "../utils/parseToHtml";
import selectorsMap from "../map/selectorsMap";

/**
 * Handle update product action hook
 * @param {string} addToCartTemplate - Add to cart html string
 */
const handleUpdateProductActionHook = (addToCartTemplate) => {
  const { inpostIziProductButtonWrapper } = selectorsMap();
  const html = parseToHtml(addToCartTemplate);
  const newButtonWrapper = html.querySelector(inpostIziProductButtonWrapper);
  const currentButtonWrapper = document.querySelector(`${inpostIziProductButtonWrapper}[data-hook="displayProductActions"]`);

  if (newButtonWrapper && currentButtonWrapper) {
    currentButtonWrapper.replaceWith(newButtonWrapper);
  }
}

const updatedProductHandler = (e) => {
  const addToCartTemplate = e?.product_add_to_cart;

  if (addToCartTemplate) {
    handleUpdateProductActionHook(addToCartTemplate);
  }

  updateInpostButtonsHandler();
}

export default updatedProductHandler;
