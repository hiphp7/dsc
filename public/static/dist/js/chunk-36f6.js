(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["chunk-36f6"],{"0653":function(e,t,i){"use strict";i("68ef")},1146:function(e,t,i){},"20fb":function(e,t,i){"use strict";var n=i("fe7e");t["a"]=Object(n["a"])({render:function(){var e=this,t=e.$createElement,i=e._self._c||t;return i("div",{class:e.b()},[i("button",{class:e.b("minus",{disabled:e.minusDisabled}),on:{click:function(t){e.onChange("minus")}}}),i("input",{class:e.b("input"),attrs:{type:"number",disabled:e.disabled||e.disableInput},domProps:{value:e.currentValue},on:{input:e.onInput,blur:e.onBlur}}),i("button",{class:e.b("plus",{disabled:e.plusDisabled}),on:{click:function(t){e.onChange("plus")}}})])},name:"stepper",props:{value:null,integer:Boolean,disabled:Boolean,disableInput:Boolean,min:{type:[String,Number],default:1},max:{type:[String,Number],default:1/0},step:{type:[String,Number],default:1},defaultValue:{type:[String,Number],default:1}},data:function(){var e=this.range(this.isDef(this.value)?this.value:this.defaultValue);return e!==+this.value&&this.$emit("input",e),{currentValue:e}},computed:{minusDisabled:function(){return this.disabled||this.currentValue<=this.min},plusDisabled:function(){return this.disabled||this.currentValue>=this.max}},watch:{value:function(e){e!==this.currentValue&&(this.currentValue=this.format(e))},currentValue:function(e){this.$emit("input",e),this.$emit("change",e)}},methods:{format:function(e){return e=String(e).replace(/[^0-9\.-]/g,""),""===e?0:this.integer?Math.floor(e):+e},range:function(e){return Math.max(Math.min(this.max,this.format(e)),this.min)},onInput:function(e){var t=e.target.value,i=this.format(t);+t!==i&&(e.target.value=i),this.currentValue=i},onChange:function(e){if(this[e+"Disabled"])this.$emit("overlimit",e);else{var t="minus"===e?-this.step:+this.step,i=Math.round(100*(this.currentValue+t))/100;this.currentValue=this.range(i),this.$emit(e)}},onBlur:function(e){this.currentValue=this.range(this.currentValue),this.$emit("blur",e)}}})},2381:function(e,t,i){},"255a":function(e,t,i){"use strict";var n=function(){var e=this,t=e.$createElement,i=e._self._c||t;return i("van-popup",{attrs:{position:"bottom","close-on-click-overlay":!1},on:{"click-overlay":e.overlay},model:{value:e.display,callback:function(t){e.display=t},expression:"display"}},[i("div",{staticClass:"mod-address-main"},[i("div",{staticClass:"mod-address-head dis-box"},[i("div",{staticClass:"mod-address-head-tit box-flex"},[e._v(e._s(e.$t("lang.delivery_to_the")))]),i("div",{staticClass:"mod-address-head-right box-flex"},[i("i",{staticClass:"iconfont icon-close",on:{click:e.onRegionClose}})])]),i("div",{staticClass:"mod-address-body"},[i("ul",{staticClass:"ulAddrTab"},[e.regionOption.province.name?i("li",{class:{cur:e.regionLevel-1==1},on:{click:function(t){e.tabClickRegion(1,1)}}},[i("span",[e._v(e._s(e.regionOption.province.name))])]):i("li",[i("span",[e._v(e._s(e.$t("lang.select")))])]),e.regionOption.city.name?i("li",{class:{cur:e.regionLevel-1==2},on:{click:function(t){e.tabClickRegion(e.regionOption.province.id,2)}}},[i("span",[e._v(e._s(e.regionOption.city.name))])]):e._e(),e.regionOption.district.name?i("li",{class:{cur:e.regionLevel-1==3},on:{click:function(t){e.tabClickRegion(e.regionOption.city.id,3)}}},[i("span",[e._v(e._s(e.regionOption.district.name))])]):e._e(),e.regionOption.street.name?i("li",{class:{cur:e.regionLevel-1==4},on:{click:function(t){e.tabClickRegion(e.regionOption.district.id,4)}}},[i("span",[e._v(e._s(e.regionOption.street.name))])]):e._e()]),2==e.regionLevel?i("ul",{staticClass:"ulAddrList"},e._l(e.regionDate.provinceData,function(t,n){return i("li",{key:n,class:{active:e.regionOption.province.id==t.id},on:{click:function(i){e.childRegion(t.id,t.name,t.level)}}},[e._v(e._s(t.name))])})):e._e(),3==e.regionLevel?i("ul",{staticClass:"ulAddrList"},e._l(e.regionDate.cityDate,function(t,n){return i("li",{key:n,class:{active:e.regionOption.city.id==t.id},on:{click:function(i){e.childRegion(t.id,t.name,t.level)}}},[e._v(e._s(t.name))])})):e._e(),4==e.regionLevel?i("ul",{staticClass:"ulAddrList"},e._l(e.regionDate.districtDate,function(t,n){return i("li",{key:n,class:{active:e.regionOption.district.id==t.id},on:{click:function(i){e.childRegion(t.id,t.name,t.level)}}},[e._v(e._s(t.name))])})):e._e(),5==e.regionLevel?i("ul",{staticClass:"ulAddrList"},e._l(e.regionDate.streetDate,function(t,n){return i("li",{key:n,class:{active:e.regionOption.street.id==t.id},on:{click:function(i){e.childRegion(t.id,t.name,t.level)}}},[e._v(e._s(t.name))])})):e._e()])])])},s=[],a=i("88d8"),o=(i("7f7f"),i("8a58"),i("e41f")),r=(i("cadf"),i("551c"),i("097d"),i("2f62"),{props:["display","regionOptionDate","isPrice"],data:function(){return{regionOption:this.regionOptionDate}},components:Object(a["a"])({},o["a"].name,o["a"]),created:function(){this.$store.dispatch("setRegion",{region:1,level:1});var e=localStorage.getItem("regionOption"),t=JSON.parse(localStorage.getItem("userRegion"));null==e&&null!==t&&(this.regionOption.province=t.province?t.province:"",this.regionOption.city=t.city?t.city:"",this.regionOption.district=t.district?t.district:"",this.regionOption.regionSplic=this.regionOption.province.name+" "+this.regionOption.city.name+" "+this.regionOption.district.name,localStorage.setItem("regionOption",JSON.stringify(this.regionOption)))},computed:{regionId:function(){return this.$store.state.region.id},regionLevel:function(){return this.$store.state.region.level},regionDate:function(){return this.$store.state.region.data},status:{get:function(){return this.$store.state.region.status},set:function(e){this.$store.state.region.status=e}},isLogin:function(){return null!=localStorage.getItem("token")},userRegion:function(){return this.$store.state.userRegion}},methods:{onRegionClose:function(){this.$emit("update:display",!1)},childRegion:function(e,t,i){switch(this.status=!1,i){case 2:this.regionOption.province.id=e,this.regionOption.province.name=t;break;case 3:this.regionOption.city.id=e,this.regionOption.city.name=t;break;case 4:this.regionOption.district.id=e,this.regionOption.district.name=t;break;case 5:this.regionOption.street.id=e,this.regionOption.street.name=t;break;default:break}this.$store.dispatch("setRegion",{region:e,level:i})},tabClickRegion:function(e,t){var i=this,n=["province","city","district","street"];n.forEach(function(e,n){n+1>t&&(i.regionOption[e].id="",i.regionOption[e].name="")}),this.$store.dispatch("setRegion",{region:e,level:t})},overlay:function(){this.$emit("update:display",!1)}},watch:{status:function(){1==this.status&&(this.regionOption.regionSplic=this.regionOption.province.name+" "+this.regionOption.city.name+" "+this.regionOption.district.name+" "+this.regionOption.street.name,localStorage.setItem("regionOption",JSON.stringify(this.regionOption)),this.$emit("update:regionOptionDate",this.regionOption),this.$emit("update:display",!1),this.$emit("update:isPrice",1))},userRegion:function(){var e=localStorage.getItem("regionOption");null==e&&this.userRegion&&(this.regionOption.province=this.userRegion.province?this.userRegion.province:"",this.regionOption.city=this.userRegion.city?this.userRegion.city:"",this.regionOption.district=this.userRegion.district?this.userRegion.district:"",this.regionOption.regionSplic=this.regionOption.province.name+" "+this.regionOption.city.name+" "+this.regionOption.district.name,localStorage.setItem("regionOption",JSON.stringify(this.regionOption)))}}}),l=r,c=i("2877"),u=Object(c["a"])(l,n,s,!1,null,null,null);u.options.__file="Region.vue";t["a"]=u.exports},"3acc":function(e,t,i){"use strict";var n=i("fe7e");t["a"]=Object(n["a"])({render:function(){var e=this,t=e.$createElement,i=e._self._c||t;return i("div",{class:e.b()},[e._t("default")],2)},name:"checkbox-group",props:{value:Array,disabled:Boolean,max:{type:Number,default:0}},watch:{value:function(e){this.$emit("change",e)}}})},"3c32":function(e,t,i){"use strict";i("68ef"),i("2381")},"417e":function(e,t,i){"use strict";var n=i("fe7e"),s=i("f331");t["a"]=Object(n["a"])({render:function(){var e=this,t=e.$createElement,i=e._self._c||t;return i("div",{class:e.b()},[i("div",{class:[e.b("icon",[e.shape,{disabled:e.isDisabled,checked:e.checked}])],on:{click:e.toggle}},[e._t("icon",[i("icon",{style:e.iconStyle,attrs:{name:"success"}})],{checked:e.checked})],2),e.$slots.default?i("span",{class:e.b("label",e.labelPosition),on:{click:function(t){e.toggle("label")}}},[e._t("default")],2):e._e()])},name:"checkbox",mixins:[s["a"]],props:{name:null,value:null,disabled:Boolean,checkedColor:String,labelPosition:String,labelDisabled:Boolean,shape:{type:String,default:"round"}},computed:{checked:{get:function(){return this.parent?-1!==this.parent.value.indexOf(this.name):this.value},set:function(e){this.parent?this.setParentValue(e):this.$emit("input",e)}},isDisabled:function(){return this.parent&&this.parent.disabled||this.disabled},iconStyle:function(){var e=this.checkedColor;if(e&&this.checked&&!this.isDisabled)return{borderColor:e,backgroundColor:e}}},watch:{value:function(e){this.$emit("change",e)}},created:function(){this.findParent("van-checkbox-group")},methods:{toggle:function(e){this.isDisabled||"label"===e&&this.labelDisabled||(this.checked=!this.checked)},setParentValue:function(e){var t=this.parent,i=t.value.slice();if(e){if(t.max&&i.length>=t.max)return;-1===i.indexOf(this.name)&&(i.push(this.name),t.$emit("input",i))}else{var n=i.indexOf(this.name);-1!==n&&(i.splice(n,1),t.$emit("input",i))}}}})},"565f":function(e,t,i){"use strict";var n=i("c31d"),s=i("fe7e"),a=i("a142");t["a"]=Object(s["a"])({render:function(){var e,t=this,i=t.$createElement,n=t._self._c||i;return n("cell",{class:t.b((e={error:t.error,disabled:t.$attrs.disabled,"min-height":"textarea"===t.type&&!t.autosize},e["label-"+t.labelAlign]=t.labelAlign,e)),attrs:{icon:t.leftIcon,title:t.label,center:t.center,border:t.border,"is-link":t.isLink,required:t.required}},[t._t("left-icon",null,{slot:"icon"}),t._t("label",null,{slot:"title"}),n("div",{class:t.b("body")},["textarea"===t.type?n("textarea",t._g(t._b({ref:"input",class:t.b("control",t.inputAlign),attrs:{readonly:t.readonly},domProps:{value:t.value}},"textarea",t.$attrs,!1),t.listeners)):n("input",t._g(t._b({ref:"input",class:t.b("control",t.inputAlign),attrs:{type:t.type,readonly:t.readonly},domProps:{value:t.value}},"input",t.$attrs,!1),t.listeners)),t.showClear?n("icon",{class:t.b("clear"),attrs:{name:"clear"},on:{touchstart:function(e){return e.preventDefault(),t.onClear(e)}}}):t._e(),t.$slots.icon||t.icon?n("div",{class:t.b("icon"),on:{click:t.onClickIcon}},[t._t("icon",[n("icon",{attrs:{name:t.icon}})])],2):t._e(),t.$slots.button?n("div",{class:t.b("button")},[t._t("button")],2):t._e()],1),t.errorMessage?n("div",{class:t.b("error-message"),domProps:{textContent:t._s(t.errorMessage)}}):t._e()],2)},name:"field",inheritAttrs:!1,props:{value:[String,Number],icon:String,label:String,error:Boolean,center:Boolean,isLink:Boolean,leftIcon:String,readonly:Boolean,required:Boolean,clearable:Boolean,labelAlign:String,inputAlign:String,onIconClick:Function,autosize:[Boolean,Object],errorMessage:String,type:{type:String,default:"text"},border:{type:Boolean,default:!0}},data:function(){return{focused:!1}},watch:{value:function(){this.$nextTick(this.adjustSize)}},mounted:function(){this.format(),this.$nextTick(this.adjustSize)},computed:{showClear:function(){return this.clearable&&this.focused&&""!==this.value&&this.isDef(this.value)&&!this.readonly},listeners:function(){return Object(n["a"])({},this.$listeners,{input:this.onInput,keypress:this.onKeypress,focus:this.onFocus,blur:this.onBlur})}},methods:{focus:function(){this.$refs.input&&this.$refs.input.focus()},blur:function(){this.$refs.input&&this.$refs.input.blur()},format:function(e){void 0===e&&(e=this.$refs.input);var t=e,i=t.value,n=this.$attrs.maxlength;return this.isDef(n)&&i.length>n&&(i=i.slice(0,n),e.value=i),i},onInput:function(e){this.$emit("input",this.format(e.target))},onFocus:function(e){this.focused=!0,this.$emit("focus",e),this.readonly&&this.blur()},onBlur:function(e){this.focused=!1,this.$emit("blur",e)},onClickIcon:function(){this.$emit("click-icon"),this.onIconClick&&this.onIconClick()},onClear:function(){this.$emit("input",""),this.$emit("clear")},onKeypress:function(e){if("number"===this.type){var t=e.keyCode,i=-1===String(this.value).indexOf("."),n=t>=48&&t<=57||46===t&&i||45===t;n||e.preventDefault()}"search"===this.type&&13===e.keyCode&&this.blur(),this.$emit("keypress",e)},adjustSize:function(){var e=this.$refs.input;if("textarea"===this.type&&this.autosize&&e){e.style.height="auto";var t=e.scrollHeight;if(Object(a["d"])(this.autosize)){var i=this.autosize,n=i.maxHeight,s=i.minHeight;n&&(t=Math.min(t,n)),s&&(t=Math.max(t,s))}t&&(e.style.height=t+"px")}}}})},"66b9":function(e,t,i){"use strict";i("68ef")},"8a58":function(e,t,i){"use strict";i("68ef"),i("4d75")},"8b38":function(e,t,i){"use strict";i.r(t);var n,s=function(){var e=this,t=e.$createElement,i=e._self._c||t;return i("div",{staticClass:"user-detail"},[i("section",{staticClass:"section-list"},[i("div",{staticClass:"bg-color-write"},[e.goodsList?i("div",{staticClass:"product-list product-list-small"},[e.goodsList?[i("ul",e._l(e.goodsList,function(t,n){return i("li",{key:n},[i("div",{staticClass:"product-div"},[i("div",{staticClass:"product-list-img"},[i("img",{staticClass:"img",attrs:{src:t.goods_img}})]),i("div",{staticClass:"product-info"},[i("h4",[e._v(e._s(t.goods_name))]),i("div",{staticClass:"price"},[i("em",[e._v(e._s(t.shop_price_formated))]),i("span",[e._v("x"+e._s(t.goods_number))])]),t.goods_bonus>0?i("div",{staticClass:"price"},[e._v("- "+e._s(t.formated_goods_bonus))]):e._e(),i("div",{staticClass:"p-t-remark m-top04"},[e._v(e._s(t.attr_name))])])])])}))]:e._e()],2):e._e()])]),i("van-cell-group",{staticClass:"van-cell-noleft m-top08"},[i("van-cell",[i("div",{attrs:{slot:"title"},slot:"title"},[i("em",{staticClass:"color-red"},[e._v(e._s(e.$t("lang.reminder"))+"：")])]),i("div",{staticClass:"f-03 col-6"},[e._v(e._s(e.$t("lang.reminder_one"))),e.goodsList?i("em",{staticClass:"color-red"},[e._v(e._s(e.goodsList[0].shop_name))]):e._e(),e._v(e._s(e.$t("lang.reminder_two")))])])],1),i("section",{staticClass:"user-return-list-box padding-all bg-color-write m-top08"},[i("h4",{staticClass:"f-04 col-7"},[e._v(e._s(e.$t("lang.service_type"))),i("em",[e._v("*")])]),i("div",{staticClass:"select-one-1"},[i("ul",{staticClass:"ect-selects"},e._l(e.goods_cause,function(t,n){return i("li",{staticClass:"ect-select",class:{active:t.cause==e.retrun_cause_id},on:{click:function(i){e.causeSelect(t.cause)}}},[i("span",[e._v(e._s(t.lang))])])}))])]),i("section",{staticClass:"user-return-list-box padding-all bg-color-write m-top08"},[i("h4",{staticClass:"f-04 col-7"},[e._v(e._s(e.$t("lang.return_reason"))),i("em",[e._v("*")])]),i("div",{staticClass:"select-one-1"},[i("select",{directives:[{name:"model",rawName:"v-model",value:e.causeSelected,expression:"causeSelected"}],staticClass:"select form-control parent_cause_select",on:{change:function(t){var i=Array.prototype.filter.call(t.target.options,function(e){return e.selected}).map(function(e){var t="_value"in e?e._value:e.value;return t});e.causeSelected=t.target.multiple?i:i[0]}}},e._l(e.parent_cause,function(t){return i("option",{domProps:{value:t.cause_id}},[e._v(e._s(t.cause_name))])}))])]),e.shippingStatus?i("section",{staticClass:"user-return-list-box padding-all bg-color-write m-top08"},[i("h4",{staticClass:"f-04 col-7"},[e._v(e._s(e.$t("lang.return_number"))),i("em",[e._v("*")])]),i("div",{staticClass:"select-one-1"},[i("van-stepper",{attrs:{integer:"",min:1,max:e.applyRefoundDetail.return_goods_num,step:1},model:{value:e.value,callback:function(t){e.value=t},expression:"value"}})],1)]):e._e(),i("section",{staticClass:"user-return-list-box padding-all bg-color-write m-top08"},[i("h4",{staticClass:"f-04 col-7"},[e._v(e._s(e.$t("lang.problem_desc"))),i("em",[e._v("*")])]),i("van-field",{staticClass:"not_padding",attrs:{placeholder:e.$t("lang.problem_desc"),type:"textarea"},model:{value:e.return_brief,callback:function(t){e.return_brief=t},expression:"return_brief"}})],1),i("section",{staticClass:"user-return-list-box padding-all bg-color-write m-top08"},[i("h4",{staticClass:"f-04 col-7"},[e._v(e._s(e.$t("lang.application_credentials")))]),i("div",{staticClass:"select-one-1"},[i("van-cell",{staticClass:"not_padding",attrs:{title:e.$t("lang.has_test_report"),clickable:""}},[i("van-checkbox",{model:{value:e.checked,callback:function(t){e.checked=t},expression:"checked"}})],1)],1)]),i("section",{staticClass:"user-return-list-box padding-all bg-color-write m-top08"},[i("h4",{staticClass:"f-04 col-7"},[e._v(e._s(e.$t("lang.pic_info")))]),e.materialList.length>0?i("div",{staticClass:"goods-info-img-box"},e._l(e.materialList,function(t,n){return i("div",{key:n,staticClass:"goods-info-img"},[i("img",{attrs:{src:t}}),i("i",{staticClass:"iconfont icon-delete",on:{click:function(t){e.deleteImg(n)}}})])})):e._e(),i("van-uploader",{attrs:{"after-read":e.onRead(),accept:"image/jpg, image/jpeg, image/png, image/gif",multiple:""}},[i("div",{staticClass:"user-return-img"},[i("h5",[i("i",{staticClass:"iconfont icon-jiahao"})]),i("p",[e._v(e._s(e.$t("lang.pic_voucher")))])])]),i("p",{staticClass:"f-03 col-7 m-top06"},[e._v(" "+e._s(e.$t("lang.pic_prompt_notic_one"))),i("br"),e._v(e._s(e.$t("lang.pic_prompt_notic_two")))])],1),e.consignee?[0==e.retrun_cause_id||2==e.retrun_cause_id?i("section",{staticClass:"user-return-list-box padding-all bg-color-write m-top08"},[i("h4",{staticClass:"f-04 col-7"},[e._v(e._s(e.$t("lang.profile"))),i("em",[e._v("*")])]),i("van-field",{staticClass:"my-bottom",attrs:{label:e.$t("lang.consignee"),placeholder:e.$t("lang.enter_consignee")},model:{value:e.addressee,callback:function(t){e.addressee=t},expression:"addressee"}}),i("van-field",{staticClass:"my-bottom",attrs:{type:"tel",label:e.$t("lang.phone_number"),placeholder:e.$t("lang.enter_mobile")},model:{value:e.mobile,callback:function(t){e.mobile=t},expression:"mobile"}}),i("van-cell",{staticClass:"my-bottom not_cell",attrs:{title:e.$t("lang.region_alt"),"is-link":""},on:{click:e.handelRegionShow},model:{value:e.regionSplic,callback:function(t){e.regionSplic=t},expression:"regionSplic"}}),i("van-field",{staticClass:"my-bottom",attrs:{label:e.$t("lang.address_alt"),type:"textarea",placeholder:e.$t("lang.enter_address")},model:{value:e.address,callback:function(t){e.address=t},expression:"address"}})],1):e._e()]:e._e(),i("section",{staticClass:"user-return-list-box m-top08"},[i("van-field",{staticClass:"my-bottom",attrs:{label:e.$t("lang.message"),placeholder:e.$t("lang.enter_message"),type:"textarea"},model:{value:e.return_remark,callback:function(t){e.return_remark=t},expression:"return_remark"}})],1),i("div",{staticClass:"padding-all user-bg m-top12"},[i("h4",{staticClass:"f-04 col-6 m-b10"},[e._v(" "+e._s(e.$t("lang.service_note")))]),i("p",{staticClass:"f-03 col-9"},[e._v(e._s(e.$t("lang.return_explain_1")))]),i("p",{staticClass:"f-03 col-9 m-top04"},[e._v(e._s(e.$t("lang.return_explain_2")))]),i("p",{staticClass:"f-03 col-9 m-top04"},[e._v(e._s(e.$t("lang.return_explain_3")))]),i("p",{staticClass:"f-03 col-9 m-top04"},[e._v(e._s(e.$t("lang.return_explain_4")))])]),i("div",{staticClass:"filter-btn dis-box"},[i("a",{staticClass:"btn btn-submit",attrs:{href:"javascript:void(0)"},on:{click:function(t){e.submitBtn()}}},[e._v(e._s(e.$t("lang.submit_apply")))])]),i("Region",{attrs:{display:e.regionShow,regionOptionDate:e.regionOptionDate},on:{"update:display":function(t){e.regionShow=t},"update:regionOptionDate":function(t){e.regionOptionDate=t}}})],2)},a=[],o=i("9395"),r=i("88d8"),l=(i("e7e5"),i("d399")),c=(i("e17f"),i("2241")),u=(i("66b9"),i("b650")),d=(i("e930"),i("8f80")),p=(i("3c32"),i("417e")),h=(i("a909"),i("3acc")),f=(i("be7f"),i("565f")),g=(i("f06a"),i("20fb")),m=(i("0653"),i("34e9")),_=(i("7f7f"),i("c194"),i("7744")),v=i("2f62"),b=i("4328"),y=i.n(b),O=i("255a"),$={data:function(){return{value:1,checked:!1,return_brief:"",retrun_cause_id:"",return_remark:"",causeSelected:"",regionShow:!1,regionOptionDate:{province:{id:"",name:""},city:{id:"",name:""},district:{id:"",name:""},street:{id:"",name:""},regionSplic:""}}},components:(n={},Object(r["a"])(n,_["a"].name,_["a"]),Object(r["a"])(n,m["a"].name,m["a"]),Object(r["a"])(n,g["a"].name,g["a"]),Object(r["a"])(n,f["a"].name,f["a"]),Object(r["a"])(n,h["a"].name,h["a"]),Object(r["a"])(n,p["a"].name,p["a"]),Object(r["a"])(n,d["a"].name,d["a"]),Object(r["a"])(n,u["a"].name,u["a"]),Object(r["a"])(n,c["a"].name,c["a"]),Object(r["a"])(n,l["a"].name,l["a"]),Object(r["a"])(n,"Region",O["a"]),n),created:function(){this.$store.dispatch("setApplyRefound",{rec_id:this.$route.query.rec_id,order_id:this.$route.query.order_id}),this.$store.dispatch("setMaterial",{file:[]});var e=JSON.parse(localStorage.getItem("regionOption"));console.log(e),e&&(this.regionOptionDate=e)},computed:Object(o["a"])({},Object(v["c"])({materialList:function(e){return e.user.materialList},applyRefoundDetail:function(e){return e.user.applyRefoundDetail}}),{goodsList:function(){return!!this.applyRefoundDetail.goods_list&&this.applyRefoundDetail.goods_list},consignee:function(){return this.applyRefoundDetail.consignee},goods_cause:function(){return this.applyRefoundDetail.goods_cause},parent_cause:function(){return this.applyRefoundDetail.parent_cause},shippingStatus:function(){return this.applyRefoundDetail.order?this.applyRefoundDetail.order.shipping_status:0},addressee:{get:function(){return this.applyRefoundDetail.consignee.consignee},set:function(e){this.applyRefoundDetail.consignee.consignee=e}},mobile:{get:function(){return this.applyRefoundDetail.consignee.mobile},set:function(e){this.applyRefoundDetail.consignee.mobile=e}},address:{get:function(){return this.applyRefoundDetail.consignee.address},set:function(e){this.applyRefoundDetail.consignee.address=e}},regionSplic:function(){return this.regionOptionDate.regionSplic},returnGoodsNum:function(){return 0==this.applyRefoundDetail.order.shipping_status?this.applyRefoundDetail.return_goods_num:this.value}}),methods:{onRead:function(){var e=this;return function(t){var i=0;i=void 0==t.length?e.materialList.length+1:t.length+e.materialList.length,i>5?Object(l["a"])(e.$t("lang.return_max_pic_prompt")):e.$store.dispatch("setMaterial",{file:t})}},causeSelect:function(e){this.retrun_cause_id=e},handelRegionShow:function(){this.regionShow=!this.regionShow},submitBtn:function(){var e=this,t={rec_id:this.$route.query.rec_id,last_option:this.causeSelected,return_remark:this.return_remark,return_brief:this.return_brief,chargeoff_status:this.applyRefoundDetail.order.chargeoff_status,return_type:this.retrun_cause_id,return_images:this.materialList,return_number:this.returnGoodsNum,addressee:this.addressee,mobile:this.mobile,code:this.email,return_address:this.address,province_region_id:this.regionOptionDate.province.id,city_region_id:this.regionOptionDate.city.id,district_region_id:this.regionOptionDate.district.id,street:""!=this.regionOptionDate.street.id?this.regionOptionDate.street.id:0};return this.return_brief?this.retrun_cause_id?0==this.causeSelected?(Object(l["a"])(this.$t("lang.fill_in_return_reason")),!1):void this.$http.post("".concat(window.ROOT_URL,"api/v4/refound/submit_return"),y.a.stringify(t)).then(function(t){var i=t.data.data;Object(l["a"])({message:i.msg,duration:1e3}),0==i.code&&e.returnApply()}):(Object(l["a"])(this.$t("lang.fill_in_service_type")),!1):(Object(l["a"])(this.$t("lang.fill_in_problem_desc")),!1)},deleteImg:function(e){var t=this;c["a"].confirm({message:this.$t("lang.confirm_remove_pic"),className:"text-center"}).then(function(){t.$store.dispatch("setDeleteImg",{index:e})})},returnApply:function(){var e=this;setTimeout(function(){e.$router.push({name:"refound"})},1e3)}},watch:{parent_cause:function(){this.causeSelected=this.parent_cause[0].cause_id},goods_cause:function(){this.retrun_cause_id=this.goods_cause[0].cause},applyRefoundDetail:function(){this.applyRefoundDetail.msg&&(Object(l["a"])({message:this.applyRefoundDetail.msg,duration:1e3}),this.returnApply())}}},C=$,k=i("2877"),x=Object(k["a"])(C,s,a,!1,null,null,null);x.options.__file="ApplyReturn.vue";t["default"]=x.exports},"8f80":function(e,t,i){"use strict";var n=i("fe7e");t["a"]=Object(n["a"])({render:function(){var e=this,t=e.$createElement,i=e._self._c||t;return i("div",{class:e.b()},[e._t("default"),i("input",e._b({ref:"input",class:e.b("input"),attrs:{type:"file",accept:e.accept,disabled:e.disabled},on:{change:e.onChange}},"input",e.$attrs,!1))],2)},name:"uploader",inheritAttrs:!1,props:{disabled:Boolean,beforeRead:Function,afterRead:Function,accept:{type:String,default:"image/*"},resultType:{type:String,default:"dataUrl"},maxSize:{type:Number,default:Number.MAX_VALUE}},methods:{onChange:function(e){var t=this,i=e.target.files;!this.disabled&&i.length&&(i=1===i.length?i[0]:[].slice.call(i,0),!i||this.beforeRead&&!this.beforeRead(i)||(Array.isArray(i)?Promise.all(i.map(this.readFile)).then(function(e){var n=!1,s=i.map(function(s,a){return s.size>t.maxSize&&(n=!0),{file:i[a],content:e[a]}});t.onAfterRead(s,n)}):this.readFile(i).then(function(e){t.onAfterRead({file:i,content:e},i.size>t.maxSize)})))},readFile:function(e){var t=this;return new Promise(function(i){var n=new FileReader;n.onload=function(e){i(e.target.result)},"dataUrl"===t.resultType?n.readAsDataURL(e):"text"===t.resultType&&n.readAsText(e)})},onAfterRead:function(e,t){t?this.$emit("oversize",e):(this.afterRead&&this.afterRead(e),this.$refs.input&&(this.$refs.input.value=""))}}})},a909:function(e,t,i){"use strict";i("68ef")},bcd3:function(e,t,i){},be7f:function(e,t,i){"use strict";i("68ef"),i("1146")},c194:function(e,t,i){"use strict";i("68ef")},e41f:function(e,t,i){"use strict";var n=i("fe7e"),s=i("6605");t["a"]=Object(n["a"])({render:function(){var e,t=this,i=t.$createElement,n=t._self._c||i;return n("transition",{attrs:{name:t.currentTransition}},[t.shouldRender?n("div",{directives:[{name:"show",rawName:"v-show",value:t.value,expression:"value"}],class:t.b((e={},e[t.position]=t.position,e))},[t._t("default")],2):t._e()])},name:"popup",mixins:[s["a"]],props:{transition:String,overlay:{type:Boolean,default:!0},closeOnClickOverlay:{type:Boolean,default:!0},position:{type:String,default:""}},computed:{currentTransition:function(){return this.transition||(""===this.position?"van-fade":"popup-slide-"+this.position)}}})},e930:function(e,t,i){"use strict";i("68ef"),i("bcd3")},f06a:function(e,t,i){"use strict";i("68ef"),i("fb6c")},f331:function(e,t,i){"use strict";t["a"]={data:function(){return{parent:null}},methods:{findParent:function(e){var t=this.$parent;while(t){if(t.$options.name===e){this.parent=t;break}t=t.$parent}}}}},fb6c:function(e,t,i){}}]);