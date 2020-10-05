<!DOCTYPE HTML>
<html>
<head>
	<meta name="csrf-token" content="{{ csrf_token() }}">
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
	<meta name="apple-mobile-web-app-capable" content="yes"/>
	<meta name="apple-mobile-web-app-status-bar-style" content="black"/>
	<meta name="format-detection" content="telephone=no"/>
	<title>{{ $data['media']['title'] ?? '' }}</title>
	<link href="{{ asset('assets/wechat/sign/css/public.css') }}" rel="stylesheet">
	<link href="{{ asset('assets/wechat/sign/css/sign.css') }}" rel="stylesheet">
	<script type="text/javascript" src="{{ asset('assets/mobile/vendor/common/jquery.min.js') }}"></script>
	<script type="text/javascript" src="{{ asset('assets/mobile/vendor/layer/layer.js') }}"></script>

	<script type="text/javascript" src="{{ asset('assets/wechat/sign/js/calendar.js') }}"></script>

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
<div class="top flex flex-align-end flex-pack-center flex-warp">
	<div class="out-1 flex flex-align-center flex-pack-center signIn" >
		<div class="out-2 flex flex-align-center flex-pack-center">
			<div class="signBtn">
				<strong id="sign-txt">{{ $lang['sign'] }}</strong>
				{{--已签到几天--}}
				<span><em id="sign-count">{{ $sign_day ?? 0 }}</em>{{ $lang['day'] }}</span>
			</div>
		</div>
	</div>
</div>


<div class="tips
	@if (isset($can_sign) && $can_sign == 1)
		show
	@else
		hide
	@endif
		">{{ $lang['today_is_signed'] }}， {{ $lang['continuous'] }} {{ $continue_day ?? 0 }} {{ $lang['day'] }} </div>


<div class="Calendar">
	<div id="toyear" class="flex flex-pack-center">
		{{--<div id="idCalendarPre">&lt;</div>--}}
		<div class="year-month">
			<span id="idCalendarYear">2018</span>{{ $lang['year'] }}<span id="idCalendarMonth">6</span>{{ $lang['month'] }}
		</div>
		{{--<div id="idCalendarNext">&gt;</div>--}}
	</div>
	<table border="1px" cellpadding="0" cellspacing="0">
		<thead>
		<tr class="tou">
			<td>{{ $lang['week_0'] }}</td>
			<td>{{ $lang['week_1'] }}</td>
			<td>{{ $lang['week_2'] }}</td>
			<td>{{ $lang['week_3'] }}</td>
			<td>{{ $lang['week_4'] }}</td>
			<td>{{ $lang['week_5'] }}</td>
			<td>{{ $lang['week_6'] }}</td>
		</tr>
		</thead>
		<tbody id="idCalendar">
		</tbody>
	</table>
</div>

{{--<p><a href="{{ $data['sign_list_url'] ?? '' }}" >历史签到记录</a></p>--}}

<script type="text/javascript">
    $(function () {
        var ISWeixin = !!navigator.userAgent.match(/MicroMessenger/i); //wp手机无法判断
//        if (!ISWeixin) {
//            var rd_url = location.href.split('#')[0];  // remove hash
//            var oauth_url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=APPID&redirect_uri=' + encodeURIComponent(rd_url) + '&response_type=code&scope=snsapi_base&state=STATE#wechat_redirect';
//            location.href = oauth_url;
//            return false;
//        }

        var isSign = false;
        var myday = "{{ $myday_str ?? [] }}";//已签到的时间戳数组
		// 已签到时间戳
//		 myday[0] = "1554086288";
//		 myday[1] = "1554172688";
//		 myday[2] = "1554259088";

		myday = myday.length == 0 ? new Array() : JSON.parse(myday);

        var cale = new Calendar("idCalendar", {
            qdDay: myday,
            onToday: function(o) {
                o.className = "onToday";
            },
            onSignIn: function (){
                $$("sign-txt").innerHTML = '{{ $lang['is_signed'] }}';
            },
            onFinish: function() {
                $$("sign-count").innerHTML = myday.length //已签到次数
                $$("idCalendarYear").innerHTML = this.Year;
                $$("idCalendarMonth").innerHTML = this.Month; //表头年份
            }
        });

        /**
        $$("idCalendarPre").onclick = function() {
            cale.PreMonth();
        }
        $$("idCalendarNext").onclick = function() {
            cale.NextMonth();
        }**/

        //添加今天签到
        $('.signIn').bind('click', function() {

            if(isSign == false) {
                var res = cale.SignIn();
                if(res == '1') {
                    // 请求服务
                    var url = "{{ route('wechat/plugin_action', ['name' => 'sign']) }}";

                    $.post(url, {act:'do'}, function (data) {

                        console.log(data)

                        layer.msg(data.msg)

                        if (data.status == 0) {
                            $$("sign-txt").innerHTML = '{{ $lang['is_signed'] }}';
                            $$("sign-count").innerHTML = parseInt($$("sign-count").innerHTML) + 1;

                            // 显示今天已签到
                            if ($(".tips").hasClass("hide")) {
                                $(".tips").removeClass("hide");
                            } else {
                                $(".tips").addClass("show");
                            }

                            isSign = true;
                        } else {
                            reload();
						}

                        return false;

                    }, 'json');

                } else if (res == '2'){
                    $$("sign-txt").innerHTML = '{{ $lang['is_signed'] }}';
                    layer.msg('{{ $lang['continuous'] }}')
                }
            } else {
                layer.msg('{{ $lang['continuous'] }}')
            }

		});


    });

    // 兼容微信安卓下 不刷新的问题
    function reload() {
        var url = location.href;
        var name = 'random';
        // 过滤重复参数
        var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)");
        var r = window.location.search.substr(1).match(reg);
        if (r != null) {
            url = url.replace(r[0], '');
        }
        location.href = url + "&random=" + Math.floor(Math.random() * 100000000);
    }
</script>
</body>
</html>