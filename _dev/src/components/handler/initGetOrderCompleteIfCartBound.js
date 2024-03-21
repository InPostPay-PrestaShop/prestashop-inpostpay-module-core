import iziGetIsBound from "../actions/iziGetIsBound";
import iziGetOrderComplete from "../actions/iziGetOrderComplete";
import eventSourceCleanupHandler from "./eventSourceCleanupHandler";

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
    eventSourceCleanupHandler();
    return;
  }

  if (bindingData?.inpost_basket_id && bindingData?.status === 'SUCCESS') {
    const result = await iziGetOrderComplete();

    // We have to handle it by ourselves because inpostizi don't run iziGetOrderComplete if there is not button on the page
    if ('redirect' === result?.action && result.redirect) {
      window.location.href = result.redirect
    } else if ('refresh' === result?.action) {
      window.location = window.location.href
    }
  }
}

export default initGetOrderCompleteIfCartBound;
