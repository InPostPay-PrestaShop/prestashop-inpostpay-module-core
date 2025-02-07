import useHttpRequest from '../http/base/useHttpRequest';
import handleAddToCartException from '../../../shared/handler/handleAddToCartException';

const addToCartHandler = async (formData) => {
  const { getResponse, setParams } = useHttpRequest(prestashop.urls.pages.cart, 'POST', null, {
    'X-Requested-With': 'XMLHttpRequest',
    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
  });

  setParams(formData);

  const resp = await getResponse();

  if (resp?.hasError && resp?.errors && Array.isArray(resp.errors)) {
    const error = new Error(resp.errors.join('\n'));

    setTimeout(() => {
      handleAddToCartException(error);
      // It's a workaround for the button 'loading' state - there is no other way to handle it
      if (typeof window.handleInpostIziButtons === 'function') {
        window.handleInpostIziButtons();
      }
    });

    throw error;
  }

  prestashop.emit('updateCart', {
    reason: {
      idProduct: resp.id_product,
      idProductAttribute: resp.id_product_attribute,
    },
    resp,
  });
};

export default addToCartHandler;
