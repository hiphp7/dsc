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
                            <li>
                                <a href="{{ route('seller/wechat/reply_subscribe') }}">{{ $lang['subscribe_autoreply'] }}</a>
                            </li>
                            <li><a href="{{ route('seller/wechat/reply_msg') }}">{{ $lang['msg_autoreply'] }}</a></li>
                            <li class="active"><a
                                        href="{{ route('seller/wechat/reply_keywords') }}">{{ $lang['keywords_autoreply'] }}</a>
                            </li>
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
                                <!-- 添加规则 -->
                                <div class="bottom of"><a href="javascript:;" class="sc-btn sc-blue-btn add-gui "><i
                                                class="fa fa-plus"></i>{{ $lang['rule_add'] }}</a></div>
                                <div class="account-setting" style="display:none">
                                    <div class="ecsc-form-goods ">
                                        <form action="{{ route('seller/wechat/rule_edit') }}" method="post"
                                              class="form-horizontal" role="form">
                                            <dl>
                                                <dt>{{ $lang['rule_name'] }}：</dt>
                                                <dd>
                                                    <input type="text" name="rule_name" value="" class="text input-sm">
                                                    <div class="form_prompt"></div>
                                                    <div class="notic">{{ $lang['rule_name_length_limit'] }}</div>
                                                </dd>
                                            </dl>
                                            <dl>
                                                <dt>{{ $lang['rule_keywords'] }}：</dt>
                                                <dd>
                                                    <input type="text" name="rule_keywords" value=""
                                                           class="text input-sm">
                                                    <div class="form_prompt"></div>
                                                    <div class="notic">{{ $lang['rule_keywords_notice'] }}</div>
                                                </dd>
                                            </dl>
                                            <dl>
                                                <dt>{{ $lang['rule_content'] }}：</dt>
                                                <dd>
                                                    <div class="pull-left">
                                                        <div class="account-setting">
                                                            <div class="panel panel-default">
                                                                <div class="panel-heading">
                                                                    <ul class="nav nav-pills" role="tablist">
                                                                        <li role="presentation" class="guize"><a
                                                                                    href="javascript:;"
                                                                                    title="{{ $lang['text'] }}"
                                                                                    class="f-14" type="text"><i
                                                                                        class="fa fa-pencil"></i><span>{{ $lang['text'] }}</span></a>
                                                                        </li>
                                                                        <li role="presentation" class="guize"><a
                                                                                    href="{{ route('seller/wechat/auto_reply', array('type'=>'image')) }}"
                                                                                    class="fancybox fancybox.iframe f-14"
                                                                                    title="{{ $lang['picture'] }}"
                                                                                    type="image"><i
                                                                                        class="fa fa-file-image-o"></i><span>{{ $lang['picture'] }}</span></a>
                                                                        </li>
                                                                        <li role="presentation" class="guize"><a
                                                                                    href="{{ route('seller/wechat/auto_reply', array('type'=>'voice')) }}"
                                                                                    class="fancybox fancybox.iframe f-14"
                                                                                    title="{{ $lang['voice'] }}"
                                                                                    type="voice"><i
                                                                                        class="fa fa-volume-up"></i><span>{{ $lang['voice'] }}</span></a>
                                                                        </li>
                                                                        <li role="presentation" class="guize"><a
                                                                                    href="{{ route('seller/wechat/auto_reply', array('type'=>'video')) }}"
                                                                                    class="f-14 fancybox fancybox.iframe"
                                                                                    title="{{ $lang['video'] }}"
                                                                                    type="video"><i
                                                                                        class="fa fa-film"></i><span>{{ $lang['video'] }}</span></a>
                                                                        </li>
                                                                        <li role="presentation" class="guize"><a
                                                                                    href="{{ route('seller/wechat/auto_reply', array('type'=>'news')) }}"
                                                                                    class="f-14 fancybox fancybox.iframe"
                                                                                    title="{{ $lang['article_news'] }}"
                                                                                    type="news"><i
                                                                                        class="glyphicon glyphicon-list-alt"></i><span>{{ $lang['article_news'] }}</span></a>
                                                                        </li>
                                                                    </ul>
                                                                </div>
                                                                <div class="panel-body">
                                                                    <div class="wechat_text"><textarea name="content"
                                                                                                       class="form-control"
                                                                                                       rows="6"></textarea>
                                                                    </div>
                                                                    <div class="content_0 change_content thumbnail borderno hidden picture-list"></div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </dd>
                                            </dl>
                                            <dl>
                                                <dt>&nbsp;</dt>
                                                <dd class="button_info">
                                                    @csrf
                                                    <input type="hidden" name="content_type" value="text"
                                                           id="content_type_0">
                                                    <input type="submit" value="{{ $lang['button_submit'] }}"
                                                           class="sc-btn sc-blueBg-btn btn35"/>
                                                    <input type="reset" value="{{ $lang['button_reset'] }}"
                                                           class="sc-btn sc-blue-btn btn35"/>
                                                </dd>
                                            </dl>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <!-- 规则列表 -->
                            <div class="wrapper-list border1 mt20">

                                @foreach($list as $val)

                                    <div class="account-setting mb10">
                                        <div class="ecsc-form-goods ">
                                            <div class="item">
                                                <span class="label-t " style="margin: 5px 0px 0px 20px; width:auto">{{ $lang['rule_name'] }}
                                                    ：{{ $val['rule_name'] }}</span>
                                            </div>
                                            <div class="panel-footer info_btn of">
                                                <a href="javascript:;"
                                                   class="sc-btn sc-blueBg-btn btn35 edit">{{ $lang['edit'] }}</a>
                                                <a href="javascript:if(confirm('{{ $lang['confirm_delete'] }}')){window.location.href='{{ route('seller/wechat/reply_del', array('id'=>$val['id'])) }}'};"
                                                   class="sc-btn btn35 ">{{ $lang['drop'] }}</a>
                                            </div>
                                            <form action="{{ route('seller/wechat/rule_edit') }}" method="post"
                                                  class="form-horizontal hidden" role="form">
                                                <dl>
                                                    <dt>{{ $lang['rule_name'] }}：</dt>
                                                    <dd>
                                                        <input type="text" name='rule_name'
                                                               value="{{ $val['rule_name'] }}" class="text input-sm">
                                                        <div class="form_prompt"></div>
                                                        <div class="notic">{{ $lang['rule_name_length_limit'] }}</div>
                                                    </dd>
                                                </dl>
                                                <dl>
                                                    <dt>{{ $lang['rule_keywords'] }}：</dt>
                                                    <dd>
                                                        <input type="text" name='rule_keywords'
                                                               value="{{ $val['rule_keywords_string'] }}"
                                                               class="text input-sm">
                                                        <div class="form_prompt"></div>
                                                        <div class="notic">{{ $lang['rule_keywords_notice'] }}</div>
                                                    </dd>
                                                </dl>
                                                <dl>
                                                    <dt>{{ $lang['rule_content'] }}：</dt>
                                                    <dd>
                                                        <div class="pull-left">
                                                            <div class="account-setting">
                                                                <div class="panel panel-default">
                                                                    <div class="panel-heading">
                                                                        <ul class="nav nav-pills" role="tablist">
                                                                            <li role="presentation" class="guize"><a
                                                                                        href="javascript:;"
                                                                                        title="{{ $lang['text'] }}"
                                                                                        class="f-14" type="text"><i
                                                                                            class="fa fa-pencil"></i><span>{{ $lang['text'] }}</span></a>
                                                                            </li>
                                                                            <li role="presentation" class="guize"><a
                                                                                        href="{{ route('seller/wechat/auto_reply', array('type'=>'image', 'reply_id' => $val['id'])) }}"
                                                                                        class="fancybox fancybox.iframe f-14"
                                                                                        title="{{ $lang['picture'] }}"
                                                                                        type="image"><i
                                                                                            class="fa fa-file-image-o"></i><span>{{ $lang['picture'] }}</span></a>
                                                                            </li>
                                                                            <li role="presentation" class="guize"><a
                                                                                        href="{{ route('seller/wechat/auto_reply', array('type'=>'voice', 'reply_id' => $val['id'])) }}"
                                                                                        class="fancybox fancybox.iframe f-14"
                                                                                        title="{{ $lang['voice'] }}"
                                                                                        type="voice"><i
                                                                                            class="fa fa-volume-up"></i><span>{{ $lang['voice'] }}</span></a>
                                                                            </li>
                                                                            <li role="presentation" class="guize"><a
                                                                                        href="{{ route('seller/wechat/auto_reply', array('type'=>'video', 'reply_id' => $val['id'])) }}"
                                                                                        class="f-14 fancybox fancybox.iframe"
                                                                                        title="{{ $lang['video'] }}"
                                                                                        type="video"><i
                                                                                            class="fa fa-film"></i><span>{{ $lang['video'] }}</span></a>
                                                                            </li>
                                                                            <li role="presentation" class="guize"><a
                                                                                        href="{{ route('seller/wechat/auto_reply', array('type'=>'news', 'reply_id' => $val['id'])) }}"
                                                                                        class="f-14 fancybox fancybox.iframe"
                                                                                        title="{{ $lang['article_news'] }}"
                                                                                        type="news"><i
                                                                                            class="glyphicon glyphicon-list-alt"></i><span>{{ $lang['article_news'] }}</span></a>
                                                                            </li>
                                                                        </ul>
                                                                    </div>
                                                                    <div class="panel-body">
                                                                        <div class="
