import dispatchInpostUpdateCount from "../widget/dispatchInpostUpdateCount";
import getCartCountRequest from "../http/getCartCountRequest";

const updateCartHandler = async (event) => {
  let count = event?.resp?.cart?.products_count || null;

  if (null === count) {
    count = window.prestashop?.cart?.products_count || null;
  }

  if (typeof count === 'number') {
    dispatchInpostUpdateCount(count);
  } else {
    try {
      const { count = null } = await getCartCountRequest();

      if (typeof count === 'number') {
        dispatchInpostUpdateCount(count);
      }
    } catch (e) {
      console.error(e);
    }
  }
}

export default updateCartHandler;
