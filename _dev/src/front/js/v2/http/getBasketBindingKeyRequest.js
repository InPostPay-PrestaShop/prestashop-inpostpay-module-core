import endpoints from '../map/endpoints';
import useEndpointRequest from './base/useEndpointRequest';

/**
 * @returns {Promise<string|undefined>}
 */
const getBasketBindingKeyRequest = async () => {
  const { bindingKey } = endpoints;
  const { getResponse } = useEndpointRequest(bindingKey, 'GET');

  const response = await getResponse();

  if (!response.ok && response.status !== 404) {
    throw new Error('Error while fetching binding key');
  }

  if (response.status === 404) {
    return undefined;
  }

  return response.json();
};

export default getBasketBindingKeyRequest;
