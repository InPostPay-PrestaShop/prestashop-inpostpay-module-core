/**
 * @param idProduct {number}
 * @return {boolean}
 */
const isIdProductForCurrentProduct = (idProduct) =>
  window?.inpostizi_product_page_id_product === idProduct;

/**
 * @param productId {number|string}
 * @return {boolean}
 */
function iziCanBeBound(productId) {
  if (!productId) {
    return true;
  }

  const idProduct = parseInt(productId, 10);

  if (isIdProductForCurrentProduct(idProduct)) {
    return true;
  }

  const isBoundButtonSelector = `inpost-izi-button[product-id="${idProduct}"][baskedlinked="true"]`;

  if (document.querySelector(isBoundButtonSelector)) {
    return false;
  }

  return false;
}

export default iziCanBeBound;
