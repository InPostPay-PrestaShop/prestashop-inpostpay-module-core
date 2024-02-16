(()=>{(()=>{"use strict";var E={380:u=>{var l=typeof Reflect=="object"?Reflect:null,h=l&&typeof l.apply=="function"?l.apply:function(e,t,r){return Function.prototype.apply.call(e,t,r)},g;l&&typeof l.ownKeys=="function"?g=l.ownKeys:Object.getOwnPropertySymbols?g=function(e){return Object.getOwnPropertyNames(e).concat(Object.getOwnPropertySymbols(e))}:g=function(e){return Object.getOwnPropertyNames(e)};function m(n){console&&console.warn&&console.warn(n)}var _=Number.isNaN||function(e){return e!==e};function o(){o.init.call(this)}u.exports=o,o.EventEmitter=o,o.prototype._events=void 0,o.prototype._eventsCount=0,o.prototype._maxListeners=void 0;var b=10;function c(n){if(typeof n!="function")throw new TypeError('The "listener" argument must be of type Function. Received type '+typeof n)}Object.defineProperty(o,"defaultMaxListeners",{enumerable:!0,get:function(){return b},set:function(n){if(typeof n!="number"||n<0||_(n))throw new RangeError('The value of "defaultMaxListeners" is out of range. It must be a non-negative number. Received '+n+".");b=n}}),o.init=function(){(this._events===void 0||this._events===Object.getPrototypeOf(this)._events)&&(this._events=Object.create(null),this._eventsCount=0),this._maxListeners=this._maxListeners||void 0},o.prototype.setMaxListeners=function(e){if(typeof e!="number"||e<0||_(e))throw new RangeError('The value of "n" is out of range. It must be a non-negative number. Received '+e+".");return this._maxListeners=e,this};function f(n){return n._maxListeners===void 0?o.defaultMaxListeners:n._maxListeners}o.prototype.getMaxListeners=function(){return f(this)},o.prototype.emit=function(e){for(var t=[],r=1;r<arguments.length;r++)t.push(arguments[r]);var s=e==="error",a=this._events;if(a!==void 0)s=s&&a.error===void 0;else if(!s)return!1;if(s){var i;if(t.length>0&&(i=t[0]),i instanceof Error)throw i;var d=new Error("Unhandled error."+(i?" ("+i.message+")":""));throw d.context=i,d}var L=a[e];if(L===void 0)return!1;if(typeof L=="function")h(L,this,t);else for(var j=L.length,N=I(L,j),r=0;r<j;++r)h(N[r],this,t);return!0};function v(n,e,t,r){var s,a,i;if(c(t),a=n._events,a===void 0?(a=n._events=Object.create(null),n._eventsCount=0):(a.newListener!==void 0&&(n.emit("newListener",e,t.listener?t.listener:t),a=n._events),i=a[e]),i===void 0)i=a[e]=t,++n._eventsCount;else if(typeof i=="function"?i=a[e]=r?[t,i]:[i,t]:r?i.unshift(t):i.push(t),s=f(n),s>0&&i.length>s&&!i.warned){i.warned=!0;var d=new Error("Possible EventEmitter memory leak detected. "+i.length+" "+String(e)+" listeners added. Use emitter.setMaxListeners() to increase limit");d.name="MaxListenersExceededWarning",d.emitter=n,d.type=e,d.count=i.length,m(d)}return n}o.prototype.addListener=function(e,t){return v(this,e,t,!1)},o.prototype.on=o.prototype.addListener,o.prototype.prependListener=function(e,t){return v(this,e,t,!0)};function y(){if(!this.fired)return this.target.removeListener(this.type,this.wrapFn),this.fired=!0,arguments.length===0?this.listener.call(this.target):this.listener.apply(this.target,arguments)}function O(n,e,t){var r={fired:!1,wrapFn:void 0,target:n,type:e,listener:t},s=y.bind(r);return s.listener=t,r.wrapFn=s,s}o.prototype.once=function(e,t){return c(t),this.on(e,O(this,e,t)),this},o.prototype.prependOnceListener=function(e,t){return c(t),this.prependListener(e,O(this,e,t)),this},o.prototype.removeListener=function(e,t){var r,s,a,i,d;if(c(t),s=this._events,s===void 0)return this;if(r=s[e],r===void 0)return this;if(r===t||r.listener===t)--this._eventsCount===0?this._events=Object.create(null):(delete s[e],s.removeListener&&this.emit("removeListener",e,r.listener||t));else if(typeof r!="function"){for(a=-1,i=r.length-1;i>=0;i--)if(r[i]===t||r[i].listener===t){d=r[i].listener,a=i;break}if(a<0)return this;a===0?r.shift():C(r,a),r.length===1&&(s[e]=r[0]),s.removeListener!==void 0&&this.emit("removeListener",e,d||t)}return this},o.prototype.off=o.prototype.removeListener,o.prototype.removeAllListeners=function(e){var t,r,s;if(r=this._events,r===void 0)return this;if(r.removeListener===void 0)return arguments.length===0?(this._events=Object.create(null),this._eventsCount=0):r[e]!==void 0&&(--this._eventsCount===0?this._events=Object.create(null):delete r[e]),this;if(arguments.length===0){var a=Object.keys(r),i;for(s=0;s<a.length;++s)i=a[s],i!=="removeListener"&&this.removeAllListeners(i);return this.removeAllListeners("removeListener"),this._events=Object.create(null),this._eventsCount=0,this}if(t=r[e],typeof t=="function")this.removeListener(e,t);else if(t!==void 0)for(s=t.length-1;s>=0;s--)this.removeListener(e,t[s]);return this};function S(n,e,t){var r=n._events;if(r===void 0)return[];var s=r[e];return s===void 0?[]:typeof s=="function"?t?[s.listener||s]:[s]:t?M(s):I(s,s.length)}o.prototype.listeners=function(e){return S(this,e,!0)},o.prototype.rawListeners=function(e){return S(this,e,!1)},o.listenerCount=function(n,e){return typeof n.listenerCount=="function"?n.listenerCount(e):x.call(n,e)},o.prototype.listenerCount=x;function x(n){var e=this._events;if(e!==void 0){var t=e[n];if(typeof t=="function")return 1;if(t!==void 0)return t.length}return 0}o.prototype.eventNames=function(){return this._eventsCount>0?g(this._events):[]};function I(n,e){for(var t=new Array(e),r=0;r<e;++r)t[r]=n[r];return t}function C(n,e){for(;e+1<n.length;e++)n[e]=n[e+1];n.pop()}function M(n){for(var e=new Array(n.length),t=0;t<e.length;++t)e[t]=n[t].listener||n[t];return e}}},w={};function p(u){var l=w[u];if(l!==void 0)return l.exports;var h=w[u]={exports:{}};return E[u](h,h.exports,p),h.exports}p.n=u=>{var l=u&&u.__esModule?()=>u.default:()=>u;return p.d(l,{a:l}),l},p.d=(u,l)=>{for(var h in l)p.o(l,h)&&!p.o(u,h)&&Object.defineProperty(u,h,{enumerable:!0,get:l[h]})},p.o=(u,l)=>Object.prototype.hasOwnProperty.call(u,l);var R={};(()=>{var u=p(380),l=p.n(u);/**
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
 */const h=new(l()),g=null;/**
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
 */const{$:m}=window;class _{constructor(c){const f=c||{};return this.localeItemSelector=f.localeItemSelector||".js-locale-item",this.localeButtonSelector=f.localeButtonSelector||".js-locale-btn",this.localeInputSelector=f.localeInputSelector||".js-locale-input",this.selectedLocale=m(this.localeItemSelector).data("locale"),m("body").on("click",this.localeItemSelector,this.toggleLanguage.bind(this)),h.on("languageSelected",this.toggleInputs.bind(this)),{localeItemSelector:this.localeItemSelector,localeButtonSelector:this.localeButtonSelector,localeInputSelector:this.localeInputSelector,refreshFormInputs:v=>{this.refreshInputs(v)},getSelectedLocale:()=>this.selectedLocale}}refreshInputs(c){this.selectedLocale&&h.emit("languageSelected",{selectedLocale:this.selectedLocale,form:c})}toggleLanguage(c){const f=m(c.target),v=f.closest("form");this.selectedLocale=f.data("locale"),this.refreshInputs(v)}toggleInputs(c){const{form:f}=c;this.selectedLocale=c.selectedLocale;const v=f.find(this.localeButtonSelector),y=v.data("change-language-url");v.text(this.selectedLocale),f.find(this.localeInputSelector).addClass("d-none"),f.find(`${this.localeInputSelector}.js-locale-${this.selectedLocale}`).removeClass("d-none"),y&&this.saveSelectedLanguage(y,this.selectedLocale)}saveSelectedLanguage(c,f){m.post({url:c,data:{language_iso_code:f}})}}const o=_;$(document).ready(()=>{new o})})()})();})();

//# sourceMappingURL=admin.js.map