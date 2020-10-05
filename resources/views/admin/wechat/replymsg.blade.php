@include('admin.wechat.pageheader')
<div class="wrapper">
    <div class="title">{{ $lang['wechat_menu'] }} - {{ $lang['autoreply_manage'] }}</div>
    <div class="content_tips">
        <div class="tabs_info">
            <ul>
                <li><a href="{{ route('admin/wechat/reply_subscribe') }}">{{ $lang['subscribe_autoreply'] }}</a></li>
                <li class="curr"><a href="{{ route('admin/wechat/reply_msg') }}">{{ $lang['msg_autoreply'] }}</a></li>
                <li><a href="{{ route('admin/wechat/reply_keywords') }}">{{ $lang['keywords_autoreply'] }}</a></li>
            </ul>
        </div>
        <div class="explanation" id="explanation">
            <div class="ex_tit"><i class="sc_icon"></i><h4>{{ $lang['operating_hints'] }}</h4><span id="explanationZoom"
                                                                                                    title="{{ $lang['fold_tips'] }}"></span>
            </div>
            <ul>
                @if(isset($lang['autoreply_manage_tips']) && !empty($lang['autoreply_manage_tips']))

                    @foreach($lang['autoreply_manage_tips'] as $v)
                        <li>{{ $v }}</li>
                    @endforeach

                @endif
            </ul>
        </div>
        <div class="flexilist">
            <div class="main-info">
                <form action="{{ route('admin/wechat/reply_msg') }}" method="post">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <ul class="nav nav-pills" role="tablist">
                                <li role="presentation"><a href="javascript:;"
                                                           class="glyphicon glyphicon-pencil ectouch-fs18"
                                                           title="{{ $lang['text'] }}"></a></li>
                                <li role="presentation"><a
                                            href="{{ route('admin/wechat/auto_reply', array('type'=>'image')) }}"
                                            class="glyphicon glyphicon-picture ectouch-fs18 fancybox fancybox.iframe"
                                            title="{{ $lang['picture'] }}"></a></li>
                                <li role="presentation"><a
                                            href="{{ route('admin/wechat/auto_reply', array('type'=>'voice')) }}"
                                            class="glyphicon glyphicon-volume-up ectouch-fs18 fancybox fancybox.iframe"
                                            title="{{ $lang['voice'] }}"></a></li>
                                <li role="presentation"><a
                                            href="{{ route('admin/wechat/auto_reply', array('type'=>'video')) }}"
                                            class="glyphicon glyphicon-film ectouch-fs18 fancybox fancybox.iframe"
                                            title="{{ $lang['video'] }}"></a></li>
                            </ul>
                        </div>
                        <div class="panel-body" style="padding:0;">
                            <div
                                    @if(isset($msg['media_id']) && $msg['media_id'])
                                    class="hidden"
                                    @endif
                            ><textarea name="content" class="form-control" rows="6"
                                       style="border:none;">{!! $msg['content'] ?? '' !!}</textarea>
                            </div>
                            <div class="
@if(empty($msg) || (isset($msg['content']) && $msg['content']))
                                    hidden
                                    @endif
                                    col-xs-6 col-md-3 thumbnail content_0" style="border:none;">

                                @if(isset($msg['media']) && $msg['media'])


                                    @if(isset($msg['media']['type']) && $msg['media']['type'] == 'voice')

                                        <input type='hidden' name='media_id' value="{{ $msg['media_id'] }}"><img
                                                src="{{ asset('img/voice.png') }}" class='img-rounded'/><span
                                                class='help-block'>{{ $msg['media']['file_name'] }}</span>

                                    @elseif(isset($msg['media']['type']) && $msg['media']['type'] == 'video')

                                        <input type='hidden' name='media_id' value="{{ $msg['media_id'] }}"><img
                                                src="{{ asset('img/video.png') }}" class='img-rounded'/><span
                                                class='help-block'>{{ $msg['media']['file_name'] }}</span>

                                    @else

                                        <input type='hidden' name='media_id' value="{{ $msg['media_id'] }}"><img
                                                src="{{ $msg['media']['file'] }}" class='img-rounded'/>

                                    @endif


                                @endif

                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="info_btn">
                            @csrf
                            <input type="hidden" name="content_type" value="text" id="content_type_0"/>
                            <input type="hidden" name="id" value="{{ $msg['id'] ?? '' }}"/>
                            <input type="submit" class="button btn-danger bg-red" name="submit"
                                   value="{{ $lang['button_save'] }}"/>
                            <input type="reset" class="button button_reset" name="reset"
                                   value="{{ $lang['button_reset'] }}"/>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
    $(function () {
        $(".nav-pills li").click(function () {
            var index = $(this).index();
            var tab = $(this).parent().parent(".panel-heading").siblings(".panel-body");
            if (index == 0) {
                tab.find("div").addClass("hidden");
                tab.find("div").eq(index).removeClass("hidden");
                $("input[name=content_type]").val("text");
            }
        });
    })
</script>

@include('admin.wechat.pagefooter')
