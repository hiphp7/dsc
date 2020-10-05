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
    @if ($param == 'admin')
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/admin/css/main.css') }} " />
    @elseif ($param == 'seller')
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/seller/css/style.css') }} " />
    @elseif ($param == 'suppliers')
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/suppliers/css/style.css') }} " />
    @else
	<link rel="stylesheet" type="text/css" href="{{ asset('themes/ecmoban_dsc2017/css/base.css') }} " />
    @endif
    <script type="text/javascript" src="{{ asset('js/jquery-1.12.4.min.js') }}"></script>
</head>
<body>
<div class="filter-words-param">
	@if ($param == 'admin')
	<div class="fh_message">
        <div class="fr_content">
            <div class="success_img">
                <img src="{{ asset('assets/admin/images/error.jpg') }}">
            </div>
            <div class="success_right">
                <h3 class="title">{{ lang('admin/filter_words.error_easy_notice') }}{{ $msg }}</h3>
                <span class="ts" id="redirectionMsg"> {{ lang('admin/filter_words.auto_redirection_begin') }}<span id="spanSeconds">3</span> {{ lang('admin/filter_words.auto_redirection_end') }}</span>
                <ul class="msg-link">
                    <li><a href="javascript:;" onclick="updatelogs()">{{ lang('admin/filter_words.return_url') }}</a></li>
                </ul>
            </div>
        </div>
    </div>
	@elseif ($param == 'seller')
	<div style="text-align: center;">
		<div class="fh_message">
		<div class="fr_content">
			<div class="img">
				<i class="fh_icon confirm"></i>
			</div>
			<h3 class="confirm">{{ lang('admin/filter_words.error_easy_notice') }}{{ $msg }}</h3>
			<span class="ts" id="redirectionMsg"> {{ lang('admin/filter_words.auto_redirection_begin') }}<span id="spanSeconds">3</span> {{ lang('admin/filter_words.auto_redirection_end') }}</span>
			<ul class="msg-link" style="padding-left: 0;">
				<li><a href="javascript:;" onclick="updatelogs()">{{ lang('admin/filter_words.return_url') }}</a></li>
			</ul>
			</div>
		</div>
	</div>
	@elseif ($param == 'suppliers')
	<div style="text-align: center;">
		<div class="fh_message">
		<div class="fr_content">
			<div class="img">
				<i class="fh_icon confirm"></i>
			</div>
			<h3 class="confirm">{{ lang('admin/filter_words.error_easy_notice') }}{{ $msg }}</h3>
			<span class="ts" id="redirectionMsg"> {{ lang('admin/filter_words.auto_redirection_begin') }}<span id="spanSeconds">3</span> {{ lang('admin/filter_words.auto_redirection_end') }}</span>
			<ul class="msg-link" style="padding-left: 0;">
				<li><a href="javascript:;" onclick="updatelogs()">{{ lang('admin/filter_words.return_url') }}</a></li>
			</ul>
			</div>
		</div>
	</div>
	@else
	<div class="w w1200">
	    <div class="no_records message_records">
	        <i class="no_icon_two"></i>
	        <div class="no_info no_info_line w500">
	            <h3>{{ lang('admin/filter_words.error_easy_notice') }}{{ $msg }}</h3>
	            <div class="no_btn">
	            	<a href="javascript:;" class="btn sc-redBg-btn" onclick="updatelogs()">{{ lang('admin/filter_words.return_url') }}</a>
	            </div>
	        </div>
	    </div>
	</div>
	@endif
</div>
<script type="text/javascript">
var seconds = 3;
var defaultUrl = "{$default_url}";

$(function(){
	if(document.getElementById('redirectionMsg') && defaultUrl == 'javascript:history.go(-1)' && window.history.length == 0){
		document.getElementById('redirectionMsg').innerHTML = '';
		return;
	}

	window.setInterval(redirection, 1000);
});

function redirection(){
	if (seconds <= 0){
		window.clearInterval();
		return;
	}

	seconds --;
	document.getElementById('spanSeconds').innerHTML = seconds;

	if(seconds == 0){
		updatelogs();
	}
}
function updatelogs(){
	$.ajax({
		type:"get",
		url:"filter/updatelogs",
		data:'id=' + '{{ $log_id }}',
		dataType: 'json',
		async : false, //设置为同步操作就可以给全局变量赋值成功
		success:function(result){
			window.history.go(-1);
		}
	});
}
</script>
</body>
</html>
