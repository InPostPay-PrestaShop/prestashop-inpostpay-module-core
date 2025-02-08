import {DOMReady} from './helpers/DOM';

DOMReady(() => {
  /** @type {HTMLTemplateElement|null} */
  const tabTemplate = document.getElementById('inpostizi_form_tab');
  const form = document.getElementById('cart_rule_form');

  if (null !== tabTemplate && null !== form) {
    form.appendChild(tabTemplate.content.cloneNode(true));
  }

  /** @type {HTMLTemplateElement|null} */
  const linkTemplate = document.getElementById('inpostizi_nav_link');
  const nav = document.getElementById('cart_rule_link_actions').closest('ul');

  if (null !== linkTemplate && null !== nav) {
    nav.appendChild(linkTemplate.content.cloneNode(true));
  }
});
