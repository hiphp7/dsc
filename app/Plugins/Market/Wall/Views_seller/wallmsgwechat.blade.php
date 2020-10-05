<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>聊天室</title>
    <link href="{{ asset('assets/wechat/wall/css/wechat_wall_user.css') }}" rel="stylesheet" type="text/css"/>
    <link href="{{ asset('assets/wechat/wall/css/fonts/iconfont.css') }}" rel="stylesheet" type="text/css"/>
    <script type="text/javascript">var ROOT_URL = "{{ url('/') }}";</script>
    <script src="{{ asset('assets/mobile/vendor/common/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/mobile/vendor/layer/layer.js') }}"></script>
    <script src="{{ asset('assets/wechat/wall/js/jquery.nicescroll.js') }}"></script>
    <script src="{{ asset('assets/wechat/wall/js/jquery.scrollTo.min.js') }}"></script>
    <script src="{{ asset('assets/wechat/wall/js/wechat_wall.js') }}"></script>
    <script type="text/javascript">
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
    </script>
</head>
<body>
<div class="user-con">
    <!--header-->
    <header>
        <a href="javascript:history.go(-1);" class="fl">
            <i class="iconfont">&#xe600;</i>
        </a>
        <h1>当前聊天（<span id="user-num">{{ $user_num }}</span>人）</h1>
    </header>
    <!--main-->
    <div class="main chat-main">
        <div class="user-chat">

            @foreach($list as $val)


                @if($val['id'] == $user['id'])

                    <div class="chat-me">
                        <div class="fr chat-img">
                            <img src="{{ $val['headimg'] }}"/>
                        </div>
                        <div class="fr chat-content">
                            <h2><span>{{ $val['nickname'] }}</span></h2>
                            <div class="chat-others-content">
                                <div class="arrow"></div>
                                {{ $val['content'] }}
                            </div>
                        </div>
                    </div>

                @else

                    <div class="chat-others">
                        <div class="fl chat-img">
                            <img src="{{ $val['headimg'] }}"/>
                        </div>
                        <div class="fl chat-content">
                            <h2><span>{{ $val['nickname'] }}</span></h2>
                            <div class="chat-others-content">
                                <div class="arrow"></div>
                                {{ $val['content'] }}
                            </div>
                        </div>
                    </div>

                @endif


            @endforeach

        </div>
    </div>
    <div class="user-chat-comment">
        <form action="{{ route('wechat/market_show', array('type' => 'wall', 'function' => 'wall_msg_wechat', 'wechat_ru_id' => $wechat_ru_id)) }}"
            method="post" class="msg-form">
            @csrf
            <input type="hidden" name="wall_id" value="{{ $wall_id }}">
            <input type="hidden" name="user_id" value="{{ $user['id'] }}">
            <textarea name="content" rows="2" placeholder="请输入信息" maxlength="100"></textarea>
            <a href="javascript:;" class="fr send">发送</a>
        </form>
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

        var interval_time = 5000; // 间隔时间
        window.setInterval("refresh()", interval_time);

        $(".user-chat").animate({
            scrollTop: $(".user-list").height()
        }, 800);

        $(".send").click(function () {
            var data = $(".msg-form").serialize();
            $.post("{!!  route('wechat/market_show', array('type' => 'wall', 'function' => 'wall_msg_wechat', 'wechat_ru_id' => $wechat_ru_id))  !!}", data, function (result) {
                layer.msg(result.errMsg);
                $("textarea[name=content]").val("");
                return false;
            }, 'json');
            refresh();
        });
    })
    var start = '{{ $msg_count ?? '' }}';
    var num = 5;
    var user = '{{ $user['id'] ?? '' }}';
    var wall_id = '{{ $wall_id ?? '' }}';

    var req = 0;
    function refresh() {
        $.post("{!!  route('wechat/market_show', array('type' => 'wall', 'function' => 'get_wall_msg', 'wechat_ru_id' => $wechat_ru_id))  !!}", {
            start: start,
            num: num,
            wall_id: wall_id,
            req: req
        }, function (result) {
            if (result.code == 0 && result.data.length > 0) {
                var html = '', j = 0;
                for (var i = result.data.length; i > 0; i--) {
                    if (result['data'][j]['id'] == user) {
                        html += '<div class="chat-me"><div class="fr chat-img"><img src="' + result['data'][j]['headimg'] + '"/></div><div class="fr chat-content"><h2><span>' + result['data'][j]['nickname'] + '</span></h2><div class="chat-others-content"><div class="arrow"></div>' + result['data'][j]['content'] + '</div></div></div>';
                    } else {
                        html += '<div class="chat-others"><div class="fl chat-img"><img src="' + result['data'][j]['headimg'] + '"/></div><div class="fl chat-content"><h2><span>' + result['data'][j]['nickname'] + '</span></h2><div class="chat-others-content"><div class="arrow"></div>' + result['data'][j]['content'] + '</div></div></div>';
                    }
                    j++;
                }
                if (html) {
                    $(".user-chat").append(html);
                }

                start = parseInt(start) + parseInt(result.data.length);
            }
            if (result.user_num) {
                $('#user-num').html(result.user_num);
            }
            req++;
        }, 'json');
    }
</script>
</body>
</html>
