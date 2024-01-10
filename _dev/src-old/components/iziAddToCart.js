export default async function iziAddToCart(id) {
  const productIsInCart = checkIfProductIsInCart(id);
  if (productIsInCart) return;

  if (typeof prestashop === "undefined") {
    throw new Error("Prestashop is not defined.");
  }

  const formElement = document.querySelector("#add-to-cart-or-refresh");

  if (!formElement) {
    console.warn("Error while adding product to cart: product not found.");
    return;
  }

  let quantity = 1;
  let productId = id;
  let customizationId = 0;
  let group = [];

  if (formElement) {
    const formData = new FormData(formElement);
    quantity = formData.get("qty");
    productId = formData.get("id_product");
    customizationId = formData.get("id_customization");

    for (const data of formData.entries()) {
      const name = data[0];
      const value = data[1];

      if (name.indexOf("group") === 0) {
        group.push({
          name: name,
          value: encodeURIComponent(value),
        })
      }
    }
  }

  const query = new URLSearchParams({
    'add': 1,
    'action': 'update',
    'token': prestashop.static_token,
    'id_product': productId,
    'id_customization': customizationId,
    'qty': quantity,
  });

  for (const item of group) {
    query.append(item.name, item.value);
  }

  const response = await $.post(prestashop.urls.pages.cart, query.toString(), null, "json")
    .then((resp) => {
      prestashop.emit("updateCart", {
        reason: {
          idProduct: resp.id_product,
          idProductAttribute: resp.id_product_attribute,
        },
        resp,
      });
      return resp;
    })
    .catch((error) => {
      prestashop.emit("handleError", { eventType: "addProductToCart", resp: error });
      return error;
    });

  return response;
}

const checkIfProductIsInCart = (productId) => {
  const products = prestashop.cart.products;

  if (products.length === 0) {
    return false;
  }

  const productInCart = products.find((product) => product.id_product === productId);

  return productInCart ? true : false;
};
