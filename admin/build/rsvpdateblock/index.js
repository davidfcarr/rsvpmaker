(()=>{"use strict";var e,t={884:(e,t,n)=>{const r=window.wp.blocks,o=window.React,l=(window.wp.i18n,window.wp.blockEditor),a=window.wp.apiFetch;var i=n.n(a);window.wp.url;const s=JSON.parse('{"UU":"rsvpmaker/rsvpdateblock"}');(0,r.registerBlockType)(s.UU,{edit:function(e){const{attributes:t,attributes:{alignment:n},context:{postId:r},setAttributes:a,isSelected:s}=e,[c,u]=(0,o.useState)(null);return console.log("post id "+r),(0,o.useEffect)((()=>{i()({path:"/rsvpmaker/v1/dateblock?post_id="+r+"&alignment="+n}).then((e=>{u(e.dateblock)}))}),[n]),(0,o.createElement)("div",{...(0,l.useBlockProps)()},(0,o.createElement)(l.BlockControls,null,(0,o.createElement)(l.AlignmentToolbar,{value:n,onChange:e=>a({alignment:e})})),c&&(0,o.createElement)(o.Fragment,null,(0,o.createElement)("div",{dangerouslySetInnerHTML:{__html:c}})),!c&&(0,o.createElement)(o.Fragment,null,(0,o.createElement)("p",null,"Loading ...")))},save:function(){return null}})}},n={};function r(e){var o=n[e];if(void 0!==o)return o.exports;var l=n[e]={exports:{}};return t[e](l,l.exports,r),l.exports}r.m=t,e=[],r.O=(t,n,o,l)=>{if(!n){var a=1/0;for(u=0;u<e.length;u++){for(var[n,o,l]=e[u],i=!0,s=0;s<n.length;s++)(!1&l||a>=l)&&Object.keys(r.O).every((e=>r.O[e](n[s])))?n.splice(s--,1):(i=!1,l<a&&(a=l));if(i){e.splice(u--,1);var c=o();void 0!==c&&(t=c)}}return t}l=l||0;for(var u=e.length;u>0&&e[u-1][2]>l;u--)e[u]=e[u-1];e[u]=[n,o,l]},r.n=e=>{var t=e&&e.__esModule?()=>e.default:()=>e;return r.d(t,{a:t}),t},r.d=(e,t)=>{for(var n in t)r.o(t,n)&&!r.o(e,n)&&Object.defineProperty(e,n,{enumerable:!0,get:t[n]})},r.o=(e,t)=>Object.prototype.hasOwnProperty.call(e,t),(()=>{var e={530:0,46:0};r.O.j=t=>0===e[t];var t=(t,n)=>{var o,l,[a,i,s]=n,c=0;if(a.some((t=>0!==e[t]))){for(o in i)r.o(i,o)&&(r.m[o]=i[o]);if(s)var u=s(r)}for(t&&t(n);c<a.length;c++)l=a[c],r.o(e,l)&&e[l]&&e[l][0](),e[l]=0;return r.O(u)},n=globalThis.webpackChunkadmin=globalThis.webpackChunkadmin||[];n.forEach(t.bind(null,0)),n.push=t.bind(null,n.push.bind(n))})();var o=r.O(void 0,[46],(()=>r(884)));o=r.O(o)})();