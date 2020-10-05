<!doctype html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="csrf-token" content="{{ csrf_token() }}">
	<title>聊天列表</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-status-bar-style" content="black">
	<meta name="format-detection" content="telephone=no">
	<link rel="stylesheet" href="{{ asset('assets/chat/css/font-awesome.css') }}">
	<link rel="stylesheet" href="{{ asset('assets/chat/css/index.css') }}">
	<link rel="stylesheet" href="{{ asset('fonts/iconfont.css') }}">
</head>

<body>
	<div class="content-box">
		@if($message)
		<ul>
			@foreach($message as $k => $v)
			<li>
				<a href="{{ route('kefu.adminp.chat_list', ['ru_id' => $v['ru_id']]) }}">
					<div class="chat-left" style="position: relative;"><img src="{{ $v['thumb'] }}" alt="">
						@if($v['count'] > 0)
						<i class="paidui-num">
							@if($v['count'] > 99)
							99+
							@else
							{{ $v['count'] }}
							@endif
						</i>
						@endif
					</div>
					<div class="chat-center">
						<p class="chat-title clamp clamp1">{{ $v['shop_name'] }}</p>
						<span class="chat-info clamp clamp2">{{ $v['message'] }}</span>
					</div>
					<div class="chat-right"><span>{{ $v['add_time'] }}</span></div>
				</a>
			</li>
			@endforeach
		</ul>
		@else
		<div id="j-tab-con" class="tab-con">
			<div class="swiper-wrapper">
				<section class="swiper-slide store_info">
					<div class="no-div-message">
						<i class="iconfont icon-biaoqingleiben"></i>
						<p>亲，还没有聊天记录哦～！</p>
					</div>
				</section>
			</div>
		</div>
		@endif
	</div>
</body>
</html>