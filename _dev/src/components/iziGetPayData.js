import Server from "../commn/Server";

const BASE_URL = "index.php?fc=module&module=inpostizi&controller=backend&path=inpost/v1/izi/merchant/basket/post/binding/";

export default async function iziGetPayData(prefix, phoneNumber, bindingPlace) {
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
