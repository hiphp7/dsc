(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["fb65"],{fb65:function(t,i,a){"use strict";a.r(i);var c=function(){var t=this,i=t.$createElement,a=t._self._c||i;return a("div",{staticClass:"activity"},t._l(t.topicList,function(i,c){return a("div",{key:c,staticClass:"list",on:{click:function(a){t.detailClick(i.topic_id)}}},[a("div",{staticClass:"p-r"},[a("span",{staticClass:"tag p-a color-white tag-gradients-color"},[t._v(t._s(t.$t("lang.topic")))]),i.topic_img?a("img",{staticClass:"img",attrs:{src:i.topic_img}}):t._e()]),a("div",{staticClass:"cont padding-all text-center bg-color-write"},[a("h4",{staticClass:"f-06 f-weight color-3"},[t._v(t._s(i.title))])])])}))},s=[],o=(a("ac6a"),a("cadf"),a("551c"),a("097d"),{name:"topic",components:{},data:function(){return{topicList:[]}},created:function(){var t=this;this.$http.get("".concat(window.ROOT_URL,"api/v4/topic"),{params:{page:1,size:10}}).then(function(i){var a=i.data;t.topicList=a.data})},computed:{},methods:{detailClick:function(t){var i="";this.topicList.forEach(function(a){a.topic_id==t&&(i=a.title)}),this.$router.push({name:"topicHome",params:{id:t},query:{title:i}})}}}),e=o,n=a("2877"),l=Object(n["a"])(e,c,s,!1,null,null,null);l.options.__file="Index.vue";i["default"]=l.exports}}]);