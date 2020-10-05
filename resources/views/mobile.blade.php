<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0,viewport-fit=cover">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ config('shop.shop_name') }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="keywords" content="{{ config('shop.shop_keywords') }}"/>
    <meta name="description" content="{{ config('shop.shop_desc') }}"/>
    <meta name="format-detection" content="telephone=no"/>
    <link href="//at.alicdn.com/t/font_u366719ytlat6gvi.css" rel="stylesheet">
    <link href="//at.alicdn.com/t/font_242849_d1e6kntzwv.css" rel="stylesheet">
    <link href="//at.alicdn.com/t/font_242849_oiofj1qu35p.css" rel=stylesheet >
    <link href="{{ asset('static/dist/js/chunk-common.js?v=' . md5(VERSION)) }}" rel=preload as=script>
    <link href="{{ asset('static/dist/js/chunk-vendors.js?v=' . md5(VERSION)) }}" rel=preload as=script>
    <link href="{{ asset('static/dist/js/index.js?v=' . md5(VERSION)) }}" rel=preload as=script>
    <link href="{{ asset('static/dist/css/chunk-vendors.css?v=' . md5(VERSION)) }}" rel=stylesheet>
    <link href="{{ asset('static/dist/css/index.css?v=' . md5(VERSION)) }}" rel=stylesheet>
    <script type="text/javascript">
        window.ROOT_URL = '{{ url('/') . '/' }}';
        window.API_URL = '{{ url('/api/v4') }}';
        window.APP_DOWNLOAD_URL = '';
        window.pageTitle = "{{ config('shop.shop_name') }}";
        window.shopInfo = {type: 'web', ruid: 0, authority: 1};
        window.sTenKey = "{{ config('shop.tengxun_key') }}";
    </script>
    <!-- 微信 JS-SDK 如果不需要兼容微信小程序，则无需引用此 JS 文件。 -->
    <script type="text/javascript" src="{{ asset('js/jweixin-1.4.0.js') }}"></script>
    <!-- uni 的 SDK -->
    <script type="text/javascript" src="{{ asset('js/uni.webview.1.5.1.js') }}"></script>
</head>
<body>
<noscript><strong>We're sorry but dscmall doesn't work properly without JavaScript enabled. Please enable it to continue.</strong></noscript>
<div id="app"></div>
<script src="{{ asset('static/dist/js/chunk-vendors.js?v=' . md5(VERSION)) }}"></script>
<script src="{{ asset('static/dist/js/chunk-common.js?v=' . md5(VERSION)) }}"></script>
<script src="{{ asset('static/dist/js/index.js?v=' . md5(VERSION)) }}"></script>
<script src="{{ asset('js/ap.js?v=' . md5(VERSION)) }}"></script>
<script type="text/javascript">
    //处理iphone出现输入框被档住情况
    const input = document.getElementsByTagName('input')[0];
    var interval;

    if (input) {
        input.onfocus = () => {
            interval = setInterval(() => {
                input.scrollIntoViewIfNeeded();
            }, 1000);
        };

        input.onblur = () => {
            clearInterval(interval);
        };
    }
</script>
{!! html_out(config('shop.stats_code', '')) !!}
</body>
</html>
