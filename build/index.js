(()=>{"use strict";var e={n:t=>{var n=t&&t.__esModule?()=>t.default:()=>t;return e.d(n,{a:n}),n},d:(t,n)=>{for(var a in n)e.o(n,a)&&!e.o(t,a)&&Object.defineProperty(t,a,{enumerable:!0,get:n[a]})},o:(e,t)=>Object.prototype.hasOwnProperty.call(e,t)};const t=window.wp.element,n=window.wp.components,a=window.wp.blocks,r=window.wp.blockEditor,o=window.wp.blockLibrary,l=window.wp.domReady;function s(){const[e,r]=(0,t.useState)(""),[o,l]=(0,t.useState)(""),[s,i]=(0,t.useState)(),[c,u]=(0,t.useState)(!1),[m,p]=(0,t.useState)(!1),d=e=>{let t=(0,a.pasteHandler)({HTML:e});return console.log(t),console.log((0,a.serialize)(t)),(0,a.serialize)(t)};return(0,t.createElement)("div",null,(0,t.createElement)("main",{className:"main"},(0,t.createElement)("h3",null,"Jarvis"),(0,t.createElement)("form",{onSubmit:async function(t){t.preventDefault();try{const t=await fetch("/wp-json/jarvis/v1/crawler?url="+e,{method:"GET",headers:{"Content-Type":"application/json"}}),n=await t.json();if(200!==t.status)throw n.error||new Error(`Request failed with status ${t.status}`);u(n.result),r("")}catch(e){console.error(e),alert(e.message)}}},(0,t.createElement)("input",{type:"text",name:"url",placeholder:"Enter url",value:e,onChange:e=>r(e.target.value)}),(0,t.createElement)("input",{type:"submit",value:"Crawl Url"})),m&&(0,t.createElement)(n.Notice,{status:"success",isDismissible:!0},"Post created successfully."),c&&(0,t.createElement)(t.Fragment,null,(0,t.createElement)("div",null,(0,t.createElement)("h3",null,c.title),(0,t.createElement)("p",null,c.content),(0,t.createElement)("div",{dangerouslySetInnerHTML:{__html:c.html}})),(0,t.createElement)("button",{onClick:()=>((e,t)=>{const n={title:e,content:d(t),status:"draft"};fetch("/wp-json/wp/v2/posts",{method:"POST",headers:{"Content-Type":"application/json","X-WP-Nonce":jarvisSettings.nonce},body:JSON.stringify(n)}).then((e=>e.json())).then((e=>{console.log(e),p(!0)}))})(c.title,c.html)},"Create Draft Post")),(0,t.createElement)("form",{onSubmit:async function(e){e.preventDefault();try{const e=await fetch("/wp-json/openai/v1/create_completion",{method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify({animal:o})}),t=await e.json();if(200!==e.status)throw t.error||new Error(`Request failed with status ${e.status}`);i(t.result),l("")}catch(e){console.error(e),alert(e.message)}}},(0,t.createElement)("input",{type:"text",name:"animal",placeholder:"Enter an animal",value:o,onChange:e=>l(e.target.value)}),(0,t.createElement)("input",{type:"submit",value:"Generate names"})),(0,t.createElement)("div",null,s)))}function i(){const[e,n]=(0,t.useState)([]);return(0,t.createElement)(r.BlockEditorProvider,{value:[],onInput:e=>n(e),onChange:e=>n(e)},(0,t.createElement)(s,null))}e.n(l)()((function(){const e=window.getdaveSbeSettings||{};(0,o.registerCoreBlocks)(),(0,t.render)((0,t.createElement)(i,{settings:e}),document.getElementById("bca-jarvis-root"))}))})();