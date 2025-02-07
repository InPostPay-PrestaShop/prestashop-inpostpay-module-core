/**
 * @type {{cartBound: boolean}}
 */
const state = {
  cartBound: false,
};

/**
 * Application state for cart binding
 * @return {{
 * setCartBound: function,
 * getCartBound: function,
 * }}
 */
const widgetState = () => {
  /**
   * @return {boolean}
   */
  const getCartBound = () => state.cartBound;

  /**
   * @param value {boolean}
   */
  const setCartBound = (value) => {
    state.cartBound = value;
  };

  return {
    getCartBound,
    setCartBound,
  };
};

export default widgetState;
