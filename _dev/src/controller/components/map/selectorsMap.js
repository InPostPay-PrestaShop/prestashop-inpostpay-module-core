
const defaultMap = {
  productPageForm: '#add-to-cart-or-refresh',
  inpostIziButton: 'inpost-izi-button',
};

/**
 * @return {{
 *   productPageForm: string,
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
