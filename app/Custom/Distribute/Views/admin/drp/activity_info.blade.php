@include('admin.base.header')
<link rel="stylesheet" type="text/css" href="{{ asset('assets/mobile/css/console_team.css') }}" />
<style>
    .label {
        color: #130303;
        font-size: 121%;
        float: none;
        margin-left: 20em;
    }
    .main-info .item .label_value {
        float: right;
    }
</style>
<div class="warpper">
    <div class="title">{{ $lang['activity_list'] }} - {{ $lang['activity_info'] }}</div>
    <div class="content">
        <div class="tabs_info">
            <ul>
                <li class="curr"><a href="{{ route('distribute.admin.activity_info',array('id'=>$activity_detail['id'] ?? 0)) }}">{{$lang['self_info']}}</a></li>
{{--                <li><a href="{{ route('distribute.admin.activity_info_dsc',array('id'=>$activity_detail['id'] ?? 0))  }}">{{$lang['act_dsc_info']}}</a></li>--}}
            </ul>
        </div>
        <div class="explanation" id="explanation">
            <div class="ex_tit"><i class="sc_icon"></i><h4>{{ $lang['operating_hints'] }}</h4><span id="explanationZoom" title="{{ $lang['activity_info_menu'] }}"></span>
            </div>
            <ul>
                @if(isset($lang['activity_info_tips']) && !empty($lang['activity_info_tips']))

                    @foreach($lang['activity_info_tips'] as $v)
                        <li>{!! $v !!}</li>
                    @endforeach
                @endif
            </ul>
        </div>
        <div class="main-info">
            <form method="post" action="{{ route('distribute.admin.activity_info_add') }}" enctype="multipart/form-data"
                  role="form" id="group_buy_form" class="validation">
            <div class="switch_info" style="overflow:inherit;">

                <!--搜索-->
                <div class="goods_search_div bor_bt_das">
                    <div class="search_select">
                        <div class="categorySelect">
                            <div class="selection">
                                <input type="text" name="category_name" id="category_name"
                                       class="text w250 valid" value="{{ $lang['please_category'] }}"
                                       autocomplete="off" readonly="" data-filter="cat_name">
                                <input type="hidden" name="category_id" id="category_id" value="0"
                                       data-filter="cat_id">
                            </div>
                            <div class="select-container" style="display: none;">
                                <!--分类搜索-->
                                {{--@include('mobile.base.filter_team_category')--}}
                                <div class="select-top">
                                    <a href="javascript:;" class="categoryTop" data-cid="0"
                                       data-cname="">{{ $lang['choose_again'] }}</a>

                                    @if(isset($filter_category_navigation) && $filter_category_navigation)

                                        @foreach($filter_category_navigation as $navigation)

                                            &gt;<a href="javascript:;" class="categoryTop"
                                                   data-cid="{{ $navigation['cat_id'] }}"
                                                   data-cname="{{ $navigation['cat_name'] }}">{{ $navigation['cat_name'] }}</a>

                                        @endforeach

                                    @else

                                        &gt; <span>{{ $lang['please_category'] }}</span>

                                    @endif

                                </div>
                                <div class="select-list">
                                    <ul>
                                        @if(isset($filter_category_list) && $filter_category_list)
                                            @foreach($filter_category_list as $category)

                                                <li data-cid="{{ $category['cat_id'] }}"
                                                    data-cname="{{ $category['cat_name'] }}">
                                                    <em>
                                                        @if($filter_category_level == 1)
                                                            Ⅰ
                                                        @elseif($filter_category_level == 2)
                                                            Ⅱ
                                                        @elseif($filter_category_level == 3)
                                                            Ⅲ
                                                        @else
                                                            Ⅰ
                                                        @endif
                                                    </em>{{ $category['cat_name'] }}</li>

                                            @endforeach
                                        @endif

                                    </ul>
                                </div>
                                <!--分类搜索-->
                            </div>
                        </div>
                    </div>
                    <div class="search_select">
                        <div class="brandSelect">
                            <div class="selection">
                                <input type="text" name="brand_name" id="brand_name" class="text w120 valid"
                                       value="{{ $lang['choose_brand'] }}" autocomplete="off" readonly=""
                                       data-filter="brand_name">
                                <input type="hidden" name="brand_id" id="brand_id" value="0"
                                       data-filter="brand_id">
                            </div>
                            <div class="brand-select-container" style="display: none;">
                                <div class="brand-top">
                                    <div class="letter">
                                        <ul>
                                            <li><a href="javascript:void(0);"
                                                   data-letter="">{{ $lang['all_brand'] }}</a></li>
                                            <li><a href="javascript:void(0);" data-letter="A">A</a></li>
                                            <li><a href="javascript:void(0);" data-letter="B">B</a></li>
                                            <li><a href="javascript:void(0);" data-letter="C">C</a></li>
                                            <li><a href="javascript:void(0);" data-letter="D">D</a></li>
                                            <li><a href="javascript:void(0);" data-letter="E">E</a></li>
                                            <li><a href="javascript:void(0);" data-letter="F">F</a></li>
                                            <li><a href="javascript:void(0);" data-letter="G">G</a></li>
                                            <li><a href="javascript:void(0);" data-letter="H">H</a></li>
                                            <li><a href="javascript:void(0);" data-letter="I">I</a></li>
                                            <li><a href="javascript:void(0);" data-letter="J">J</a></li>
                                            <li><a href="javascript:void(0);" data-letter="K">K</a></li>
                                            <li><a href="javascript:void(0);" data-letter="M">M</a></li>
                                            <li><a href="javascript:void(0);" data-letter="N">N</a></li>
                                            <li><a href="javascript:void(0);" data-letter="O">O</a></li>
                                            <li><a href="javascript:void(0);" data-letter="P">P</a></li>
                                            <li><a href="javascript:void(0);" data-letter="Q">Q</a></li>
                                            <li><a href="javascript:void(0);" data-letter="R">R</a></li>
                                            <li><a href="javascript:void(0);" data-letter="S">S</a></li>
                                            <li><a href="javascript:void(0);" data-letter="T">T</a></li>
                                            <li><a href="javascript:void(0);" data-letter="U">U</a></li>
                                            <li><a href="javascript:void(0);" data-letter="V">V</a></li>
                                            <li><a href="javascript:void(0);" data-letter="W">W</a></li>
                                            <li><a href="javascript:void(0);" data-letter="X">X</a></li>
                                            <li><a href="javascript:void(0);" data-letter="Y">Y</a></li>
                                            <li><a href="javascript:void(0);" data-letter="Z">Z</a></li>
                                            <li><a href="javascript:void(0);"
                                                   data-letter="QT">{{ $lang['other'] }}</a></li>
                                        </ul>
                                    </div>
                                    <div class="b_search">
                                        <input name="search_brand_keyword" id="search_brand_keyword" type="text"
                                               class="b_text" placeholder="{{ $lang['search_brand'] }}"
                                               autocomplete="off">
                                        <a href="javascript:void(0);" class="btn-mini"><i
                                                    class="fa fa-search"></i></a>
                                    </div>
                                </div>
                                <div class="brand-list ps-container ps-active-y">

                                    <!--品牌搜索-->
                                    {{--@include('mobile.base.team_brand_list')--}}
                                    <ul>
                                        <li data-id="0" data-name="{{ $lang['choose_brand'] }}"
                                            class="blue">{{ $lang['choose_cancel'] }}</li>
                                        @if(isset($filter_brand_list) && $filter_brand_list)
                                            @foreach($filter_brand_list as $brand)

                                                <li data-id="{{ $brand['brand_id'] }}"
                                                    data-name="{{ $brand['brand_name'] }}">
                                                    <em>{{ $brand['letter'] }}</em>{{ $brand['brand_name'] }}
                                                </li>

                                            @endforeach
                                        @endif
                                    </ul>
                                    <!--品牌搜索-->

                                    <div class="ps-scrollbar-x-rail"
                                         style="width: 234px; display: none; left: 0px; bottom: 3px;">
                                        <div class="ps-scrollbar-x" style="left: 0px; width: 0px;"></div>
                                    </div>
                                    <div class="ps-scrollbar-y-rail"
                                         style="top: 0px; height: 220px; display: inherit; right: 3px;">
                                        <div class="ps-scrollbar-y" style="top: 0px; height: 13px;"></div>
                                    </div>
                                </div>
                                <div class="brand-not"
                                     style="display:none;">{{ $lang['no_brand_records'] }}</div>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="ru_id" value="0">
                    <input type="text" name="keyword" class="text w150"
                           placeholder="{{ $lang['keyword'] }}"
                           data-filter="keyword" autocomplete="off">
                    <a href="javascript:void(0);" class="btn btn30" onclick="searchGoods()"><i
                                class="fa fa-search"></i>{{ $lang['button_search'] }}</a>
                </div>


                <div class="item">
                    <div class="label-t">{{$lang['require_field']}}{{$lang['snatch_name']}}</div>
                    <div class="label_value">
                        <input type="text" name="data[act_name]" id="snatch_name" class="text" value="{{$activity_detail['act_name'] ?? ''}}" autocomplete="off" />
                        <div class="form_prompt"></div>
                    </div>
                </div>

                <div class="item">
                    <div class="label-t">{{$lang['require_field']}}{{ $lang['select_goods_activity'] }}</div>
                        <div class="label_value">
                            <div id="goods_id" class="imitate_select select_w320">
                                <div class="cite">
                                    @if(isset($activity_detail['id']) && $activity_detail['id'] && !empty($activity_detail['goods_name']))
                                        {{ $activity_detail['goods_name'] }}
                                    @else
                                        {{ $lang['please_select'] }}
                                    @endif
                                </div>
                                <ul>

                                    @if(isset($activity_detail['id']) && !$activity_detail['id'])
                                        <li class="li_not">{{ $lang['please_search_goods'] }}</li>
                                    @endif

                                </ul>
                                <input name="data[goods_id]" type="hidden" datatype="*"
                                       nullmsg="{{ $lang['please_select_team_goods'] }}" value="
                                @if(isset($activity_detail['id']) && $activity_detail['id'])
                                {{ $activity_detail['goods_id'] }}
                                @endif
                                        " id="goods_id_val">
                            </div>
                            <div class="form_prompt"></div>
                        </div>
                </div>

                <div class="item">
                    <div class="label-t">{{$lang['require_field']}}{{$lang['start_end_time']}}</div>
                    <div class="label_value text_time">
                        <div class="text_time" id="text_time1" style="float:left;">
                            <input type="text" name="data[start_time]" value="{{$activity_detail['start_time'] ?? date('Y-m-d H:i') }}" id="start_time" class="text mr0" readonly />
                        </div>
                        <span class="bolang">&nbsp;&nbsp;~&nbsp;&nbsp;</span>
                        <div class="text_time" id="text_time2" style="float:left;">
                            <input type="text" name="data[end_time]" value="{{$activity_detail['end_time'] ?? date('Y-m-d H:i', mktime(0,0,0,date('m') + 1, date('d'), date('Y'))) }}" id="end_time" class="text" readonly />
                        </div>
                        <div class="form_prompt"></div>
                    </div>
                </div>

                <div class="item">
                    <div class="label-t">{{$lang['require_field']}}{{$lang['raward_money']}}</div>
                    <div class="label_value">
                        <input type="number" name="data[raward_money]" id="snatch_name" class="text" value="{{$activity_detail['raward_money'] ?? ''}}" autocomplete="off" />
                        <div class="form_prompt"></div>
                    </div>
                </div>

                <div class="item">
                    <div class="label-t">{{$lang['require_field']}}{{$lang['raward_type']}}</div>
                    <input type="hidden" name="data[raward_type]" value="{{ $activity_detail['raward_type'] ?? 0 }}" id="raward_type">
                    <div class="label_value">
                        <input type="radio" @if(isset($activity_detail['raward_type']) && $activity_detail['raward_type'] == 0) checked="checked" @endif name="raward_type" class="ui-radio" id="raward_type_zero" value="0" onclick="raward_type_status()"/>
                        <label for="raward_type_zero" class="ui-radio-label">{{$lang['integral']}}</label>

                        <input type="radio" @if(isset($activity_detail['raward_type']) && $activity_detail['raward_type'] == 1) checked="checked" @endif name="raward_type" class="ui-radio" id="raward_type_one" value="1" onclick="raward_type_status()"/>
                        <label for="raward_type_one" class="ui-radio-label">{{$lang['balance']}}</label>

                        <input type="radio" @if(isset($activity_detail['raward_type']) && $activity_detail['raward_type'] == 2) checked="checked" @endif name="raward_type" class="ui-radio" id="raward_type_two" value="2" onclick="raward_type_status()"/>
                        <label for="raward_type_two" class="ui-radio-label">{{$lang['commission']}}</label>
                    </div>
                </div>

                {{--<div class="item">--}}
                    {{--<div class="label-t">{{$lang['require_field']}}{{$lang['text_info']}}</div>--}}
                    {{--<div class="label_value">--}}
                        {{--<input type="text" name="data[text_info]" id="text_info" class="text" value="{{$activity_detail['text_info'] ?? ''}}" autocomplete="off" />--}}
                        {{--<div class="form_prompt"></div>--}}
                    {{--</div>--}}
                {{--</div>--}}

                <div class="item">
                    <div class="label-t">{{$lang['require_field']}}{{$lang['activity_act_type_share']}}</div>
                    <div class="label_value">
                        <input type="number" name="data[act_type_share]" id="snatch_name" class="text" value="{{$activity_detail['act_type_share'] ?? ''}}" autocomplete="off" />
                        <div class="form_prompt"></div>
                    </div>
                </div>

                <div class="item">
                    <div class="label-t">{{$lang['require_field']}}{{$lang['activity_act_type_place']}}</div>
                    <div class="label_value">
                        <input type="number" name="data[act_type_place]" id="snatch_name" class="text" value="{{$activity_detail['act_type_place'] ?? ''}}" autocomplete="off" />
                        <div class="form_prompt"></div>
                    </div>
                </div>

                <div class="item">
                    <div class="label-t">{{$lang['require_field']}}{{$lang['activity_complete_required']}}</div>
                    <input type="hidden" name="data[complete_required]" id="complete_required" value="{{ $activity_detail['complete_required'] ?? 0 }}">
                    <div class="label_value">
                        <input type="radio" @if(isset($activity_detail['complete_required']) && $activity_detail['complete_required'] == 0) checked="checked" @endif name="complete_required" class="ui-radio" id="complete_required_zero" value="0" onclick="complete_required_status()"/>
                        <label for="complete_required_zero" class="ui-radio-label">{{$lang['required_all']}}</label>

                        <input type="radio" @if(isset($activity_detail['complete_required']) && $activity_detail['complete_required'] == 1) checked="checked" @endif name="complete_required" class="ui-radio" id="complete_required_one" value="1" onclick="complete_required_status()"/>
                        <label for="complete_required_one" class="ui-radio-label">{{$lang['required_one']}}</label>

                    </div>
                </div>

                <div class="item">
                    <div class="label-t">{{$lang['require_field']}}{{$lang['act_dsc']}}</div>
                    <div class="label_value">
                        <textarea name="data[act_dsc]" id="text_info" class="textarea" cols="80" rows="5">{{$activity_detail['act_dsc'] ?? ''}}</textarea>
                    </div>
                </div>

                <div class="item">
                    <div class="label-t">&nbsp;</div>
                    <div class="lable_value info_btn">
                        @csrf
                        <input type="hidden" name="data[id]" value="{{ $activity_detail['id'] ?? '' }}">
                        <input type="submit" name="submit" value="{{ $lang['button_submit'] }}" class="button"
                               style="margin:0 auto;">
                    </div>
                </div>

            </div>
            </form>
        </div>

    </div>
