@include('admin.team.admin_pageheader')

<div class="wrapper">
    <div class="title">拼团 - 订单列表</div>
    <div class="content_tips">
        <div class="tabs_info">
            <ul>
                <li><a href="{{ route('admin/team/index') }}">{{ $lang['team_goods'] }}</a></li>
                <li><a href="{{ route('admin/team/category') }}">{{ $lang['team_category'] }}</a></li>
                <li class="curr"><a href="{{ route('admin/team/teaminfo') }}">{{ $lang['team_info'] }}</a></li>
                <li style="display:none"><a href="{{ route('admin/team/teamrecycle') }}">{{ $lang['team_recycle'] }}</a></li>
            </ul>
        </div>
        <div class="explanation" id="explanation">
            <div class="ex_tit"><i class="sc_icon"></i><h4>{{ $lang['operating_hints'] }}</h4><span id="explanationZoom" title="{{ $lang['fold_tips'] }}"></span>
            </div>
            <ul>
                <li>显示平台内所有参与拼团的订单信息，管理员可以看到由拼团产生的订单，并对订单进行处理操作。</li>

            </ul>
        </div>
        <div class="flexilist">
        <!-- <div class="common-head">
            <form action="{{ route('admin/team/teaminfo') }}" method="post">
            @csrf
              <div class="search">
                <div class="input">
                    <input type="text" name="keyword" class="text nofocus" placeholder="关键词" autocomplete="off">
                    <input type="submit" value="" class="btn" name="export">
                </div>
              </div>
            </form>
        </div> -->
            <div class="common-content">
                <div class="list-div" id="listDiv">
                    <table cellspacing="0" cellpadding="0" border="0">
                        <tr class="active">
                            <th>
                                <div class="tDiv">订单号</div>
                            </th>
                            <th width="20%">
                                <div class="tDiv">商品名称</div>
                            </th>
                            <th>
                                <div class="tDiv">商家名称</div>
                            </th>
                            <th>
                                <div class="tDiv">下单时间</div>
                            </th>
                            <th>
                                <div class="tDiv">收货人</div>
                            </th>
                            <th>
                                <div class="tDiv">信息标签</div>
                            </th>
                            <th>
                                <div class="tDiv">金额标签</div>
                            </th>
                            <th>
                                <div class="tDiv">订单状态</div>
                            </th>
                            <th width="6%">
                                <div class="tDiv">{{ $lang['handler'] }}</div>
                            </th>
                        </tr>

                        @if($list)

                            @foreach($list as $list)

                                <tr>
                                    <td>
                                        <div class="tDiv">{{ $list['order_sn'] }}</div>
                                    </td>
                                    <td>
                                        <div class="tDiv clamp2">{{ $list['goods_name'] }}</div>
                                    </td>
                                    <td>
                                        <div class="tDiv">
                                            <span class="red">{{ $list['user_name'] ?? '' }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="tDiv">{{ $list['add_time'] }}</div>
                                    </td>
                                    <td>
                                        <div class="tDiv">
                                            {{ $list['consignee'] ?? '' }}
                                            @if($list['mobile'])
                                                [TEL: {{ $list['mobile'] }}]
                                            @endif
                                            <br>
                                            [{{ $list['region'] ?? '' }}]{{ $list['address'] ?? '' }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="tDiv">
                                            {{ $lang['label_payment'] }}{{ $list['pay_name'] ?? '' }}<br>
                                            {{ $lang['referer'] }}
                                            @if($list['referer'] == 'mobile')
                                                APP
                                            @elseif($list['referer'] == 'touch')
                                                {{ $lang['touch'] }}
                                            @elseif($list['referer'] == 'H5')
                                                H5
                                            @elseif($list['referer'] == 'wxapp')
                                                {{ $lang['wxapp'] }}
                                            @elseif($list['referer'] == 'ecjia-cashdesk')
                                                {{ $lang['cashdesk'] }}
                                            @else
                                                PC
                                            @endif

                                        </div>
                                    </td>
                                    <td>
                                        <div class="tDiv">
                                            总金额：{{ $list['formated_total_fee'] }}<br>
                                            应付金额：{{ $list['formated_order_amount'] }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="tDiv">{{ $list['status'] ?? '' }}</div>
                                    </td>
                                    <td>
                                        <div class="tDiv">
                                            <a href="../order.php?act=info&order_id={{ $list['order_id'] }}">查看</a>
                                        </div>
                                    </td>
                                </tr>

                            @endforeach

                        @else

                            <tbody>
                            <tr>
                                <td class="no-records" colspan="9">{{ $lang['no_records'] }}</td>
                            </tr>
                            </tbody>

                        @endif

                        <tfoot>
                        <tr>
                            <td colspan="9">
                                <div class="list-page">
                                    @include('admin.team.admin_pageview')
                                </div>
                            </td>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $("#explanationZoom").on("click", function () {
        var explanation = $(this).parents(".explanation");
        var width = $(".content_tips").width();
        if ($(this).hasClass("shopUp")) {
            $(this).removeClass("shopUp");
            $(this).attr("title", "{{ $lang['fold_tips'] }}");
            explanation.find(".ex_tit").css("margin-bottom", 10);
            explanation.animate({
                width: width
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
</script>
@include('admin.base.footer')
