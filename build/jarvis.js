(()=>{"use strict";const t=window.wp.element,e=window.wp.compose,o=window.wp.blockEditor,n=window.wp.components,r=window.wp.data,s=(0,e.createHigherOrderComponent)((e=>s=>{if("core/paragraph"!==s.name)return(0,t.createElement)(e,s);const[a,c]=(0,t.useState)(!1),{attributes:{content:i},setAttributes:l}=s,w=(0,r.select)("core/editor").getCurrentPostAttribute("title");return console.log(w,s),(0,t.createElement)(t.Fragment,null,(0,t.createElement)(e,s),(0,t.createElement)(o.InspectorControls,null,(0,t.createElement)(n.PanelBody,null,(0,t.createElement)(n.Button,{variant:"secondary",isBusy:a,onClick:()=>{(async()=>{try{c(!0);const t=await fetch("/wp-json/openai/v1/create_completion",{method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify({content:i,title:w})}),e=await t.json();if(200!==t.status)throw e.error||new Error(`Request failed with status ${t.status}`);console.log(e),l({content:e.result}),c(!1)}catch(t){console.error(t),alert(t.message)}})()}},"Rewrite"))))}),"withInspectorControl");wp.hooks.addFilter("editor.BlockEdit","my-plugin/with-inspector-controls",s)})();