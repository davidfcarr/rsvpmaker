(()=>{"use strict";var e,r={190:()=>{const e=window.wp.blocks,r=window.React,o=(window.wp.i18n,window.wp.blockEditor),t=JSON.parse('{"UU":"rsvpmaker/loop-blocks"}');(0,e.registerBlockType)(t.UU,{edit:function(){return(0,r.createElement)("div",{...(0,o.useBlockProps)()},(0,r.createElement)(o.InnerBlocks,{template:[["rsvpmaker/rsvpdateblock"],["rsvpmaker/excerpt"],["core/read-more",{content:"Read More >>",style:{spacing:{padding:{bottom:"var:preset|spacing|10"}}}}],["rsvpmaker/button"]]}))},save:function(){return(0,r.createElement)("div",{...o.useBlockProps.save()},(0,r.createElement)(o.InnerBlocks.Content,null))},transforms:{from:[{type:"block",blocks:["core/post-excerpt"],transform:({attributes:r})=>(0,e.createBlock)("rsvpmaker/loop-blocks")},{type:"block",blocks:["rsvpmaker/loop-excerpt"],transform:({attributes:r})=>(0,e.createBlock)("rsvpmaker/loop-blocks")}]}})}},o={};function t(e){var n=o[e];if(void 0!==n)return n.exports;var s=o[e]={exports:{}};return r[e](s,s.exports,t),s.exports}t.m=r,e=[],t.O=(r,o,n,s)=>{if(!o){var a=1/0;for(i=0;i<e.length;i++){o=e[i][0],n=e[i][1],s=e[i][2];for(var c=!0,l=0;l<o.length;l++)(!1&s||a>=s)&&Object.keys(t.O).every((e=>t.O[e](o[l])))?o.splice(l--,1):(c=!1,s<a&&(a=s));if(c){e.splice(i--,1);var p=n();void 0!==p&&(r=p)}}return r}s=s||0;for(var i=e.length;i>0&&e[i-1][2]>s;i--)e[i]=e[i-1];e[i]=[o,n,s]},t.o=(e,r)=>Object.prototype.hasOwnProperty.call(e,r),(()=>{var e={493:0,881:0};t.O.j=r=>0===e[r];var r=(r,o)=>{var n,s,a=o[0],c=o[1],l=o[2],p=0;if(a.some((r=>0!==e[r]))){for(n in c)t.o(c,n)&&(t.m[n]=c[n]);if(l)var i=l(t)}for(r&&r(o);p<a.length;p++)s=a[p],t.o(e,s)&&e[s]&&e[s][0](),e[s]=0;return t.O(i)},o=self.webpackChunkadmin=self.webpackChunkadmin||[];o.forEach(r.bind(null,0)),o.push=r.bind(null,o.push.bind(o))})();var n=t.O(void 0,[881],(()=>t(190)));n=t.O(n)})();