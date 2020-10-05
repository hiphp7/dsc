<!DOCTYPE html>
<html lang="zh-cn">
<head>
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ lang('admin/filter_words.cp_home') }}</title>
{!! global_assets('css', 'wechat', 1, 'mobile') !!}
<script type="text/javascript">var ROOT_URL = '{{ url('/') }}';</script>
<script type="text/javascript">var PC_URL = "{{ url('/') }}";</script>
{!! global_assets('js', 'wechat', 1, 'mobile') !!}

<link rel="stylesheet" type="text/css" href="{{ asset('js/calendar/calendar.min.css') }}" />
<script src="{{ asset('js/calendar/calendar.min.js') }}"></script>
<link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/js/spectrum-master/spectrum.css') }} " />
<script src="{{ asset('assets/admin/js/spectrum-master/spectrum.js') }}"></script>
<script src="{{ asset('assets/admin/js/jquery.SuperSlide.2.1.1.js') }}"></script>

<script type="text/javascript">
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }});
</script>
</head>
<body>
