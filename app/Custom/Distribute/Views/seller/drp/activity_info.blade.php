@include('seller.base.seller_pageheader')

@include('base.seller_nave_header')
<style>
    .categorySelect .select-container  .categorySelect .select-container {
        height: 500px;
    }

    .dates_box_top {
        height: 37px;
    }
    .dates_bottom {
        height: 38px;
    }
    .dates_bottom {
        padding: 8px 5px;
        height: 37px;
        overflow: hidden;
        border: 1px solid #ccc;
        border-top: 0;
    }
    * {
        box-sizing: inherit;
    }
</style>

<div class="ecsc-layout">
    <div class="site wrapper">
        @include('seller.base.seller_menu_left')

        <div class="ecsc-layout-right">
            <div class="main-content" id="mainContent">
                <div class="ecsc-path"><span>{{ $menu_select['action_label'] ?? '' }} - {{ $lang['seller_activity_info'] ?? '' }}</span></div>
                <div class="wrapper-right of">

                    <div class="explanation" id="explanation">
                        <div class="ex_tit"><i class="sc_icon"></i><h4>{{ $lang['operating_hints'] }}</h4></div>
                        <ul>
                            @if(isset($lang['seller_activity_info_tips']) && !empty($lang['seller_activity_info_tips']))

                                @foreach($lang['seller_activity_info_tips'] as $v)
                                    <li>{{ $v }}</li>
                                @endforeach

                            @endif
                        </ul>
                    </div>

                    <div class="goods_search_div search-form mb10">
                        <div class="search_select">
                            <div class="categorySelect">
                                <div class="selection">
                                    <input type="text" name="category_name" id="category_name" class="text w250 valid" value="{{$lang['select_cat']}}" autocomplete="off" readonly data-filter="cat_name" />
                                    <input type="hidden" name="category_id" id="category_id" value="0" data-filter="cat_id" />
                                </div>
                                <div class="select-container" style="display:none;">
                                    @include('base.filter_category')
                                </div>
                            </div>
                        </div>
                        <div class="search_select">
                            <div class="brandSelect">
                                <div class="selection">
                                    <input type="text" name="brand_name" id="brand_name" class="text w100 valid" value="{{$lang['choose_brand']}}" autocomplete="off" readonly data-filter="brand_name" />
                                    <input type="hidden" name="brand_id" id="brand_id" value="0" data-filter="brand_id" />
                                </div>
                                <div class="brand-select-container" style="display:none;">
                                    @include('base.filter_brand')
                                </div>
                            </div>
                        </div>

                        <div class="search-key">
                            <input type="text" name="keyword" size="20" class="text text_2 mr10" placeholder="{{$lang['input_keywords']}}" autocomplete="off" data-filter="keyword" autocomplete="off"  />
                            <input type="submit" class="sc-btn sc-blueBg-btn btn30" value="{{$lang['button_search']}}" onclick="searchGoods()">
                        </div>
                        <input type="hidden" name="ru_id" value="{{$ru_id}}" />
                        <input type="hidden" name="presale" value="1" />
                        <input type="hidden" name="cat_id" id="category">
                    </div>

                    <div class="ecsc-form-goods">
                        <div class="items-info">
                            <form method="post" action="{{ route('distribute.seller.activity_info_add') }}" enctype="multipart/form-data" name="theForm" role="form">
                                @csrf
                                <div class="wrapper-list border1">
                                    <dl>
                                        <dt>{{$lang['require_field']}}&nbsp;{{$lang['label_goods_name']}}</dt>
                                        <dd>
                                            <div id="goods_id" class="imitate_select select_w320 mr0">
                                                <div class="cite">
                                                    @if(isset($activity_detail['goods_name']))
                                                    {{$activity_detail['goods_name']}}
                                                    @else
                                                    {{$lang['please_select']}}
                                                    @endif
                                                </div>
                                                <ul>
                                                    <li class="li_not">
                                                        @if(isset($activity_detail['goods_name']))
                                                            {{$activity_detail['goods_name']}}
                                                        @else
                                                            {{$lang['please_select']}}
                                                        @endif
                                                    </li>
                                                </ul>
                                                <input name="data[goods_id]" type="hidden" value="{{$activity_detail['goods_id'] ?? 0}}" id="goods_id_val">
                                            </div>
                                            <div class="form_prompt"></div>

                                        </dd>
                                    </dl>

                                    <dl>
                                            <dt>{{$lang['require_field']}}{{$lang['act_name']}}</dt>
                                        <dd>
                                            <input type="text" name="data[act_name]" id="snatch_name" class="text" value="{{$activity_detail['act_name'] ?? ''}}" autocomplete="off" />
                                        </dd>
                                    </dl>

                                    <dl>
                                        <dt>{{$lang['require_field']}}&nbsp;{{$lang['label_start_end_date']}}</dt>
                                        <dd>
                                            <div class="text_time" id="text_time1">
                                                <input name="data[start_time]" type="text" class="text" id="start_time" size="22" value='{{$activity_detail['start_time'] ?? date('Y-m-d H:i') }}' readonly="readonly" />
                                            </div>
                                            <span class="bolang">&nbsp;&nbsp;~&nbsp;&nbsp;</span>
                                            <div class="text_time" id="text_time2">
                                                <input name="data[end_time]" type="text" class="text" id="end_time" size="22" value='{{$activity_detail['end_time'] ?? date('Y-m-d H:i', mktime(0,0,0,date('m') + 1, date('d'), date('Y'))) }}' readonly="readonly" />
                                            </div>
                                            <div class="form_prompt"></div>
                                        </dd>
                                    </dl>

                                    <dl>
                                        <dt>{{$lang['require_field']}}{{$lang['award_money']}}</dt>
                                        <dd>
                                            <input type="text" name="data[raward_money]" id="raward_money" class="text" value="{{$activity_detail['raward_money'] ?? ''}}" autocomplete="off" />
                                        </dd>
                                    </dl>

                                    <dl>
                                        <dt>{{$lang['require_field']}}{{$lang['seller_raward_type']}}</dt>
                                        <input type="hidden" name="data[raward_type]" value="{{ $activity_detail['raward_type'] ?? 0 }}" id="raward_type">
                                        <dd style="margin-top: 8px;">
                                            <input type="radio" @if(isset($activity_detail['raward_type']) && $activity_detail['raward_type'] == 0) checked="checked" @endif name="raward_type" class="ui-radio" id="raward_type_zero" value="0" onclick="raward_type_status()"/>
                                            <label for="raward_type_zero" class="ui-radio-label">{{$lang['integral']}}</label>

                                            <input type="radio" @if(isset($activity_detail['raward_type']) && $activity_detail['raward_type'] == 1) checked="checked" @endif name="raward_type" class="ui-radio" id="raward_type_one" value="1" onclick="raward_type_status()"/>
                                            <label for="raward_type_one" class="ui-radio-label">{{$lang['balance']}}</label>

                                            <input type="radio" @if(isset($activity_detail['raward_type']) && $activity_detail['raward_type'] == 2) checked="checked" @endif name="raward_type" class="ui-radio" id="raward_type_two" value="2" onclick="raward_type_status()"/>
                                            <label for="raward_type_two" class="ui-radio-label">{{$lang['commission']}}</label>
                                        </dd>
                                    </dl>

                                    <dl>
                                        <dt>{{$lang['require_field']}}{{$lang['seller_activity_act_type_share']}}</dt>
                                        <dd>
                                            <input type="text" name="data[act_type_share]" id="raward_money" class="text" value="{{$activity_detail['act_type_share'] ?? ''}}" autocomplete="off" />
                                        </dd>
                                    </dl>

                                    <dl>
                                        <dt>{{$lang['require_field']}}{{$lang['seller_activity_act_type_place']}}</dt>
                                        <dd>
                                            <input type="text" name="data[act_type_place]" id="raward_money" class="text" value="{{$activity_detail['act_type_place'] ?? ''}}" autocomplete="off" />
                                        </dd>
                                    </dl>

                                    <div class="item">
                                        <dt>{{$lang['seller_act_dsc']}}</dt>
                                        <dd>
                                            <textarea name="data[act_dsc]" id="text_info" class="textarea" cols="80" rows="5">{{$activity_detail['act_dsc'] ?? ''}}</textarea>
                                        </dd>
                                    </div>

                                    <div class="button-bottom">
                                        <dl class="button_info">
                                            <dt>&nbsp;</dt>
                                            <dd>
                                                <input name="data[id]" type="hidden" id="data[id]" value="{{$activity_detail['id'] ?? 0}}">
                                                <input type="submit" name="submit" value="{{$lang['button_submit']}}" class="sc-btn sc-blueBg-btn btn35" id="submitBtn" />
                                            </dd>
                                        </dl>
                                    </div>

                            </form>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>
