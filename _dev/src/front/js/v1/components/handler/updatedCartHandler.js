import updateInpostButtonsHandler from './updateInpostButtonsHandler';

const updatedCartHandler = () => {
  // before PS version 1.7.5 the native "updateCart" listener emits
  // "updatedCart" even if the former event was not triggered on the cart page
  if (document.querySelector('.js-cart') === null) {
    return;
  }

  updateInpostButtonsHandler();
};

export default updatedCartHandler;
