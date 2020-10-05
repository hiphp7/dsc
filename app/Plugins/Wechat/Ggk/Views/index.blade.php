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
    <link href="{{ asset('assets/wechat/ggk/css/activity-style.css') }}" rel="stylesheet" type="text/css">
    <script type="text/javascript" src="{{ asset('assets/mobile/vendor/common/jquery.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('assets/wechat/ggk/js/wScratchPad.js') }}"></script>

    <script src="{{ asset('assets/mobile/vendor/layer/mobile/layer.js') }}"></script>
    <link href="{{ asset('assets/mobile/vendor/layer/mobile/need/layer.css') }}" rel="stylesheet" type="text/css">

    @include('mobile.base.jssdk')
    <script type="text/javascript">
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
    </script>
</head>
<body data-role="page" class="activity-scratch-card-winning">
<div class="main">
    <div class="cover">
        <img src="{{ asset('assets/wechat/ggk/images/activity-scratch-card-bannerbg.png') }}">
        <div id="prize">{{ $lang['no_prize'] }}</div>
        <div id="scratchpad"></div>
    </div>
    <div class="content box-again hide">
        <div class="boxcontent boxwhite">
            <p class="text-center again"><a href="javascript:;" class="text-center red" onclick="act_draw(this)" id="draw"><input type="hidden" name="draw" value="0">{{ $lang['draw_again'] }}</a></p>
        </div>
    </div>
    <div class="content">
        <div class="boxcontent boxwhite">
            <div class="box">
                <div class="title-green">{{ $lang['prize_set'] }}</div>
                <div class="Detail">

                    @foreach($data['prize'] as $v)

                        <p>{{ $v['prize_level'] }}：{{ $v['prize_name'] }}，{{ $lang['total'] }}<span class="total">{{ $v['prize_count'] }}</span>{{ $lang['part'] }}。</p>

                    @endforeach

                </div>
            </div>
        </div>
        <div class="boxcontent boxwhite">
            <div class="box">
                <div class="title-brown">{{ $lang['my_prize_log'] }}<span class="more fr"><a href="{{ route('wechat/plugin_action', ['name'=> $plugin_name, 'act' => 'list']) }}" >{{ $lang['more'] }}</a> &raquo;</span></div>
                <div class="Detail">

                    @if(!empty($list_oneself))

                        @if(isset($list_oneself['nickname']) && $list_oneself['nickname'])
                            <p>{{ $list_oneself['nickname'] }} {{ $lang['get_prize'] }}：{{ $list_oneself['prize_name'] }}</p>
                        @endif

                        @if(empty($list_oneself['winner']))
                            <p class="edit_message text-center"><a href="{{ $list_oneself['winner_url'] }}"> =={{ $lang['go_to_fill_info'] }}== </a></p>
                        @endif

                    @else

                        <p>{{ $lang['no_prize_log'] }}</p>

                    @endif

                </div>
            </div>
        </div>
        <div class="boxcontent boxwhite">
            <div class="box">
                <div class="title-green">{{ $lang['activity_desc'] }}</div>
                <div class="Detail">
                    <p>{{ $lang['ggk_residue_numbers'] }}：<span id="num">{{ $data['prize_num'] }}</span></p>
                    <p>{{ $data['description'] ?? '' }}</p>
                </div>
            </div>
        </div>
        <div class="boxcontent boxwhite">
            <div class="box">
                <div class="title-brown">{{ $lang['prize_log'] }}</div>
                <div class="Detail">

                    @if(!empty($list))

                        @foreach($list as $val)

                            @if(isset($val['nickname']) && $val['nickname'])
                                <p>{{ $val['nickname'] }} {{ $lang['get_prize'] }}：{{ $val['prize_name'] }}</p>
                            @endif

                        @endforeach

                    @else

                        <p>{{ $lang['no_prize_log'] }}</p>

                    @endif

                </div>
            </div>
        </div>
    </div>
    <div style="clear:both;">
    </div>
