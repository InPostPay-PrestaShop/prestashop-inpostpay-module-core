import { getBackendUrl } from "../confg/confg";
import useUrlBuilder from "../../utils/urlBuilder";

const eventSourceMap = new Map();

/**
 * @param endpoint {string}
 * @param onMessage {function}
 * @param onError {function}
 * @return {{close: function, open: function}}
 */
const useEventSource = (endpoint, onMessage, onError = () => {}) => {
  /**
   * Build Event source url
   * @return {string}
   */
  const getEventSourceURL = () => {
    const { addParam, getURL } = useUrlBuilder(getBackendUrl());

    addParam('path', endpoint);

    return getURL();
  }

  /**
   * @return {EventSource|null}
   */
  const getEventSource = () => {
    return eventSourceMap.get(endpoint);
  }

  /**
   * @param {EventSource} eventSource
   * @return {void}
   */
  const setEventSource = (eventSource) => {
    eventSourceMap.set(endpoint, eventSource);
  }

  /**
   * Close Event source
   * @return {void}
   */
  const close = () => {
    const eventSource = getEventSource();

    if (eventSource) {
      eventSource.close();
    }
  }

  /**
   * Open Event source and close previous one
   * @return {void}
   */
  const open = () => {
    close();

    const eventSource = new EventSource(getEventSourceURL());
    eventSource.addEventListener('message', onMessage);
    eventSource.addEventListener('error', onError);

    setEventSource(eventSource);
  }

  return {
    close,
    open,
  }
}

export default useEventSource;
