import useUrlBuilder from '../../../shared/utils/urlBuilder';

/**
 * @param url {string}
 * @param method {string}
 * @param body {object|null}
 * @param headers {object}
 * @return {{getResponse: (function(): Promise<Response>), setParam: Function, setParams: Function}}
 */
const useHttpRequest = (url, method, body = null, headers = {}) => {
  const { addParam, addParams, getURL } = useUrlBuilder(url);

  const options = {
    method,
    headers: {
      'Content-Type': 'application/json',
      ...headers,
    },
  };

  if (body !== null) {
    options.body = JSON.stringify(body);
  }

  /**
   * @return {object}
   */
  const getOptions = () => {
    return options;
  };

  /**
   * @param key {string}
   * @param value {string}
   */
  const setParam = (key, value) => {
    addParam(key, value);
  };

  /**
   * @param params {object}
   */
  const setParams = (params) => {
    addParams(params);
  };

  /**
   * @return {Promise<Response>}
   */
  const getResponse = () => {
    const requestOptions = getOptions();
    const requestUrl = getURL();

    return fetch(requestUrl, requestOptions);
  };

  return {
    getResponse,
    setParams,
    setParam,
  };
};

export default useHttpRequest;
