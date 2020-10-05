<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>微信墙 - 抽奖</title>
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
            $(".award-main").css("height", contHeight)
        }
    </script>
    <style>
        .bg {
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            width: 100%;
            background: url({{ $wall['background'] ?? '' }}) no-repeat;
            background-size: 100% 100%;
            -webkit-background-size: 100% 100%;
            -moz-background-size: 100% 100%;
            -o-background-size: 100% 100%;
            z-index: -1;
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
<div class="bg"></div>
<div class="con" id="con">
    <div class="main">
        <!--logo-->
        <div class="logo">
            <img src="{{ $wall['logo'] ?? '' }}" class="fl"/>
            <h1 class="fl">{{ $wall['name'] ?? '' }}</h1>
        </div>

        <!--main-->
        <div class="award-main">
            <!---->
            <div class="award-content fl">
                <div class="award-content-header">
                    <img src="{{ asset('assets/wechat/wall/images/choujiang.png') }}" class="fl"/>
                    <h1 class="fl">现场抽奖</h1>
                    <h2 class="fr">参加抽奖人数 <span id="total">{{ $total ?? '0' }}</span> 人</h2>
                </div>

                <div class="award-content-detail">
                    <ul class="prize-list">

                        @foreach($wall['config'] as $v)

                            <li>{{ $v['prize_level'] ?? '' }} : {{ $v['prize_name'] ?? '' }}</li>

                        @endforeach

                    </ul>
                    <ul class="award-detail-pic">
                        <li class="active">
                            <img src="{{ asset('assets/wechat/wall/images/unknow.png') }}"/>
                            <p class="award-content-name">求中奖</p>
                        </li>
                        <li class="container" style="display:none;">
                            <img src=""/>
                            <p class="award-content-name"></p>
                        </li>
                    </ul>
                    <input type="button" name="start" id="start" value="开始抽奖"/>
                    <input type="button" name="stop" id="stop" value="停止抽奖" style="display:none;"/>
                </div>
            </div>


            <!---->
            <div class="fr award-list">
                <div class="award-list-name">
                    <h1 class="fl">获奖名单</h1>
                    <h2 class="fr">获奖人数：<span id="prize_num">{{ $prize_num ?? '0' }}</span></h2>
                </div>
                <div class="award-list-title">
                    <h3 class="fl">序号</h3>
                    <h4 class="fr">用户昵称</h4>
                </div>
                <ul id="award">
                    @if(isset($list))

                    @foreach($list as $key=>$val)

                        <li>
                            <div class="fl award-list-info">
                                <span class="fl">{{ $key }}</span>
                                <img src="{{ $val['headimgurl'] }}" class="fl"/>
                            </div>
                            <p class="fr">{{ $val['wechatname'] }}</p>
                        </li>

                    @endforeach

                    @endif

                </ul>
                <input type="button" name="restart" value="重新抽奖" class="award-restart"/>
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
                        <a href="{{ route('wechat/market_show', array('type' => 'wall', 'function' => 'wall_user', 'wall_id' => $wall['id'])) }}">
                            <div class="footer-menu-pic shangqiang">微信上墙</div>
                        </a>
                    </li>
                    <li class="footer-menu">
                        <a href="{{ route('wechat/market_show', array('type' => 'wall', 'function' => 'wall_msg', 'wall_id' => $wall['id'])) }}">
                            <div class="footer-menu-pic liebiao ">留言列表</div>
                        </a>
                    </li>
                    <li class="footer-menu">
                        <a href="{{ route('wechat/market_show', array('type' => 'wall', 'function' => 'wall_prize', 'wall_id' => $wall['id'])) }}"
                           class="active">
                            <div class="footer-menu-pic choujiang active">抽奖</div>
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
        // 全局sessionStorage
        var prizeDate = window.sessionStorage ? sessionStorage.getItem("prize_list") : Session.read("prize_list");
        // console.log(prizeDate);
        if (prizeDate) {
            $("#award").html(prizeDate);
            // 获奖人数
            var num = $('.award-list-info span').length > 0 ? $('.award-list-info span').length : 0;
            $("#prize_num").html(num);
        }
        var newPrizeDate = '';

        //开始抽奖
        var time;
        $("#start").click(function () {
            $.post("{!!  route('wechat/market_show', array('type' => 'wall', 'function' => 'no_prize', 'wall_id'=> $wall['id']))  !!}", '', function (result) {
                if (result.errCode == 0) {
                    $("#stop").css("display", "inline-block");
                    $("#start").css("display", "none");
                    var len = result.data.length > 0 ? result.data.length - 1 : 0, i = 0;
                    if (result.data.length > 0) {
                        var prize = result.data;
                        time = setInterval(function () {
                            if (i > len) {
                                i = 0;
                            }
                            $(".container").html('<img src="' + prize[i].headimgurl + '" /><p class="award-content-name">' + prize[i].wechatname + '</p>').addClass("active").css("display", "block").siblings("li").removeClass("active");
                            i++;
                        }, 80);
                    } else {
                        layer.msg("暂无参与抽奖用户");
                    }
                } else if (result.errCode == 1) {
                    location.reload();
                } else {
                    layer.msg(result.errMsg);
                }
                return false;
            }, 'json');
        });
        //停止抽奖
        $("#stop").click(function () {
            $.post("{!!  route('wechat/market_show', array('type' => 'wall', 'function' => 'start_draw', 'wall_id' => $wall['id']))  !!}", '', function (result) {
                if (result.errCode == 0 && result.data) {
                    //中奖
                    $(".container").html('<img src="' + result.data.headimgurl + '" /><p class="award-content-name">' + result.data.wechatname + '</p>').addClass("active").css("display", "block").siblings("li").removeClass("active");

                    var key = $("#award li span.fl:last").text();
                    key = key ? parseInt(key) + 1 : 1;
                    var html = '<li><div class="fl award-list-info"><span class="fl">' + key + '</span><img src="' + result.data.headimgurl + '" class="fl"/></div><p class="fr">' + result.data.wechatname + '</p></li>';
                    $("#award").append(html);

                    // 存储sessionStorage
                    newPrizeDate += html;
                    sessionStorage.setItem("prize_list", newPrizeDate);

                    //获奖人数
                    var prize_num = parseInt($("#prize_num").html());
                    $("#prize_num").html(prize_num + 1);
                    // 参加抽奖人数
                    var total = parseInt($("#total").html());
                    total = total > 0 ? total - 1 : 0;
                    $("#total").html(total);

                    layer.msg("恭喜用户：" + result.data.wechatname, {
                        icon: 6,
                        time: 0, //不自动关闭
                        btn: ['确定'],
                        yes: function (index) {
                            setTimeout("remove_prize()", 100);
                            layer.close(index);
                        }
                    });
                } else if (result.errCode == 1) {
                    location.reload();
                } else {
                    $(".award-detail-pic li:first").addClass("active").siblings("li").removeClass("active");
                    layer.msg(result.errMsg);
                }
                return false;
            }, 'json');
            clearInterval(time);
            $("#stop").css("display", "none");
            $("#start").css("display", "inline-block");
        });
        //重新抽奖 新一轮抽奖 已中奖用户不参与
        $(".award-restart").click(function () {
            //询问框
            layer.confirm('确定要重置吗？', {
                btn: ['确定', '取消'] //按钮
            }, function () {
                $.post("{!!  route('wechat/market_show', array('type' => 'wall', 'function' => 'reset_draw', 'wall_id' => $wall['id']))  !!}", '', function (result) {
                    if (result.errCode == 0) {
                        $("#award").empty();
                        sessionStorage.removeItem("prize_list"); // 清空 sessionStorage
                        $(".award-detail-pic li:first").addClass("active").siblings("li").removeClass("active");
                        $("#prize_num").html("0");
                        // 参加抽奖人数
                        $("#total").html(result.data.total_num);
                    } else if (result.errCode == 1) {
                        location.reload();
                    } else {
                        layer.msg(result.errMsg);
                    }
                    return false;
                }, 'json');

                layer.closeAll();// 关闭弹窗
            });

        });


        // 点击弹窗二维码
        $(".j-market-qrocde").click(function () {
            var wall_id = "{{ $wall['id'] ?? '' }}";
            $.post("{!!  route('wechat/market_show', array('type' => 'wall', 'function' => 'wall_user'))  !!}", {wall_id: wall_id}, function (res) {
                marketQrcodeResponse(res);
            }, 'json');
        });

    });

    function remove_prize() {
        $(".award-detail-pic li:first").addClass("active").siblings("li").removeClass("active");
        $(".container").html("").css("display", "none");
    }

    // 回调
    function marketQrcodeResponse(res) {
        if (res.error == 0) {
            var qr_code = res.qr_code;
            //页面层
            layer.open({
                type: 1, //0,1,2
                skin: 'layui-layer-rim',
                closeBtn: 1, //不显示关闭按钮
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
