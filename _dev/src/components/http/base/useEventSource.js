import { getBackendUrl } from "../confg/confg";
import useUrlBuilder from "../../utils/urlBuilder";
import useEventSourceStore from "../store/EventSourceStore";

const {
  getEvent,
  setEvent,
  removeEvent,
} = useEventSourceStore();

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
   * Close Event source
   * @return {void}
   */
  const close = () => {
    const eventSource = getEvent(endpoint);

    if (eventSource) {
      eventSource.close();
      removeEvent(endpoint);
    }
  }

  /**
   * Open Event source and close previous one
   * @return {void}
   */
  const open = () => {
    const eventSource = new EventSource(getEventSourceURL());

    const handleOpen = () => {
      close();
      setEvent(endpoint, eventSource);
      eventSource.removeEventListener('open', handleOpen);
    }

    eventSource.addEventListener('open', handleOpen);
    eventSource.addEventListener('message', onMessage);
    eventSource.addEventListener('error', onError);
  }

  return {
    close,
    open,
  }
}

export default useEventSource;
