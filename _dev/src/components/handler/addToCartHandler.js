import useHttpRequest from "../http/base/useHttpRequest";
import buildAlertBlock from "../utils/buildAlertBlock";
import selectorsMap from "../map/selectorsMap";

/**
 * @param error {Error}
 */
const handleException = (error) => {
  const { inpostIziProductButtonWrapper, inpostIziAddToCartAlert } = selectorsMap();
  const alertBlock = buildAlertBlock(error.message, 'danger', inpostIziAddToCartAlert.replace('.', ''));

  const btnWrapper = document.querySelector(inpostIziProductButtonWrapper);

  if (btnWrapper) {
    const currentAlert = btnWrapper.querySelector(inpostIziAddToCartAlert);

    if (currentAlert) {
      currentAlert.remove();
    }

    btnWrapper.prepend(alertBlock);
  }
}

const addToCartHandler = async (formData) => {
  const { getResponse, setParams } = useHttpRequest(
    prestashop.urls.pages.cart,
    'POST',
    null,
    {
      'X-Requested-With': 'XMLHttpRequest',
      'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
    }
  );

  setParams(formData);

  const resp = await getResponse();

  if (resp?.hasError && resp?.errors && Array.isArray(resp.errors)) {
    const error = new Error(resp.errors.join("\n"));

    setTimeout(() => {
      handleException(error);
      // It's a workaround for the button 'loading' state - there is no other way to handle it
      typeof window.handleInpostIziButtons === 'function' && window.handleInpostIziButtons();
    })

    throw error;
  }

  prestashop.emit("updateCart", {
    reason: {
      idProduct: resp.id_product,
      idProductAttribute: resp.id_product_attribute,
    },
    resp,
  });
}

export default addToCartHandler;
