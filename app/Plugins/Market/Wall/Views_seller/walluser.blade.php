<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>微信墙 - 微信上墙</title>
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
            contHeight = c - logo - footer - 50 + "px";
            $(".index-list").css("height", contHeight)
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
            <ul class="index-list" id="ul" style="position: relative;">

                @foreach($list as $val)

                    <li>
                        <img src="{{ $val['headimg'] }}"/>
                        <p>{{ $val['nickname'] }}</p>
                    </li>

                @endforeach

            </ul>
        </div>

        <!--footer-->
        <div class="footer">
            <div class="footer-msg">
                <h1>{{ $wall['content'] ?? '' }}</h1>
                <ul class="fr">
                    <li class="footer-menu">
                        <a href="{{ route('wechat/market_show', array('type' => 'wall', 'function' => 'wall_user', 'wall_id' => $wall['id'], 'wechat_ru_id' => $wechat_ru_id)) }}"
                           class="active">
                            <div class="footer-menu-pic shangqiang active">微信上墙</div>
                        </a>
                    </li>
                    <li class="footer-menu">
                        <a href="{{ route('wechat/market_show', array('type' => 'wall', 'function' => 'wall_msg', 'wall_id' => $wall['id'], 'wechat_ru_id' => $wechat_ru_id)) }}">
                            <div class="footer-menu-pic liebiao ">留言列表</div>
                        </a>
                    </li>
                    <li class="footer-menu">
                        <a href="{{ route('wechat/market_show', array('type' => 'wall', 'function' => 'wall_prize', 'wall_id' => $wall['id'], 'wechat_ru_id' => $wechat_ru_id)) }}">
                            <div class="footer-menu-pic choujiang ">抽奖</div>
                        </a>
                    </li>
                </ul>
            </div>
            <p>{{ $wall['support'] ?? '' }}</p>
        </div>
    </div>
</div>
</body>
</html>
