
@if($is_wechat)

    <script type="text/javascript" src="https://res.wx.qq.com/open/js/jweixin-1.2.0.js"></script>
    <script type="text/javascript">
        // 分享内容
        var shareContent = {
            title: "{{ $share_data['title'] ?? '' }}",
            desc: "{{ $share_data['desc'] ?? '' }}",
            link: "{{ $share_data['link'] ?? '' }}",
            imgUrl: "{{ $share_data['img'] ?? '' }}",
            success: function (res) {
                // 用户确认分享后执行的回调函数
                // res {"checkResult":{"onMenuShareTimeline":true},"errMsg":"onMenuShareTimeline:ok"}
                var msg = res.errMsg;
                var jsApiname = msg.replace(':ok','');
                console.log(jsApiname);
                // shareCount(jsApiname,'{{ $share_data['link'] }}');
            }
        };

        $(function(){
            var url = location.href.split('#')[0];
            var jsConfig = {
                debug: false,
                jsApiList: [
                    'onMenuShareTimeline',
                    'onMenuShareAppMessage',
                    'onMenuShareQQ',
                    'onMenuShareQZone'
                ]
            };
            var wechat_ru_id = "{{ $wechat_ru_id ?? 0 }}";
            $.post('{{ route("wechat/api/jssdk") }}', {url: url, ru_id: wechat_ru_id}, function (res) {
                if(res.status == 200){
                    jsConfig.appId = res.data.appId;
                    jsConfig.timestamp = res.data.timestamp;
                    jsConfig.nonceStr = res.data.nonceStr;
                    jsConfig.signature = res.data.signature;
                    // 配置注入
                    wx.config(jsConfig);
                    // 事件注入
                    wx.ready(function () {
                        wx.onMenuShareTimeline(shareContent);
                        wx.onMenuShareAppMessage(shareContent);
                        wx.onMenuShareQQ(shareContent);
                        wx.onMenuShareQZone(shareContent);
                    });
                }
            }, 'json');
        })
    </script>

@endif
