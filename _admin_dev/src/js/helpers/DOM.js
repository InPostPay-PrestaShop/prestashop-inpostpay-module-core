/**
 * DOMReady function runs a callback function when the DOM is ready (DOMContentLoaded)
 * @param callback {function} - callback function
 */
export const DOMReady = (callback) => {
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', callback);
  } else {
    callback();
  }
};
