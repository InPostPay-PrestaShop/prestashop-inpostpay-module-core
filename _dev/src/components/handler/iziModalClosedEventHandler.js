import widgetState from "../state/widgetState";
import evenSourceCleanupHandler from "./evenSourceCleanupHandler";

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
      evenSourceCleanupHandler();
    }
  }, 1)
}

export default iziModalClosedEventHandler

