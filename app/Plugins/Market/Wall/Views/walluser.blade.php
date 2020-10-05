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
    <script type="text/javascript" src="{{ asset('assets/wechat/wall/js/jquery.SuperSlide.2.1.1.js') }}"></script>
    <style>
        html {
            height: 100%;
        }

        body {
            width: 100%;
            height: 100%;
            margin: 0;
            padding: 0;
            background: url({{ $wall['background'] ?? '' }}) center no-repeat;
            -webkit-background-size: cover;
            -moz-background-size: cover;
            -o-background-size: cover;
            background-size: cover;
            overflow: hidden;
            position: fixed;
        }

        .wall-cont-section, .wall-select-list {
            overflow: hidden;
        }

        /*.wall-cont-section .left{float:left;width:70%;}*/
        .wall-cont-section .right {
            float: left;
            width: 30%;
        }

        .wall-select-list .li {
            width: 20%;
            float: left;
        }

        .wall-select-list .li .img {
            width: 100px;
            margin: 0 auto;
        }

        .wall-select-list .li .img img {
            width: 105px;
            height: 105px;
            border-radius: 50%;
        }

        .wall-select-list .li .img p {
            text-align: center;
        }

        .picMarquee-top .bd {
            margin-top: 10px;
        }

        .picMarquee-top ul li {
            margin-bottom: 20px;
        }

        .wall-right-cont .input input {
            position: static;
            margin: 0 auto;
        }

        .award-content-name {
            padding-bottom: 30px !important;
        }

        .wall-right-cont {
            width: initial;
            margin-left: 10px;
            padding-left: 15px
        }
    </style>
    <script type="text/javascript">
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
    </script>
</head>
<body>
<div class="con wall-con" id="con">
    <div class="main">
        <!--logo-->
        <div class="logo">
            <img src="{{ $wall['logo'] ?? '' }}" class="fl"/>
            <h1 class="fl">{{ $wall['name'] ?? '' }}</h1>
        </div>
        <div class="wall-cont-section">
            <!--main-->
            <div class="content">
                <ul class="" id="ul">
                    <div class="picMarquee-top">
                        <div class="bd">
                            <ul class="picList">
                                <li>
                                    <div class="wall-select-list">

                                        @foreach($list as $k => $val)

                                            <div class="li">
                                                <div class="img">
                                                    <img src="{{ $val['headimg'] ?? '' }}"/>
                                                    <p>{{ $val['nickname'] }}</p>
                                                </div>
                                            </div>

                                            @if(($k+1)%5 == 0)

                                    </div>
                                </li>
                                <li>
                                    <div class="wall-select-list">

                                        @endif

                                        @endforeach

                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </ul>
            </div>

        </div>
        <!--footer-->
        <div class="footer">
            <div class="footer-msg">
                <h1>{{ $wall['description'] ?? '' }}</h1>
                <ul class="fr">
                    <li class="footer-menu">
                        <a href="javascript:;" class="j-market-qrocde">
                            <div class="footer-menu-pic erweima">二维码</div>
                        </a>
                    </li>
                    <li class="footer-menu">
                        <a href="{{ route('wechat/market_show', array('type' => 'wall', 'function' => 'wall_user', 'wall_id' => $wall['id'])) }}"
                           class="active">
                            <div class="footer-menu-pic shangqiang active">微信上墙</div>
                        </a>
                    </li>
                    <li class="footer-menu">
                        <a href="{{ route('wechat/market_show', array('type' => 'wall', 'function' => 'wall_msg', 'wall_id' => $wall['id'])) }}">
                            <div class="footer-menu-pic liebiao ">留言列表</div>
                        </a>
                    </li>
                    <li class="footer-menu">
                        <a href="{{ route('wechat/market_show', array('type' => 'wall', 'function' => 'wall_prize', 'wall_id' => $wall['id'])) }}">
                            <div class="footer-menu-pic choujiang ">抽奖</div>
                        </a>
                    </li>
                </ul>
            </div>
            <p>{{ $wall['support'] ?? '' }}</p>
        </div>
    </div>

</div>
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
        $(".content").css("height", contHeight)
    }

    $(function () {

        var interval_time = 1000 * 60 * 5; // 间隔时间 5分钟
        window.setInterval("refresh()", interval_time);

        // 滚动效果
        $(".picMarquee-top").slide({
            mainCell: ".bd ul",
            effect: "topLoop",
            autoPlay: true,
            vis: 4,
            delayTime: 400,
            interTime: 2000
        });

        // 点击弹窗二维码
        $(".j-market-qrocde").click(function () {
            var wall_id = "{{ $wall['id'] ?? '' }}";
            $.post("{!!  route('wechat/market_show', array('type' => 'wall', 'function' => 'wall_user'))  !!}", {wall_id: wall_id}, function (res) {
                marketQrcodeResponse(res);
            }, 'json');
        });

    })

    function refresh() {
        window.location.reload();
    }

    // 回调
    function marketQrcodeResponse(res) {
        if (res.error == 0) {
            var qr_code = res.qr_code;
            //页面层
            layer.open({
                type: 1, //0,1,2
                skin: 'layui-layer-rim',
                closeBtn: 0, //不显示关闭按钮
                title: false,
                shadeClose: true, //开启遮罩关闭
                content: '<div class="share_goods"><img class="img-rounded" style="width:100%" src="' + qr_code + '"></div>',
                style: 'position:fixed; top:15%; left:15%; width: 70%; height: 70%;margin:0 auto; border:none;background-color:rgba(0,0,0,0)'
            });
            // 居中显示
            var h = document.documentElement.clientHeight;
            var w = document.documentElement.clientWidth;
            var top = (h - 387) / 2;
            var left = (w - 387) / 2;

            $('.layui-layer-rim').css('top', top);
            $('.layui-layer-rim').css('left', left);
            $('.layui-layer-rim').css('background-color', 'rgba(0,0,0,0)');
        }
        return false;
    }
</script>
</body>
</html>
