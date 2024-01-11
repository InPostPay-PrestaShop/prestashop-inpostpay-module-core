import useEventSource from "../http/base/useEventSource";
import endpoints from "../map/endpoints";
import parseToJson from "../utils/parseToJson";
import isObject from "../utils/isObject";

function iziGetOrderComplete() {
  return new Promise((resolve, reject) => {

    const onMessage = (event) => {
      const {data = null} = event;

      if (data) {
        let parsedData;
        let actionData;

        try {
          parsedData = parseToJson(data);
        } catch (e) {
          return;
        }

        if (parsedData?.redirect) {
          actionData = { action: "redirect", redirect: parsedData.redirect };
        } else if (parsedData?.action === "delete" || parsedData?.action === "refresh") {
          // Disabled for now we don't have to refresh page on cart changes
          actionData = { action: "refresh" };

          if (parsedData?.action === 'refresh') {
            prestashop.emit('updateCart', {
              reason: {
                linkAction: 'refresh'
              },
              resp: {}
            });

            return;
          }
        }

        if (actionData) {
          resolve(actionData);
        }
      }
    }

    const onError = () => {}

    const {
      open,
    } = useEventSource(
      endpoints.orderComplete,
      onMessage,
      onError
    );

    open();
  });
}

export default iziGetOrderComplete;