@if( (isset($val['medias']) && $val['medias']) || (isset($val['media']) && $val['media']))
                                                                                hidden
                                                                               @endif
                                                                                wechat_text">
                                                                            <textarea name="content"
                                                                                      class="form-control"
                                                                                      rows="6">{!! $val['content'] ?? '' !!}</textarea>
                                                                        </div>
                                                                        <div class="content_{{ $val['id'] ?? '0' }}  change_content  thumbnail borderno picture-list
@if(empty($val['medias']) && empty($val['media']))
                                                                                hidden
                                                                                @endif
                                                                                " style="padding:0;margin:0px;">
                                                                            <input type="hidden" name="media_id"
                                                                                   value="{{ $val['media_id'] ?? '0' }}"/>

                                                                        @if(isset($val['medias']) && $val['medias'])
                                                                            <!-- 多图文 -->

                                                                                @foreach($val['medias'] as $k=>$v)


                                                                                    @if($k == 0)

                                                                                        <div class="article">
                                                                                            <p></p>
                                                                                            <div class="cover"><img
                                                                                                        src="{{ $v['file'] ?? '' }}"/><span>{{ $v['title'] ?? '' }}</span>
                                                                                            </div>
                                                                                        </div>

                                                                                    @else

                                                                                        <div class="article_list">
                                                                                            <span>{{ $v['title'] ?? '' }}</span>
                                                                                            <img src="{{ $v['file'] ?? '' }}"
                                                                                                 width="78" height="78"
                                                                                                 class="pull-right"/>
                                                                                        </div>

                                                                                    @endif


                                                                                @endforeach

                                                                            @elseif(isset($val['media']) && $val['media'])
                                                                            <!-- 单图文，图片，语音，视频 -->

                                                                                @if(isset($val['media']['type']) && $val['media']['type'] == 'news' && $val['reply_type'] == 'news')

                                                                                    <div class="article">
                                                                                        <h4>{{ $val['media']['title'] }}</h4>
                                                                                        <p>{{ date('Y年m月d日', $val['media']['add_time']) }}</p>
                                                                                        <div class="cover"><img
                                                                                                    src="{{ $val['media']['file'] }}"/>
                                                                                        </div>
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
                                                    </dd>
                                                </dl>
                                                <dl>
                                                    <dt>&nbsp;</dt>
                                                    <dd class="button_info">
                                                        @csrf
                                                        <input type="hidden" name="content_type"
                                                               value="{{ $val['reply_type'] }}"
                                                               id="content_type_{{ $val['id'] ?? '0'  }}"/>
                                                        <input type="hidden" name="id" value="{{ $val['id'] ?? '' }}"/>
                                                        <input type="submit" value="{{ $lang['button_submit'] }}"
                                                               class="sc-btn sc-blueBg-btn btn35"/>
                                                        <input type="reset" value="{{ $lang['button_reset'] }}"
                                                               class="sc-btn sc-blue-btn btn35"/>
                                                    </dd>
                                                </dl>

                                            </form>
                                        </div>
                                    </div>

                                @endforeach

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
        //添加规则显示隐藏
        $(".add-gui").click(function () {
            $(this).parent().next().toggle();
        });
        //修改规则显示
        $(".edit").click(function () {
            $(this).parent().next().toggleClass("hidden");
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

    });
</script>

@include('seller.base.seller_pagefooter')
