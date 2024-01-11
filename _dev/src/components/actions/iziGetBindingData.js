import iziAddToCart from "./iziAddToCart";
import bindBasketRequest from "../http/bindBasketRequest";

/**
 * @param productId {number}
 * @return {boolean}
 */
const checkIfProductIsInCart = (productId) => {
  const products = window?.prestashop?.cart?.products;

  if (!products || products.length === 0) {
    return false;
  }

  const productInCart = products.find((product) => product.id_product === productId);

  return !!productInCart;
};


/**
 * @param id {number|string}
 * @param prefix {string}
 * @param phoneNumber {string}
 * @param bindingPlace {string}
 * @return {Promise<any>}
 */
async function iziGetBindingData(
  id,
  prefix = '',
  phoneNumber = '',
  bindingPlace = 'PRODUCT_CARD'
) {
  if (id) {
    const productIsInCart = checkIfProductIsInCart(id);

    if (!productIsInCart) {
      await iziAddToCart(id);
    }
  }

  return await bindBasketRequest(prefix, phoneNumber, bindingPlace);
}

export default iziGetBindingData;
