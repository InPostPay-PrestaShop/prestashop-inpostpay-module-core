import updateButtonCount from '../components/widget/updateButtonCount';
import updatedCartHandler from '../components/handler/updatedCartHandler';
import updateCartHandler from '../components/handler/updateCartHandler';
import updatedProductHandler from '../components/handler/updatedProductHandler';
import iziModalClosedEventHandler from '../components/handler/iziModalClosedEventHandler';
import bindIziButtonEvents from '../components/events/bindIziButtonEvents';
import initGetOrderCompleteIfCartBound from '../components/handler/initGetOrderCompleteIfCartBound';
import refreshProductButtonHandler from '../components/handler/refreshProductButtonHandler';
import selectorsMap from '../../shared/map/selectorsMap';
import eventSourceCleanupHandler from '../components/handler/eventSourceCleanupHandler';

const mainController = () => {
  const attachEvents = () => {
    prestashop.on('updateCart', updateCartHandler);
    prestashop.on('updatedCart', updatedCartHandler);
    prestashop.on('updatedProduct', updatedProductHandler);
    document.addEventListener('iziModalEventClose', iziModalClosedEventHandler);
    window.addEventListener('beforeunload', eventSourceCleanupHandler);
    bindIziButtonEvents();
  };

  const refreshProductButtons = () => {
    const buttons = document.querySelectorAll(selectorsMap().inpostIziProductButtonWrapper);

    buttons.forEach((button) => {
      if (button.getAttribute('data-refresh') !== 'true') {
        return;
      }

      const idProduct = parseInt(button.getAttribute('data-id-product'), 10);
      const hookName = button.getAttribute('data-hook');

      refreshProductButtonHandler(button, hookName, idProduct);
    });
  };

  const init = () => {
    if (prestashop?.cart?.products_count) {
      updateButtonCount(prestashop.cart.products_count);
    }

    initGetOrderCompleteIfCartBound();
    attachEvents();
    refreshProductButtons();
  };

  return {
    init,
  };
};

export default mainController;
