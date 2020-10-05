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
    <link href="{{ asset('assets/wechat/zjd/css/style.css') }}" rel="stylesheet">
    <script type="text/javascript" src="{{ asset('assets/mobile/vendor/common/jquery.min.js') }}"></script>

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
<div class="grid">
    <div id="hammer"><img src="{{ asset('assets/wechat/zjd/images/img-6.png') }}" height="87" width="74" alt=""></div>
    <div id="f"><img src="{{ asset('assets/wechat/zjd/images/img-4.png') }}"/></div>
    <div id="banner">
        <dl>
            <dt>
                <a href="javascript:;"><img src="{{ asset('assets/wechat/zjd/images/egg_1.png') }}"></a>
                <a href="javascript:;"><img src="{{ asset('assets/wechat/zjd/images/egg_1.png') }}"></a>
                <a href="javascript:;"><img src="{{ asset('assets/wechat/zjd/images/egg_1.png') }}"></a>
                <a href="javascript:;"><img src="{{ asset('assets/wechat/zjd/images/egg_1.png') }}"></a>
                <a href="javascript:;"><img src="{{ asset('assets/wechat/zjd/images/egg_1.png') }}"></a>
                <a href="javascript:;"><img src="{{ asset('assets/wechat/zjd/images/egg_1.png') }}"></a>
                <a href="javascript:;"><img src="{{ asset('assets/wechat/zjd/images/egg_1.png') }}"></a>
            </dt>
            <dd></dd>
        </dl>
    </div>

    @if($data['activity_status'] == 0)

        <div class="block">
            <div class="title">{{ $lang['zjd_residue_numbers'] }}</div>
            <p>{{ $lang['zjd_your_numbers'] }}：<span class="num">{{ $data['prize_num'] ?? 0 }}</span></p>

            @if($data['point_status'] == 1)
                <p>{{ $lang['zjd_deduct_pay_points'] }} ：{{ $data['point_value'] ?? 0 }}， {{ $lang['zjd_your_pay_points'] }} ：{{ $data['user_pay_points'] ?? 0 }}</p>
            @endif

        </div>

    @elseif($data['activity_status'] == 1)

        <div class="act_status">
            <p class="text-center">{{ $lang['zjd_no_start'] }}</p>
        </div>

    @elseif($data['activity_status'] == 2)

        <div class="act_status">
            <p class="text-center">{{ $lang['zjd_is_finish'] }}</p>
        </div>

    @endif

    <div class="block">
        <div class="title">{{ $lang['prize_set'] }}</div>

@if(isset($data['prize']) && $data['prize'])

        @foreach($data['prize'] as $v)

            <p>{{ $v['prize_level'] }}:{{ $v['prize_name'] }}({{ $lang['zjd_prize_number'] }}：{{ $v['prize_count'] ?? 0 }})</p>

        @endforeach

@endif

    </div>
    <div class="block">
        <div class="title"><h4 class="fl">{{ $lang['my_prize_log'] }}</h4><span class="more fr"><a href="{{ route('wechat/plugin_action', ['name'=> $plugin_name, 'act' => 'list']) }}" >{{ $lang['more'] }}</a> &raquo;</span></div>

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
    <div class="block">
        <div class="title">{{ $lang['activity_desc'] }}</div>
        <p>{{ $data['description'] ?? '' }}</p>
    </div>

    <div class="block">
        <div class="title">{{ $lang['prize_log'] }}</div>

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
<div id="mask"></div>
<div id="dialog" class="yes">
    <div id="content"></div>
    <a href="javascript:;" id="link">{{ $lang['go_accept_prize'] }}</a>
    <button id="close">close</button>
</div>

<script>
    $(function () {
        var ISWeixin = !!navigator.userAgent.match(/MicroMessenger/i); //wp手机无法判断
        if (!ISWeixin) {
            var rd_url = location.href.split('#')[0];  // remove hash
            var oauth_url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=APPID&redirect_uri=' + encodeURIComponent(rd_url) + '&response_type=code&scope=snsapi_base&state=STATE#wechat_redirect';
            location.href = oauth_url;
            return false;
        }
        var timer, forceStop;
        var wxch_Marquee = function (id) {
            try {
                document.execCommand("BackgroundImageCache", false, true);
            } catch (e) {

            };
            var container = document.getElementById(id),
                original = container.getElementsByTagName("dt")[0],
                clone = container.getElementsByTagName("dd")[0],
                speed = arguments[1] || 10;
            clone.innerHTML = original.innerHTML;
            var rolling = function () {
                if (container.scrollLeft == clone.offsetLeft) {
                    container.scrollLeft = 0;
                } else {
                    container.scrollLeft++;
                }
            }
            this.stop = function () {
                clearInterval(timer);
            }
            //设置定时器
            timer = setInterval(rolling, speed);
            //鼠标移到marquee上时，清除定时器，停止滚动
            container.onmouseover = function () {
                clearInterval(timer);
            }
            //鼠标移开时重设定时器
            container.onmouseout = function () {
                if (forceStop) return;
                timer = setInterval(rolling, speed);
            }
        };

        var wxch_stop = function () {
            clearInterval(timer);
            forceStop = true;
        };
        var wxch_start = function () {
            forceStop = false;
            wxch_Marquee("banner", 20);
        };

        wxch_Marquee("banner", 20);

        var $egg;

        $("#banner a").on('click', function () {
            wxch_stop();
            $egg = $(this);
            var offset = $(this).position();
            $hammer = $("#hammer");
            var leftValue = offset.left + 30;
            $hammer['animate']({left: leftValue}, 1000, function () {
                $(this).addClass('hit');
                $("#f").css('left', offset.left).show();
                $egg['find']('img').attr('src', '{{ asset("assets/wechat/zjd/images/egg_2.png") }}');
                setTimeout(function () {
                    wxch_result.call(window);
                }, 500);
            });
        });

        $("#mask").on('click', function () {
            $(this).hide();
            $("#dialog").hide();
            $egg['find']('img').attr('src', '{{ asset("assets/wechat/zjd/images/egg_1.png") }}');
            $("#f").hide();
            $("#hammer").css('left', '-74px').removeClass('hit');
            wxch_start();
        });

        $("#close").click(function () {
            $("#mask").trigger('click');
            reload();
        });

        function wxch_result() {
            var url = "{{ route('wechat/plugin_action', ['name'=>'zjd']) }}";
            $.post(url, {act:'play'}, function (data) {
                $("#mask").show();
                if (data.status == 1) {
                    $("#content").html(data.msg);
                    $(".num").html(data.num);
                    $("#link").attr("href", data.link);
                    $("#dialog").attr("class", 'yes').show();
                }
                else if (data.status == 0) {
                    $("#content").html(data.msg);
                    $(".num").html(data.num);
                    $("#dialog").attr("class", 'no').show();
                }
                else if (data.status == 2) {
                    $("#content").html(data.msg);
                    $(".num").html(data.num);
                    $("#dialog").attr("class", 'no').show();
                }
            }, 'json');
        }
    });

    // 兼容微信安卓下 不刷新的问题
    function reload() {
        var url = location.href;
        var name = 'random';
        // 过滤重复参数
        var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)");
        var r = window.location.search.substr(1).match(reg);
        if (r != null) {
            url = url.replace(r[0], '');
        }
        location.href = url + "&random=" + Math.floor(Math.random() * 100000000);
    }

</script>
</body>
</html>
