import endpoints from "../map/endpoints";
import useEndpointRequest from "../http/base/useEndpointRequest";
import addToCartHandler from "../handler/addToCartHandler";

/**
 * @return {Promise<any>}
 */
async function iziBindingDelete() {
  const { getResponse } = useEndpointRequest(endpoints.basketDeleteBinding, 'DELETE');

  try {
    return await getResponse();
  } catch (error) {
    throw error;
  }
}

export default iziBindingDelete;
