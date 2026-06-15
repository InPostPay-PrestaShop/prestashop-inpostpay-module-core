import useHttpRequest from '../http/base/useHttpRequest';
import getBindingApiKey from './getBindingApiKey';
import handleAddToCartException from '../../shared/handler/handleAddToCartException';
import UndeliverableProductError from '../error/UndeliverableProductError';

const addToCartHandler = async (formData) => {
  const { getResponse, setParams } = useHttpRequest(prestashop.urls.pages.cart, 'POST', null, {
    'X-Requested-With': 'XMLHttpRequest',
    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
  });

  setParams(formData);

  const response = await getResponse();
  const resp = await response.json();

  if (resp?.hasError && resp?.errors && Array.isArray(resp.errors)) {
    const error = new Error(resp.errors.join('\n'));

    setTimeout(() => {
      handleAddToCartException(error);
    });

    throw new UndeliverableProductError({ cause: error });
  }

  try {
    prestashop.emit('updateCart', {
      reason: {
        idProduct: resp.id_product,
        idProductAttribute: resp.id_product_attribute,
        idCustomization: resp.id_customization,
        cart: resp.cart,
        linkAction: 'inpostizi-widget-bind',
      },
      resp,
    });
  } catch (err) {
    // Listener failures must not affect widget flow
    // eslint-disable-next-line no-console
    console.error('updateCart listener failed', err);
  }

  return getBindingApiKey();
};

export default addToCartHandler;
