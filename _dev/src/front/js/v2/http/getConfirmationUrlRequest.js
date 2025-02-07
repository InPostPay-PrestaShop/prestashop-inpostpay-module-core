import endpoints from '../map/endpoints';
import useEndpointRequest from './base/useEndpointRequest';

/**
 * @returns {Promise<string>}
 */
const getConfirmationUrlRequest = async () => {
  const { orderConfirmationUrl } = endpoints;
  const { getResponse } = useEndpointRequest(orderConfirmationUrl, 'GET');

  const response = await getResponse();

  if (!response.ok) {
    throw new Error('NOK');
  }

  return response.json();
};

export default getConfirmationUrlRequest;
