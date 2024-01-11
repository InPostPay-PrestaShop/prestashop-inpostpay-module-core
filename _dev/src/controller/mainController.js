import updateButtonCount from "../components/widget/updateButtonCount";
import updatedCartHandler from "../components/handler/updatedCartHandler";
import updateCartHandler from "../components/handler/updateCartHandler";
import updatedProductHandler from "../components/handler/updatedProductHandler";

const mainController = () => {

  const attachEvents = () => {
    prestashop.on('updateCart', updateCartHandler);
    prestashop.on('updatedCart', updatedCartHandler);
    prestashop.on('updatedProduct', updatedProductHandler);
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
