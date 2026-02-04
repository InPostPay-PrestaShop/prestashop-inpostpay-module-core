import endpoints from '../map/endpoints';
import useEndpointRequest from './base/useEndpointRequest';

/**
 * @param hookName {string}
 * @param idProduct {number}
 * @param idProductAttribute {number|null}
 * @return {string}
 */
const buildEndpointUrl = (hookName, idProduct, idProductAttribute = null) => {
  const { widgetGet } = endpoints;

  if (null !== idProductAttribute) {
    return `${widgetGet}/${hookName}/${idProduct}/${idProductAttribute}`;
  }

  return `${widgetGet}/${hookName}/${idProduct}`;
};

/**
 * @param hookName {string}
 * @param idProduct {number}
 * @param idProductAttribute {number|null}
 * @return {Promise<*>}
 */
const widgetGetRequest = async (hookName, idProduct, idProductAttribute = null) => {
  const endpointUrl = buildEndpointUrl(hookName, idProduct, idProductAttribute);

  const { getResponse } = useEndpointRequest(endpointUrl, 'GET');

  return getResponse();
};

export default widgetGetRequest;