</div>
<script type="text/javascript">

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

    $(function () {

        // 选择品牌
        $('input[name="brand_name"]').click(function () {
            $(".brand-select-container").hide();
            $(this).parents(".selection").next(".brand-select-container").show();
            //$(".brand-list").perfectScrollbar("destroy");
            //$(".brand-list").perfectScrollbar();
        });

        /* AJAX选择品牌 */
        // 根据首字母查询
        $('.letter').find('a[data-letter]').click(function () {
            var goods_id = $("input[name=goods_id]").val();
            var letter = $(this).attr('data-letter');
            $(".brand-not strong").html(letter);
            $.post("{{ route('admin/team/searchbrand') }}", {goods_id: goods_id, letter: letter}, function (result) {
                if (result.content) {
                    $(".brand-list").html(result.content);
                    $(".brand-not").hide();
                } else {
                    $(".brand-list").html("");
                    $(".brand-not").show();
                }
                //$(".brand-list").perfectScrollbar("destroy");
                //$(".brand-list").perfectScrollbar();
            }, 'json')
        });


        // 根据关键字查询品牌
        $('.b_search').find('a').click(function () {
            var goods_id = $("input[name=goods_id]").val();
            var keyword = $(this).prev().val();
            $(".brand-not strong").html(keyword);
            $.post("{{ route('admin/team/searchbrand') }}", {goods_id: goods_id, keyword: keyword}, function (result) {
                if (result.content) {
                    $(".brand-list").html(result.content);
                    $(".brand-not").hide();
                } else {
                    $(".brand-list").html("");
                    $(".brand-not").show();
                }
                //$(".brand-list").perfectScrollbar("destroy");
                //$(".brand-list").perfectScrollbar();
            }, 'json')
        });

        // 选择品牌
        $('.brand-list').on('click', 'li', function () {
            $(this).parents('.brand-select-container').prev().find('input[data-filter=brand_id]').val($(this).data('id'));
            $(this).parents('.brand-select-container').prev().find('input[data-filter=brand_name]').val($(this).data('name'));
            $('.brand-select-container').hide();
        });

        jQuery.category = function () {
            $('.selection input[name="category_name"]').click(function () {
                $(this).parents(".selection").next('.select-container').show();
            });

            /*点击分类获取下级分类列表*/
            $(document).on('click', '.select-list li', function () {
                var obj = $(this);
                var cat_id = $(this).data('cid');
                $.post("{{ route('admin/team/filtercategory') }}", {cat_id: cat_id}, function (result) {
                    if (result.content) {
                        obj.parents(".categorySelect").find("input[data-filter=cat_name]").val(result.cat_nav); //修改cat_name
                        obj.parents(".select-container").html(result.content);
                        //$(".select-list").perfectScrollbar("destroy");
                        //$(".select-list").perfectScrollbar();
                    }
                }, 'json');
                obj.parents(".categorySelect").find("input[data-filter=cat_id]").val(cat_id); //修改cat_id

                var cat_level = obj.parents(".categorySelect").find(".select-top a").length; //获取分类级别
                if (cat_level >= 3) {
                    $('.categorySelect .select-container').hide();
                }
            });

            //点击a标签返回所选分类 by wu
            $(document).on('click', '.select-top a', function () {
                var obj = $(this);
                var cat_id = $(this).data('cid');
                $.post("{{ route('admin/team/filtercategory') }}", {cat_id: cat_id}, function (result) {
                    if (result.content) {
                        obj.parents(".categorySelect").find("input[data-filter=cat_name]").val(result.cat_nav); //修改cat_name
                        obj.parents(".select-container").html(result.content);
                        //$(".select-list").perfectScrollbar("destroy");
                        //$(".select-list").perfectScrollbar();
                    }
                }, 'json');
                obj.parents(".categorySelect").find("input[data-filter=cat_id]").val(cat_id); //修改cat_id
            });
            /*分类搜索的下拉列表end*/
        }


        //div仿select下拉选框 start
        $(document).on("click", ".imitate_select .cite", function () {
            $(".imitate_select ul").hide();
            $(this).parents(".imitate_select").find("ul").show();
            //$(this).siblings("ul").perfectScrollbar("destroy");
            //$(this).siblings("ul").perfectScrollbar();
        });

        $(document).on("click", ".imitate_select li  a", function () {
            var _this = $(this);
            var val = _this.data('value');
            var text = _this.html();
            _this.parents(".imitate_select").find(".cite").html(text);
            _this.parents(".imitate_select").find("input[type=hidden]").val(val);
            _this.parents(".imitate_select").find("ul").hide();
        });
        //div仿select下拉选框 end

        $(document).click(function (e) {
            /*
             **点击空白处隐藏展开框
             */

            //会员搜索
            if (e.target.id != 'user_name' && !$(e.target).parents("div").is(".select-container")) {
                $('.selection_select .select-container').hide();
            }
            //品牌
            if (e.target.id != 'brand_name' && !$(e.target).parents("div").is(".brand-select-container")) {
                $('.brand-select-container').hide();
                $('.brandSelect .brand-select-container').hide();
            }
            //分类
            if (e.target.id != 'category_name' && !$(e.target).parents("div").is(".select-container")) {
                $('.categorySelect .select-container').hide();
            }
            //仿select
            if (e.target.className != 'cite' && !$(e.target).parents("div").is(".imitate_select")) {
                $('.imitate_select ul').hide();
            }
            //日期选择插件
            if (!$(e.target).parent().hasClass("text_time")) {
                $(".iframe_body").removeClass("relative");
            }
        });

        //select下拉默认值赋值
        $('.imitate_select').each(function () {
            var sel_this = $(this)
            var val = sel_this.children('input[type=hidden]').val();
            sel_this.find('a').each(function () {
                if ($(this).attr('data-value') == val) {
                    sel_this.children('.cite').html($(this).html());
                }
            })
        });

        //分类选择
        $.category();





        // 全局
        var articleDate = window.sessionStorage ? sessionStorage.getItem("article_ids") : Session.read("article_ids");
        // 本页面
        var article = [];
        // 显示已经选中的
        if (articleDate) {
            articleDate.split(",").each(function (val, index) {
                $("input[value=" + val + "]").attr("checked", 'checked');
                $("input[value=" + val + "]").siblings('.news_mask').removeClass("hidden");
                // 保存已有值
                article.push(val);
            });
        }
        // 点击选择与取消
        $(".artlist").click(function () {
            article = article.unique3(); // 去重
            if ($(this).is(":checked")) {
                $(this).siblings(".news_mask").removeClass("hidden");  // 显示遮罩 选中状态
                // 添加
                if (article.indexOf($(this).val()) == -1) {
                    article.push($(this).val());
                }
            } else {
                // 取消选择
                $(this).attr("checked", false);
                $(this).siblings(".news_mask").addClass("hidden");  // 移除遮罩  取消选中
                // 删除
                article.splice(article.indexOf($(this).val()), 1);
            }
            //article = article.unique3(); // 去重
            sessionStorage.setItem("article_ids", article);  // 存储sessionStorage
        });

        //选择提交
        $(".confrim").click(function () {
            var formArticleDate = '';
            formArticleDate = sessionStorage.getItem("article_ids");
            formArticleDate = formArticleDate.split(","); // 字符串转数组
            // 兼容
            var localArticle = [];
            $("input[type=checkbox]:checked").each(function () {
                localArticle.push($(this).val());
            });
            formArticleDate = formArticleDate ? formArticleDate : localArticle;

            if (formArticleDate.length > 8) {
                sessionStorage.removeItem("article_ids"); // 清空 sessionStorage article_ids
                window.location.reload();
                return false;
            }
            console.log(formArticleDate)

            sessionStorage.removeItem("article_ids"); // 清空 sessionStorage article_ids
            // 数组转字符串
            var str = formArticleDate.toString();
            // 给父级页面传值
            window.parent.$("input[name='data[buy_goods]']").val(str);
            window.parent.$.fancybox.close();

        });

        // 重置选择
        $(".button_reset").click(function () {
            sessionStorage.removeItem("article_ids");
            window.location.reload();
        });


        // 去重
        Array.prototype.unique3 = function () {
            var res = [];
            var json = {};
            for (var i = 0; i < this.length; i++) {
                if (!json[this[i]]) {
                    res.push(this[i]);
                    json[this[i]] = 1;
                }
            }
            return res;
        }
        // 查找位置
        Array.prototype.indexOf = function (val) {
            for (var i = 0; i < this.length; i++) {
                if (this[i] == val) return i;
            }
            return -1;
        };
        // 移除
        Array.prototype.remove = function (val) {
            var index = this.indexOf(val);
            if (index > -1) {
                this.splice(index, 1);
            }
        };


    })

    //分销商活动要求类型
    function click_all_direct_order_money_status() {
        var dis_commission_type = $('input[name=all_direct_order_money_status]:checked').val();
        $('#all_direct_order_money_status').val(dis_commission_type);
    }

    //奖励类型
    function raward_type_status() {
        var dis_commission_type = $('input[name=raward_type]:checked').val();
        $('#raward_type').val(dis_commission_type);
    }

    //奖励类型
    function complete_required_status() {
        var dis_commission_type = $('input[name=complete_required]:checked').val();
        $('#complete_required').val(dis_commission_type);
    }

    /**
     * 搜索商品
     */
    function searchGoods() {
        var form = $("#group_buy_form");
        var cat_id = form.find("input[name='category_id']").val();
        var brand_id = form.find("input[name='brand_id']").val();
        var keyword = form.find("input[name='keyword']").val();
        var ru_id = form.find("input[name='ru_id']").val();
        $.post("{{ route('admin/team/searchgoods') }}", {
            cat_id: cat_id,
            brand_id: brand_id,
            keyword: keyword
        }, function (data) {
            searchGoodsResponse(data);
        }, 'json');
    }

    function searchGoodsResponse(result) {
        $("#goods_id").children("ul").find("li").remove();

        var goods = result.content;
        if (goods) {
            for (i = 0; i < goods.length; i++) {
                $("#goods_id").children("ul").append("<li><i class='sc_icon sc_icon_no'></i><a href='javascript:;' data-value='" + goods[i].goods_id + "' class='ftx-01'>" + goods[i].goods_name + "</a><input type='hidden' name='user_search[]' value='" + goods[i].value + "'></li>")
            }
        }
    }


    // 选择品牌
    $('input[name="brand_name"]').click(function () {
        $(".brand-select-container").hide();
        $(this).parents(".selection").next(".brand-select-container").show();
        //$(".brand-list").perfectScrollbar("destroy");
        //$(".brand-list").perfectScrollbar();
    });

    /* AJAX选择品牌 */
    // 根据首字母查询
    $('.letter').find('a[data-letter]').click(function () {
        var goods_id = $("input[name=goods_id]").val();
        var letter = $(this).attr('data-letter');
        $(".brand-not strong").html(letter);
        $.post("{{ route('admin/team/searchbrand') }}", {goods_id: goods_id, letter: letter}, function (result) {
            if (result.content) {
                $(".brand-list").html(result.content);
                $(".brand-not").hide();
            } else {
                $(".brand-list").html("");
                $(".brand-not").show();
            }
            //$(".brand-list").perfectScrollbar("destroy");
            //$(".brand-list").perfectScrollbar();
        }, 'json')
    });


    // 根据关键字查询品牌
    $('.b_search').find('a').click(function () {
        var goods_id = $("input[name=goods_id]").val();
        var keyword = $(this).prev().val();
        $(".brand-not strong").html(keyword);
        $.post("{{ route('admin/team/searchbrand') }}", {goods_id: goods_id, keyword: keyword}, function (result) {
            if (result.content) {
                $(".brand-list").html(result.content);
                $(".brand-not").hide();
            } else {
                $(".brand-list").html("");
                $(".brand-not").show();
            }
            //$(".brand-list").perfectScrollbar("destroy");
            //$(".brand-list").perfectScrollbar();
        }, 'json')
    });

    // 选择品牌
    $('.brand-list').on('click', 'li', function () {
        $(this).parents('.brand-select-container').prev().find('input[data-filter=brand_id]').val($(this).data('id'));
        $(this).parents('.brand-select-container').prev().find('input[data-filter=brand_name]').val($(this).data('name'));
        $('.brand-select-container').hide();
    });

    jQuery.category = function () {
        $('.selection input[name="category_name"]').click(function () {
            $(this).parents(".selection").next('.select-container').show();
        });

        /*点击分类获取下级分类列表*/
        $(document).on('click', '.select-list li', function () {
            var obj = $(this);
            var cat_id = $(this).data('cid');
            $.post("{{ route('admin/team/filtercategory') }}", {cat_id: cat_id}, function (result) {
                if (result.content) {
                    obj.parents(".categorySelect").find("input[data-filter=cat_name]").val(result.cat_nav); //修改cat_name
                    obj.parents(".select-container").html(result.content);
                    //$(".select-list").perfectScrollbar("destroy");
                    //$(".select-list").perfectScrollbar();
                }
            }, 'json');
            obj.parents(".categorySelect").find("input[data-filter=cat_id]").val(cat_id); //修改cat_id

            var cat_level = obj.parents(".categorySelect").find(".select-top a").length; //获取分类级别
            if (cat_level >= 3) {
                $('.categorySelect .select-container').hide();
            }
        });

        //点击a标签返回所选分类 by wu
        $(document).on('click', '.select-top a', function () {
            var obj = $(this);
            var cat_id = $(this).data('cid');
            $.post("{{ route('admin/team/filtercategory') }}", {cat_id: cat_id}, function (result) {
                if (result.content) {
                    obj.parents(".categorySelect").find("input[data-filter=cat_name]").val(result.cat_nav); //修改cat_name
                    obj.parents(".select-container").html(result.content);
                    //$(".select-list").perfectScrollbar("destroy");
                    //$(".select-list").perfectScrollbar();
                }
            }, 'json');
            obj.parents(".categorySelect").find("input[data-filter=cat_id]").val(cat_id); //修改cat_id
        });
        /*分类搜索的下拉列表end*/
    }


    //div仿select下拉选框 start
    $(document).on("click", ".imitate_select .cite", function () {
        $(".imitate_select ul").hide();
        $(this).parents(".imitate_select").find("ul").show();
        //$(this).siblings("ul").perfectScrollbar("destroy");
        //$(this).siblings("ul").perfectScrollbar();
    });

    $(document).on("click", ".imitate_select li  a", function () {
        var _this = $(this);
        var val = _this.data('value');
        var text = _this.html();
        _this.parents(".imitate_select").find(".cite").html(text);
        _this.parents(".imitate_select").find("input[type=hidden]").val(val);
        _this.parents(".imitate_select").find("ul").hide();
    });
    //div仿select下拉选框 end

    $(document).click(function (e) {
        /*
         **点击空白处隐藏展开框
         */

        //会员搜索
        if (e.target.id != 'user_name' && !$(e.target).parents("div").is(".select-container")) {
            $('.selection_select .select-container').hide();
        }
        //品牌
        if (e.target.id != 'brand_name' && !$(e.target).parents("div").is(".brand-select-container")) {
            $('.brand-select-container').hide();
            $('.brandSelect .brand-select-container').hide();
        }
        //分类
        if (e.target.id != 'category_name' && !$(e.target).parents("div").is(".select-container")) {
            $('.categorySelect .select-container').hide();
        }
        //仿select
        if (e.target.className != 'cite' && !$(e.target).parents("div").is(".imitate_select")) {
            $('.imitate_select ul').hide();
        }
        //日期选择插件
        if (!$(e.target).parent().hasClass("text_time")) {
            $(".iframe_body").removeClass("relative");
        }
    });

    //select下拉默认值赋值
    $('.imitate_select').each(function () {
        var sel_this = $(this)
        var val = sel_this.children('input[type=hidden]').val();
        sel_this.find('a').each(function () {
            if ($(this).attr('data-value') == val) {
                sel_this.children('.cite').html($(this).html());
            }
        })
    });

    //分类选择
    $.category();

    $(".categorySelect .select-container ul li").click(function () {
        var cate = $(this).attr("data-cname")
        $("#category_name").val(cate)
    })
</script>
@include('admin.drp.pagefooter')