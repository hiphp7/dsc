<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>微信墙 - 上墙</title>
    <link href="{{ asset('assets/wechat/wall/css/wechat_wall_common.css') }}" rel="stylesheet" type="text/css"/>
    <link href="{{ asset('assets/wechat/wall/css/wechat_wall_user.css') }}" rel="stylesheet" type="text/css"/>
    <link href="{{ asset('assets/wechat/wall/css/fonts/iconfont.css') }}" rel="stylesheet" type="text/css"/>
    <link href="{{ asset('assets/wechat/wall/new/css/style.css') }}" type="text/css" rel="stylesheet" media="screen, projection">
    <link href="{{ asset('assets/wechat/wall/new/css/ui-dialog.css') }}" type="text/css" rel="stylesheet" media="screen, projection">
    <script type="text/javascript">var ROOT_URL = "{{ url('/') }}";</script>
    <script src="{{ asset('assets/mobile/vendor/common/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/mobile/vendor/layer/layer.js') }}"></script>
    <audio id="runingmic" class="hide" loop>
        <source src="{{ asset('assets/wechat/wall/new/music/runing.mp3') }}">
    </audio>
    <audio id="pausemic" class="hide">
        <source src="{{ asset('assets/wechat/wall/new/music/stop.mp3') }}">
    </audio>
    <script type="text/javascript" src="{{ asset('assets/wechat/wall/new/js/jquery.pulsate.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('assets/wechat/wall/new/js/dialog-plus-min.js') }}"></script>
    <script type="text/javascript">
        // 处理背景图高度
        window.onload = function () {
            var con = document.getElementById('con');
            var conHeight = con.offsetHeight;
            var c = document.documentElement.clientHeight;
            con.style.height = c + 'px';
            var logo = $(".logo").outerHeight(true);
            var footer = $(".footer").outerHeight(true);
            var contHeight = c - logo - footer - 35 + "px";
            var contHeightLeft = c - logo - newheightfooter - 35 + "px";
            var newheightfooter = $(".wall-footer-start").outerHeight(true);
            $(".wall-Lucky-section").css("height", contHeightLeft);
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

        .wall-footer-start .li {
            width: 50%;
            float: left;
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
        <div class="logo wall-Lucky-section">
            <img src="{{ $wall['logo'] ?? '' }}" class="fl"/>
            <h1 class="fl">{{ $wall['name'] ?? '' }}</h1>
            <!--footer-->
            <div class="footer" style="width:346px;float:right">
                <div class="footer-msg" style="margin:0">
                    {{--<h1>{{ $wall['content'] ?? '' }}</h1>--}}
                    <ul class="fr">
                        <li class="footer-menu">
                            <div class="footer-menu-pic header-nav erweima">二维码</div>
                        </li>
                        <li class="footer-menu">
                            <div class="footer-menu-pic header-nav  liebiao j-list-wall">名单</div>
                        </li>
                        <li class="footer-menu">
                            <a href="javascript:;" class="active">
                                <div class="footer-menu-pic header-nav choujiang active">上墙</div>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <!--main-->
        <div class="award-main" style="overflow: initial;">
            <!---->
            <div class="award-content wall-Lucky-section" style="width:inherit;">
                <div class="cont-box">
                    <div class="cont-list-left items">

                        @foreach($user_list as $k=>$v)

                            <div class="item i{{ $k+1 }}">
                                <div class="cont-list
                                   @if(isset($v['is_prized']) && $v['is_prized'] == 1)
                                    ignore
                                   @endif
                                    "><label></label><em>{{ $v['nickname'] }}</em>
                                    <div class="img"><img src="{{ $v['headimgurl'] }}"/></div>
                                    {{--<span class="">{{ $v['sign_number'] }}</span>--}}
                                    <i class="hide">{{ $v['id'] }}</i>
                                </div>
                            </div>

                        @endforeach

                    </div>
                    <div class="wall-footer-start">
                        <div class="li">
                            <input type="button" name="restart" value="开始" class="award-restart footer-btn award-start"
                                   style="position:static"/>
                            <input type="button" name="restart" value="停止" class="award-restart footer-btn award-stop"
                                   style="position:static; display:none"/>
                        </div>
                        <div class="li">
                            <input type="button" name="restart" value="重置"
                                   class="award-restart footer-list wall-btn-white close" style="position:static"/>
                        </div>
                    {{--<div class="footer-box"><p>{{ $wall['support'] ?? '' }}</p></div>--}}
                    </div>
                </div>
            </div>
            <div class="award-list cont-list-max" style="width:500px;">
                <div class="award-list-name">
                    <h1 class="fl">名单</h1>
                    <h2 class="fr">人数：
                        <span id="prize_num">{{ $prize_num ?? 0 }}</span>
                    </h2>
                </div>
                <div class="award-list-title">
                    <h3 class="fl">序号</h3>
                    <h4 class="fr">用户</h4>
                </div>
                <ul id="award" style="height:470px">

                </ul>
                {{--<input type="button" name="restart" value="返回" class="award-restart j-wall"/>--}}
            </div>
        </div>

        <div class="t-wall-footer j-wall"></div>
        <div class="t-wall-info j-wall hide"></div>
        <!-- 参数调整 -->
        <div class="model hide">
            <p><b>图片尺寸：</b><input type="text" id="itemk" placeholder="100"></p>
            <p><b>向右偏移：</b><input type="text" id="itemkRight" placeholder="10(选填)"></p>
            <div class="model-cont">
                <h4> 参考分辨率(80人) 单位px</h4>
                <ul>
                    <li>1920：112</li>
                    <li>1440：90</li>
                    <li>1200：84</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!--设置按钮-->
<a class="set-box config" href="javascript:;"><img src="{{ asset('assets/wechat/wall/new/images/shezhi.png') }}"/></a>
<div class="bg-show j-wall">
    <img class="i-close j-wall " src="{{ asset('assets/wechat/wall/new/images/close.png') }}"/>
</div>
<div class="close-btn j-wall"><input type="button" name="restart" value="返回" class="award-restart wall-btn-white j-list-wall" style="position:static"/></div>
<!--二维码-->
<div class="walluser_cont">
    <div class="bg-show-red j-wall"></div>
    <div class="erweima-box">
        <h4>入场二维码</h4>
        <div class="img qrcode">
            <img src=""/>
        </div>
    </div>
    <input type="button" name="restart" value="返回" class="award-restart footer-list wall-btn-white j-wall"
           style="z-index:4999;bottom:25px;"/>
</div>
<script type="text/javascript">
    $(document).keydown(function (event) {
        //屏蔽F5刷新键
        if (event.keyCode == 116) {
            return false;
        }
    });
    $(function () {

        //参与抽奖人数初始值
        var itemCount = 80;
        //跑马灯循环
        var tx;
        var runtx;
        //是否正在运行跑马灯
        var isRun = true;
        //是否跑马灯暂停状态
        var pause = false;
        //排名分组显示算法已经取消
        //var ts=20
        //默认跑马灯频率
        var pl = 100;
        //程序是否开始运行用于判断程序是否开始运行
        var isStart = false;

        var zzs = "#FF0000";
        //跑马灯音效
        var runingmic = document.getElementById("runingmic");
        runingmic.volume = 0.5;
        //中奖音效
        var pausemic = document.getElementById("pausemic");
        pausemic.volume = 1.0;

        var keyStatus = false;

        // 初始跑动效果
        var timer;

        $("document").ready(function () {
            // 全局sessionStorage
            var prizeDate = window.sessionStorage ? sessionStorage.getItem("prize_list") : Session.read("prize_list");
            if (prizeDate) {
                $("#award").html(prizeDate);
                // 获奖人数
                var num = $('.award-list-info span').length > 0 ? $('.award-list-info span').length : 0;
                $("#prize_num").html(num);
            }
            var newPrizeDate = '';

            // 创建item小方格
            // for(var i=1;i<=itemCount;i++){
            //     var head_img = "{{ asset('assets/wechat/wall') }}/images/wall_1.png";
            //     $("div.items").append(
            //         "<div class='item i"+i+"'><div class='cont-list'><label></label><em>张三三</em><div class='img'><img src='"+head_img+"'/></div><span>"+i+"</span></div></div>"
            //     );
            // }

            // 获取本地存储item宽度、高度信息
            if (sessionStorage.getItem("itemk")) {
                $("div.item .cont-list").css("width", sessionStorage.getItem("itemk") + "px");
                $("div.item .cont-list").css("height", sessionStorage.getItem("itemk") + "px");
            }
            if (sessionStorage.getItem("itemkRight")) {
                $(".cont-list-left").css("margin-left", sessionStorage.getItem("itemkRight") + "px");
            }
            //回显设定
            if (sessionStorage.getItem("itemk")) {
                itemk = sessionStorage.getItem("itemk");
            } else {
                itemk = $(".i1 img").css("width");
            }
            if (sessionStorage.getItem("itemkRight")) {
                itemkRight = sessionStorage.getItem("itemkRight");
            } else {
                itemkRight = 10;
            }
            $("#itemk").attr("placeholder", itemk);
            $("#itemk").val(itemk);
            $("#itemkRight").attr("placeholder", itemkRight);
            $("#itemkRight").val(itemkRight);

            // //已中奖 显示为灰色状态
            // $("#award li").each(function(idx,item){
            //     var newclass = ".i"+$(item).attr("data-number");
            //     $(newclass+" .cont-list").addClass("ignore");
            // });

            //开始抽奖
            $(".award-start").click(function () {
                clearTimeout(timer);
                // 重新开始
                if (isRun == false) {
                    isRun = true;
                }
                if (isStart == true) {
                    isStart = false;
                }

                $(".award-start").hide();
                $(".award-start").attr('disabled', true);
                $(".award-stop").show();
                $(".award-stop").removeAttr("disabled");

                //存在未中奖用户切程序出于未开始运行状态执行开始方法
                var no_prizeNum = $("div.item .cont-list:not(.ignore)").size();
                if (no_prizeNum != 0 && !isStart) {
                    isStart = !isStart;
                    startApp();
                    return false;
                }
            });

            //停止抽奖
            $(".award-stop").click(function () {
                //当所有抽奖号全部抽取完毕则销毁跑马和音效循环
                var no_prizeNum = $("div.item .cont-list:not(.ignore)").size();
                if (no_prizeNum == 0) {
                    layer.msg("已经结束");
                    clearInterval(tx);
                    clearInterval(runtx);
                    runingmic.puase();
                    return false;
                }
                //更新运行状态
                isRun = !isRun;
                //如果项目出于运行状态
                if (!isRun) {
                    //取得当前选中号码
                    // var it = $(".item .cont-list.active span").text();
                    var user_id = $(".item .cont-list.active i").text();
                    //停止跑马灯
                    runingmic.pause();
                    //Math.floor($(".sequence li").size()/ts)

                    //中奖号处理
                    var user_id = Number(user_id);

                    $(".award-start").show();
                    $(".award-start").removeAttr("disabled");
                    $(".award-stop").hide();
                    $(".award-stop").attr('disabled', true);

                    if (user_id) {
                        $.post("{!!  route('wechat/market_show', array('type' => 'wall', 'function' => 'getPrizeUser', 'wall_id' => $wall['id']))  !!}", {user_id: user_id}, function (result) {
                            if (result.errCode == 0 && result.data) {

                                var key = $("#award li span.fl:last").text();
                                key = key ? parseInt(key) + 1 : 1;
                                var html = '<li><div class="fl award-list-info"><span class="fl">' + key + '</span><img src="' + result.data.headimgurl + '" class="fl"/></div><p class="fr">' + result.data.nickname + '</p></li>';
                                $("#award").append(html);

                                //获奖人数
                                var prize_num = parseInt($("#prize_num").html());
                                $("#prize_num").html(prize_num + 1);

                                $(".item .cont-list.active").addClass("ignore");
                                // $(".item .cont-list.active").pulsate({
                                //     color: zzs, //#98ff98
                                //     repeat: 10, // 显示时间 秒
                                //     reach: 25 // 边框间距
                                // });

                                //播放中奖音效
                                pausemic.currentTime = 0;
                                pausemic.play();

                                // 弹窗
                                var result = '<div class="header-img"><img src="' + result.data.headimgurl + '"/></div><h3><strong>恭喜 ' + result.data.nickname + '</strong></h3>';
                                $(".t-wall-footer").html(result);

                                $(".t-wall-footer").show();
                                $(".bg-show").show();
                                $(".close-btn").show();

                                // 存储sessionStorage
                                if (prizeDate) {
                                    newPrizeDate = prizeDate;
                                }
                                newPrizeDate += html;
                                sessionStorage.setItem("prize_list", newPrizeDate);

                                clearTimeout(timer);
                                // domeLucky();
                            }
                            return false;
                        }, 'json');
                    }
                } else {
                    $(".active").removeClass("active");
                    runingmic.play();
                }
            });

            //重新抽奖 新一轮抽奖 已中奖用户不参与
            $(".close").click(function () {
                //询问框
                layer.confirm('确定要重置吗？', {
                    btn: ['确定', '取消'] //按钮
                }, function () {
                    clearTimeout(timer);
                    $.post("{!!  route('wechat/market_show', array('type' => 'wall', 'function' => 'reset_draw', 'wall_id' => $wall['id']))  !!}", '', function (result) {
                        if (result.errCode == 0) {
                            $("#award").empty();
                            sessionStorage.removeItem("prize_list"); // 清空 sessionStorage
                            $("#prize_num").html("0");
                            layer.msg('已重置');
                        } else {
                            layer.msg(result.errMsg);
                        }
                        location.reload();
                        return false;
                    }, 'json');

                    layer.closeAll();// 关闭弹窗
                });
            });

            // 点击查看信息
            $(".cont-list").click(function () {
                var user_id = parseInt($(this).children("i").html());
                $.post("{!!  route('wechat/market_show', array('type' => 'wall', 'function' => 'getOneUser', 'wall_id' => $wall['id']))  !!}", {user_id: user_id}, function (result) {
                    if (result.errCode == 0 && result.data) {
                        // 弹窗
                        var result = '<div class="header-img"><img src="' + result.data.headimgurl + '"/></div><h3>' + result.data.sign_number + '号  <strong>' + result.data.nickname + '</strong></h3>';
                        $(".t-wall-info").html(result);
                        $(".t-wall-info").show();
                    }
                    return false;
                }, 'json');
            });


            domeLucky();

        });

        //程序开始入口
        function startApp() {
            //开始播放跑马灯音效
            runingmic.play();
            //产生随机数临时变量
            var rand = 0;
            //存储上一次随机数的临时变量
            var prenum;
            tx = setInterval(function () {
                if (isRun) {
                    var no_prizeNum = $("div.item .cont-list:not(.ignore)").size();
                    if (no_prizeNum > 0) {
                        while (true) {
                            rand = Math.floor(Math.random() * no_prizeNum);
                            if (rand == 0 || rand != prenum) {
                                break;
                            }
                        }
                        prenum = rand;
                        $("div.item .cont-list.active").removeClass("active");
                        $("div.item .cont-list:not(.ignore):not(.active)").eq(rand).addClass("active");
                    }
                }
            }, pl);
            runtx = setInterval(function () {
                runingmic.currentTime = 0;
            }, 7000);
        }


        function domeLucky() {
            var all_num = $("div.item .cont-list:not(.ignore)").size();
            rand = Math.floor(Math.random() * all_num);
            $("div.item .cont-list").removeClass("active");
            $("div.item .cont-list:not(.ignore):not(.active)").eq(rand).addClass("active");
            timer = setTimeout(domeLucky, 800);
        };

        // 点击弹窗二维码
        $(".erweima").click(function () {
            var wall_id = "{{ $wall['id'] ?? '' }}";
            $.post("{!!  route('wechat/market_show', array('type' => 'wall', 'function' => 'wall_user'))  !!}", {wall_id: wall_id}, function (res) {
                if (res.error == 0) {
                    $('.qrcode img').attr("src", res.qr_code);
                    $('.walluser_cont').show();
                    $('.bg-show-red').show();
                }
            }, 'json');
        });

        // 中奖名单
        $(".j-list-wall").click(function () {
            $(".bg-show").show();
            $(".cont-list-max").show();
        });
        // 弹窗二维码 关闭
        $(".j-wall").click(function () {
            $(".walluser_cont").hide()
            $(".bg-show0-red").hide();
        });
        $(".j-wall").click(function () {
            $(".bg-show").hide();
            $(".cont-list-max").hide();
            $(".t-wall-footer").hide();
            $(".close-btn").hide();
            $(".t-wall-info").hide();
        })

        $("a.config").click(function () {
            pause = true;
            runingmic.pause();
            var d = dialog({
                title: '页面参数调整',
                content: $(".model"),
                okValue: '确定',
                ok: function () {
                    if ($("#itemk").val()) {
                        sessionStorage.setItem("itemk", $("#itemk").val());
                    }
                    if ($("#itemkRight").val()) {
                        sessionStorage.setItem("itemkRight", $("#itemkRight").val());
                    }
                    window.location.reload();
                }, onclose: function () {
                    pause = false;
                }
            });
            d.show();
        });

    });
</script>
</body>
</html>
