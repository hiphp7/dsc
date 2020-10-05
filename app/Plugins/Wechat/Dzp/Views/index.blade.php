<!DOCTYPE HTML>
<html>
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes"/>
    <meta name="apple-mobile-web-app-status-bar-style" content="black"/>
    <meta name="format-detection" content="telephone=no"/>
    <title>{{ $data['media']['title'] ?? '' }}</title>
    <link href="{{ asset('assets/wechat/dzp/css/activity-style.css') }}" rel="stylesheet" type="text/css">
    <script type="text/javascript" src="{{ asset('assets/mobile/vendor/common/jquery.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('assets/wechat/dzp/js/jQueryRotate.2.2.js') }}"></script>
    <script type="text/javascript" src="{{ asset('assets/wechat/dzp/js/jquery.easing.min.js') }}"></script>

    <script src="{{ asset('assets/mobile/vendor/layer/mobile/layer.js') }}"></script>

    @include('mobile.base.jssdk')
    <script type="text/javascript">
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
    </script>
</head>
<body>
<div class="content-wrap">
    <div id="roundabout">
        <div class="r-panel">
            <div class="dots"></div>
            <div data-count="{{ $prize_num }}" class="lucky">

                @foreach($data['prize'] as $v)

                    <span data-level="{{ $v['prize_level'] }}">{{ $v['prize_level'] }}</span>

                @endforeach

            </div>
            <div class="point-panel"></div>
            <div class="point-arrow"></div>
            <div class="point-cdot"></div>
            <div class="point-btn"></div>
        </div>
    </div>
    <div class="info-box">
        <div class="info-box-inner">
            <h4>{{ $lang['prize_set'] }}</h4>
            <div>
                <ul style="padding-left:16px;margin-bottom:0;">

                    @foreach($data['prize'] as $v)

                        <li data-level="{{ $v['prize_level'] }}">
                            {{ $v['prize_level'] }}：{{ $v['prize_name'] }}，{{ $lang['total'] }}<span class="total">{{ $v['prize_count'] }}</span>{{ $lang['part'] }}。
                        </li>

                    @endforeach

                </ul>
            </div>
        </div>
    </div>
    <div class="info-box">
        <div class="info-box-inner">
            <h4 class="fl">{{ $lang['my_prize_log'] }}</h4><span class="more fr"><a href="{{ route('wechat/plugin_action', ['name'=> $plugin_name, 'act' => 'list']) }}" >{{ $lang['more'] }}</a> &raquo;</span>
            <div>

                @if(!empty($list_oneself))

                    @if(isset($list_oneself['nickname']) && $list_oneself['nickname'])
                        <p>{{ $list_oneself['nickname'] }} {{ $lang['get_prize'] }} ：{{ $list_oneself['prize_name'] }}</p>
                    @endif

                    @if(empty($list_oneself['winner']))
                        <p class="edit_message"><a href="{{ $list_oneself['winner_url'] }}"> =={{ $lang['go_to_fill_info'] }}== </a></p>
                    @endif

                @else

                    <p>{{ $lang['no_prize_log'] }}</p>

                @endif

            </div>
        </div>
    </div>
    <div class="info-box">
        <div class="info-box-inner">
            <h4>{{ $lang['activity_desc'] }}</h4>
            <div>{{ $data['description'] ?? '' }}</div>
        </div>
    </div>
    <div class="info-box">
        <div class="info-box-inner">
            <h4>{{ $lang['prize_log'] }}</h4>
            <div>

                @if(!empty($list))

                    @foreach($list as $val)

                        @if(isset($val['nickname']) && $val['nickname'])
                            <p>{{ $val['nickname'] }} {{ $lang['get_prize'] }} ：{{ $val['prize_name'] }}</p>
                        @endif

                    @endforeach

                @else

                    <p>{{ $lang['no_prize_log'] }}</p>

                @endif

            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(function () {
        var ISWeixin = !!navigator.userAgent.match(/MicroMessenger/i); //wp手机无法判断
        if(!ISWeixin){
            var rd_url = location.href.split('#')[0];  // remove hash
            var oauth_url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=APPID&redirect_uri='+encodeURIComponent(rd_url) + '&response_type=code&scope=snsapi_base&state=STATE#wechat_redirect';
            location.href = oauth_url;
            return false;
        }
        var dot_round = 0;
        var lucky_span = $('.lucky span');
        var lucky_p = LUCKY_POS[lucky_span.length];
        lucky_span.each(function (idx, item) {
            item = $(item);
            item.addClass('item' + lucky_p[idx] + ' z' + item.text().length);
            item.rotate(LUCKY_ROTATE[lucky_p[idx]]);
        });
        var NOL_TXTs = ['{{ $lang['no_prize_1'] }}', '{{ $lang['no_prize_2'] }}', '{{ $lang['no_prize_3'] }}', '{{ $lang['no_prize_4'] }}', '{{ $lang['no_prize_5'] }}', '{{ $lang['no_prize_6'] }}', '{{ $lang['no_prize_7'] }}'];
        for (var i = 1; i <= 12; i++) {
            if ($('.lucky .item' + i).length == 0) {
                var item = $('<span class="item' + i + ' nol z4">' + NOL_TXTs[i > 6 ? 12 - i : i] + '</span>').appendTo('.lucky');
                item.rotate(LUCKY_ROTATE[i]);
            }
        }
        $('.lucky span').show();

        $('.point-btn').click(function () {
            var lucky_l = POINT_LEVEL[$('.lucky').data('count')];
            $.post("{{ route('wechat/plugin_action', ['name'=>'dzp']) }}", {act:'play'}, function (data) {
                //中奖
                if (data.status == 1) {
                    var b = $(".lucky span[data-level='" + data.level + "']").index();
                    var a = lucky_l[b];
                    var msg = '{{ $lang['congratulation'] }}' + $(".lucky span[data-level='" + data.level + "']").text();
                    $(".point-btn").hide();
                    $(".point-arrow").rotate({
                        duration: 3000, //转动时间
                        angle: 0,
                        animateTo: 360 * 2 + a, //转动角度
                        easing: $.easing.easeOutSine,
                        callback: function () {
                            $(".point-btn").show();
                            if (data.link) {
                                layer.open({
                                    content: msg + "\r\n" + "{{ $lang['go_accept_prize'] }}",
                                    btn: ['{{ $lang['ok'] }}', '{{ $lang['cancel'] }}'],
                                    yes: function(index){
                                        location.href = data.link;
                                        layer.close(index);
                                    }
                                });
                            }
                            return false;
                        }
                    });
                }
                else if (data.status == 2) {
                    //未登录
                    layer.open({content: data.msg, skin: 'msg', time: 2});
                    return false;
                }
                else {
                    var a = 0;
                    var arrow_angle = 1;
                    while (true) {
                        arrow_angle = ~~(Math.random() * 12);
                        if ($.inArray(arrow_angle * 30, lucky_l) == -1) break;
                    }
                    a = arrow_angle * 30;
                    var msg = $(".lucky span.item" + arrow_angle).text() ? $(".lucky span.item" + arrow_angle).text() : '{{ $lang['no_prize'] }}';
                    $(".point-btn").hide();
                    $(".point-arrow").rotate({
                        duration: 3000, //转动时间
                        angle: 0,
                        animateTo: 360 * 2 + a, //转动角度
                        easing: $.easing.easeOutSine,
                        callback: function () {
                            layer.open({content: msg, skin: 'msg', time: 2});
                            $(".point-btn").show();
                        }
                    });
                }

            }, 'json');

        });
        //跑马灯
        dot_timer = setInterval(function () {
            dot_round = dot_round == 0 ? 15 : 0;
            $('.dots').rotate(dot_round);
        }, 800);

    })

    var POINT_LEVEL = {
        3: [30, 150, 270],
        4: [30, 90, 210, 270],
        5: [30, 90, 150, 210, 270],
        6: [30, 90, 150, 210, 270, 330],
        7: [30, 90, 150, 210, 270, 330, 0],
        8: [30, 90, 150, 210, 270, 330, 0, 180],
        9: [30, 90, 150, 210, 270, 330, 0, 180, 120]
    };
    var LUCKY_POS = {
        3: [1, 5, 9],
        4: [1, 3, 7, 9],
        5: [1, 3, 5, 7, 9],
        6: [1, 3, 5, 7, 9, 11],
        7: [1, 3, 5, 7, 9, 11, 12],
        8: [1, 3, 5, 7, 9, 11, 12, 6],
        9: [1, 3, 5, 7, 9, 11, 12, 6, 4]
    };
    var LUCKY_ROTATE = {1: -15, 2: 14, 3: 45, 4: 75, 5: 103, 6: 134, 7: 167, 8: 197, 9: 224, 10: 255, 11: 283, 12: 316};
</script>
</body>
</html>
