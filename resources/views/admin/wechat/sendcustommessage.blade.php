@include('admin.wechat.pageheader')
<style>
    .wechat-message .t-message-box {
        overflow: hidden;
        height: auto;
        line-height: auto;
        padding: 5px 8px;
        position: relative;
    }

    .t-message-box .his-cont {
        float: left;
        width: 80%;
        line-height: 1.7;
        color: #888;
    }

    .t-message-box .his-messages {
        float: left;
        width: 20%;
    }

    .t-message-box .his-messages a {
        position: absolute;
        right: 10px;
        top: 50%;
        margin-top: -10px;
    }

    .his-content {
        border: 0;
        border-top: 1px solid #ddd;
        margin: 20px 0 0 0;
        border-radius: 0;
        -webkit-box-shadow: 0 1px 1px rgba(0, 0, 0, 0);
        box-shadow: 0 1px 1px rgba(0, 0, 0, 0);
    }

    /*选择素材展示*/
    .article {
        border: none;
    }

    .cover {
        height: 160px;
        position: relative;
        margin-bottom: 5px;
        overflow: hidden;
    }

    .article .cover img {
        width: 160px;
        height: auto;
    }

    .article span {
        height: 40px;
        line-height: 40px;
        display: block;
        z-index: 5;
        position: absolute;
        width: 100%;
        bottom: 0px;
        color: #FFF;
        padding: 0 10px;
        background-color: rgba(0, 0, 0, 0.6)
    }

    .article_list {
        padding: 10px 0;
        border-bottom: 1px solid #ddd;
        border-top: 0;
        overflow: hidden;
    }

    .article_list span {
        font-size: 16px;
        color: #333;
    }

    .panel-body {
        background: #f5f5f5;
    }

    .thumbnail {
        padding: 15px;
        -webkit-box-shadow: 0 0 1px rgba(100, 100, 100, 0.8);
        box-shadow: 0 0 1px rgba(100, 100, 100, 0.8);
    }

    .article h4 {
        font-size: 15px;
        color: #333;
    }

    .article p {
        padding: 6px 0;
    }

    .wechat_text textarea {
        resize: none;
    }

    #ul {
        overflow: hidden;
        height: 300px;
    }
</style>

<div class="panel panel-default wechat-message" style="margin:0;overflow:hidden">
    <div class="panel-heading header-bg" style="border-bottom:none;">
        <img src="{{ $info['headimgurl'] ?? '' }}">
        <span>{{ $info['nickname'] ?? '' }}</span>
    </div>
    <div class="wechat-show">
        <div class="" id="ul">
            <ul id="message_list">

                @foreach($list as $val)

                    @if(isset($val['is_wechat_admin']) && $val['is_wechat_admin'] == 1)

                        <li class="talk-right">

                    @else

                        <li class="talk-left">

                            @endif

                            <div class="user">

                                @if(isset($val['is_wechat_admin']) && $val['is_wechat_admin'] == 0)
                                    <img src="{{ $val['headimgurl'] ?? '' }}">
                                @else
                                    <img src="{{ $val['wechat_headimgurl'] ?? '' }}">
                                @endif

                                <div class="cite">{{ $val['nickname'] ?? '' }}<i>{{ $val['send_time'] ?? '' }}</i></div>
                                <div class="speak-text
                                    @if($val['is_wechat_admin'] == 0)
                                    green
                                    @endif
                                    ">{{ $val['msg'] }}</div>
                            </div>
                        </li>

                        @endforeach

            </ul>
        </div>
    </div>
    <div class="header-bg t-message-box ">
        <span class="his-cont">{{ $lang['sub_help1'] ?? '' }}</span>
        <span class="his-messages"><a class="history-mass fr" href="{{ route('admin/wechat/custom_message_list', array('uid'=>$info['uid'])) }}" title="{{ $lang['custom_message_list'] }}">{{ $lang['custom_message_list'] }}</a></span>
    </div>

    <form action="{{ route('admin/wechat/send_custom_message') }}" method="post" class="form-horizontal" role="form" onsubmit="return false;">
        <div class="panel panel-default his-content of">
            <div class="panel-heading">
                <ul class="nav nav-pills" role="tablist">
                    <li role="presentation"><a href="javascript:;" class="glyphicon glyphicon-pencil ectouch-fs18" title="{{ $lang['text'] }}" type="text"></a></li>
                    <li role="presentation"><a href="{{ route('admin/wechat/auto_reply', array('type'=>'image')) }}" class="glyphicon glyphicon-picture ectouch-fs18 fancybox80 fancybox.iframe" title="{{ $lang['picture'] }}" type="image"></a></li>
                    <li role="presentation"><a href="{{ route('admin/wechat/auto_reply', array('type'=>'voice')) }}" class="glyphicon glyphicon-volume-up ectouch-fs18 fancybox80 fancybox.iframe" title="{{ $lang['voice'] }}" type="voice"></a></li>
                    <li role="presentation"><a href="{{ route('admin/wechat/auto_reply', array('type'=>'video')) }}" class="glyphicon glyphicon-film ectouch-fs18 fancybox80 fancybox.iframe" title="{{ $lang['video'] }}" type="video"></a></li>
                    <li role="presentation"><a href="{{ route('admin/wechat/auto_reply', array('type'=>'news')) }}" class="glyphicon glyphicon-list-alt ectouch-fs18 fancybox80 fancybox.iframe" title="{{ $lang['article_news'] }}" type="news"></a></li>

                </ul>
            </div>
            <div class="panel-body" style="padding:5px;">
                <div class="wechat_text"><textarea name="data[msg]" placeholder="{{ $lang['message_content'] }}" class="wechat-content" style="padding: 6px;height:100px;"></textarea></div>
                <div class="content_0  change_content  thumbnail borderno hidden">

                </div>
            </div>
        </div>
        <div class="col-md-4 info_btn pull-right" style="padding:8px 0">
            @csrf
            <input type="hidden" name="msgtype" value="text" id="content_type_0"/>
            <input type="hidden" name="data[uid]" value="{{ $info['uid'] ?? '' }}"/>
            <input type="hidden" name="openid" value="{{ $info['openid'] ?? '' }}"/>
            <input type="submit" value="{{ $lang['button_send'] }}" class="btn button btn-danger bg-red"/>
            <input type="reset" value="{{ $lang['button_reset'] }}" class="button button_reset fn"/>
        </div>
    </form>

