import { getCartUrl } from "./confg/confg";
import useHttpRequest from "./base/useHttpRequest";

/**
 * Get cart count request
 * @returns {Promise<*>}
 */
const getCartCountRequest = async () => {
  const { getResponse } = useHttpRequest(getCartUrl(), 'GET');

  return await getResponse();
}

export default getCartCountRequest;
