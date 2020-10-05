<!DOCTYPE html>
<html lang="zh-cn">
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $page_title ?? lang('admin/common.cp_home') }}</title>
    {!! global_assets('css', 'mobile', 1, 'mobile') !!}
    <script type="text/javascript">var ROOT_URL = "{{ url('/') }}";</script>
    {!! global_assets('js', 'mobile', 1, 'mobile') !!}

    @if(config('shop.lang') == 'en')
        <link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/css/en.css') }}" />
    @endif

    <script type="text/javascript">
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }});
    </script>
    <link rel="stylesheet" type="text/css" href="{{ asset('js/calendar/calendar.min.css') }}" />
    <script src="{{ asset('js/calendar/calendar.min.js') }}"></script>
</head>

<body class="iframe_body">