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

    <link rel="stylesheet" href="{{ asset('assets/wechat/redpack/css/font-awesome.min.css') }}"/>
    <link rel="stylesheet" href="{{ asset('assets/wechat/redpack/css/sweet-alert.css') }}"/>
    <link rel="stylesheet" href="{{ asset('assets/wechat/redpack/css/main.css') }}"/>
    <style>
        body {
            background: url({{ $info['background'] ?? asset('assets/wechat/redpack/images/bg.jpg') }}) no-repeat;
            background-size: 100% 100%;
        }
    </style>
    @include('mobile.base.jssdk')
    <script type="text/javascript">
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
    </script>
</head>
<body>
<div id="content">

    @if(!$is_subscribe)

        <header class="header-max-box">
            <div class="ect-header-banner dis-box">
                <div class="box-flex">
                    <div class="ect-header-text fl">
                        <h4>请先关注微信公众号,享专属服务</h4>
                    </div>
                </div>
                <a class="btn-submit1 j-ewm-box" href="javascript:;">立即关注</a>
            </div>
            <div class="index-weixin-box">
                <div><img src="{{ asset('assets/mobile/img/ewm.png') }}"></div>
                <p class="text-c">长按二维码关注微信公众号</p>
            </div>
            <div class="index-bg-box"></div>
        </header>

    @endif

    <footer>
        <div class="suport">
            <p>{{ $info['support'] ?? '' }}</p>
        </div>
    </footer>
    <div class="global-nav">
        <div class="global-nav-wrap">
            <div class="global-nav-item">
                <a href="#" id="comment" class="global-nav-link">
                    <i class="fa fa-fw fa-info fa-2x "></i>
                    <div class="global-nav-tip">活动说明</div>
                </a>
            </div>
            <div class="global-nav-item">
                <a href="
                @if($flag == null || $flag == '')
                {{ $shake_url }}
                @else
                    #
                    @endif
                    " id="shake" class="global-nav-link">
                    <i class="fa fa-fw fa-hand-o-up fa-2x"></i>
                    <div class="global-nav-tip">摇一摇</div>
                </a>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('assets/wechat/redpack/js/sweet-alert.min.js') }}"></script>
<script type="text/javascript">
    $(function () {
        // 判断微信浏览器
        var ISWeixin = !!navigator.userAgent.match(/MicroMessenger/i); //wp手机无法判断
        if (!ISWeixin) {
            var rd_url = location.href.split('#')[0];  // remove hash
            var oauth_url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=APPID&redirect_uri=' + encodeURIComponent(rd_url) + '&response_type=code&scope=snsapi_base&state=STATE#wechat_redirect';
            location.href = oauth_url;
            return false;
        }

        // 活动说明
        $("#comment").click(function () {
            var comment = "{{ $info['description'] ?? ''}}";
            sweetAlert(comment);

        });
        // 关注公众号
        {{--// $("#observer").click(function(){--}}
        {{--//     var wechat_img = "{{ asset('img/ewm.png') }}";--}}
        {{--//     var text = "扫描或长按识别关注微信二维码";--}}
        {{--//     swal({title: "", text: text, imageUrl: wechat_img , imageSize : "180x180"});--}}
        {{--// });--}}
        // 摇一摇
        $("#shake").click(function () {
            var flag = "{{ $flag ?? '' }}";
            if (flag !== null && flag !== '') {
                swal({title: "", text: flag, imageUrl: "{{ asset('assets/wechat/redpack/images/thumbs-up.jpg') }}"});
                return false;
            }
        });

        /*首页二维码*/
        $(".j-ewm-box").click(function () {
            $(".index-bg-box").addClass("active");
            $(".index-weixin-box").addClass("active");
        });
        $(".index-bg-box").click(function () {
            $(".index-bg-box").removeClass("active");
            $(".index-weixin-box").removeClass("active");
        });

        /*立即关注*/
        if ($(".index-weixin-box").hasClass("index-weixin-box")) {
            $(".index-banner").css({
                "marginTop": "0rem",
            })
        } else {
            $(".index-banner").css({
                "marginTop": "0rem",
            })
        }

    });
</script>
</body>
</html>
