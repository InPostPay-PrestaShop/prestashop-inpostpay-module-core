import getConfirmationUrlRequest from '../http/getConfirmationUrlRequest';

const handleBasketEvent = async (event) => {
  if (event === 'basketProductChanged' || event === 'basketDeleted') {
    prestashop.emit('updateCart', {
      reason: {
        linkAction: 'refresh',
      },
      resp: {},
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
