@include('admin.wechat.pageheader')

{{--选择分页样式 共用拼团的样式--}}
<link rel="stylesheet" type="text/css" href="{{ asset('assets/mobile/css/console_team.css') }}" />

<style type="text/css">
    .article {
        border: 1px solid #ddd;
        padding: 5px 5px 0 5px;
    }

    .cover {
        /*height: 160px;*/
        position: relative;
        margin-bottom: 5px;
        overflow: hidden;
    }

    .article .cover img {
        width: 100%;
        height: auto;
    }

    .article span {
        height: 40px;
        line-height: 40px;
        display: block;
        z-index: 5;
        position: absolute;
        width: 100%;
        bottom: 0px;
        color: #FFF;
        padding: 0 10px;
        background-color: rgba(0, 0, 0, 0.6)
    }

    .article_list {
        padding: 5px;
        border: 1px solid #ddd;
        border-top: 0;
        overflow: hidden;
    }

    .checkbox label {
        width: 100%;
        position: relative;
        padding: 0;
    }

    .checkbox .news_mask {
        position: absolute;
        left: 0;
        top: 0;
        background-color: #000;
        opacity: 0.5;
        width: 100%;
        height: 100%;
        z-index: 10;
    }

    .checkbox .news_mask img {
        width: 50px;
        position: absolute;
        left: 50%;
        top: 50%;
        margin-left: -25px;
        margin-top: -25px;
    }

    .goods_search_div .btn{
        height: 30px; line-height:30px;
    }

    .goods-list h4 {
        min-height: 30px;
        max-height: 30px;
        text-overflow:ellipsis;
        white-space: nowrap;
        overflow: hidden;
    }
    .switch_info {
        padding: 30px 20px 0px;
    }

    .goods-name em {
        height: 17px;
        width: 37px;
        float: left;
        font-size: 0;
        background: url('{{ asset('assets/admin/images/act_icon.png') }}') no-repeat;
    }

    .goods-name .distribution {
        background-position: -383px 0;
    }
</style>
<div class="panel panel-default" style="margin:0;">
    <div class="panel-heading">{{ $lang['select_goods_menu'] }}</div>

    <div class="content">
        <div class="main-info">

            <div class="switch_info" style="overflow:inherit;">

                <!--搜索-->
                <form method="post" action="{{ route('distribute.admin.select_goods') }}" enctype="multipart/form-data" role="form" id="group_buy_form" class="validation">
                <div class="goods_search_div bor_bt_das">
                    <div class="search_select">
                        <div class="categorySelect">
                            <div class="selection">
                                <input type="text" name="category_name" id="category_name" class="text w250 valid" value="{{ $lang['please_category'] }}" autocomplete="off" readonly="" data-filter="cat_name">
                                <input type="hidden" name="category_id" id="category_id" value="0" data-filter="cat_id">
                            </div>
                            <div class="select-container" style="display: none;">
                                <!--分类搜索-->
                                <div class="select-top">
                                    <a href="javascript:;" class="categoryTop" data-cid="0" data-cname="">{{ $lang['choose_again'] }}</a>

                                    @if(isset($filter_category_navigation) && $filter_category_navigation)

                                        @foreach($filter_category_navigation as $navigation)

                                            &gt;<a href="javascript:;" class="categoryTop" data-cid="{{ $navigation['cat_id'] }}" data-cname="{{ $navigation['cat_name'] }}">{{ $navigation['cat_name'] }}</a>

                                        @endforeach

                                    @else

                                        &gt; <span>{{ $lang['please_category'] }}</span>

                                    @endif

                                </div>
                                <div class="select-list">
                                    <ul>
                                        @if(isset($filter_category_list) && $filter_category_list)
                                            @foreach($filter_category_list as $category)

                                                <li data-cid="{{ $category['cat_id'] }}" data-cname="{{ $category['cat_name'] }}">
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
                                <input type="text" name="brand_name" id="brand_name" class="text w120 valid" value="{{ $lang['choose_brand'] }}" autocomplete="off" readonly="" data-filter="brand_name">
                                <input type="hidden" name="brand_id" id="brand_id" value="0" data-filter="brand_id">
                            </div>
                            <div class="brand-select-container" style="display: none;">
                                <div class="brand-top">
                                    <div class="letter">
                                        <ul>
                                            <li><a href="javascript:void(0);" data-letter="">{{ $lang['all_brand'] }}</a></li>
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
                                            <li><a href="javascript:void(0);" data-letter="QT">{{ $lang['other'] }}</a></li>
                                        </ul>
                                    </div>
                                    <div class="b_search">
                                        <input name="search_brand_keyword" id="search_brand_keyword" type="text" class="b_text" placeholder="{{ $lang['search_brand'] }}" autocomplete="off">
                                        <a href="javascript:void(0);" class="btn-mini"><i class="fa fa-search"></i></a>
                                    </div>
                                </div>
                                <div class="brand-list ps-container ps-active-y">

                                    <!--品牌搜索-->
                                    {{--@include('mobile.base.team_brand_list')--}}
                                    <ul>
                                        <li data-id="0" data-name="{{ $lang['choose_brand'] }}" class="blue">{{ $lang['choose_cancel'] }}</li>
                                        @if(isset($filter_brand_list) && $filter_brand_list)
                                            @foreach($filter_brand_list as $brand)

                                                <li data-id="{{ $brand['brand_id'] }}" data-name="{{ $brand['brand_name'] }}">
                                                    <em>{{ $brand['letter'] }}</em>{{ $brand['brand_name'] }}
                                                </li>

                                            @endforeach
                                        @endif
                                    </ul>
                                    <!--品牌搜索-->

                                    <div class="ps-scrollbar-x-rail" style="width: 234px; display: none; left: 0px; bottom: 3px;">
                                        <div class="ps-scrollbar-x" style="left: 0px; width: 0px;"></div>
                                    </div>
                                    <div class="ps-scrollbar-y-rail" style="top: 0px; height: 220px; display: inherit; right: 3px;">
                                        <div class="ps-scrollbar-y" style="top: 0px; height: 13px;"></div>
                                    </div>
                                </div>
                                <div class="brand-not" style="display:none;">{{ $lang['no_brand_records'] }}</div>
                            </div>
                        </div>
                    </div>


                    <div class="input">
                        @csrf
                        <input type="hidden" name="ru_id" value="0">
                        <input type="text" name="keyword" class="text w150" placeholder="{{ $lang['keyword'] }}" data-filter="keyword" autocomplete="off">

                        <input type="submit" value="{{ $lang['button_search'] }}" class="btn search_button">
                    </div>
                </div>
                </form>

            </div>
        </div>
    </div>

    <div class="panel-body">

        <div class="row">

            @if($goods)

                @foreach($goods as $k=>$v)

                <div class="col-sm-6 col-md-4 col-lg-2 ">

                    <div class="checkbox article goods-list">
                        <label>
                            <input type="checkbox" name="goods[]"  @if(isset($v['checked']) && $v['checked'] == 1) checked @endif value="{{ $v['goods_id'] }}" class="hidden artlist"/>
                            <div class="article goods-name">
                                <div class="cover">
                                    <img src="{{ $v['goods_thumb'] }}" title="{{ $v['goods_name'] }}"/>

                                    <h4 class="pt5">@if(isset($v['is_distribution']) && $v['is_distribution'] == 1) <em class="distribution">分销</em> @endif {{ $v['goods_name'] }}</h4>
                                </div>

                            </div>
                            <div class="news_mask @if(isset($v['checked']) && $v['checked'] == 1) show @else hidden @endif "><img src="{{ asset('img/radio.png') }}"/></div>
                        </label>
                    </div>

                </div>

                @endforeach

            @endif
        </div>

    </div>
    <div class="panel-footer">
        @include('admin.wechat.pageview')
        <div class="info_btn of">
            <input type="submit" value="{{ $lang['button_submit'] }}" class="button btn-danger bg-red confrim" name="file_submit"/>
            <input type="reset" value="{{ $lang['button_reset'] }}" class="button button_reset" name="reset"/>
        </div>
    </div>
