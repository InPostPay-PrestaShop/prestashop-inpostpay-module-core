
const defaultMap = {
  productPageForm: '#add-to-cart-or-refresh',
  inpostIziButton: 'inpost-izi-button',
  inpostIziProductButtonWrapper: '.js-inpost-izi-product-btn-wrapper'
};

/**
 * @return {{
 *   productPageForm: string,
 *   inpostIziButton: string,
 *   inpostIziProductButtonWrapper: string
 * }}
 */
const selectorsMap = () => {
  if (typeof window.inpost_izi_selectors_map !== "undefined") {
    return  {
      ...defaultMap,
      ...window.inpost_izi_selectors_map
    }
  } else {
    return defaultMap;
  }
};

export default selectorsMap;
