(()=>{(()=>{"use strict";var m={};(()=>{const c=s=>{document.readyState==="loading"?document.addEventListener("DOMContentLoaded",s):s()};/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */const{$:a}=window;class l{constructor(n){return this.$container=a(n),this.$container.on("click",".js-input-wrapper",e=>{const t=a(e.currentTarget);this.toggleChildTree(t)}),this.$container.on("click",".js-toggle-choice-tree-action",e=>{const t=a(e.currentTarget);this.toggleTree(t)}),{enableAutoCheckChildren:()=>this.enableAutoCheckChildren(),enableAllInputs:()=>this.enableAllInputs(),disableAllInputs:()=>this.disableAllInputs()}}enableAutoCheckChildren(){this.$container.on("change",'input[type="checkbox"]',n=>{const e=a(n.currentTarget);e.closest("li").find('ul input[type="checkbox"]').prop("checked",e.is(":checked"))})}enableAllInputs(){this.$container.find("input").removeAttr("disabled")}disableAllInputs(){this.$container.find("input").attr("disabled","disabled")}toggleChildTree(n){const e=n.closest("li");if(e.hasClass("expanded")){e.removeClass("expanded").addClass("collapsed");return}e.hasClass("collapsed")&&e.removeClass("collapsed").addClass("expanded")}toggleTree(n){const e=n.closest(".js-choice-tree-container"),t=n.data("action"),o={addClass:{expand:"expanded",collapse:"collapsed"},removeClass:{expand:"collapsed",collapse:"expanded"},nextAction:{expand:"collapse",collapse:"expand"},text:{expand:"collapsed-text",collapse:"expanded-text"},icon:{expand:"collapsed-icon",collapse:"expanded-icon"}};e.find("li").each((f,C)=>{const r=a(C);r.hasClass(o.removeClass[t])&&r.removeClass(o.removeClass[t]).addClass(o.addClass[t])}),n.data("action",o.nextAction[t]),n.find(".material-icons").text(n.data(o.icon[t])),n.find(".js-toggle-text").text(n.data(o.text[t]))}}const i={form:'[name="gui_configuration"]',selects:".js-widget-attribute-provider",previewContents:".js-inpostizi-btn-preview-content"},d=(s,n)=>{const e=n.getAttribute("data-type"),t=s.querySelector(`${i.previewContents}[data-type="${e}"]`);t&&n.replaceWith(t),typeof window.handleInpostIziButtons=="function"&&window.handleInpostIziButtons()},p=s=>{const e=new DOMParser().parseFromString(s,"text/html");if(!e)return;document.querySelectorAll(i.previewContents).forEach(o=>{d(e,o)})},h=()=>{const s=document.querySelector(i.form),n=document.querySelectorAll(i.selects),e=()=>{const t=new FormData(s);t.append("gui_configuration[invalid_extra_field]","temporary valid form submission countermeasure"),fetch("",{headers:{"content-type":"application/x-www-form-urlencoded; charset=UTF-8","x-requested-with":"XMLHttpRequest"},body:new URLSearchParams(t).toString(),method:"POST"}).then(o=>o.text()).then(p)};n.forEach(t=>{t.addEventListener("change",e)})},u=()=>{const s=document.querySelector(".js-choice-tree-container");s!==null&&new l(s).enableAutoCheckChildren()};c(()=>{u()}),c(h)})()})();})();

//# sourceMappingURL=gui.js.map