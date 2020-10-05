@include('admin.wechat.pageheader')
<style>
    .article {
        border: 1px solid #ddd;
        padding: 5px 5px 0 5px;
        overflow: hidden;
    }

    .cover {
        height: 160px;
        position: relative;
        margin-bottom: 5px;
        overflow: hidden;
    }

    .article .cover img {
        width: 100%;
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
        padding: 5px;
        border: 1px solid #ddd;
        border-top: 0;
        overflow: hidden;
    }
</style>
<div class="container-fluid wrapper">
    <div class="title">
        <a href="{{ route('admin/wechat/article') }}"
           class="s-back">{{ $lang['back'] }}</a>{{ $lang['wechat_article'] }} - {{ $lang['article_edit_news'] }}
    </div>
    <div class="content_tips">
        <div class="explanation" id="explanation">
            <div class="ex_tit"><i class="sc_icon"></i><h4>{{ $lang['operating_hints'] }}</h4><span id="explanationZoom"
                                                                                                    title="{{ $lang['fold_tips'] }}"></span>
            </div>
            <ul>
                @if(isset($lang['article_news_tips']) && !empty($lang['article_news_tips']))

                    @foreach($lang['article_news_tips'] as $v)
                        <li>{{ $v }}</li>
                    @endforeach

                @endif
            </ul>
        </div>
        <div class="flexilist">
            <div class="main-info">
                <form action="{{ route('admin/wechat/article_edit_news') }}" method="post" enctype="multipart/form-data"
                      class="form-horizontal" role="form">
                    <div id="general-table" class="switch_info ectouch-table">
                        <div class="item">
                            <div class="label-t">{{ $lang['article_news_select'] }}：</div>
                            <div class="label_value info_btn" style="margin-top:0;">
                                <a class="button btn-info bg-green fancybox_article fancybox.iframe"
                                   href="{{ route('admin/wechat/articles_list') }}">{{ $lang['article_news_select'] }}</a>
                                <a class="button button_reset"
                                   href="javascript:if(confirm('{{ $lang['confirm_delete'] }}')){window.location.href='{{ route('admin/wechat/article_news_del', array('id'=>$id)) }}'};">{{ $lang['article_news_reset'] }}</a>
                                <div class="notic"></div>
                            </div>
                        </div>
                        <div class="item">
                            <div class="label-t">{{ $lang['article_news'] }}：</div>
                            <div class="label_value ajax-data" style="width:299px;">

                                @if($articles)

                                    @foreach($articles as $k=>$v)

                                        @if($k == 0)

                                            <div class="article">
                                                <input type="hidden" name="article[]" value="{{ $v['id'] }}"/>
                                                <p>{{ date('Y年m月d日', $v['add_time']) }}</p>
                                                <div class="cover"><img
                                                            src="{{ $v['file'] }}"/><span>{{ $v['title'] }}</span></div>
                                            </div>

                                        @else

                                            <div class="article_list">
                                                <input type="hidden" name="article[]" value="{{ $v['id'] }}"/>
                                                <span>{{ $v['title'] }}</span>
                                                <img src="{{ $v['file'] }}" width="78" height="78" class="pull-right"/>
                                            </div>

                                        @endif

                                    @endforeach

                                @endif

                            </div>
                        </div>
                        <div class="item">
                            <div class="label-t">{{ $lang['sort_order'] }}：</div>
                            <div class="label_value">
                                <input type="text" name="sort" class="text" value="{{ $sort }}"/>
                            </div>
                        </div>
                        <div class="item">
                            <div class="label-t">&nbsp;</div>
                            <div class="label_value info_btn">
                                @csrf
                                <input type="hidden" name="id" value="{{ $id }}"/>
                                <input type="submit" value="{{ $lang['button_submit'] }}"
                                       class="button btn-danger bg-red"/>
                                <input type="reset" value="{{ $lang['button_reset'] }}" class="button button_reset"/>
                            </div>
                        </div>
                    </div>
                </form>
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
@include('admin.wechat.pagefooter')

