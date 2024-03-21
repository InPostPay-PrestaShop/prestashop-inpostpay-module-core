import widgetState from "../state/widgetState";
import eventSourceCleanupHandler from "./eventSourceCleanupHandler";

const modalCloseHack = () => {
  // DUMMY HACK - STAYS FOR NOW - for some reason modal is not removed from DOM after close in inpostizi.js
  setTimeout(() => {
    const modal = document.querySelector('.inpostizi-modal');

    if (modal) {
      modal.remove();
    }
  }, 1);
}

/**
 * @param {Event} event
 */
const iziModalClosedEventHandler = (event) => {
  modalCloseHack();

  const { getCartBound } = widgetState();

  // DUMB CONDITION RACE FIX - modal is closed before binding is complete
  setTimeout(() => {
    if (!getCartBound()) {
      eventSourceCleanupHandler();
    }
  }, 50);
}

export default iziModalClosedEventHandler

