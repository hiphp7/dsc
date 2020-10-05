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
                            <li class="active"><a href="{{ route('seller/wechat/article') }}">{{ $lang['article'] }}</a>
                            </li>
                            <li><a href="{{ route('seller/wechat/picture') }}">{{ $lang['picture'] }}</a></li>
                            <li><a href="{{ route('seller/wechat/voice') }}">{{ $lang['voice'] }}</a></li>
                            <li><a href="{{ route('seller/wechat/video') }}">{{ $lang['video'] }}</a></li>
                        </ul>
                    </div>
                    <div class="explanation" id="explanation">
                        <div class="ex_tit"><i class="sc_icon"></i><h4>{{ $lang['operating_hints'] }}</h4></div>
                        <ul>
                            @if(isset($lang['article_tips']) && !empty($lang['article_tips']))

                                @foreach($lang['article_tips'] as $v)
                                    <li>{{ $v }}</li>
                                @endforeach

                            @endif
                        </ul>
                    </div>
                    <div class="tab-content">
                        <div class="tab-pane active">
                            <!-- 单图文 -->
                            <div class="common-head mt20">
                                <div class="fl mb20">
                                    <a href="{{ route('seller/wechat/article_edit') }}"
                                       class="sc-btn sc-blue-btn"><span><i
                                                    class="fa fa-plus"></i>{{ $lang['article_add'] }}</span></a>
                                </div>
                            </div>
                            <div class="clear"></div>
                            <div class="wrapper-list " style="overflow:hidden">
                                <ul>

                                    @foreach($list as $key=>$val)

                                        @if(empty($val['article_id']))

                                            <li class="picture-add" style="">
                                                <div class="picture-add-top">
                                                    <h3>{{ $val['title'] }}</h3>
                                                    <p>{{ date('Y年m月d日', $val['add_time']) }}</p>
                                                    <img src="{{ $val['file'] }}" alt=""/>
                                                    <span class="seller_arite_cont twolist-hidden">{{ $val['content'] }}</span>
                                                </div>
                                                <div class="operate">
                                                    <a href="{{ route('seller/wechat/article_edit', array('id'=>$val['id'])) }}"
                                                       title="{{ $lang['edit'] }}" class=""><span
                                                                class="glyphicon glyphicon-pencil"></span></a>

                                                    @if(isset($val['command']) && !empty($val['command']))

                                                        <a href="javascript:;" title="{{ $lang['disabled_drop'] }}"
                                                           class="disabled">

                                                    @else
                                                        <a href="javascript:;" data-href="{{ route('seller/wechat/article_del', array('id'=>$val['id'])) }}'}" title="{{ $lang['drop'] }}" class="delete_confirm">
                                                     @endif

                                                     <span class="glyphicon glyphicon-trash"></span></a>

                                                </div>

                                            </li>

                                        @endif

                                        @if(($key+1) % 4 == 0)

                                </ul>
                                <ul>

                                    @endif

                                    @endforeach

                                </ul>
                            </div>
                            <div class="clear"></div>
                            <!-- 多图文 -->
                            <div class="common-head mt20">
                                <div class="fl mb20">
                                    <a href="{{ route('seller/wechat/article_edit_news') }}" class="sc-btn sc-blue-btn"><span><i
                                                    class="fa fa-plus"></i>{{ $lang['article_add_news'] }}"</span></a>
                                </div>
                            </div>
                            <div class="clear"></div>
                            <div class="wrapper-list" style="overflow:hidden">
                                <ul>

                                    @foreach($list as $key=>$val)

                                        @if($val['article_id'])

                                            <li class="picture-list">

                                                @foreach($val['articles'] as $k=>$v)

                                                    @if($k == 0)

                                                        <div class="article">
                                                            <p>{{ date('Y年m月d日', $v['add_time']) }}</p>
                                                            <div class="cover"><img
                                                                        src="{{ $v['file'] }}"/><span>{{ $v['title'] }}</span>
                                                            </div>
                                                        </div>

                                                    @else

                                                        <div class="article_list">
                                                            <span>{{ $v['title'] }}</span>
                                                            <img src="{{ $v['file'] }}" width="78" height="78"
                                                                 class="pull-right"/>
                                                        </div>

                                                    @endif

                                                @endforeach

                                                <div class="operate">
                                                    <a href="{{ route('seller/wechat/article_edit_news', array('id'=>$val['id'])) }}"
                                                       title="{{ $lang['edit'] }}" class=""><span
                                                                class="glyphicon glyphicon-pencil"></span></a>

                                                    <a href="javascript:;"
                                                       data-href="{{ route('seller/wechat/article_del', array('id'=>$val['id'])) }}'}"
                                                       title="{{ $lang['drop'] }}" class="delete_confirm"><span
                                                                class="glyphicon glyphicon-trash"></span></a>
                                                </div>
                                            </li>

                                        @endif

                                        @if(($key+1) % 4 == 0)

                                </ul>
                                <ul>

                                    @endif

                                    @endforeach

                                </ul>
                            </div>
                        </div>

                        @include('seller.base.seller_pageview')
                    </div>

                </div>
            </div>
        </div>

    </div>
</div>

@include('seller.base.seller_pagefooter')
