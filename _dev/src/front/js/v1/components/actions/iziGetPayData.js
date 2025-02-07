import bindBasketRequest from '../http/bindBasketRequest';

/**
 * @param prefix {string}
 * @param phoneNumber {string}
 * @param bindingPlace {string}
 * @return {Promise<any>}
 */
async function iziGetPayData(prefix = '', phoneNumber = '', bindingPlace = 'PRODUCT_CARD') {
  return bindBasketRequest(prefix, phoneNumber, bindingPlace);
}

export default iziGetPayData;
