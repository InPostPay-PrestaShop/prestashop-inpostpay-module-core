import Server from "../commn/Server";

const BASE_URL = "index.php?fc=module&module=inpostizi&controller=backend&path=inpost/v1/izi/merchant/basket/post/binding/";

// Deprecated function, use iziGetPayData instead

async function iziGetBindingData(id, prefix, phoneNumber, bindingPlace) {
  if (id) {
    const productIsInCart = checkIfProductIsInCart(id);
    if (!productIsInCart) {
      await iziAddToCart(id);
    }
  }
  const params = {
    prefix: prefix || "",
    number: phoneNumber || "",
    slash: prefix && phoneNumber ? "/" : "",
    browser: window.iziGetBrowserData({ base64: true }),
    binding_place: bindingPlace || "PRODUCT_CARD",
  };

  const url =
    BASE_URL +
    params.prefix +
    params.slash +
    params.number +
    "&browser=" +
    params.browser +
    "&binding_place=" +
    params.binding_place;

  return Server.fetch(url, true);
}

export default iziGetBindingData;

const checkIfProductIsInCart = (productId) => {
  const products = prestashop.cart.products;

  if (products.length === 0) {
    return false;
  }

  const productInCart = products.find((product) => product.id_product === productId);

  return productInCart ? true : false;
};
