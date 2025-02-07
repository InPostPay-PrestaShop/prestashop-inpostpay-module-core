import endpoints from '../map/endpoints';
import useEndpointRequest from './base/useEndpointRequest';

/**
 * @param prefix {string}
 * @param phoneNumber {string}
 * @return {string}
 */
const buildEndpointUrl = (prefix, phoneNumber) => {
  const { basketPostBinding } = endpoints;

  if (!prefix && !phoneNumber) {
    return basketPostBinding;
  }

  if (prefix && phoneNumber) {
    return `${basketPostBinding}/${prefix}/${phoneNumber}`;
  }

  return '';
};

/**
 * @param prefix {string}
 * @param phoneNumber {string}
 * @param bindingPlace {string}
 * @return {Promise<*>}
 */
const bindBasketRequest = async (prefix, phoneNumber, bindingPlace) => {
  const endpointUrl = buildEndpointUrl(prefix, phoneNumber);

  const { getResponse, setParam } = useEndpointRequest(endpointUrl, 'POST');

  setParam('browser', window.iziGetBrowserData({ base64: true }));
  setParam('binding_place', bindingPlace);

  return getResponse();
};

export default bindBasketRequest;
