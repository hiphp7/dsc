@include('seller.base.seller_pageheader')

@include('seller.base.seller_nave_header')

<div class="ecsc-layout">
    <div class="site wrapper">
        @include('seller.base.seller_menu_left')

        <div class="ecsc-layout-right">
            <div class="main-content" id="mainContent">
                @include('seller.base.seller_nave_header_title')
                <div class="wrapper-right of">
                    <div class="tabmenu">
                        <ul class="tab">
                            <li class="active"><a href="{{ route('seller/wechat/reply_subscribe') }}">{{ $lang['subscribe_autoreply'] }}</a></li>
                            <li><a href="{{ route('seller/wechat/reply_msg') }}">{{ $lang['msg_autoreply'] }}</a></li>
                            <li><a href="{{ route('seller/wechat/reply_keywords') }}">{{ $lang['keywords_autoreply'] }}</a></li>
                        </ul>
                    </div>
                    <div class="explanation" id="explanation">
                        <div class="ex_tit"><i class="sc_icon"></i><h4>{{ $lang['operating_hints'] }}</h4></div>
                        <ul>
                            @if(isset($lang['autoreply_manage_tips']) && !empty($lang['autoreply_manage_tips']))

                                @foreach($lang['autoreply_manage_tips'] as $v)
                                    <li>{{ $v }}</li>
                                @endforeach

                            @endif
                        </ul>
                    </div>
                    <div class="tab-content">
                        <div class="tab-pane active">
                            <div class="wrapper-list mt20">
                                <form action="{{ route('seller/wechat/reply_subscribe') }}" method="post">
                                    <div class="account-setting">
                                        <div class="panel panel-default">
                                            <div class="panel-heading">
                                                <ul class="nav nav-pills" role="tablist">
                                                    <li role="presentation"><a href="javascript:;"
                                                                               title="{{ $lang['text'] }}" class="f-14"><i
                                                                    class="fa fa-pencil"></i><span>{{ $lang['text'] }}</span></a></li>
                                                    <li role="presentation"><a
                                                                href="{{ route('seller/wechat/auto_reply', array('type'=>'image')) }}"
                                                                class="fancybox fancybox.iframe f-14"
                                                                title="{{ $lang['picture'] }}"><i
                                                                    class="fa fa-file-image-o"></i><span>{{ $lang['picture'] }}</span></a>
                                                    </li>
                                                    <li role="presentation"><a
                                                                href="{{ route('seller/wechat/auto_reply', array('type'=>'voice')) }}"
                                                                class="fancybox fancybox.iframe f-14"
                                                                title="{{ $lang['voice'] }}"><i
                                                                    class="fa fa-volume-up"></i><span>{{ $lang['voice'] }}</span></a></li>
                                                    <li role="presentation"><a
                                                                href="{{ route('seller/wechat/auto_reply', array('type'=>'video')) }}"
                                                                class="f-14 fancybox fancybox.iframe"
                                                                title="{{ $lang['video'] }}"><i
                                                                    class="fa fa-film"></i><span>{{ $lang['video'] }}</span></a></li>
                                                </ul>
                                            </div>
                                            <div class="panel-body">
                                                <div
                                                        @if(isset($subscribe['media_id']) && $subscribe['media_id'])
                                                        class="hidden"
                                                        @endif
                                                >
                                                    <textarea name="content" rows="6" class="form-control"
                                                              style="border:none;">{!! $subscribe['content'] ?? '' !!}</textarea>
                                                </div>
                                                <div class="
@if(empty($subscribe) || (isset($subscribe['content']) && $subscribe['content']))
                                                        hidden
                                                        @endif
                                                        col-xs-6 col-md-3 thumbnail content_0" style="border:none;">

                                                    @if(isset($subscribe['media']) && $subscribe['media'])


                                                        @if(isset($subscribe['media']['type']) && $subscribe['media']['type'] == 'voice')

                                                            <input type='hidden' name='media_id'
                                                                   value="{{ $subscribe['media_id'] }}"><img
                                                                    src="{{ asset('img/voice.png') }}"
                                                                    class='img-rounded'/><span
                                                                    class='help-block'>{{ $subscribe['media']['file_name'] }}</span>

                                                        @elseif(isset($subscribe['media']['type']) && $subscribe['media']['type'] == 'video')

                                                            <input type='hidden' name='media_id'
                                                                   value="{{ $subscribe['media_id'] }}"><img
                                                                    src="{{ asset('img/video.png') }}"
                                                                    class='img-rounded'/><span
                                                                    class='help-block'>{{ $subscribe['media']['file_name'] }}</span>

                                                        @else

                                                            <input type='hidden' name='media_id'
                                                                   value="{{ $subscribe['media_id'] }}"><img
                                                                    src="{{ $subscribe['media']['file'] }}"
                                                                    class='img-rounded'/>

                                                        @endif

                                                    @endif

                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="bottom mt20">
                                        @csrf
                                        <input type="hidden" name="content_type" value="text" id="content_type_0"/>
                                        <input type="hidden" name="id" value="{{ $subscribe['id'] ?? '' }}"/>
                                        <input type="submit" class="sc-btn sc-blueBg-btn btn35" name="submit"
                                               value="{{ $lang['button_save'] }}"/>
                                        <input type="reset" class="sc-btn sc-blue-btn btn35" name="reset"
                                               value="{{ $lang['button_reset'] }}"/>
                                    </div>
                                </form>
                            </div>

                        </div>
                    </div>
                </div>
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

@include('seller.base.seller_pagefooter')
