<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>用户信息</title>
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
    <!-- <header>
        <a href="javascript:history.go(-1);" class="fl">
            <i class="iconfont">&#xe600;</i>
        </a>
        <h1>用户信息</h1>
    </header> -->
    <!--main-->
    <div class="main">
        <form action="{{ route('wechat/market_show', array('type' => 'wall', 'function' => 'wall_user_wechat', 'wechat_ru_id' => $wechat_ru_id)) }}"
            method="post" class="user-form">
            <div id="" style="border-bottom: 1px solid #d1d1d1;">
                <div class="fl user-pic">

                    @if($user['headimgurl'])

                        <img src="{{ $user['headimgurl'] }}">

                    @else

                        <img src="{{ asset('assets/wechat/wall/images/ava1.png') }}"/>

                    @endif

                </div>
                <div class="user-info">
                    <div class="user-name" id="name">
                        <input type="text" placeholder="姓名" name="nickname" value="{{ $user['nickname'] }}"/>
                        <i class="iconfont fr">&#xe601;</i>
                    </div>
                    <div class="user-name">
                        <select name="sex">
                            <option value="0"
                                    @if($user['sex'] == 0)
                                    selected
                                @endif
                            >保密
                            </option>
                            <option value="1"
                                    @if($user['sex'] == 1)
                                    selected
                                @endif
                            >男
                            </option>
                            <option value="2"
                                    @if($user['sex'] == 2)
                                    selected
                                @endif
                            >女
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="other-ava">
                <h2>其他头像</h2>
                <ul>
                    <li>
                        <a href="javascript:;">
                            <img src="{{ asset('assets/wechat/wall/images/ava1.png') }}"/>
                        </a>
                    </li>
                    <li>
                        <a href="javascript:;">
                            <img src="{{ asset('assets/wechat/wall/images/ava2.png') }}"/>
                        </a>
                    </li>
                    <li>
                        <a href="javascript:;">
                            <img src="{{ asset('assets/wechat/wall/images/ava3.png') }}"/>
                        </a>
                    </li>

                    @if($user['headimgurl'])

                        <li>
                            <a href="javascript:;">
                                <img src="{{ $user['headimgurl'] }}"/>
                            </a>
                        </li>

                    @endif

                </ul>
                @csrf
                <input type="hidden" name="headimg" value="
@if($user['headimgurl'])
                {{ $user['headimgurl'] }}
                @else
                {{ asset('assets/wechat/wall') }}/images/ava1.png
@endif
                    "/>
                <input type="hidden" name="wall_id" value="{{ $wall_id }}">
                <a href="javascript:$('.user-form').submit();" class="user-btn">确定</a>
            </div>

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

        $('.user-info i').show();
        $('#name input[type=text]').bind("blur", function () {
            var Value = $(this).val();
            var isEmpty = false;
            if (Value == "") {
                $('.user-info i').hide();
                isEmpty = true;
            }
            if (!isEmpty) {
                $('.user-info i').hide();
                $('.user-info i').show();
            }
            $('#name input[type=text]').bind('input propertychange', function () {
                $('.user-info i').show();
            });
        });

        $('.user-info i').click(function () {
            $('#name input').val('');
            $('.user-info i').hide();
        });
        $(".other-ava ul li a").click(function () {
            var img = $(this).find("img").attr("src");
            if (img) {
                $(".user-pic img").attr("src", img);
                $("input[name=headimg]").val(img);

                $(this).addClass("selected").parents(".other-ava ul li").siblings().find("a").removeClass("selected");
            }
        });
    });
</script>
</body>
</html>
