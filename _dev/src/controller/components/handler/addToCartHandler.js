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

  try {
    const resp = await getResponse();

    prestashop.emit("updateCart", {
      reason: {
        idProduct: resp.id_product,
        idProductAttribute: resp.id_product_attribute,
      },
      resp,
    });
  } catch (error) {
    prestashop.emit("handleError", { eventType: "addProductToCart", resp: error });
  }
}

export default addToCartHandler;
