import{H as h,q as C,c as p}from"./app-c73618bf.js";/**
 * @license lucide-vue-next v1.0.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const k=t=>{for(const e in t)if(e.startsWith("aria-")||e==="role"||e==="title")return!0;return!1};/**
 * @license lucide-vue-next v1.0.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const f=t=>t==="";/**
 * @license lucide-vue-next v1.0.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const y=(...t)=>t.filter((e,n,o)=>!!e&&e.trim()!==""&&o.indexOf(e)===n).join(" ").trim();/**
 * @license lucide-vue-next v1.0.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const w=t=>t.replace(/([a-z0-9])([A-Z])/g,"$1-$2").toLowerCase();/**
 * @license lucide-vue-next v1.0.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const L=t=>t.replace(/^([A-Z])|[\s-_]+(\w)/g,(e,n,o)=>o?o.toUpperCase():n.toLowerCase());/**
 * @license lucide-vue-next v1.0.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const $=t=>{const e=L(t);return e.charAt(0).toUpperCase()+e.slice(1)};/**
 * @license lucide-vue-next v1.0.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */var l={xmlns:"http://www.w3.org/2000/svg",width:24,height:24,viewBox:"0 0 24 24",fill:"none",stroke:"currentColor","stroke-width":2,"stroke-linecap":"round","stroke-linejoin":"round"};/**
 * @license lucide-vue-next v1.0.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const A=({name:t,iconNode:e,absoluteStrokeWidth:n,"absolute-stroke-width":o,strokeWidth:c,"stroke-width":i,size:u=l.width,color:d=l.stroke,...r},{slots:s})=>h("svg",{...l,...r,width:u,height:u,stroke:d,"stroke-width":f(n)||f(o)||n===!0||o===!0?Number(c||i||l["stroke-width"])*24/Number(u):c||i||l["stroke-width"],class:y("lucide",r.class,...t?[`lucide-${w($(t))}-icon`,`lucide-${w(t)}`]:["lucide-icon"]),...!s.default&&!k(r)&&{"aria-hidden":"true"}},[...e.map(a=>h(...a)),...s.default?[s.default()]:[]]);/**
 * @license lucide-vue-next v1.0.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const v=(t,e)=>(n,{slots:o,attrs:c})=>h(A,{...c,...n,iconNode:e,name:t},o);/**
 * @license lucide-vue-next v1.0.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const b=v("shield-check",[["path",{d:"M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z",key:"oel41y"}],["path",{d:"m9 12 2 2 4-4",key:"dzmm74"}]]);function j(){const t=C(),e=p(()=>t.props.locale||"en"),n=p(()=>t.props.direction||(e.value==="ar"?"rtl":"ltr")),o=p(()=>t.props.translations||{});function c(r){return r.split(".").reduce((s,a)=>s&&typeof s=="object"?s[a]:void 0,o.value)}function i(r,s={}){let a=c(r);return typeof a!="string"&&(a=r),Object.entries(s).forEach(([m,g])=>{a=a.replaceAll(`:${m}`,g??"")}),a}function u(r){return i(`common.statuses.${String(r||"").toLowerCase()}`)}function d(r){return i(`common.input_types.${String(r||"").toLowerCase()}`)}return{direction:n,inputTypeLabel:d,locale:e,statusLabel:u,t:i}}export{b as S,v as c,j as u};
