import bindBasketRequest from "../http/bindBasketRequest";

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
  return await bindBasketRequest(prefix, phoneNumber, bindingPlace);
}

export default iziGetBindingData;
