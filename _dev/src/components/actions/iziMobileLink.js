import endpoints from "../map/endpoints";
import useEndpointRequest from "../http/base/useEndpointRequest";

/**
 * @return {Promise<any>}
 */
async function iziMobileLink() {
  const { getResponse } = useEndpointRequest(endpoints.basketGetLink, 'GET');

  return await getResponse();
}

export default iziMobileLink;
