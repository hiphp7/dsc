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
                        <ul class="tab ">
                            <li>
                                <a href="{{ route('seller/wechat/article_edit_news') }}">{{ $lang['article_edit_news'] }}</a>
                            </li>
                        </ul>
                    </div>
                    <div class="explanation" id="explanation">
                        <div class="ex_tit"><i class="sc_icon"></i><h4>{{ $lang['operating_hints'] }}</h4></div>
                        <ul>
                            @if(isset($lang['article_news_tips']) && !empty($lang['article_news_tips']))

                                @foreach($lang['article_news_tips'] as $v)
                                    <li>{{ $v }}</li>
                                @endforeach

                            @endif
                        </ul>
                    </div>

                    <div class="wrapper-list mt20">
                        <form action="{{ route('seller/wechat/article_edit_news') }}" method="post"
                              enctype="multipart/form-data" class="form-horizontal" role="form">
                            <div class="account-setting ecsc-form-goods">
                                <dl>
                                    <dt>{{ $lang['article_news_select'] }}：</dt>
                                    <dd>
                                        <div class="label_value info_btn" style="margin-top:0;">
                                            <a class="sc-btn sc-blueBg-btn btn30 fancybox_article fancybox.iframe"
                                               href="{{ route('seller/wechat/articles_list') }}">{{ $lang['article_news_select'] }}</a>
                                            <a class="sc-btn sc-blue-btn btn30 delete_confirm" href="javascript:;"
                                               data-href="{{ route('seller/wechat/article_news_del', array('id'=>$id)) }}'}">{{ $lang['article_news_reset'] }}</a>
                                        </div>
                                    </dd>
                                </dl>
                                <dl>
                                    <dt>{{ $lang['article_news'] }}：</dt>
                                    <dd>
                                        <div class="picture-list ajax-data">

                                            @if($articles)

                                                @foreach($articles as $k=>$v)

                                                    @if($k == 0)

                                                        <div class="article">
                                                            <input type="hidden" name="article[]"
                                                                   value="{{ $v['id'] }}"/>
                                                            <p>{{ date('Y年m月d日', $v['add_time']) }}</p>
                                                            <div class="cover"><img
                                                                        src="{{ $v['file'] }}"/><span>{{ $v['title'] }}</span>
                                                            </div>
                                                        </div>

                                                    @else

                                                        <div class="article_list">
                                                            <input type="hidden" name="article[]"
                                                                   value="{{ $v['id'] }}"/>
                                                            <span>{{ $v['title'] }}</span>
                                                            <img src="{{ $v['file'] }}" width="78" height="78"
                                                                 class="pull-right"/>
                                                        </div>

                                                    @endif

                                                @endforeach

                                            @endif

                                        </div>
                                    </dd>
                                </dl>
                                <dl>
                                    <dt>{{ $lang['sort_order'] }}：</dt>
                                    <dd>
                                        <input type="text" name="sort" class="text" value="{{ $sort }}"/>
                                    </dd>
                                </dl>
                                <dl>
                                    <dt>&nbsp;</dt>
                                    <dd class="button_info">
                                        @csrf
                                        <input type="hidden" name="id" value="{{ $id }}"/>
                                        <input type="submit" value="{{ $lang['button_submit'] }}"
                                               class="sc-btn sc-blueBg-btn btn35"/>
                                        <input type="reset" value="{{ $lang['button_reset'] }}"
                                               class="sc-btn sc-blue-btn btn35"/>
                                    </dd>
                                </dl>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>

    </div>
</div>

<script type="text/javascript">
    $(function () {

        // 重定义弹出框
        $(".fancybox_article").fancybox({
            afterClose: function () {
                sessionStorage.removeItem("article_ids"); // 关闭弹窗时 清空 sessionStorage article_ids
            },
            width: '60%',
            height: '60%',
            closeBtn: true,
            title: ''
        });

    });
</script>

@include('seller.base.seller_pagefooter')