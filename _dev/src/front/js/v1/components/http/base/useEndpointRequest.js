import useHttpRequest from './useHttpRequest';
import urlBuilder from '../../../../shared/utils/urlBuilder';
import { getBackendUrl } from '../../../../shared/confg/confg';

/**
 * @param endpoint {string}
 * @param method {string}
 * @param body {object|null}
 * @param headers {object}
 * @return {{getResponse: (function(): Promise<*>), setParam: Function, setParams: Function}}
 */
const useEndpointRequest = (endpoint, method, body = null, headers = {}) => {
  const { addParam, getURL } = urlBuilder(getBackendUrl());

  addParam('path', endpoint);

  return useHttpRequest(getURL(), method, body, headers);
};

export default useEndpointRequest;
