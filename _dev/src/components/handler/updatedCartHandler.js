import updateInpostButtonsHandler from "./updateInpostButtonsHandler";

const updatedCartHandler = () => {
  // before PS version 1.7.5 the native "updateCart" listener emits "updatedCart" even if the former event was not triggered on the cart page
  if (null === document.querySelector('.js-cart')) {
    return;
  }

  updateInpostButtonsHandler();
}

export default updatedCartHandler;
