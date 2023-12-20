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
  let storeButton;
  if (window.document.querySelector(".page-cart")) {
    storeButton = window.document.querySelector(".cart-grid #inpostizi_block_home");
  }
  if (storeButton) {
    storeButton = storeButton.cloneNode(true);
  }

  if (typeof prestashop !== "undefined") {
    updateCountOnLoad(prestashop.cart.products_count);
    prestashop.on("updateCart", function (event) {
      const count = event?.resp?.cart.products_count || prestashop.cart.products_count || null;

      if (null !== count) {
        updateCount(count);
      }
    });

    prestashop.on("updatedCart", () => {
      if (window.document.querySelector(".page-cart")) {
        const cartButton = window.document.querySelector(".cart-grid #inpostizi_block_home");
        if (!cartButton) {
          const target = window.document.querySelector(".checkout.cart-detailed-actions");
          if (target) {
            target.append(storeButton);
            window.handleInpostIziButtons();
            setTimeout(updateWithFetch, 1000);
          }
        }
      } else {
        updateWithFetch();
      }
    });

    prestashop.on("updatedProduct", function (event) {
      window.handleInpostIziButtons();
    });
  }

  let cartButton = window.document.querySelector('.cart-grid #inpostizi_block_home');
  if (cartButton) {
    let target = window.document.querySelector('.checkout.cart-detailed-actions');
    if (target) {
      target.append(cartButton);
      let cartButtons = window.document.querySelectorAll('.cart-grid #inpostizi_block_home');
      if (cartButtons.length > 1) {
        cartButtons[0].remove();
      }
    }
  }
}

function updateWithFetch() {
  const baseURI = window.location.origin;
  $.ajax({
    url: baseURI + "/index.php?fc=module&module=inpostizi&controller=cart",
    type: "GET",
    dataType: "json",
    success: (data) => {
      if (data.count) {
        updateCount(data.count);
      } else {
        updateCount(0);
      }
    },
    error: (error) => {
      console.error("Error retrieving cart details: ", error);
    }
  });
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
