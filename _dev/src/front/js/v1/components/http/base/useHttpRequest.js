import isObject from '../../../../shared/utils/isObject';
import parseToJson from '../../../../shared/utils/parseToJson';
import useUrlBuilder from '../../../../shared/utils/urlBuilder';
import getHttpGenericError from '../getHttpGenericError';

/**
 * @param url {string}
 * @param method {string}
 * @param body {object|null}
 * @param headers {object}
 * @return {{getResponse: (function(): Promise<any>), setParam: Function, setParams: Function}}
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
   * @return {Promise<any>}
   * @throws {Error}
   */
  const getResponse = async () => {
    try {
      const requestOptions = getOptions();
      const requestUrl = getURL();

      let response = await fetch(requestUrl, requestOptions);

      if (response.status === 405 && ['DELETE', 'PUT'].includes(requestOptions.method)) {
        response = await fetch(requestUrl, {
          ...requestOptions,
          method: 'POST',
          headers: {
            ...requestOptions.headers,
            'X-HTTP-Method-Override': requestOptions.method,
          },
        });
      }

      if (response.status === 204) {
        return null;
      }

      let json = await response.json();

      if (!isObject(json) && typeof json === 'string') {
        json = parseToJson(json);
      }

      if (!response.ok) {
        if (json?.message) {
          throw new Error(json.message);
        } else {
          throw getHttpGenericError();
        }
      }

      return json;
    } catch (e) {
      // DOMException is thrown when user aborts request
      if (e instanceof DOMException) {
        return null;
      }

      throw getHttpGenericError();
    }
  };

  return {
    getResponse,
    setParams,
    setParam,
  };
};

export default useHttpRequest;