</div>
<script src="{{ asset('assets/wechat/wall/js/jquery.scrollTo.min.js') }}"></script>
<script type="text/javascript">

    var interval_time = 15000; // 间隔时间 s
    // 消息
    var inttime = window.setInterval("refresh()", interval_time);

    $(function () {

        //选择内容显示以及类型更改
        $(".nav-pills li").click(function () {
            let type = $(this).find("a").attr("type");
            let tab = $(this).parent().parent(".panel-heading").siblings(".panel-body");
            if (type == "text") {
                tab.find(".change_content").addClass("hidden");
                tab.find(".wechat_text").removeClass("hidden");
                $("input[name=msgtype]").val(type);
            }
        });

        $("#ul").animate({scrollTop: $("#ul ul").height()}, 800);

        $(".form-horizontal").submit(function () {
            let ajax_data = $(".form-horizontal").serialize();
            $.post("{{ route('admin/wechat/send_custom_message') }}", ajax_data, function (data) {
                layer.msg(data.msg);
                if (data.status == 0) {
                    $('textarea[name="data[msg]"]').val("");
                }
                return false;
            }, 'json');

            refresh()
        });

        //弹出框
        $(".fancybox80").fancybox({
            width: '80%',
            height: '80%',
            closeBtn: true,
            title: ''
        });


    });

    var start = '{{ $last_msg_id ?? 0 }}';
    var num = 6;
    var uid = '{{ request()->get('uid') }}';

    var req = 0;

    function refresh() {

        $.post("{{ route('admin/wechat/select_custom_message') }}", {
            uid: uid,
            start: start,
            num: num,
            req: req
        }, function (result) {
            //console.log(result)
            if (result.status == 1 && result.data.length > 0) {
                $.each(result.data, function (index, e) {
                    if (e.is_wechat_admin == 1) {
                        $("#message_list").append('<li class="talk-right"><div class="user"><img src="' + e.wechat_headimgurl + '"><div class="cite">' + e.nickname + '<i>' + e.send_time + '</i></div><div class="speak-text ">' + e.msg + '</div></div></li>');
                    } else {
                        $("#message_list").append('<li class="talk-left"><div class="user"><img src="' + e.headimgurl + '"><div class="cite">' + e.nickname + '<i>' + e.send_time + '</i></div><div class="speak-text green">' + e.msg + '</div></div></li>');
                    }

                    //添加跳转锚点
                    $("#ul").animate({scrollTop: $("#ul ul").height()}, 800);

                });
                start = parseInt(start) + parseInt(result.data.length);
            }

            req++;

        }, 'json');

    }

</script>
