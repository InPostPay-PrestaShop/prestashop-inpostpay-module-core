export default function iziCanBeBound(productId) {
  if (!productId) {
    return true;
  }

  const productIdSelector = `[name="id_product"]`;
  const articleThumbSelector = `article[data-id-product="${productId}"] .thumbnail`;
  const productWithoutVariantsSelector = `article[data-id-product="${productId}"][data-id-product-attribute="0"]`;
  const isBoundButtonSelector = `inpost-izi-button[product-id="${productId}"][baskedlinked="true"]`;

  const productIdOnPage = window.document.querySelector(productIdSelector)?.value;
  const articleThumb = window.document.querySelector(articleThumbSelector);
  const productWithoutVariants = window.document.querySelector(productWithoutVariantsSelector);
  const isBoundButton = window.document.querySelector(isBoundButtonSelector);

  if (productWithoutVariants || productIdOnPage || !articleThumb) {
    return true;
  }

  if (isBoundButton) {
    return false;
  }
  articleThumb.click();
  return false;
}
