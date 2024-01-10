
const useUrlBuilder = (url) => {
  const urlObject = new URL(url);

  /**
   * @param key
   * @param value
   */
  const addParam = (key, value) => {
    urlObject.searchParams.set(key, value);
  }

  /**
   * @param params {object}
   */
  const addParams = (params) => {
    const keys = Object.keys(params);

    keys.forEach(key => {
      addParam(key, params[key]);
    });
  }

  /**
   * @return {string}
   */
  const getURL = () => {
    return urlObject.toString();
  }

  return {
    getURL,
    addParam,
    addParams,
  }
}

export default useUrlBuilder;
