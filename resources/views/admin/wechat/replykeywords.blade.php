@include('admin.wechat.pageheader')
<style>
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

    /* 规则 */
    .main-info .item {
        margin-bottom: 0px;
        line-height: 15px;
    }

    .switch_info {
        padding: 15px 10px;
        margin-bottom: 20px;
    }

    .main-info .rolelist .label-t {
        width: auto;
        padding: 5px 15px;
    }

    .main-info .rolelist .label_value {
        width: 70%;
        padding: 5px 15px;
    }

    .main-info .rolelist .panel {
        margin-bottom: 0px;
    }

    .form-control {
        border: 1px solid #ddd;
    }

    #footer {
        position: static;
        bottom: 0px;
    }

    .wechat_text textarea {
        resize: none;
    }
</style>
<div class="wrapper">
    <div class="title">{{ $lang['wechat_menu'] }} - {{ $lang['autoreply_manage'] }}</div>
    <div class="content_tips">
        <div class="tabs_info">
            <ul>
                <li><a href="{{ route('admin/wechat/reply_subscribe') }}">{{ $lang['subscribe_autoreply'] }}</a></li>
                <li><a href="{{ route('admin/wechat/reply_msg') }}">{{ $lang['msg_autoreply'] }}</a></li>
                <li class="curr"><a
                            href="{{ route('admin/wechat/reply_keywords') }}">{{ $lang['keywords_autoreply'] }}</a></li>
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
            <div class="panel-body" style="margin-bottom:20px">
                <div class="fl">
                    <a href="javascript:;" class="rule_add">
                        <div class="fbutton">
                            <div>
                                <span><i class="fa fa-plus"></i>{{ $lang['rule_add'] }}</span>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
            <div class="common-content hidden rule_form">
                <div class="main-info">
                    <form action="{{ route('admin/wechat/rule_edit') }}" method="post" class="form-horizontal"
                          role="form">
                        <div class="switch_info">
                            <div class="item rolelist">
                                <div class="label-t">{{ $lang['rule_name'] }}:</div>
                                <div class="label_value">
                                    <input type='text' name='rule_name' class="text input-sm"/>
                                    <div class="notic">{{ $lang['rule_name_length_limit'] }}</div>
                                </div>
                            </div>
                            <div class="item rolelist">
                                <div class="label-t">{{ $lang['rule_keywords'] }}:</div>
                                <div class="label_value">
                                    <input type='text' name='rule_keywords' class="text input-sm"/>
                                    <div class="notic">{{ $lang['rule_keywords_notice'] }}</div>
                                </div>
                            </div>
                            <div class="item rolelist">
                                <div class="label-t">{{ $lang['rule_content'] }}:</div>
                                <div class="label_value">
                                    <div class="panel panel-default">
                                        <div class="panel-heading">
                                            <ul class="nav nav-pills" role="tablist">
                                                <li role="presentation"><a href="javascript:;"
                                                                           class="glyphicon glyphicon-pencil ectouch-fs18"
                                                                           title="{{ $lang['text'] }}" type="text"></a>
                                                </li>
                                                <li role="presentation"><a
                                                            href="{{ route('admin/wechat/auto_reply', array('type'=>'image')) }}"
                                                            class="glyphicon glyphicon-picture ectouch-fs18 fancybox fancybox.iframe"
                                                            title="{{ $lang['picture'] }}" type="image"></a></li>
                                                <li role="presentation"><a
                                                            href="{{ route('admin/wechat/auto_reply', array('type'=>'voice')) }}"
                                                            class="glyphicon glyphicon-volume-up ectouch-fs18 fancybox fancybox.iframe"
                                                            title="{{ $lang['voice'] }}" type="voice"></a></li>
                                                <li role="presentation"><a
                                                            href="{{ route('admin/wechat/auto_reply', array('type'=>'video')) }}"
                                                            class="glyphicon glyphicon-film ectouch-fs18 fancybox fancybox.iframe"
                                                            title="{{ $lang['video'] }}" type="video"></a></li>
                                                <li role="presentation"><a
                                                            href="{{ route('admin/wechat/auto_reply', array('type'=>'news')) }}"
                                                            class="glyphicon glyphicon-list-alt ectouch-fs18 fancybox fancybox.iframe"
                                                            title="{{ $lang['article_news'] }}" type="news"></a></li>
                                            </ul>
                                        </div>
                                        <div class="panel-body">
                                            <div class="wechat_text"><textarea name="content" class="form-control"
                                                                               rows="6"></textarea>
                                            </div>
                                            <div class="content_0 change_content thumbnail borderno hidden"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="item">
                                <div class="label-t">&nbsp;</div>
                                <div class="label_value info_btn">
                                    @csrf
                                    <input type="hidden" name="content_type" value="text" id="content_type_0">
                                    <input type="submit" value="{{ $lang['button_submit'] }}"
                                           class="button btn-danger bg-red"/>
                                    <input type="reset" value="{{ $lang['button_reset'] }}"
                                           class="button button_reset"/>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="common-content">
                <div class="main-info">

                    @foreach($list as $val)

                        <div class="switch_info ">
                            <div class="item rolelist">
                                <div class="label-t">{{ $lang['rule_name'] }}：{{ $val['rule_name'] ?? '' }}</div>
                            </div>
                            <div class="panel-footer info_btn of" style="margin-bottom:20px;background:#fff">
                                <a href="javascript:;" class="button bg-green edit">{{ $lang['edit'] }}</a>
                                <a href="javascript:if(confirm('{{ $lang['confirm_delete'] }}')){window.location.href='{{ route('admin/wechat/reply_del', array('id'=>$val['id'])) }}'};"
                                   class="button btn-danger bg-red">{{ $lang['drop'] }}</a>
                            </div>
                            <form action="{{ route('admin/wechat/rule_edit') }}" method="post"
                                  class="form-horizontal hidden" role="form">
                                <div class="item rolelist">
                                    <div class="label-t">{{ $lang['rule_name'] }}:</div>
                                    <div class="label_value">
                                        <input type="text" name="rule_name" value="{{ $val['rule_name'] ?? '' }}"
                                               class="text input-sm"/>
                                        <div class="notic">{{ $lang['rule_name_length_limit'] }}</div>
                                    </div>
                                </div>
                                <div class="item rolelist">
                                    <div class="label-t">{{ $lang['rule_keywords'] }}:</div>
                                    <div class="label_value">
                                        <input type="text" name="rule_keywords"
                                               value="{{ $val['rule_keywords_string'] ?? '' }}" class="text input-sm"/>
                                        <div class="notic">{{ $lang['rule_keywords_notice'] }}</div>
                                    </div>
                                </div>
                                <div class="item rolelist">
                                    <div class="label-t">{{ $lang['rule_content'] }}:</div>
                                    <div class="label_value">
                                        <div class="panel panel-default">
                                            <div class="panel-heading">
                                                <ul class="nav nav-pills" role="tablist">
                                                    <li role="presentation"><a href="javascript:;"
                                                                               class="glyphicon glyphicon-pencil ectouch-fs18"
                                                                               title="{{ $lang['text'] }}"
                                                                               type="text"></a></li>
                                                    <li role="presentation"><a
                                                                href="{{ route('admin/wechat/auto_reply', array('type'=>'image', 'reply_id' => $val['id'])) }}"
                                                                class="glyphicon glyphicon-picture ectouch-fs18 fancybox fancybox.iframe"
                                                                title="{{ $lang['picture'] }}" type="image"></a></li>
                                                    <li role="presentation"><a
                                                                href="{{ route('admin/wechat/auto_reply', array('type'=>'voice', 'reply_id' => $val['id'])) }}"
                                                                class="glyphicon glyphicon-volume-up ectouch-fs18 fancybox fancybox.iframe"
                                                                title="{{ $lang['voice'] }}" type="voice"></a></li>
                                                    <li role="presentation"><a
                                                                href="{{ route('admin/wechat/auto_reply', array('type'=>'video', 'reply_id' => $val['id'])) }}"
                                                                class="glyphicon glyphicon-film ectouch-fs18 fancybox fancybox.iframe"
                                                                title="{{ $lang['video'] }}" type="video"></a></li>
                                                    <li role="presentation"><a
                                                                href="{{ route('admin/wechat/auto_reply', array('type'=>'news', 'reply_id' => $val['id'])) }}"
                                                                class="glyphicon glyphicon-list-alt ectouch-fs18 fancybox fancybox.iframe"
                                                                title="{{ $lang['article_news'] }}" type="news"></a>
                                                    </li>
                                                </ul>
                                            </div>
                                            <div class="panel-body">
                                                <div class="
