import iziGetBindingData from "./components/iziGetBindingData";
import iziGetIsBound from "./components/iziGetIsBound";
import iziGetOrderComplete from "./components/iziGetOrderComplete";
import iziBindingDelete from "./components/iziBindingDelete";
import iziCanBeBound from "./components/iziCanBeBound";
import iziMobileLink from "./components/iziMobileLink";
import iziAddToCart from "./components/iziAddToCart";
import iziGetPayData from "./components/iziGetPayData";
import settings from "../package.json";

window.iziGetBindingData = iziGetBindingData;
window.iziGetIsBound = iziGetIsBound;
window.iziGetOrderComplete = iziGetOrderComplete;
window.iziBindingDelete = iziBindingDelete;
window.iziCanBeBound = iziCanBeBound;
window.iziAddToCart = iziAddToCart;
window.iziMobileLink = iziMobileLink;
window.iziGetPayData = iziGetPayData;
window.INPOST_PAY_PRESTASHOP_VERSION = settings.version_presta;

function handleDOMLoaded() {
  if (typeof prestashop !== "undefined") {
    updateCountOnLoad(prestashop.cart.products_count);
    prestashop.on("updateCart", function (event) {
      updateCount(event?.resp?.cart.products_count || prestashop.cart.products_count || 0);
    });
  }

  let cartButton = window.document.querySelector('.cart-grid  #inpostizi_block_home');
  if (cartButton) {
    let target = window.document.querySelector('.checkout.cart-detailed-actions');
    if (target) {
      target.append(cartButton);
      cartButton.remove();
      let cartButtons = window.document.querySelectorAll('.cart-grid  #inpostizi_block_home');
      if (cartButtons.length > 1) {
        cartButtons[0].remove();
      }
    }
  }
}

function updateCount(count = 0) {
  const event = new CustomEvent("inpost-update-count", { detail: count });
  const inpostIziButtonCollection = document.getElementsByTagName("inpost-izi-button");

  for (const button of inpostIziButtonCollection) {
    button.dispatchEvent(event);
  }
}

function updateCountOnLoad(count = 0) {
  const inpostIziButtonCollection = document.getElementsByTagName("inpost-izi-button");

  for (const button of inpostIziButtonCollection) {
    button.attributes["count"].value = count;
  }
}

document.addEventListener("DOMContentLoaded", handleDOMLoaded);