</div>
<script>
    //奖励类型
    function raward_type_status() {
        var dis_commission_type = $('input[name=raward_type]:checked').val();
        $('#raward_type').val(dis_commission_type);
    }
    //时间选择
    var opts1 = {
        'targetId':'start_time',
        'triggerId':['start_time'],
        'alignId':'text_time1',
        'format':'-',
        'min':''
    },opts2 = {
        'targetId':'end_time',
        'triggerId':['end_time'],
        'alignId':'text_time2',
        'format':'-',
        'min':''
    }
    xvDate(opts1);
    xvDate(opts2);

    //表单验证
    $(function(){
        $("#submitBtn").click(function(){
            if($("#presale_form").valid()){
                $("#presale_form").submit();
            }
        });

        $('#presale_form').validate({
            errorPlacement: function(error, element){
                var error_div = element.parents('dl').find('div.form_prompt');
                //element.parents('dl').find(".notic").hide();
                error_div.append(error);
            },
            ignore:'.ignore',
            rules : {
                goods_id : {
                    required : true
                },
                cat_id: {
                    required : true,
                    min : 1
                },
                start_time :{
                    required : true
                },
                end_time :{
                    required : true,
                    compareDate:"#start_time",
                },
                pay_start_time:{
                    required : true,
                    compareDate:"#end_time",
                },
                pay_end_time :{
                    required : true,
                    compareDate:"#pay_start_time",
                }
            },
            messages : {
                goods_id : {
                    required : '<i class="icon icon-exclamation-sign"></i>'+error_goods_null
                },
                cat_id : {
                    required : '<i class="icon icon-exclamation-sign"></i>'+select_cat_null,
                    min : '<i class="icon icon-exclamation-sign"></i>'+select_cat_null
                },
                start_time :{
                    required : '<i class="icon icon-exclamation-sign"></i>'+start_data_notnull
                },
                end_time :{
                    required : '<i class="icon icon-exclamation-sign"></i>'+end_data_notnull,
                    compareDate:'<i class="icon icon-exclamation-sign"></i>'+data_invalid_gt
                },
                pay_start_time:{
                    required : '<i class="icon icon-exclamation-sign"></i>'+pay_start_time_null,
                    compareDate:'<i class="icon icon-exclamation-sign"></i>'+pay_start_time_cw
                },
                pay_end_time :{
                    required : '<i class="icon icon-exclamation-sign"></i>'+pay_end_time_null,
                    compareDate:'<i class="icon icon-exclamation-sign"></i>'+pay_end_time_cw
                }
            },
            onfocusout:function(element,event){
                //实时去除结束时间是否大于开始时间验证
                var name = $(element).attr("name");

                if(name == "end_time"){
                    var endDate = $(element).val();
                    var startDate = $(element).parents("dd").find("input[name='start_time']").val();

                    var date1 = new Date(Date.parse(startDate.replace(/-/g, "/")));
                    var date2 = new Date(Date.parse(endDate.replace(/-/g, "/")));

                    if(date1 > date2){
                        $(element).removeClass("error");
                        $(element).parents("dd").find(".form_prompt").html("");
                    }
                }else if(name == "pay_end_time"){
                    var endDate = $(element).val();
                    var startDate = $(element).parents("dd").find("input[name='pay_start_time']").val();

                    var date1 = new Date(Date.parse(startDate.replace(/-/g, "/")));
                    var date2 = new Date(Date.parse(endDate.replace(/-/g, "/")));

                    if(date1 > date2){
                        $(element).removeClass("error");
                        $(element).parents("dd").find(".form_prompt").html("");
                    }
                }else if(name == "pay_start_time"){
                    var endDate = $(element).val();
                    var startDate = $("input[name='end_time']").val();

                    var date1 = new Date(Date.parse(startDate.replace(/-/g, "/")));
                    var date2 = new Date(Date.parse(endDate.replace(/-/g, "/")));

                    if(date1 > date2){
                        $(element).removeClass("error");
                        $(element).parents("dd").find(".form_prompt").html("");
                    }
                }
            }
        });
    });


    /**
     * 搜索商品
     */
    function searchGoods(){
        var frm = $('.search-form');
        var filter = new Object;
        filter.cat_id   = frm.find("input[name='category_id']").val();
        filter.brand_id = frm.find("input[name='brand_id']").val();
        filter.keyword  = frm.find("input[name='keyword']").val();
        filter.ru_id = frm.find("input[name='ru_id']").val();
        filter.presale = frm.find("input[name='presale']").val();


        Ajax.call( "{{ url('seller/presale.php') }}?is_ajax=1&act=search_goods", filter, searchGoodsResponse, 'GET', 'JSON');
    }

    function searchGoodsResponse(result){
        if(result.error == '1' && result.message != ''){
            alert(result.message);
            return;
        }

        $("#goods_id").find("li").remove();

        var goods = result.content;
        if (goods){
            for (i = 0; i < goods.length; i++){
                $("#goods_id").children("ul").append("<li><a href='javascript:;' data-value='"+goods[i].goods_id+"' class='ftx-01'>"+goods[i].goods_name+"</a><input type='hidden' name='user_search' value='"+goods[i].goods_id+"'></li>")
            }
            $("#goods_id").children("ul").show();
        }
        return;
    }
</script>
@include('admin.drp.pagefooter')

