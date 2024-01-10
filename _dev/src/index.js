import mainController from './controller/mainController.js';
import iziAddToCart from "./controller/components/actions/iziAddToCart";
import iziBindingDelete from "./controller/components/actions/iziBindingDelete";
import iziCanBeBound from "./controller/components/actions/iziCanBeBound";
import iziGetBindingData from "./controller/components/actions/iziGetBindingData";
import iziGetIsBound from "./controller/components/actions/iziGetIsBound";
import iziGetOrderComplete from "./controller/components/actions/iziGetOrderComplete";
import iziGetPayData from "./controller/components/actions/iziGetPayData";
import iziMobileLink from "./controller/components/actions/iziMobileLink";

window.iziAddToCart = iziAddToCart;
window.iziBindingDelete = iziBindingDelete;
window.iziCanBeBound = iziCanBeBound;
window.iziGetBindingData = iziGetBindingData;
window.iziGetIsBound = iziGetIsBound;
window.iziGetOrderComplete = iziGetOrderComplete;
window.iziGetPayData = iziGetPayData;
window.iziMobileLink = iziMobileLink;

document.addEventListener('DOMContentLoaded', () => {
  mainController().init();
});
