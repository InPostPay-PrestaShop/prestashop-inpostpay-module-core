import updateButtonCount from "../components/widget/updateButtonCount";
import updatedCartHandler from "../components/handler/updatedCartHandler";
import updateCartHandler from "../components/handler/updateCartHandler";
import updatedProductHandler from "../components/handler/updatedProductHandler";
import iziModalClosedEventHandler from "../components/handler/iziModalClosedEventHandler";
import bindIziButtonEvents from "../components/events/bindIziButtonEvents";

const mainController = () => {

  const attachEvents = () => {
    prestashop.on('updateCart', updateCartHandler);
    prestashop.on('updatedCart', updatedCartHandler);
    prestashop.on('updatedProduct', updatedProductHandler);
    document.addEventListener('iziModalEventClose', iziModalClosedEventHandler);
    bindIziButtonEvents();
  }

  const init = () => {
    if (prestashop?.cart?.products_count) {
      updateButtonCount(prestashop.cart.products_count);
    }

    attachEvents();
  }

  return {
    init
  }
}

export default mainController;
