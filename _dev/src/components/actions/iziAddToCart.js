import selectorsMap from "../map/selectorsMap";
import addToCartHandler from "../handler/addToCartHandler";

/**
 * Get product form data
 * @param form {HTMLFormElement}
 * @return {{
 *   id_product: string,
 *   qty: string,
 *   id_customization: string,
 *   token: string,
 *   group: Array<{
 *   name: string,
 *   value: string,
 *   }>
 *
 * }} - object with form data
 */
const getProductFormData = (form) => {
  const formData = new FormData(form);
  const data = {};
  const group = [];

  for (let [key, value] of formData.entries()) {

    if (name.indexOf("group") === 0) {
      group.push({
        name: name,
        value: encodeURIComponent(value),
      })
    } else {
      data[key] = value;
    }
  }

  data.group = group;

  return data;
}

/**
 * @param productId
 * @return {Promise<void>}
 */
async function iziAddToCart(productId) {
  const { productPageForm } = selectorsMap();

  const formElement = document.querySelector(productPageForm);

  if (!formElement) {
    console.warn("Error while adding product to cart: product not found.");
    return;
  }

  const { group, ...restFormData } = getProductFormData(formElement);

  const formData = {
    add: 1,
    action: 'update',
    ajax: 1,
    ...restFormData,
  };

  group.forEach(item => {
    formData[item.name] = item.value;
  });

  return await addToCartHandler(formData);
}

export default iziAddToCart;
