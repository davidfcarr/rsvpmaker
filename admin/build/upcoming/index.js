(()=>{"use strict";var e,l={514:(e,l,a)=>{const t=window.wp.blocks,n=window.React,r=window.wp.i18n,o=window.wp.blockEditor,s=window.wp.apiFetch;var u=a.n(s);const c=window.wp.url,{Component:i,Fragment:p}=wp.element,{Panel:v,PanelBody:m,SelectControl:d,RadioControl:g,TextControl:b,ColorPalette:_,FontSizePicker:h}=wp.components,E=JSON.parse('{"UU":"rsvpmaker/upcoming"}');(0,t.registerBlockType)(E.UU,{edit:function(e){const{attributes:l}=e,[a,t]=(0,n.useState)([]),[s,g]=(0,n.useState)([]),[E,y]=(0,n.useState)(null);return(0,n.useEffect)((()=>{const e=[{value:"",label:"None selected (optional)"}];u()({path:"rsvpmaker/v1/types"}).then((l=>{Array.isArray(l)?l.map((function(l){l.slug&&l.name&&e.push({value:l.slug,label:l.name})})):(Object.values(l).map((function(l){l.slug&&l.name&&e.push({value:l.slug,label:l.name})})),console.log(type.slug),console.log(typeof type.slug),console.log(type.name),console.log(typeof type.name))})).catch((e=>{console.log(e)})),t(e);const l=[{value:"",label:"Any"}];u()({path:"rsvpmaker/v1/authors"}).then((e=>{Array.isArray(e)?e.map((function(e){e.ID&&e.name&&l.push({value:e.ID,label:e.name})})):(e=Object.values(e)).map((function(e){e.ID&&e.name&&l.push({value:e.ID,label:e.name})}))})).catch((e=>{console.log(e)})),g(l)}),[]),(0,n.useEffect)((()=>{u()({path:(0,c.addQueryArgs)("/rsvpmaker/v1/upcoming_preview/",l)}).then((e=>{e.calendar&&y(e.calendar)}))}),[l]),(0,n.createElement)(p,null,(0,n.createElement)("div",{...(0,o.useBlockProps)()},(0,n.createElement)(class extends i{render(){const{attributes:{calendar:e,excerpt:l,days:t,posts_per_page:u,hideauthor:c,no_events:i,nav:p,type:g,exclude_type:E,author:y,itemcolor:f,itembg:k,itemfontsize:C},setAttributes:w,isSelected:x}=this.props,S=[{name:(0,r.__)("Small"),slug:"small",size:10},{name:(0,r.__)("Medium"),slug:"medium",size:12},{name:(0,r.__)("Large"),slug:"large",size:13},{name:(0,r.__)("Extra Large"),slug:"xlarge",size:14}];return console.log("type",g),console.log("types",a),(0,n.createElement)("div",null,(0,n.createElement)(o.InspectorControls,{key:"upcominginspector"},(0,n.createElement)(m,{title:(0,r.__)("RSVPMaker Upcoming Options","rsvpmaker")},(0,n.createElement)("form",null,(0,n.createElement)(d,{label:(0,r.__)("Display Calendar","rsvpmaker"),value:e,options:[{value:1,label:(0,r.__)("Yes - Calendar plus events listing")},{value:0,label:(0,r.__)("No - Events listing only")},{value:2,label:(0,r.__)("Calendar only")}],onChange:e=>{console.log("calendar choice "+typeof e),console.log(e),w({calendar:e})}}),(0,n.createElement)(d,{label:(0,r.__)("Format","rsvpmaker"),value:l,options:[{value:0,label:(0,r.__)("Full Text")},{value:1,label:(0,r.__)("Excerpt")}],onChange:e=>{w({excerpt:e})}}),(0,n.createElement)(d,{label:(0,r.__)("Events Per Page","rsvpmaker"),value:u,options:[{value:5,label:5},{value:10,label:10},{value:15,label:15},{value:20,label:20},{value:25,label:25},{value:30,label:30},{value:35,label:35},{value:40,label:40},{value:45,label:45},{value:50,label:50},{value:"-1",label:"No limit"}],onChange:e=>{w({posts_per_page:e})}}),(0,n.createElement)(d,{label:(0,r.__)("Date Range","rsvpmaker"),value:t,options:[{value:5,label:5},{value:30,label:"30 Days"},{value:60,label:"60 Days"},{value:90,label:"90 Days"},{value:180,label:"180 Days"},{value:366,label:"1 Year"}],onChange:e=>{w({days:e})}}),(0,n.createElement)(d,{label:(0,r.__)("Event Type","rsvpmaker"),selected:g,value:g,options:a,onChange:e=>{w({type:e})}}),(0,n.createElement)(d,{label:(0,r.__)("EXCLUDE Event Type","rsvpmaker"),selected:E,value:E,options:a,onChange:e=>{w({exclude_type:e})}}),(0,n.createElement)(d,{label:(0,r.__)("Author","rsvpmaker"),value:y,options:s,onChange:e=>{w({author:e})}}),(0,n.createElement)(d,{label:(0,r.__)("Calendar Navigation","rsvpmaker"),value:p,options:[{value:"top",label:(0,r.__)("Top")},{value:"bottom",label:(0,r.__)("Bottom")},{value:"both",label:(0,r.__)("Both")}],onChange:e=>{w({nav:e})}}),(0,n.createElement)(d,{label:(0,r.__)("Show Event Author","rsvpmaker"),value:c,options:[{label:"No",value:!0},{label:"Yes",value:!1}],onChange:e=>{w({hideauthor:e})}}),(0,n.createElement)(b,{label:(0,r.__)("Text to show for no events listed","rsvpmaker"),value:i,onChange:e=>{w({no_events:e})}}))),(0,n.createElement)(v,{header:"Calendar Colors"},(0,n.createElement)(m,{title:(0,r.__)("Calendar Item Text Color","rsvpmaker")},(0,n.createElement)(_,{label:(0,r.__)("Calendar item text color","rsvpmaker"),colors:wp.data.select("core/editor").getEditorSettings().colors,value:f,defaultValue:f,onChange:e=>{w({itemcolor:e})}})),(0,n.createElement)(m,{title:(0,r.__)("Calendar Item Background Color","rsvpmaker")},(0,n.createElement)(_,{colors:wp.data.select("core/editor").getEditorSettings().colors,label:(0,r.__)("Calendar item background color","rsvpmaker"),value:k,defaultValue:k,onChange:e=>{w({itembg:e})}}),(0,n.createElement)("div",null,(0,n.createElement)("svg",{viewBox:"0 0 24 24",xmlns:"http://www.w3.org/2000/svg",width:"24",height:"24","aria-hidden":"true",focusable:"false"},(0,n.createElement)("path",{d:"M12 4c-4.4 0-8 3.6-8 8v.1c0 4.1 3.2 7.5 7.2 7.9h.8c4.4 0 8-3.6 8-8s-3.6-8-8-8zm0 15V5c3.9 0 7 3.1 7 7s-3.1 7-7 7z"}))," ",(0,n.createElement)("em",null,"See the styles tab for the overall text and background color settings.")))),(0,n.createElement)(v,{header:"Calendar Fonts"},(0,n.createElement)(m,{title:(0,r.__)("Calendar Item Font Size","rsvpmaker")},(0,n.createElement)(h,{label:(0,r.__)("Calendar item text size","rsvpmaker"),value:C,fontSizes:S,fallbackFontSize:10,onChange:e=>{w({itemfontsize:e})}})))))}},{...e}),E&&(0,n.createElement)("div",{dangerouslySetInnerHTML:{__html:E}}),!E&&(0,n.createElement)("p",null,"RSVPMaker Upcoming loading ...")))},save:function(){return null}})}},a={};function t(e){var n=a[e];if(void 0!==n)return n.exports;var r=a[e]={exports:{}};return l[e](r,r.exports,t),r.exports}t.m=l,e=[],t.O=(l,a,n,r)=>{if(!a){var o=1/0;for(i=0;i<e.length;i++){for(var[a,n,r]=e[i],s=!0,u=0;u<a.length;u++)(!1&r||o>=r)&&Object.keys(t.O).every((e=>t.O[e](a[u])))?a.splice(u--,1):(s=!1,r<o&&(o=r));if(s){e.splice(i--,1);var c=n();void 0!==c&&(l=c)}}return l}r=r||0;for(var i=e.length;i>0&&e[i-1][2]>r;i--)e[i]=e[i-1];e[i]=[a,n,r]},t.n=e=>{var l=e&&e.__esModule?()=>e.default:()=>e;return t.d(l,{a:l}),l},t.d=(e,l)=>{for(var a in l)t.o(l,a)&&!t.o(e,a)&&Object.defineProperty(e,a,{enumerable:!0,get:l[a]})},t.o=(e,l)=>Object.prototype.hasOwnProperty.call(e,l),(()=>{var e={8:0,664:0};t.O.j=l=>0===e[l];var l=(l,a)=>{var n,r,[o,s,u]=a,c=0;if(o.some((l=>0!==e[l]))){for(n in s)t.o(s,n)&&(t.m[n]=s[n]);if(u)var i=u(t)}for(l&&l(a);c<o.length;c++)r=o[c],t.o(e,r)&&e[r]&&e[r][0](),e[r]=0;return t.O(i)},a=globalThis.webpackChunkadmin=globalThis.webpackChunkadmin||[];a.forEach(l.bind(null,0)),a.push=l.bind(null,a.push.bind(a))})();var n=t.O(void 0,[664],(()=>t(514)));n=t.O(n)})();