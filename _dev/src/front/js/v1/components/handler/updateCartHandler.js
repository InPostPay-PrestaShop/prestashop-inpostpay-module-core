import dispatchInpostUpdateCount from '../widget/dispatchInpostUpdateCount';
import getCartCountRequest from '../http/getCartCountRequest';

const updateCartHandler = async (event) => {
  let count = event?.resp?.cart?.products_count || null;

  if (null === count) {
    count = window.prestashop?.cart?.products_count || null;
  }

  if (typeof count === 'number') {
    dispatchInpostUpdateCount(count);
  } else {
    try {
      const { fetchedCount = null } = await getCartCountRequest();

      if (typeof fetchedCount === 'number') {
        dispatchInpostUpdateCount(fetchedCount);
      }
    } catch (e) {
      // eslint-disable-next-line no-console
      console.error(e);
    }
  }
};

export default updateCartHandler;
