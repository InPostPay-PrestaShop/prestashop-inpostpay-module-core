import getBasketBindingKeyRequest from '../http/getBasketBindingKeyRequest';
import WidgetError from '../error/WidgetError';

/**
 *
 * @param forceNew {boolean}
 * @return {undefined|string|Promise<string>}
 */
const getBindingApiKey = (forceNew = false) => {
  if (forceNew === false && window.inpostizi_binding_api_key !== null) {
    return window.inpostizi_binding_api_key;
  }

  if (forceNew === false && !window.inpostizi_fetch_binding_key) {
    return undefined;
  }

  return new Promise((resolve, reject) => {
    getBasketBindingKeyRequest()
      .then((key) => {
        resolve(key);
      })
      .catch((err) => {
        reject(new WidgetError('BINDING_KEY_FETCH_FAILED', { cause: err }));
      });
  });
};

export default getBindingApiKey;
