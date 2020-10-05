<!DOCTYPE HTML>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>客服管理中心</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <link href="{{ asset('assets/chat/css/style.css') }}" rel="stylesheet" type="text/css" media="all"/>
</head>
<body class="login-box">
<div class="login">
    <img src="{{ asset('assets/chat/images/logo.png') }}" class="logo">
    <div class="tit">
        <h1>客服管理中心</h1>
    </div>
    <form name="form" onsubmit="return signIn();">
        @csrf
        <div class="login-top">
            <input type="text" name="username" placeholder="用户名">
            <input type="password" name="password" placeholder="密　码">
            <div class="box">
                <input type="text" name="catpcha" placeholder="验证码">
                <img class="catpcha" src="{{ route('captcha_verify') }}" alt="验证码" width="73" height="31">
            </div>
        </div>
        <div class="login-bottom">
            <input type="submit" value="登录">
            <span class="error"></span>
        </div>
    </form>
</div>
<script src="{{ asset('js/jquery-1.12.4.min.js') }}"></script>
<script>
    $(".checkbox").click(function () {
        if ($(this).hasClass("checked")) {
            $(this).removeClass("checked");
            $('input[name=remember]').val(0);
        } else {
            $(this).addClass("checked");
            $('input[name=remember]').val(1);
        }
    });

    //更新验证码
    $('.catpcha').click(function () {
        $(this).attr('src', '{{ route('captcha_verify') }}?random=' + Math.random());
    });

    function signIn() {
        //登录
        var username = $('input[name=username]').val();
        var password = $('input[name=password]').val();
        var catpcha = $('input[name=catpcha]').val();
        var remember = $('input[name=remember]').val();

        if (username == '') {
            $('.error').text('用户名不能为空');
            return false;
        }

        if (password == '') {
            $('.error').text('密码不能为空');
            return false;
        }

        if (catpcha == '') {
            $('.error').text('验证码不能为空');
            return false;
        }

        $.ajax({
            url: "{{ route('kefu.login.index') }}",
            type: "post",
            data: {
                username: username,
                password: password,
                catpcha: catpcha,
                remember: remember
            },
            dataType: 'json',
            success: function (res) {
                if (res.code == 1) {
                    $('.error').text(res.msg);
                    $('.catpcha').click();
                } else if (res.code == 0) {
                    window.location.href = "{{ route('kefu.admin.index') }}";
                }
            }
        });
        return false;
    }
</script>
</body>
</html>
