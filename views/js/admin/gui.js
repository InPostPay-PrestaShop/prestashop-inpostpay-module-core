(()=>{(()=>{"use strict";var w={};(()=>{const c=i=>{document.readyState==="loading"?document.addEventListener("DOMContentLoaded",i):i()};/**
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
 */const{$:s}=window;class p{constructor(n){return this.$container=s(n),this.$container.on("click",".js-input-wrapper",e=>{const t=s(e.currentTarget);this.toggleChildTree(t)}),this.$container.on("click",".js-toggle-choice-tree-action",e=>{const t=s(e.currentTarget);this.toggleTree(t)}),{enableAutoCheckChildren:()=>this.enableAutoCheckChildren(),enableAllInputs:()=>this.enableAllInputs(),disableAllInputs:()=>this.disableAllInputs()}}enableAutoCheckChildren(){this.$container.on("change",'input[type="checkbox"]',n=>{const e=s(n.currentTarget);e.closest("li").find('ul input[type="checkbox"]').prop("checked",e.is(":checked"))})}enableAllInputs(){this.$container.find("input").removeAttr("disabled")}disableAllInputs(){this.$container.find("input").attr("disabled","disabled")}toggleChildTree(n){const e=n.closest("li");if(e.hasClass("expanded")){e.removeClass("expanded").addClass("collapsed");return}e.hasClass("collapsed")&&e.removeClass("collapsed").addClass("expanded")}toggleTree(n){const e=n.closest(".js-choice-tree-container"),t=n.data("action"),o={addClass:{expand:"expanded",collapse:"collapsed"},removeClass:{expand:"collapsed",collapse:"expanded"},nextAction:{expand:"collapse",collapse:"expand"},text:{expand:"collapsed-text",collapse:"expanded-text"},icon:{expand:"collapsed-icon",collapse:"expanded-icon"}};e.find("li").each((x,m)=>{const d=s(m);d.hasClass(o.removeClass[t])&&d.removeClass(o.removeClass[t]).addClass(o.addClass[t])}),n.data("action",o.nextAction[t]),n.find(".material-icons").text(n.data(o.icon[t])),n.find(".js-toggle-text").text(n.data(o.text[t]))}}const a={form:'[name="gui_configuration"]',selects:".js-widget-attribute-provider",previewContents:".js-inpostizi-btn-preview-content"};let l=null;const r=()=>{var i;typeof window.handleInpostIziButtons=="function"&&window.handleInpostIziButtons(),l!==null&&l.refresh(),typeof window.inpostizi_merchant_client_id!="undefined"&&typeof((i=window==null?void 0:window.InPostPayWidget)==null?void 0:i.init)=="function"&&(l=window.InPostPayWidget.init({merchantClientId:window.inpostizi_merchant_client_id}))},h=(i,n)=>{const e=n.getAttribute("data-type"),t=i.querySelector(`${a.previewContents}[data-type="${e}"]`);t&&n.replaceWith(t),r()},u=i=>{const e=new DOMParser().parseFromString(i,"text/html");if(!e)return;document.querySelectorAll(a.previewContents).forEach(o=>{h(e,o)})},C=()=>{const i=document.querySelector(a.form),n=document.querySelectorAll(a.selects),e=()=>{const t=new FormData(i);t.append("gui_configuration[invalid_extra_field]","temporary valid form submission countermeasure"),fetch("",{headers:{"content-type":"application/x-www-form-urlencoded; charset=UTF-8","x-requested-with":"XMLHttpRequest"},body:new URLSearchParams(t).toString(),method:"POST"}).then(o=>o.text()).then(u)};n.forEach(t=>{t.addEventListener("change",e)})},f=()=>{const i=document.querySelector(".js-choice-tree-container");i!==null&&new p(i).enableAutoCheckChildren()};c(()=>{f(),r()}),c(C)})()})();})();

//# sourceMappingURL=gui.js.map