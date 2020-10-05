<!DOCTYPE html>
<html lang="zh-cn">
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ lang('seller/common.cp_home') }} - {{ $postion['ur_here'] ?? '' }}</title>
    <link href="{{ asset('assets/mobile/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css"/>
    <link href="{{ asset('assets/mobile/vendor/fancybox/jquery.fancybox.css') }}" rel="stylesheet" type="text/css"
          media="screen"/>
    <link href="{{ asset('assets/mobile/vendor/font-awesome/css/font-awesome.min.css') }}" rel="stylesheet">

    <!-- PC -->
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/seller/css/style.css') }}"/>
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/seller/css/general.css') }}"/>
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/seller/css/purebox.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('js/calendar/calendar.min.css') }}">
    <script src="{{ asset('js/calendar/calendar.min.js') }}"></script>
    <!-- PC -->

    <link href="{{ asset('assets/mobile/css/console_wechat_seller.css') }}" rel="stylesheet">

    @if(config('shop.lang') == 'en')
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/seller/css/en.css') }}" />
    @endif
    
    <script src="{{ asset('assets/mobile/vendor/common/jquery.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('assets/mobile/vendor/fancybox/jquery.fancybox.js') }}" type="text/javascript"></script>
    <script src="{{ asset('assets/mobile/vendor/bootstrap/js/bootstrap.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('assets/mobile/vendor/layer/layer.js') }}" type="text/javascript"></script>
    <script src="{{ asset('assets/mobile/vendor/common/jquery.form.js') }}" type="text/javascript"></script>
    <script type="text/javascript">
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
    </script>
</head>
<body>
