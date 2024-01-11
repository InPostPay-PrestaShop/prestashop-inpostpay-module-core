import useEventSource from "../http/base/useEventSource";
import endpoints from "../map/endpoints";
import parseToJson from "../utils/parseToJson";

/**
 * @return {Promise<any>}
 */
function iziGetIsBound() {
  return new Promise((resolve, reject) => {
    const onMessage = (event) => {
      const { data = null } = event;

      if (data) {
        let parsedData;

        try {
          parsedData = parseToJson(data);
        } catch (e) {
          return;
        }

        if (parsedData?.phone_number) {
          resolve(parsedData);
          close();
        }
      }
    }

    const onError = (event) => {
      // We don't want to reject promise if connection is in progress
      if (event.target.readyState === EventSource.CONNECTING) {
        return;
      }

      reject(new Error('An error occurred while attempting to connect.'));
      close();
    }

    const {
      open,
      close,
    } = useEventSource(
      endpoints.basketConfirmation,
      onMessage,
      onError
    );

    open();
  });
}

export default iziGetIsBound;
