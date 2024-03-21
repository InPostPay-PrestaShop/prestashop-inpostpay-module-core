import endpoints from "../map/endpoints";
import useEventSourceStore from "../http/store/EventSourceStore";

const eventSourceCleanupHandler = () => {
  const { orderComplete, basketConfirmation } = endpoints;

  const {
    getEvent,
    removeEvent,
  } = useEventSourceStore();

  const eventSourceOrderComplete = getEvent(orderComplete);
  const eventSourceBasketConfirmation = getEvent(basketConfirmation);

  if (eventSourceOrderComplete) {
    eventSourceOrderComplete.close();
    removeEvent(orderComplete);
  }

  if (eventSourceBasketConfirmation) {
    eventSourceBasketConfirmation.close();
    removeEvent(basketConfirmation);
  }
}

export default eventSourceCleanupHandler;
