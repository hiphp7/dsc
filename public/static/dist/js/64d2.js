(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["64d2"],{"64d2":function(t,o,e){"use strict";e.r(o);var n=function(){var t=this,o=t.$createElement,e=t._self._c||o;return e("div",{staticClass:"bs-list-warp"},[e("div",{staticClass:"b-l-page-pos",attrs:{id:"bs-example"}},[e("ul",t._l(t.brandList,function(o,n){return e("li",{key:n,class:{active:t.active==n}},[e("a",{attrs:{href:"javascript:;"},on:{click:function(o){t.jump(n)}}},[t._v(t._s(o.info))])])}))]),e("div",{staticClass:"brand-list-page"},[t._l(t.brandList,function(o,n){return[e("div",{staticClass:"item d_jump"},[e("em",{staticClass:"b-l-a-id",attrs:{id:"link_"+o.info}},[t._v(t._s(o.info))]),e("ul",t._l(o.list,function(o,n){return e("li",[e("router-link",{attrs:{to:{name:"brandDetail",params:{id:o.brand_id}}}},[e("img",{attrs:{src:o.brand_logo}}),e("span",[t._v(t._s(o.brand_name))])])],1)}))])]})],2)])},s=[],c=e("9395"),l=e("2f62"),i={data:function(){return{active:0}},created:function(){this.$store.dispatch("setBrandList")},computed:Object(c["a"])({},Object(l["c"])({brandList:function(t){return t.brand.brandList}})),mounted:function(){var t=this;this.$nextTick(function(){window.addEventListener("scroll",t.onScroll)})},methods:{onScroll:function(){window.pageYOffset||document.documentElement.scrollTop||document.body.scrollTop;for(var t=document.querySelectorAll(".d_jump"),o=0;o<t.length;o++)console.log(t[o].offsetHeight+t[o].offsetTop)},jump:function(t){this.active=t;var o=document.querySelectorAll(".d_jump"),e=o[t].offsetTop,n=window.pageYOffset||document.documentElement.scrollTop||document.body.scrollTop,s=e/50;if(e>n)l();else{var c=n-e;s=c/50,i()}function l(){n<e?(n+=s,document.body.scrollTop=n,document.documentElement.scrollTop=n,setTimeout(l,10)):(document.body.scrollTop=e,document.documentElement.scrollTop=e)}function i(){n>e?(n-=s,document.body.scrollTop=n,document.documentElement.scrollTop=n,setTimeout(i,10)):(document.body.scrollTop=e,document.documentElement.scrollTop=e)}}}},r=i,d=e("2877"),a=Object(d["a"])(r,n,s,!1,null,null,null);a.options.__file="List.vue";o["default"]=a.exports}}]);