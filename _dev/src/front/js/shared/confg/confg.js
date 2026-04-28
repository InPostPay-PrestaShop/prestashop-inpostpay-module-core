const BACKEND_AJAX_URL = window.inpostizi_backend_ajax_url;
const CART_CONTROLLER_URL = window.inpostizi_cart_controller_url;

// eslint-disable-next-line import/prefer-default-export
export const getBackendUrl = () => {
  return BACKEND_AJAX_URL;
};

// eslint-disable-next-line import/prefer-default-export
export const getCartControllerUrl = () => {
  return CART_CONTROLLER_URL;
};
