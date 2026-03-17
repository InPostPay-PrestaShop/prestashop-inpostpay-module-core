import getConfirmationUrlRequest from '../http/getConfirmationUrlRequest';
import getCartRequest from '../http/getCartRequest';

/**
 * @return {Promise<object>}
 */
const getCart = async () => {
  try {
    return await getCartRequest();
  } catch (e) {
    return {
      cart: window.prestashop.cart,
    };
  }
};

const handleBasketEvent = async (event) => {
  if (event === 'basketProductChanged' || event === 'basketDeleted') {
    prestashop.emit('updateCart', {
      reason: {
        linkAction: 'refresh',
      },
      resp: await getCart(),
    });
  } else if (event === 'orderCreated') {
    try {
      window.location.href = await getConfirmationUrlRequest();
    } catch (e) {
      return false;
    }
  }

  return true;
};

export default handleBasketEvent;
