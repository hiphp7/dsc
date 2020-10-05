@include('admin.wechat.pageheader')
<style>
    .article {
        border: 1px solid #ddd;
        padding: 5px 5px 0 5px;
        overflow: hidden;
    }

    .cover {
        position: relative;
        margin-bottom: 5px;
        overflow: hidden;
    }

    .article .cover img {
        width: 100%;
        height: auto;
    }

    .article h4 {
        overflow: hidden;
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

    .sigle-list {
        height: 280px;
    }

    .sigle-list .cover {
        max-height: 150px;
    }

    #footer {
        position: static;
        bottom: 0px;
    }
</style>
<div class="wrapper">
    <div class="title">{{ $lang['wechat_menu'] }} - {{ $lang['wechat_article'] }}</div>
    <div class="content_tips">
        <div class="tabs_info">
            <ul>
                <li role="presentation" class="curr"><a
                            href="{{ route('admin/wechat/article') }}">{{ $lang['article'] }}</a></li>
                <li role="presentation"><a href="{{ route('admin/wechat/picture') }}">{{ $lang['picture'] }}</a></li>
                <li role="presentation"><a href="{{ route('admin/wechat/voice') }}">{{ $lang['voice'] }}</a></li>
                <li role="presentation"><a href="{{ route('admin/wechat/video') }}">{{ $lang['video'] }}</a></li>
            </ul>
        </div>
        <div class="explanation" id="explanation">
            <div class="ex_tit"><i class="sc_icon"></i><h4>{{ $lang['operating_hints'] }}</h4><span id="explanationZoom"
                                                                                                    title="{{ $lang['fold_tips'] }}"></span>
            </div>
            <ul>
                @if(isset($lang['article_tips']) && !empty($lang['article_tips']))

                    @foreach($lang['article_tips'] as $v)
                        <li>{{ $v }}</li>
                    @endforeach

                @endif
            </ul>
        </div>
        <div class="flexilist of">
            <!-- 单图文添加 -->
            <div class="common-head">
                <div class="fl">
                    <a href="{{ route('admin/wechat/article_edit') }}">
                        <div class="fbutton">
                            <div class="add" title="{{ $lang['article_add'] }}"><span><i class="fa fa-plus"></i>{{ $lang['article_add'] }}</span></div>
                        </div>
                    </a>
                </div>
            </div>
            <div class="common-content" style="border-bottom: 1px solid #62b3ff">

                <!-- 单图文列表 -->
                <div class="row">

                    @foreach($list as $key=>$val)

                        @if(empty($val['article_id']))

                            @if(isset($val['command']) && !empty($val['command']))

                                <div class="col-sm-6 col-md-4 col-lg-2 ectouch-mb">
                                    <div class="article sigle-list">
                                        <h4>{{ $val['title'] ?? '' }}</h4>
                                        <p>{{ date('Y年m月d日', $val['add_time']) }}</p>
                                        <div class="cover"><img src="{{ $val['file'] }}"/></div>
                                        <p>{{ $val['content'] }}</p>

                                    </div>
                                    <div class="bg-info">
                                        <ul class="nav nav-pills nav-justified" role="tablist">
                                            <li role="presentation"><a
                                                        href="{{ route('admin/wechat/article_edit', array('id'=>$val['id'])) }}"
                                                        title="{{ $lang['edit'] }}" class="ectouch-fs18"><span
                                                            class="glyphicon glyphicon-pencil"></span></a></li>
                                            <li role="presentation">
                                                <a href="javascript:;"
                                                   class="ectouch-fs18 disabled"><span
                                                            class="glyphicon glyphicon-trash"></span></a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>

                            @else

                                <div class="col-sm-6 col-md-4 col-lg-2 ectouch-mb">
                                    <div class="article sigle-list">
                                        <h4>{{ $val['title'] ?? '' }}</h4>
                                        <p>{{ date('Y年m月d日', $val['add_time']) }}</p>
                                        <div class="cover"><img src="{{ $val['file'] }}"/></div>
                                        <p>{{ $val['content'] }}</p>

                                    </div>
                                    <div class="bg-info">
                                        <ul class="nav nav-pills nav-justified" role="tablist">
                                            <li role="presentation"><a
                                                        href="{{ route('admin/wechat/article_edit', array('id'=>$val['id'])) }}"
                                                        title="{{ $lang['edit'] }}" class="ectouch-fs18"><span
                                                            class="glyphicon glyphicon-pencil"></span></a></li>
                                            <li role="presentation">

                                                <a href="javascript:if(confirm('{{ $lang['confirm_delete'] }}')){window.location.href='{{ route('admin/wechat/article_del', array('id'=>$val['id'])) }}'};"
                                                   title="{{ $lang['drop'] }}" class="ectouch-fs18">

                                                    <span class="glyphicon glyphicon-trash"></span></a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>

                            @endif

                            @if(($key+1) % 6 == 0)

                </div>
                <div class="row">

                    @endif

                    @endif

                    @endforeach

                </div>
            </div>

            <!-- 多图文添加 -->
            <div class="common-head" style="padding-top:10px;">
                <div class="fl">
                    <a href="{{ route('admin/wechat/article_edit_news') }}">
                        <div class="fbutton">
                            <div class="add" title="{{ $lang['article_add_news'] }}"><span><i class="fa fa-plus"></i>{{ $lang['article_add_news'] }}</span></div>
                        </div>
                    </a>
                </div>
            </div>
            <div class="common-content">
                <!-- 多图文列表 -->
                <div class="row">

                    @foreach($list as $key=>$val)

                        @if($val['article_id'])

                            <div class="col-sm-6 col-md-4 col-lg-2 ectouch-mb">

                                @foreach($val['articles'] as $k=>$v)

                                    @if($k == 0)

                                        <div class="article">
                                            <p>{{ date('Y年m月d日', $v['add_time']) }}</p>
                                            <div class="cover"><img
                                                        src="{{ $v['file'] }}"/><span>{{ $v['title'] ?? '' }}</span>
                                            </div>
                                        </div>

                                    @else

                                        <div class="article_list">
                                            <span>{{ $v['title'] ?? '' }}</span>
                                            <img src="{{ $v['file'] }}" width="78" height="78" class="pull-right"/>
                                        </div>

                                    @endif

                                @endforeach

                                <div class="bg-info">
                                    <ul class="nav nav-pills nav-justified" role="tablist">
                                        <li role="presentation"><a
                                                    href="{{ route('admin/wechat/article_edit_news', array('id'=>$val['id'])) }}"
                                                    title="{{ $lang['edit'] }}" class="ectouch-fs18"><span
                                                        class="glyphicon glyphicon-pencil"></span></a></li>
                                        <li role="presentation"><a
                                                    href="javascript:if(confirm('{{ $lang['confirm_delete'] }}')){window.location.href='{{ route('admin/wechat/article_del', array('id'=>$val['id'])) }}'};"
                                                    title="{{ $lang['drop'] }}" class="ectouch-fs18"><span
                                                        class="glyphicon glyphicon-trash"></span></a></li>
                                    </ul>
                                </div>
                            </div>

                        @endif

                        @if(($key+1) % 6 == 0)

                </div>
                <div class="row">

                    @endif

                    @endforeach

                </div>
            </div>
        </div>
        <div class="list-div of">
            <table cellspacing="0" cellpadding="0" border="0">
                <tfoot>
                <tr>
                    <td colspan="7">
                        @include('admin.wechat.pageview')
                    </td>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

@include('admin.wechat.pagefooter')