</div>

<script type="text/javascript">
    $(function () {
        var ISWeixin = !!navigator.userAgent.match(/MicroMessenger/i); //wp手机无法判断
        if (!ISWeixin) {
            var rd_url = location.href.split('#')[0];  // remove hash
            var oauth_url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=APPID&redirect_uri=' + encodeURIComponent(rd_url) + '&response_type=code&scope=snsapi_base&state=STATE#wechat_redirect';
            location.href = oauth_url;
            return false;
        }

        // 抽奖开始
        act_draw();

    });

    var disbled = true;

    function act_draw(obj) {
        var that = $(obj)

        if (obj) {
            disbled = that.find("input[name='draw']").val() == 1 ? true : false
        }

        if (!disbled) {
            // 首次加载不可以点击再刮
            return false;
        } else {
            // 初始化
            var isLucky = false, level = "{{ $lang['no_prize'] }}";
            $("#scratchpad").html('');

            // 0 不可以点击再刮 1 可以点击
            $("#draw").find("input[name='draw']").val(0)

            $.post("{{ route('wechat/plugin_action', ['name'=>'ggk']) }}", {act: 'draw'}, function (result) {
                if (result.status == 2) {
                    $("#scratchpad").wScratchPad('enabled');
                    layer.open({content: result.msg, skin: 'msg', time: 2});
                    return false;
                } else if (result.status == 1) {
                    isLucky = true;
                    level = result.level;
                    $("#prize").html(level);
                }

                $("#scratchpad").css("background", "none");
                $("#scratchpad").wScratchPad({
                    width: 150,
                    height: 40,
                    color: "#a9a9a7",  //覆盖的刮刮层的颜色
                    scratchDown: function (e, percent) {
                        e.preventDefault();
                        $(this.canvas).css('margin-right', $(this.canvas).css('margin-right') == "0px" ? "1px" : "0px");
                    },
                    scratchMove: function (e, percent) {
                        e.preventDefault();
                        $(this.canvas).css('margin-right', $(this.canvas).css('margin-right') == "0px" ? "1px" : "0px");
                    },
                    scratchUp: function (e, percent) {
                        e.preventDefault();
                        $(this.canvas).css('margin-right', $(this.canvas).css('margin-right') == "0px" ? "1px" : "0px");
                        if (percent >= 1.5) {
                            $("#scratchpad").wScratchPad('clear');

                            // 可以再刮一次
                            $("#draw").find("input[name='draw']").val(1)

                            $('.box-again').removeClass('hide');

                            // 记录中奖
                            act_do(result);
                        }
                    }
                });
            }, 'json');
        }
    }

    function act_do(result) {

        $.post("{{ route('wechat/plugin_action', ['name' => 'ggk']) }}", {
            act: 'do',
            prize_type: result.prize_type,
            prize_name: result.msg,
            prize_level: result.level
        }, function (data) {
            if (result.num >= 0) {
                $("#num").html(result.num);
            }
            // 禁用
            $("#scratchpad").wScratchPad('enabled');
            //$("#scratchpad").css("background", "#a9a9a7");
            if (data.status == 1) {
                var msg = "{{ $lang['congratulation'] }}" + result.level + "\r\n" + "{{ $lang['go_accept_prize'] }}";
                if (data.link) {
                    layer.open({
                        content: msg,
                        btn: ['{{ $lang['ok'] }}', '{{ $lang['cancel'] }}'],
                        yes: function (index) {
                            layer.close(index);
                            location.href = data.link;
                        }
                    });
                    return false;
                }

            } else if (data.status == 0) {
                layer.open({
                    content: result.msg + "\r\n" + "{{ $lang['try_again'] }}",
                    btn: ['{{ $lang['ok'] }}', '{{ $lang['cancel'] }}'],
                    yes: function (index) {
                        layer.close(index);
                        reload();
                    }
                });
                return false;
            }

        }, 'json');
    }


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
