@include('admin.wechat.pageheader')
<style>
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
                <li role="presentation"><a href="{{ route('admin/wechat/article') }}">{{ $lang['article'] }}</a></li>
                <li role="presentation"><a href="{{ route('admin/wechat/picture') }}">{{ $lang['picture'] }}</a></li>
                <li role="presentation"><a href="{{ route('admin/wechat/voice') }}">{{ $lang['voice'] }}</a></li>
                <li role="presentation" class="curr"><a
                            href="{{ route('admin/wechat/video') }}">{{ $lang['video'] }}</a></li>
            </ul>
        </div>
        <div class="explanation" id="explanation">
            <div class="ex_tit"><i class="sc_icon"></i><h4>{{ $lang['operating_hints'] }}</h4><span id="explanationZoom"
                                                                                                    title="{{ $lang['fold_tips'] }}"></span>
            </div>
            <ul>
                @if(isset($lang['video_tips']) && !empty($lang['video_tips']))

                    @foreach($lang['video_tips'] as $v)
                        <li>{{ $v }}</li>
                    @endforeach

                @endif
            </ul>
        </div>
        <div class="flexilist">
            <div class="common-head">
                <div class="fl">
                    <a href="{{ route('admin/wechat/video_edit') }}">
                        <div class="fbutton">
                            <div class="add" title="{{ $lang['video_add'] }}"><span><i
                                            class="fa fa-plus"></i>{{ $lang['video_add'] }}</span></div>
                        </div>
                    </a>
                </div>
            </div>
            <div class="row" style="margin:0;">

                @foreach($list as $v)

                    <div class="col-xs-4 col-md-2 thumbnail" style="margin-right:10px;">
                        <img alt="{{ $v['file_name'] }}" src="{{ asset('img/video.png') }}" class="img-rounded"
                             style="height:220px"/>
                        <p class="text-muted" style="word-wrap:break-word;word-break:normal;">{{ $v['file_name'] }}</p>
                        <p class="text-muted">{{ $v['size'] }}</p>
                        <div class="bg-info">
                            <ul class="nav nav-pills nav-justified" role="tablist">
                                <li role="presentation"><a
                                            href="{{ route('admin/wechat/video_edit', array('id'=>$v['id'])) }}"
                                            title="{{ $lang['edit'] }}" class="ectouch-fs18"><span
                                                class="glyphicon glyphicon-pencil"></span></a></li>
                                <li role="presentation"><a
                                            href="javascript:if(confirm('{{ $lang['confirm_delete'] }}')){window.location.href='{{ route('admin/wechat/media_del', array('id'=>$v['id'])) }}'};"
                                            title="{{ $lang['drop'] }}" class="ectouch-fs18"><span
                                                class="glyphicon glyphicon-trash"></span></a></li>
                            </ul>
                        </div>
                    </div>

                @endforeach

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
