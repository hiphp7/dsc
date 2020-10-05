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
                            <li class="active"><a
                                        href="{{ route('seller/wechat/mass_message') }}">{{ $lang['mass_message'] }}</a>
                            </li>
                            <li><a href="{{ route('seller/wechat/mass_list') }}">{{ $lang['mass_history'] }}</a></li>
                        </ul>
                    </div>
                    <div class="explanation" id="explanation">
                        <div class="ex_tit"><i class="sc_icon"></i><h4>{{ $lang['operating_hints'] }}</h4></div>
                        <ul>
                            @if(isset($lang['mass_message_tips']) && !empty($lang['mass_message_tips']))

                                @foreach($lang['mass_message_tips'] as $v)
                                    <li>{{ $v }}</li>
                                @endforeach

                            @endif
                        </ul>
                    </div>

                    <form action="{{ route('seller/wechat/mass_message') }}" method="post" enctype="multipart/form-data"
                          class="form-horizontal" role="form">
                        <div class="wrapper-list border1 mt20 ecsc-form-goods">
                            <dl>
                                <dt>{{ $lang['select_tags'] }}：</dt>
                                <dd>
                                    <div class="txtline selection">
                                        <select name="tag_id" class="input-sm select">

                                            @foreach($tags as $val)

                                                <option value="{{ $val['tag_id'] }}">{{ $val['name'] }}</option>

                                            @endforeach

                                        </select>
                                    </div>
                                    <div class="notic">{{ $lang['mass_tags_notice'] }}</div>
                                </dd>
                            </dl>
                            <dl>
                                <dt>{{ $lang['article_news_select'] }}：</dt>
                                <dd>
                                    <div class="w299 pull-left">
                                        <a class="sc-btn sc-blueBg-btn btn30 fancybox fancybox.iframe"
                                           href="{{ route('seller/wechat/auto_reply', array('type'=>'news')) }}">{{ $lang['article_news_select'] }}</a>
                                    </div>
                                    <div class="form_prompt"></div>
                                    <div class="notic">{{ $lang['mass_article_news_notice'] }}</div>
                                </dd>
                            </dl>
                            <dl>
                                <dt>{{ $lang['article_news'] }}：</dt>
                                <dd>
                                    <div class="col-md-3 content_0 picture-list"></div>
                                </dd>
                            </dl>
                            <dl>
                                <dt>&nbsp;</dt>
                                <dd class="button_info">
                                    @csrf
                                    <input type="submit" name="submit" value="{{ $lang['button_submit'] }}"
                                           class="sc-btn sc-blueBg-btn btn35">
                                    <input type="reset" value="{{ $lang['button_reset'] }}"
                                           class="sc-btn btn35 sc-blue-btn">
                                </dd>
                            </dl>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
</div>

@include('seller.base.seller_pagefooter')
