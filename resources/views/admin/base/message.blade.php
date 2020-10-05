<!DOCTYPE html>
<html lang="zh-cn">
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>dsc message</title>

    {!! global_assets('css', 'mobile', 1, 'mobile') !!}
    <script type="text/javascript">var ROOT_URL = '{{ url('/') }}';</script>
    {!! global_assets('js', 'mobile', 1, 'mobile') !!}

    <style>
        body {background-color: #fff}
        .success_img{ float: left;}
        .fr_content{ min-width:500px;position:absolute; top:50%;left:50%; margin-left:-250px; margin-top:-150px;}
        .fr_content .title{ font-size: 16px;margin-bottom:10px;color: #333;}
        .fr_content #spanSeconds{ color: #ec5151;font-weight: bold;}
        .fr_content .ts{ margin-bottom: 10px;display: inline-block;}
        .success_right{ float:left;}
        .msg-link li{ line-height:20px;}
        .log_items{ padding:20px;}
    </style>

    <script type="text/javascript">
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }});
    </script>
</head>
<body>
<div class="list-div">
    <div class="fh_message">
        <div class="fr_content">
            <div class="success_img">
                @if(isset($data['type']) && $data['type'] == 1)
                <img src="{{ asset('assets/admin/images/success.jpg') }}">

                @elseif (isset($data['type']) && $data['type'] == 2)
                <img src="{{ asset('assets/admin/images/error.jpg') }}">

                @else
                <img src="{{ asset('assets/admin/images/tooltip.jpg') }}">
                @endif
            </div>
            <div class="success_right">
                <h3 class="title">{!! $data['message'] ?? '' !!}</h3>

                <span class="ts" id="redirectionMsg">{!! lang('admin/common.auto_redirection') !!}</span>

                <ul class="msg-link">
                    <li><a href="{!! $data['url'] !!}" >{{ lang('common.back_up_page')  }}</a></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
{{--layer.msg('{{ $data['message'] }}', {icon: '{{ $data['type'] }}', offset: '150px', time: 1000, title: '提示'});--}}

(function(){

	var time = '{{ $data['second'] ?? '1' }}';
	var href = '{!! $data['url'] !!}';

    document.getElementById('spanSeconds').innerHTML = time;

    if(document.getElementById('redirectionMsg') && href == 'javascript:history.back();' && window.history.length == 0){
        document.getElementById('redirectionMsg').innerHTML = '';
        return;
    }

	var interval = setInterval(function(){
		--time;

        document.getElementById('spanSeconds').innerHTML = time;

		if (time <= 0) {
			window.location.href = href;
			clearInterval(interval);
		}
	}, 1000);
})();
</script>
</body>
</html>
