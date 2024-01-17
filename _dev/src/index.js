import mainController from './controller/mainController.js';
import iziAddToCart from "./components/actions/iziAddToCart";
import iziBindingDelete from "./components/actions/iziBindingDelete";
import iziCanBeBound from "./components/actions/iziCanBeBound";
import iziGetBindingData from "./components/actions/iziGetBindingData";
import iziGetIsBound from "./components/actions/iziGetIsBound";
import iziGetOrderComplete from "./components/actions/iziGetOrderComplete";
import iziGetPayData from "./components/actions/iziGetPayData";
import iziMobileLink from "./components/actions/iziMobileLink";

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
