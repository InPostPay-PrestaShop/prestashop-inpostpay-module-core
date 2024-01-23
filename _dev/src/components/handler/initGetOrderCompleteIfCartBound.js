import iziGetIsBound from "../actions/iziGetIsBound";
import iziGetOrderComplete from "../actions/iziGetOrderComplete";
import evenSourceCleanupHandler from "./evenSourceCleanupHandler";

const isCartBound = () => new Promise((resolve, reject) => {
  // If we don't get EventSource message in 1000ms we reject promise
  // If message event didn't trigger in EventSource cart isn't bound
  setTimeout(() => {
    reject();
  }, 1000);

  iziGetIsBound().then((data) => {
    resolve(data);
  }).catch(() => {
    reject();
  });
});

const initGetOrderCompleteIfCartBound = async () => {
  let bindingData = null;

  try {
    bindingData = await isCartBound();
  } catch (e) {
    evenSourceCleanupHandler();
    return;
  }

  if (bindingData?.inpost_basket_id && bindingData?.status === 'SUCCESS') {
    return iziGetOrderComplete();
  }
}

export default initGetOrderCompleteIfCartBound;
