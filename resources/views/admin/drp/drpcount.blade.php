@include('admin.base.header')

<style>
    .contentWarp .contentWarp_item {
        width: 100% !important;
    }

    .contentWarp .section_drp_count .sc_icon {
        background-position: -2px -286px;
    }
</style>
<div class="warpper">
    <div class="title">{{ $lang['drp_count'] }}</div>
    <div class="content_tips start_content">
        <div class="explanation" id="explanation">
            <div class="ex_tit">
                <i class="sc_icon"></i><h4>{{ lang('admin/common.operating_hints') }}</h4><span id="explanationZoom" title="{{ lang('admin/common.fold_tips') }}"></span>
            </div>
            <ul>
                @if(isset($lang['drp_count_tips']) && !empty($lang['drp_count_tips']))

                    @foreach($lang['drp_count_tips'] as $v)
                        <li>{{ $v }}</li>
                    @endforeach

                @endif
            </ul>
        </div>

        <div class="flexilist">

            <div class="contentWarp">
            <div class="contentWarp_item clearfix">

                @if($drp_shop_trend)

                    <div class="section section_drp_count">
                        <div class="sc_title">
                            <i class="sc_icon"></i>
                            {{--分销商统计--}}
                            <h3>{{ $lang['drp_shop_trend'] }} ({{ $lang['shop_count'] }}：{{ $drp_shop_count }})</h3>
                            <div class="filter_date">
                                <a href="javascript:;"
                                   onclick="set_statistical_chart(this, 'shop', 'week')">{{ $lang['seven_day'] }}</a>
                                <a href="javascript:;"
                                   onclick="set_statistical_chart(this, 'shop', 'month')">{{ $lang['one_month'] }}</a>
                                <a href="javascript:;"
                                   onclick="set_statistical_chart(this, 'shop', 'year')">{{ $lang['half_year'] }}</a>
                            </div>
                        </div>
                        <div class="sc_warp">
                            <div id="shop_main" style="height:274px;"></div>
                        </div>
                    </div>

                @endif

                @if($drp_order_trend)

                    <div class="section section_order_count">
                        <div class="sc_title">
                            <i class="sc_icon"></i>
                            {{--分销订单统计--}}
                            <h3>{{ $lang['drp_order_trend'] }} ({{ $lang['order_count'] }}：{{ $drp_order_count }})</h3>
                            <div class="filter_date">
                                <a href="javascript:;"
                                   onclick="set_statistical_chart(this, 'order', 'week')">{{ $lang['seven_day'] }}</a>
                                <a href="javascript:;"
                                   onclick="set_statistical_chart(this, 'order', 'month')">{{ $lang['one_month'] }}</a>
                                <a href="javascript:;"
                                   onclick="set_statistical_chart(this, 'order', 'year')">{{ $lang['half_year'] }}</a>
                            </div>
                        </div>
                        <div class="sc_warp">
                            <div id="order_main" style="height:274px;"></div>
                        </div>
                    </div>

                @endif

                @if($drp_sales_trend)

                    <div class="section section_total_count">
                        <div class="sc_title">
                            <i class="sc_icon"></i>
                            {{--分销佣金统计--}}
                            <h3>{{ $lang['drp_sales_trend'] }} ({{ $lang['sale_count'] }}：{{ $drp_sales_count }})</h3>
                            <div class="filter_date">
                                <a href="javascript:;"
                                   onclick="set_statistical_chart(this, 'sale', 'week')">{{ $lang['seven_day'] }}</a>
                                <a href="javascript:;"
                                   onclick="set_statistical_chart(this, 'sale', 'month')">{{ $lang['one_month'] }}</a>
                                <a href="javascript:;"
                                   onclick="set_statistical_chart(this, 'sale', 'year')">{{ $lang['half_year'] }}</a>
                            </div>
                        </div>
                        <div class="sc_warp">
                            <div id="total_main" style="height:274px;"></div>
                        </div>
                    </div>

                @endif

            </div>
        </div>

        </div>

    </div>
</div>

<script src="{{ asset('assets/admin/js/echarts-all.js') }}"></script>

<script type="text/javascript">
    set_statistical_chart(".section_drp_count .filter_date a:first", "shop", "week"); //初始设置
    set_statistical_chart(".section_order_count .filter_date a:first", "order", "week"); //初始设置
    set_statistical_chart(".section_total_count .filter_date a:first", "sale", "week"); //初始设置
    function set_statistical_chart(obj, type, date) {
        var obj = $(obj);
        obj.addClass("active");
        obj.siblings().removeClass("active");

        $.ajax({
            type: 'post',
            url: "{{ route('admin/drp/drp_count') }}",
            data: 'type=' + type + '&date=' + date,
            dataType: 'json',
            success: function (data) {
                if (type == 'shop') {
                    var div_id = "shop_main";
                }
                if (type == 'order') {
                    var div_id = "order_main";
                }
                if (type == 'sale') {
                    var div_id = "total_main";
                }
                var myChart = echarts.init(document.getElementById(div_id));
                myChart.setOption(data);
            }
        })
    }

</script>
<script type="text/javascript">
    $(function () {
        // 操作提示
        $("#explanationZoom").on("click", function () {
            var explanation = $(this).parents(".explanation");
            var width = $(".content_tips").width();
            if ($(this).hasClass("shopUp")) {
                $(this).removeClass("shopUp");
                $(this).attr("title", "{{ lang('admin/common.fold_tips') }}");
                explanation.find(".ex_tit").css("margin-bottom", 10);
                explanation.animate({
                    width: width - 0
                }, 300, function () {
                    $(".explanation").find("ul").show();
                });
            } else {
                $(this).addClass("shopUp");
                $(this).attr("title", "提示相关设置操作时应注意的要点");
                explanation.find(".ex_tit").css("margin-bottom", 0);
                explanation.animate({
                    width: "118"
                }, 300);
                explanation.find("ul").hide();
            }
        });

    });
</script>

@include('admin.base.footer')
