import isObject from '../../utils/isObject';
import parseToJson from "../../utils/parseToJson";
import useUrlBuilder from "../../utils/urlBuilder";

/**
 * @param url {string}
 * @param method {string}
 * @param body {object|null}
 * @param headers {object}
 * @return {{getResponse: (function(): Promise<any>), setParam: Function, setParams: Function}}
 */
const useHttpRequest = (url, method, body = null, headers = {}) => {
  let params = {};
  const { addParam, addParams, getURL } = useUrlBuilder(url);
  const genericErrorMessage = window?.inpostizi_generic_http_error || 'Something went wrong. Please try again later.';

  const options = {
    method,
    headers: {
      "Content-Type": "application/json",
      ...headers,
    },
  };

  if (null !== body) {
    options.body = JSON.stringify(body);
  }

  /**
   * @return {object}
   */
  const getOptions = () => {
    return options;
  }

  /**
   * @param key {string}
   * @param value {string}
   */
  const setParam = (key, value) => {
    addParam(key, value);
  }

  /**
   * @param params {object}
   */
  const setParams = (params) => {
    addParams(params);
  }

  /**
   * @return {Promise<any>}
   */
  const getResponse = async () => new Promise(async (resolve, reject) => {
    try {
      const response = await fetch(getURL(), getOptions());
      if (response.status === 204) {
        resolve();
        return;
      }

      let json = await response.json();

      if (!isObject(json)) {
        json = parseToJson(json);
      }

      if (!response.ok) {
        if (json?.message) {
          reject(new Error(json.message));
        } else {
          reject(new Error(genericErrorMessage));
        }
      } else {
        resolve(json);
      }
    } catch (e) {
      // DOMException is thrown when user aborts request
      if (e instanceof DOMException) {
        return;
      }

      reject(new Error(genericErrorMessage));
    }
  });

  return {
    getResponse,
    setParams,
    setParam,
  }
}

export default useHttpRequest;
