<!DOCTYPE html>
<html>
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta charset="utf-8">
    <meta name=viewport content="width=device-width,initial-scale=1">
    <title>可视化装修</title>
    <link href="//at.alicdn.com/t/font_u366719ytlat6gvi.css" rel="stylesheet">
    <link href="//at.alicdn.com/t/font_lkv63qpdlo8khuxr.css" rel="stylesheet">
    <link href="//at.alicdn.com/t/font_242849_qhtun9wpkpn.css" rel="stylesheet">
    <link href="{{ asset('static/dist/css/admin.css') }}" rel=preload as=style>
    <link href="{{ asset('static/dist/css/chunk-common.css') }}" rel=preload as=style>
    <link href="{{ asset('static/dist/css/chunk-vendors.css') }}" rel=preload as=style>
    <link href="{{ asset('static/dist/js/admin.js') }}" rel=preload as=script>
    <link href="{{ asset('static/dist/js/chunk-common.js') }}" rel=preload as=script>
    <link href="{{ asset('static/dist/js/chunk-vendors.js') }}" rel=preload as=script>
    <link href="{{ asset('static/dist/css/chunk-vendors.css') }}" rel=stylesheet>
    <link href="{{ asset('static/dist/css/chunk-common.css') }}" rel=stylesheet>
    <link href="{{ asset('static/dist/css/admin.css') }}" rel=stylesheet>
    <script>
        window.ROOT_URL = '{{ url('/') . '/' }}';
        window.API_URL = '{{ url('/api/v4') }}';
        window.shopInfo = {!! $shopInfo !!};
    </script>
    <script type="text/javascript" src="{{ asset('js/jquery-1.12.4.min.js') }}"></script>
</head>
<body>
<div id="app"></div>
<script src="https://3gimg.qq.com/lightmap/components/geolocation/geolocation.min.js" async></script>
<script>
    //去除火狐默认事件
    document.body.ondrop = function (event) {
        event.preventDefault();
        event.stopPropagation();
    }
</script>
<script src="{{ asset('static/dist/js/chunk-vendors.js') }}"></script>
<script src="{{ asset('static/dist/js/chunk-common.js') }}"></script>
<script src="{{ asset('static/dist/js/admin.js') }}"></script>
</body>
</html>
