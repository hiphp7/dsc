<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>微信墙 - 聊天室</title>
    <link href="{{ asset('assets/wechat/wall/css/wechat_wall_common.css') }}" rel="stylesheet" type="text/css"/>
    <link href="{{ asset('assets/wechat/wall/css/wechat_wall_user.css') }}" rel="stylesheet" type="text/css"/>
    <link href="{{ asset('assets/wechat/wall/css/fonts/iconfont.css') }}" rel="stylesheet" type="text/css"/>
    <script type="text/javascript">var ROOT_URL = "{{ url('/') }}";</script>
    <script src="{{ asset('assets/mobile/vendor/common/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/mobile/vendor/layer/layer.js') }}"></script>
    <script src="{{ asset('assets/wechat/wall/js/jquery.nicescroll.js') }}"></script>
    <script src="{{ asset('assets/wechat/wall/js/jquery.scrollTo.min.js') }}"></script>
    <script src="{{ asset('assets/wechat/wall/js/wechat_wall.js') }}"></script>

    <script type="text/javascript">
        // 处理背景图高度
        window.onload = function () {
            var con = document.getElementById('con');
            var conHeight = con.offsetHeight;

            var c = document.documentElement.clientHeight;
            con.style.height = c + 'px';
            var logo = $(".logo").outerHeight(true);
            var footer = $(".footer").outerHeight(true);
            var contHeight = c - logo - footer - 50 + "px";
            $(".msg-list").css("height", contHeight)
        }

    </script>
    <script type="text/javascript">
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
    </script>
</head>
<body>
<div class="con wall-con" id="con"
     @if($wall['background'])
     style="background-image:url({{ $wall['background'] }})"
    @endif
>
    <div class="main">
        <!--logo-->
        <div class="logo">
            <img src="{{ $wall['logo'] ?? '' }}" class="fl"/>
            <h1 class="fl">{{ $wall['name'] ?? '' }}</h1>
        </div>

        <!--main-->
        <div class="content">
            <div class="msg-list" id="ul">
                <ul>

                    @foreach($list as $val)

                        <li>
                            <img src="{{ $val['headimg'] }}" class="fl"/>
                            <div class="fl">
                                <div class="msg-list-info">
                                    <span class="msg-name">{{ $val['nickname'] }}</span>
                                    <span>{{ $val['addtime'] }}</span>
                                </div>
                                <div class="msg-content">
                                    <div class="arrow"></div>
                                    {{ $val['content'] }}
                                </div>
                            </div>
                        </li>

                    @endforeach

                </ul>
            </div>
        </div>

        <!--footer-->
        <div class="footer">
            <div class="footer-msg">
                <h1>{{ $wall['content'] ?? '' }}</h1>
                <ul class="fr">
                    <li class="footer-menu">
                        <a href="{{ route('wechat/market_show', array('type' => 'wall', 'function' => 'wall_user', 'wall_id' => $wall['id'], 'wechat_ru_id' => $wechat_ru_id)) }}">
                            <div class="footer-menu-pic shangqiang">微信上墙</div>
                        </a>
                    </li>
                    <li class="footer-menu">
                        <a href="{{ route('wechat/market_show', array('type' => 'wall', 'function' => 'wall_msg', 'wall_id' => $wall['id'], 'wechat_ru_id' => $wechat_ru_id)) }}"
                           class="active">
                            <div class="footer-menu-pic liebiao active">留言列表</div>
                        </a>
                    </li>
                    <li class="footer-menu">
                        <a href="{{ route('wechat/market_show', array('type' => 'wall', 'function' => 'wall_prize', 'wall_id' => $wall['id'], 'wechat_ru_id' => $wechat_ru_id)) }}">
                            <div class="footer-menu-pic choujiang">抽奖</div>
                        </a>
                    </li>
                </ul>
            </div>
            <p>{{ $wall['support'] ?? '' }}</p>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(function () {

        refresh();

        $("#ul").animate({scrollTop: $("#ul ul").height()}, 800);
    });
    var start = '{{ $msg_count ?? '' }}';
    var num = 10;
    var wall_id = '{{ $wall['id'] ?? '' }}';

    var interval_time = 5000; // 间隔时间
    var req = 0; // 请求次数
    function refresh() {
        $.post("{!!  route('wechat/market_show', array('type' => 'wall', 'function' => 'get_wall_msg', 'wechat_ru_id' => $wechat_ru_id))  !!}", {
            start: start,
            num: num,
            wall_id: wall_id,
            req: req
        }, function (result) {
            if (result.code == 0 && result.data.length > 0) {
                var html = '';
                for (var i = 0; i < result.data.length; i++) {
                    html += '<li><img src="' + result['data'][i]['headimg'] + '" class="fl" /><div class="fl"><div class="msg-list-info"><span class="msg-name">' + result['data'][i]['nickname'] + '</span><span>' + result['data'][i]['addtime'] + '</span></div><div class="msg-content"><div class="arrow"></div>' + result['data'][i]['content'] + '</div></div></li>';
                }
                if (html) {
                    $("#ul ul").append(html);
                    //添加跳转锚点
                    $("#ul").animate({scrollTop: $("#ul ul").height()}, 800);
                }
                start = parseInt(start) + parseInt(result.data.length);

            }
            req++;
        }, 'json');


        @if($wall['status'] == 1)

            window.setTimeout("refresh()", interval_time);

        @endif

    }
</script>
</body>
</html>
