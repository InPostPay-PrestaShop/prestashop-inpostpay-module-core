/**
 * Unescapes the nav bar HTML if needed - PS 9.0 escapes the content of the "headerTabContent" Twig variable.
 */
(() => {
  const wrapper = document.getElementById('head_tabs');

  if (wrapper === null) {
    return;
  }

  if (wrapper.querySelector('#inpost_izi_nav_bar')) {
    return;
  }

  const parser = new DOMParser();

  wrapper.innerHTML = parser.parseFromString(wrapper.textContent, 'text/html').body.innerHTML;
})();
