import bindBasketRequest from "../http/bindBasketRequest";

/**
 * @param prefix {string}
 * @param phoneNumber {string}
 * @param bindingPlace {string}
 * @return {Promise<any>}
 */
async function iziGetPayData(
  prefix = '',
  phoneNumber = '',
  bindingPlace = 'PRODUCT_CARD'
) {
  try {
    return await bindBasketRequest(prefix, phoneNumber, bindingPlace);
  } catch (e) {
    // Based on the documentation, this method should return an empty object if the request fails.
    return {};
  }
}

export default iziGetPayData;
