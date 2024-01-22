import endpoints from "../map/endpoints";
import useEndpointRequest from "../http/base/useEndpointRequest";
import addToCartHandler from "../handler/addToCartHandler";

/**
 * @return {Promise<any>}
 */
async function iziBindingDelete() {
  const { getResponse } = useEndpointRequest(endpoints.basketDeleteBinding, 'DELETE');

  return await getResponse();
}

export default iziBindingDelete;
