import useEventSource from '../http/base/useEventSource';
import endpoints from '../map/endpoints';
import parseToJson from '../../../shared/utils/parseToJson';
import getHttpGenericError from '../http/getHttpGenericError';

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
          // eslint-disable-next-line no-use-before-define
          close();
        }
      }
    };

    const onError = (event) => {
      // We don't want to reject promise if connection is in progress
      if (event.target.readyState === EventSource.CONNECTING) {
        return;
      }

      reject(getHttpGenericError());
      // eslint-disable-next-line no-use-before-define
      close();
    };

    const { open, close } = useEventSource(endpoints.basketConfirmation, onMessage, onError);

    open();
  });
}

export default iziGetIsBound;
