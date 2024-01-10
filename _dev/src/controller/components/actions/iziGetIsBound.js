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
        let binding;

        try {
          binding = parseToJson(data);
        } catch (e) {
          return;
        }

        if (binding?.phone_number) {
          close();
          resolve(binding);
        }
      }
    }

    const onError = (event) => {
      reject(new Error('An error occurred while attempting to connect.'));
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