</div>
<script type="text/javascript">
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
            articleDate.split(",").map(function (val, index) {
                //console.log(val)
                $("input[value=" + val + "]").attr("checked", 'checked');
                $("input[value=" + val + "]").siblings('.news_mask').removeClass("hidden");
                // 保存已有值
                article.push(val);
            });
        }
        // 点击选择与取消
        $(".artlist").click(function () {
            article = article.unique3(); // 去重

            var select_goods_id = $(this).val();

            if ($(this).is(":checked")) {
                $(this).siblings(".news_mask").removeClass("hidden");  // 显示遮罩 选中状态
                // 添加
                if (article.indexOf($(this).val()) == -1) {
                    article.push($(this).val());
                }

                @if(isset($type) && $type == 'buy_drp')
                // 请求
                select_buy_drp_goods(select_goods_id, 1);
                @endif

            } else {
                // 取消选择
                $(this).attr("checked", false);
                $(this).siblings(".news_mask").addClass("hidden");  // 移除遮罩  取消选中
                // 删除
                article.splice(article.indexOf($(this).val()), 1);

                @if(isset($type) && $type == 'buy_drp')
                // 请求
                select_buy_drp_goods(select_goods_id, 0);
                @endif

            }
            //article = article.unique3(); // 去重
            sessionStorage.setItem("article_ids", article);  // 存储sessionStorage
        });

        //选择提交
        $(".confrim").click(function () {
            var formArticleDate = '';
            formArticleDate = sessionStorage.getItem("article_ids");
            formArticleDate = formArticleDate ? formArticleDate.split(",") : ''; // 字符串转数组

            // 兼容
            var localArticle = [];
            $("input[type=checkbox]:checked").each(function () {
                localArticle.push($(this).val());
            });

            // 合并 本地+缓存已选择
            if (localArticle && formArticleDate) {
                formArticleDate = localArticle.concat(formArticleDate);
                formArticleDate = formArticleDate.unique3(); // 去重
            }

            formArticleDate = formArticleDate ? formArticleDate : localArticle;

            if (formArticleDate.length > 8) {
                sessionStorage.removeItem("article_ids"); // 清空 sessionStorage article_ids
                window.location.reload();
                return false;
            }

            sessionStorage.removeItem("article_ids"); // 清空 sessionStorage article_ids
            // 数组转字符串
            var str = formArticleDate.toString();

            //console.log('str', str)
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

    // 选择加入购买分销商商品
    function select_buy_drp_goods(select_goods_id, buy_drp_show) {

        $.post("{{ route('distribute.admin.edit_goods') }}", {goods_id: select_goods_id, buy_drp_show: buy_drp_show}, function (result) {

            console.log(result)
        }, 'json')
    }
</script>

@include('admin.wechat.pagefooter')