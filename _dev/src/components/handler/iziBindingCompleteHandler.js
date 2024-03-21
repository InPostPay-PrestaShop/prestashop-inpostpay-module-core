import widgetState from "../state/widgetState";
import eventSourceCleanupHandler from "./eventSourceCleanupHandler";

const iziBindingCompleteHandler = (e) => {
  const { setCartBound, getCartBound } = widgetState();
  const { detail = null } = e;

  if (detail?.masked_phone_number // Binding cart on pageload
    || detail?.basket_linked // Binding cart on application binding
  ) {
    setCartBound(true);
  } else if (detail?.basketLinked === false) { // Unbinding cart on widget unbind request
    setCartBound(false);
  }

  if (!getCartBound()) {
    eventSourceCleanupHandler();
  }
}

export default iziBindingCompleteHandler;
