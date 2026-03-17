import useHttpRequest from './base/useHttpRequest';
import { getCartControllerUrl } from '../../shared/confg/confg';
import urlBuilder from '../../shared/utils/urlBuilder';

/**
 * @return {Promise<object>}
 */
const getCartRequest = async () => {
  const { addParam, getURL } = urlBuilder(getCartControllerUrl());

  addParam('token', window.prestashop.static_token);

  const { getResponse } = useHttpRequest(getURL(), 'GET');

  const response = await getResponse();

  if (!response.ok) {
    throw new Error('Could not fetch cart data');
  }

  return response.json();
};

export default getCartRequest;
