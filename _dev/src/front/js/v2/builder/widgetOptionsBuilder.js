/**
 * @typedef {Object} WidgetOptions
 * @property {string} merchantClientId
 * @property {undefined|string|Promise<string>} basketBindingApiKey
 * @property {Promise<string>} unboundWidgetClicked
 * @property {function} handleBasketEvent
 * @property {string|undefined} apiBaseUrl
 * @property {boolean} webView
 * @property {string} language
 */

const widgetOptionsBuilder = () => {
  /** @type {WidgetOptions} */
  const widgetOptions = {
    merchantClientId: '',
    basketBindingApiKey: undefined,
    unboundWidgetClicked: undefined,
    handleBasketEvent: () => false,
    apiBaseUrl: undefined,
    webView: false,
    language: 'pl',
  };

  /**
   * @param merchantClientId {string}
   */
  const setMerchantClientId = (merchantClientId) => {
    widgetOptions.merchantClientId = merchantClientId;
  };

  /**
   * @param basketBindingApiKey {undefined|string|Promise<string>}
   */
  const setBasketBindingApiKey = (basketBindingApiKey) => {
    widgetOptions.basketBindingApiKey = basketBindingApiKey;
  };

  /**
   * @param unboundWidgetClicked {Promise<string>}
   */
  const setUnboundWidgetClicked = (unboundWidgetClicked) => {
    widgetOptions.unboundWidgetClicked = unboundWidgetClicked;
  };

  /**
   * @param handleBasketEvent {function}
   */
  const setHandleBasketEvent = (handleBasketEvent) => {
    widgetOptions.handleBasketEvent = handleBasketEvent;
  };

  /**
   * @param apiBaseUrl {string}
   */
  const setApiBaseUrl = (apiBaseUrl) => {
    widgetOptions.apiBaseUrl = apiBaseUrl;
  };

  /**
   * @param webView {boolean}
   */
  const setWebView = (webView) => {
    widgetOptions.webView = webView;
  };

  /**
   * @param language {string}
   */
  const setLanguage = (language) => {
    widgetOptions.language = language;
  };

  /**
   * @return {WidgetOptions}
   */
  const build = () => {
    const filteredOptions = {};

    for (const key in widgetOptions) {
      if (widgetOptions[key] !== 'undefined') {
        filteredOptions[key] = widgetOptions[key];
      }
    }

    return filteredOptions;
  };

  return {
    setMerchantClientId,
    setBasketBindingApiKey,
    setUnboundWidgetClicked,
    setHandleBasketEvent,
    setApiBaseUrl,
    setWebView,
    setLanguage,
    build,
  };
};

export default widgetOptionsBuilder;
