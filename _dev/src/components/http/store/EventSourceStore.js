
const storesMap = new Map();

const useEventSourceStore = () => {
  /**
   * @param endpoint
   * @return {EventSource|null}
   */
  const getEvent = (endpoint) => {
    return storesMap.get(endpoint);
  }

  /**
   * @param {string} endpoint
   * @param {EventSource} event
   * @return {void}
   */
  const setEvent = (endpoint, event) => {
    storesMap.set(endpoint, event);
  }

  /**
   * @param {string} endpoint
   */
  const removeEvent = (endpoint) => {
    storesMap.delete(endpoint);
  }

  return {
    getEvent,
    setEvent,
    removeEvent,
  }
}

export default useEventSourceStore;
