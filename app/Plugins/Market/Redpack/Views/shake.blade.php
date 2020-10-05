<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>{{ $page_title }}</title>
    <script type="text/javascript">var ROOT_URL = "{{ url('/') }}";</script>
    <script src="{{ asset('assets/mobile/vendor/common/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/mobile/vendor/layer/layer.js') }}"></script>

    <style>
        * {
            margin: 0;
            padding: 0;
        }

        body {
            background: #292D2E;
        }

        .hand {
            width: 190px;
            height: 300px;
            background: url("{{ asset('assets/wechat/redpack/images/hand.png') }}") no-repeat;
            position: absolute;
            top: 50px;
            left: 50%;
            margin-left: -95px;
        }

        .hand-animate {
            -webkit-animation: hand_move infinite 2s;
        }

        .result {
            background: #393B3C;
            border: #2C2C2C 1px solid;
            box-shadow: inset #4D4F50 0 0 0 1px;
            border-radius: 10px;
            color: #fff;
            padding: 10px;
            width: 300px;
            position: absolute;
            top: 300px;
            left: 50%;
            margin-left: -161px;
            opacity: 0;
            -webkit-transition: all 1s;
            -moz-transition: all 1s;
            -ms-transition: all 1s;
            -o-transition: all 1s;
            transition: all 1s;
        }

        .result .pic {
            width: 50px;
            height: 50px;
            float: left;
            background: #fff;
        }

        .result .con {
            overflow: hidden;
            zoom: 1;
            padding-left: 10px;
            line-height: 24px;
        }

        .result-show {
            opacity: 1;
            margin-top: 50px;
        }

        .loading {
            position: absolute;
            top: 240px;
            left: 50%;
            margin-left: -50px;
            width: 100px;
            height: 100px;
            background: url("{{ asset('assets/wechat/redpack/images/spinner.png') }}") no-repeat;
            background-size: 100px 100px;
            opacity: 0;
            -webkit-animation: loading infinite linear .5s;
            -moz-animation: loading infinite linear .5s;
            -ms-animation: loading infinite linear .5s;
            -o-animation: loading infinite linear .5s;
            animation: loading infinite linear .5s;
            -webkit-transition: all .5s;
            -moz-transition: all .5s;
            -ms-transition: all .5s;
            -o-transition: all .5s;
            transition: all .5s;
        }

        .loading-show {
            opacity: 1;
        }

        @-webkit-keyframes hand_move {
            0% {
                -webkit-transform: rotate(0);
                -moz-transform: rotate(0);
                -ms-transform: rotate(0);
                -o-transform: rotate(0);
                transform: rotate(0);
            }
            50% {
                -webkit-transform: rotate(15deg);
                -moz-transform: rotate(15deg);
                -ms-transform: rotate(15deg);
                -o-transform: rotate(15deg);
                transform: rotate(15deg);
            }
            100% {
                -webkit-transform: rotate(0);
                -moz-transform: rotate(0);
                -ms-transform: rotate(0);
                -o-transform: rotate(0);
                transform: rotate(0);
            }
        }

        @-webkit-keyframes loading {
            0% {
                -webkit-transform: rotate(0);
                -moz-transform: rotate(0);
                -ms-transform: rotate(0);
                -o-transform: rotate(0);
                transform: rotate(0);
            }
            100% {
                -webkit-transform: rotate(360deg);
                -moz-transform: rotate(360deg);
                -ms-transform: rotate(360deg);
                -o-transform: rotate(360deg);
                transform: rotate(360deg);
            }
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
<div id="hand" class="hand hand-animate"></div>
<div id="loading" class="loading"></div>
<div id="result" class="result">
    <div class="pic"><img id="shake_icon" src="" width="50" height="50"/></div>
    <div class="con">摇一摇结果<br/><a id="shake_result" href="" style="text-decoration:none;color:#fff"></a></div>
</div>

<!-- 摇一摇的声音 -->
<audio style="display:none" src="{{ asset('assets/wechat/redpack/images/shake_sound_male.mp3') }}" preload="metadata" id="shakingAudio"></audio>
<!-- 摇到红包的声音 -->
<audio style="display:none" src="{{ asset('assets/wechat/redpack/images/shake_match.mp3') }}" preload="metadata" id="shakingResult"></audio>

<script type="text/javascript" src="https://res.wx.qq.com/open/js/jweixin-1.2.0.js"></script>
<script type="text/javascript">
$(function() {

    var u = navigator.userAgent;
    var isAndroid = u.indexOf('Android') > -1 || u.indexOf('Adr') > -1; //android终端
    var isiOS = !!u.match(/\(i[^;]+;( U;)? CPU.+Mac OS X/);

//     // 判断微信浏览器
//     var ISWeixin = !!u.match(/MicroMessenger/i); //wp手机无法判断
//     if(!ISWeixin){
//         var rd_url = location.href.split('#')[0];  // remove hash
//         var oauth_url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=APPID&redirect_uri='+encodeURIComponent(rd_url) + '&response_type=code&scope=snsapi_base&state=STATE#wechat_redirect';
//         location.href = oauth_url;
//         return false;
//     }

    var last = 0;

    var SHAKE_THRESHOLD = 1800;  //定义触发动作的阈值
    var last_update = 0;         //上一次触发的时间
    var x = y = z = last_x = last_y = last_z = 0;//x,y,z当前加速度,last_z,last_x,last_y上次加速度
    var num = 0;
    var numMax = 3;

    // 摇一摇的声音
    var shakingAudio = document.getElementById('shakingAudio');
    // 摇到红包的声音
    var shakingResult = document.getElementById('shakingResult');


    listenPhoneShake();

    //监听摇一摇的动作
    function listenPhoneShake() {
        if (window.DeviceMotionEvent) {
            window.addEventListener('devicemotion', deviceMotionHandler, false);
        } else {
            layer.msg('本设备不支持摇一摇！');
        }
    }


    // --检测设备是否有摇一摇动作
    function deviceMotionHandler(eventData) {
        var acceleration = eventData.accelerationIncludingGravity;
        var curTime = new Date().getTime();

        if ((curTime - last_update) > 100) {
            var diffTime = curTime - last_update;
            last_update = curTime;
            x = acceleration.x;
            y = acceleration.y;
            z = acceleration.z;
            var speed = Math.abs(x + y + z - last_x - last_y - last_z) / diffTime * 10000;

            if (speed > SHAKE_THRESHOLD) {

                handelShakingMotion();
            }
            last_x = x;
            last_y = y;
            last_z = z;
        }
    }

    // 设备有摇一摇动作，则对页面已摇次数进行加1，若已经摇到最大次数numMax，则请求抢红包接口
    function handelShakingMotion() {
        num++;
        // 添加摇一摇的声音
        audioAutoPlay(shakingAudio);
        if (num == numMax) {
            num = 0;

            // 关闭摇一摇的接口
            window.removeEventListener("devicemotion", deviceMotionHandler);

            // 摇一摇结束之后，请求抢红包接口
            getNewAjax();

            // 添加摇到红包的声音
            audioAutoPlay(shakingResult);
        }
    }

    // 兼容处理iphone不能自动播放声音
    function audioAutoPlay(audio) {
        if (isiOS == true) {
            //处理iphone不能自动播放
            audio.load();
            document.addEventListener('WeixinJSBridgeReady',function(){
                audio.play();
            },false);
        } else {
            //一般android机都能自动播放
            audio.play();
            // audio.trigger('play');
        }
    }

    var back_url = "{!! $back_url ?? '' !!}"; //活动页面URL

    function getNewAjax() {
        var market_id = "{{ $market_id ?? '' }}";
        var wechat_ru_id = "{{ $wechat_ru_id ?? '' }}";
        $.post("{!! route('wechat/market_show', array('type' => 'redpack','function' => 'shake')) !!}", {
            market_id: market_id,
            wechat_ru_id: wechat_ru_id,
            time: last_update,
            last: last
        }, function (data) {

            var res = data;

            document.getElementById("shake_icon").src = res['icon'];
            document.getElementById("shake_result").innerHTML = res['content'];
            document.getElementById("shake_result").href = res['url'];

            last = new Date().getTime(); // console.log(last);

            document.getElementById("result").className = "result";
            document.getElementById("loading").className = "loading loading-show";
            setTimeout(function () {
                //document.getElementById("hand").className = "hand";
                document.getElementById("result").className = "result result-show";
                document.getElementById("loading").className = "loading";

                setTimeout("location.href = '" + back_url + "'", 3000); // 3秒后返回活动页面

            }, 1000);
            return false;
        }, 'json');
    }

});
</script>
</body>
</html>
