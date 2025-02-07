import getBasketBindingKeyRequest from '../http/getBasketBindingKeyRequest';

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
      .catch(() => {
        // eslint-disable-next-line prefer-promise-reject-errors
        reject(undefined);
      });
  });
};

export default getBindingApiKey;