@if( (isset($val['medias']) && $val['medias']) || (isset($val['media']) && $val['media']))
                                                        hidden
                                                        @endif
                                                        wechat_text">
                                                    <textarea name="content" class="form-control"
                                                              rows="6">{!! $val['content'] ?? '' !!}</textarea>
                                                </div>
                                                <div class="content_{{ $val['id'] ?? '0' }}  change_content  thumbnail borderno
@if(empty($val['medias']) && empty($val['media']) )
                                                        hidden
                                                        @endif
                                                        ">
                                                    <input type="hidden" name="media_id"
                                                           value="{{ $val['media_id'] ?? '0' }}"/>

                                                @if(isset($val['medias']) && $val['medias'])
                                                    <!-- 多图文 -->
                                                        @foreach($val['medias'] as $k=>$v)

                                                            @if($k == 0)

                                                                <div class="article article_keywords">
                                                                    <p></p>
                                                                    <div class="cover"><img
                                                                                src="{{ $v['file'] ?? '' }}"/><span>{{ $v['title'] ?? '' }}</span>
                                                                    </div>
                                                                </div>

                                                            @else

                                                                <div class="article_list">
                                                                    <span>{{ $v['title'] ?? '' }}</span>
                                                                    <img src="{{ $v['file'] ?? '' }}" width="78"
                                                                         height="78" class="pull-right"/>
                                                                </div>

                                                            @endif

                                                        @endforeach

                                                    @elseif(isset($val['media']) && $val['media'])
                                                    <!-- 单图文，图片，语音，视频 -->

                                                        @if(isset($val['media']['type']) && $val['media']['type'] == 'news' && $val['reply_type'] == 'news')
                                                            <div class="article article_keywords">
                                                                <h4 class="keywords_name">{{ $val['media']['title'] }}</h4>
                                                                <p class="keywords_time">{{ date('Y年m月d日', $val['media']['add_time']) }}</p>
                                                                <div class="cover"><img
                                                                            src="{{ $val['media']['file'] }}"/></div>
                                                                <p>{{ $val['media']['content'] }}</p>
                                                            </div>

                                                        @elseif(isset($val['media']['type']) && $val['media']['type'] == 'voice')

                                                            <img src="{{ asset('img/voice.png') }}"/>
                                                            <span>{{ $val['media']['file_name'] }}</span>

                                                        @elseif(isset($val['media']['type']) && $val['media']['type'] == 'video')

                                                            <img src="{{ asset('img/video.png') }}"/>
                                                            <span>{{ $val['media']['file_name'] }}</span>

                                                        @else

                                                            <img src="{{ $val['media']['file'] }}"
                                                                 style="max-width:300px;"/>

                                                        @endif

                                                    @endif

                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="item">
                                    <div class="label-t">&nbsp;</div>
                                    <div class="label_value info_btn">
                                        @csrf
                                        <input type="hidden" name="content_type" value="{{ $val['reply_type'] }}"
                                               id="content_type_{{ $val['id'] ?? '0' }}"/>
                                        <input type="hidden" name="id" value="{{ $val['id'] ?? '' }}"/>
                                        <input type="submit" value="{{ $lang['button_submit'] }}"
                                               class="button btn-danger bg-red"/>
                                        <input type="reset" value="{{ $lang['button_reset'] }}"
                                               class="button button_reset"/>
                                    </div>
                                </div>
                            </form>
                        </div>

                    @endforeach

                </div>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
    $(function () {
        //添加规则显示隐藏
        $(".rule_add").click(function () {
            if ($(".rule_form").hasClass("hidden")) {
                $(".rule_form").removeClass("hidden");
            } else {
                $(".rule_form").addClass("hidden");
            }
        });
        //选择内容显示以及类型更改
        $(".nav-pills li").click(function () {
            var type = $(this).find("a").attr("type");
            var tab = $(this).parent().parent(".panel-heading").siblings(".panel-body");
            if (type == "text") {
                tab.find(".change_content").addClass("hidden");
                tab.find(".wechat_text").removeClass("hidden");
                $("input[name=content_type]").val(type);
            }
        });
        //规则显示
        $(".rule_title").click(function () {
            var obj = $(this).siblings("ul.view");
            if (obj.hasClass("hidden")) {
                obj.removeClass("hidden");
                $(this).addClass("dropup");
            } else {
                obj.addClass("hidden");
                $(this).removeClass("dropup");
            }
        });
        //修改规则显示
        $(".edit").click(function () {
            var obj = $(this).parent().siblings("form");
            if (obj.hasClass("hidden")) {
                obj.removeClass("hidden");
            } else {
                obj.addClass("hidden");
            }
        });
    })
</script>

@include('admin.wechat.pagefooter')
