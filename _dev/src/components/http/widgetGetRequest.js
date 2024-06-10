import endpoints from "../map/endpoints";
import useEndpointRequest from "./base/useEndpointRequest";

/**
 * @param hookName {string}
 * @param idProduct {number}
 * @return {string}
 */
const buildEndpointUrl = (hookName, idProduct) => {
  const { widgetGet } = endpoints;

  return `${widgetGet}/${hookName}/${idProduct}`;
}

/**
 * @param hookName {string}
 * @param idProduct {number}
 * @return {Promise<*>}
 */
const widgetGetRequest = async (hookName, idProduct) => {
  const endpointUrl = buildEndpointUrl(hookName, idProduct);

  const { getResponse, setParam } = useEndpointRequest(endpointUrl, 'GET');

  return await getResponse();
}

export default widgetGetRequest;
