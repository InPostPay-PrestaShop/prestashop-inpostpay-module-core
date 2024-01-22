import useHttpRequest from "../http/base/useHttpRequest";

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
    throw new Error(resp.errors.join("\n"));
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
