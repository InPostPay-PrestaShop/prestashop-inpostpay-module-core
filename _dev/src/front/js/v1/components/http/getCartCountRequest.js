import { getCartUrl } from '../../../shared/confg/confg';
import useHttpRequest from './base/useHttpRequest';

/**
 * Get cart count request
 * @returns {Promise<*>}
 */
const getCartCountRequest = async () => {
  const { getResponse } = useHttpRequest(getCartUrl(), 'GET');

  return getResponse();
};

export default getCartCountRequest;
