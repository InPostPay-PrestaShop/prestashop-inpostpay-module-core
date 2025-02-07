import selectorsMap from '../../../shared/map/selectorsMap';
import addToCartHandler from '../handler/addToCartHandler';

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

  for (const [key, value] of formData.entries()) {
    if (key.indexOf('group') === 0) {
      group.push({
        key,
        value: encodeURIComponent(value),
      });
    } else {
      data[key] = value;
    }
  }

  data.group = group;

  return data;
};

/**
 * @return {void}
 */
async function iziAddToCart() {
  const { productPageForm } = selectorsMap();

  const formElement = document.querySelector(productPageForm);

  if (!formElement) {
    // eslint-disable-next-line no-console
    console.warn('Error while adding product to cart: product not found.');
    return;
  }

  const { group, ...restFormData } = getProductFormData(formElement);

  const formData = {
    add: 1,
    action: 'update',
    ajax: 1,
    ...restFormData,
  };

  group.forEach((item) => {
    formData[item.name] = item.value;
  });

  await addToCartHandler(formData);
}

export default iziAddToCart;
