<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0,viewport-fit=cover">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ $page_title  }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="keywords" content="{{ config('shop.shop_keywords') }}"/>
    <meta name="description" content="{{ config('shop.shop_desc') }}"/>
    <meta name="format-detection" content="telephone=no"/>
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}"/>

    @include('http.js_languages_new')

    <link rel="stylesheet" type="text/css" href="{{ __TPL__ . '/css/user.css' }}">
    <link rel="stylesheet" href="{{ asset('assets/mobile/vendor/layer/skin/layer.css') }}">
    <script src="{{ asset('assets/mobile/vendor/layer/layer.js') }}"></script>

    <script type="text/javascript">
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
    </script>

    <style>
        .cont-wrapper {
            display: inline-block;
            background: #fff8f0;
            padding-top: 10px;
            padding-bottom: 10px;
        }

        .cont-wrapper p {
            vertical-align: middle;
            color: #999;
            font-size: 12px;
            display: inline-block;
        }
    </style>

</head>
<body class="third_body">
<div class="header">
    <div class="logo-con w w1200">
        <a href="{{ url('/') }}" class="logo"><img src="{{ __TPL__ . '/images/logo.gif' }}"/></a>
        <div class="logo-title">{{ $lang['bind_mobile']  }}</div>
    </div>
</div>
<div class="third_container w w1200">
    <div class="main clearfix">
        <div class="clearfix">
            <div class="cont-wrapper">
                <p>{{ sprintf(lang('user.bind_mobile_tips'), $dwt_shop_name) }}</p>
            </div>
        </div>
        <div class="reg-tab-con">
            <div class="r-tabCon bind-login-content">
                <div class="account-login-panle">
                    <div class="wellcome-tip">
                        <img src="{{ $oauth_info['headimgurl'] ?? '' }}" width="28" height="28">
                        Hi, {{ $oauth_info['nickname'] }} {{ $lang['welcome_to'] }} {{ $dwt_shop_name ?? '' }}
                    </div>
                    <form class="login-form bindform" name="loginForm" method="post"
                          action="{{ route('oauth.bind_register',['type' => $type]) }}">
                        <div class="login-error-container">
                            <div id="login-error" class="login-error">
                                <span id="login-server-error" class="error"></span>
                            </div>
                        </div>
                        <div class="form-item" id="form-item-account">
                            <label>{{ $lang['bind_mobile_lable'] }}</label>
                            <input type="tel" name="mobile" placeholder="{{ $lang['bind_mobile_null'] }}" datatype="m" nullmsg="{{ $lang['bind_mobile_null'] }}" errormsg="{{ $lang['bind_mobile_error'] }}"/>
                            <i class="i-clear"></i>
                        </div>
                        <div class="input-tip"><span id="error-username"></span></div>

                        <div class="form-item form-item-authcode">
                            <label>{{ $lang['bind_captcha_lable'] }}</label>
                            <input type="text" name="captcha" placeholder="{{ $lang['bind_captcha_null'] }}" datatype="*" nullmsg="{{ $lang['bind_captcha_null'] }}"/>
                            <img src="{{ asset('captcha_verify.php?captcha=is_register_phone&'.mt_rand()) }} " class="login_captcha" id="captcha_img" style="vertical-align: middle;cursor: pointer;" onclick="this.src='{{ asset('captcha_verify.php?captcha=is_register_phone') }}&'+Math.random()" width="119" height="52"/>
                        </div>

                        <div class="input-tip"><span></span></div>
                        <div class="form-item form-item-telyzm">
                            <label>{{ $lang['bind_mobile_code'] }}</label>
                            <input type="number" name="mobile_code" datatype="*" nullmsg="{{ $lang['bind_mobile_code_null'] }}" placeholder="{{ $lang['bind_mobile_code_null'] }}"/>
                            <a type="button" id="sendsms" href="#" class="btn-phonecode ipt-check-btn">{{ $lang['get_bind_mobile_code'] }}</a>
                        </div>
                        <div class="input-tip"><span></span></div>

                        @csrf
                        <input type="hidden" name="flag" id="flag" value='' />
                        <input type="hidden" name="seccode" id="seccode" value="{{ $sms_security_code }}"/>
                        <input name="type" type="hidden" value="{{ $type }}"/>
                        <input type="hidden" name="back_url" value="{{ $back_url ?? '' }}"/>
                        <button type="submit" class="btn-register">{{ $lang['bind_submit'] }}</button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<div class="div-messages"></div>

@include('http.page_footer_flow')

<script src="{{ asset('js/utils.js') }}"></script>
{{--短信--}}
<script type="text/javascript" src="{{ asset('plugins/sms/sms.js') }}"></script>

<script type="text/javascript" src="{{  asset('assets/mobile/vendor/common/validform.js') }}"></script>
<script type="text/javascript">
    $(function () {
        // 提交验证
        $.Tipmsg.r = null;
        $(".bindform").Validform({
            tiptype: function (msg) {
                layer.msg(msg);
            },
            tipSweep: true,
            ajaxPost: true,
            callback: function (data) {
                // {"info":"demo info","status":"y"}
                if (data.status === 'y') {
                    window.location.href = data.url;
                } else {
                    // 刷新图片验证码
                    $('#captcha_img').click();
                    layer.msg(data.info);
                }
            }
        });

        //倒计时
        var time = 60;
        var c = 1;

        function data() {
            if (time == 0) {
                c = 1;
                $("#sendsms").html("{{ $lang['get_bind_mobile_code'] }}");
                time = 60;
                document.getElementById('sendsms').disabled = false;
                return;
            }

            if (time != 0) {
                if ($(".ipt-check-btn").attr("class").indexOf("disabled") < 0) {
                    $(".ipt-check-btn").addClass('disabled');
                }
                c = 0;
                $("#sendsms").html("<span>重新获取(" + time + ")</span>");
                time--;
                document.getElementById('sendsms').disabled = true;
            }
            setTimeout(data, 1000);
        }

        // 发送短信
        $("#sendsms").click(function () {
            var myreg = /^(1[3-9])\d{9}$/; // 手机号段正则,支持166号段
            var mobile = $("input[name=mobile]").val();
            var verify_code = $("input[name=captcha]").val();
            var seccode = $("input[name=seccode]").val();
            var flag = $("input[name=flag]").val();

            if (mobile == '') {
                layer.msg('{{ $lang['bind_mobile_null'] }}');
                return false;
            }

            if (verify_code == '') {
                layer.msg('{{ $lang['bind_captcha_null'] }}');
                return false;
            }

            if (!myreg.test(mobile)) {
                layer.msg('{{ $lang['bind_mobile_error'] }}');
                return false;
            }
            if (c == 0) {
                layer.msg('{{ $lang['send_wait'] }}');
                return false;
            }

            $.post("{{ route('sms',['act' => 'send']) }}", {
                mobile: mobile,
                sms_value: 'sms_code', // 发送验证码时机
                seccode: seccode,
                flag: flag,
                username: mobile,
                captcha: verify_code
            }, function (res) {
                if (res.code == 2) {
                    layer.msg('{{ $lang['send_success'] }}');
                    data();
                } else {
                    layer.msg(res.msg);
                    // 刷新图片验证码
                    $('#captcha_img').click();
                }

            }, 'json');

        });


    });

</script>
</body>
</html>