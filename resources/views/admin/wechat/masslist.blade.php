@include('admin.wechat.pageheader')
<style>
    ul {
        margin: 0;
        padding: 0
    }

    .col-md-1 img {
        vertical-align: middle;
        width: 8rem;
        height: 8rem;
    }

    .line-center {
        line-height: 172px;
        overflow: hidden;
    }

    .text-muted {
        overflow: hidden;
    }

    .onelist-hidden {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /*超出1行隐藏*/
    .twolist-hidden {
        overflow: hidden;
        text-overflow: ellipsis;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
    }

    /*超出2行隐藏*/
    .admin-top h4 {
        margin-top: 40px;
    }

    .admin-top p {
        margin-top: 10px;
    }
</style>
<div class="wrapper">
    <div class="title">{{ $lang['wechat_menu'] }} - {{ $lang['mass_history'] }}</div>
    <div class="content_tips">
        <div class="tabs_info">
            <ul>
                <li><a href="{{ route('admin/wechat/mass_message') }}">{{ $lang['mass_message'] }}</a></li>
                <li class="curr"><a href="{{ route('admin/wechat/mass_list') }}">{{ $lang['mass_history'] }}</a></li>
            </ul>
        </div>
        <div class="explanation" id="explanation">
            <div class="ex_tit">
                <i class="sc_icon"></i><h4>{{ lang('admin/common.operating_hints') }}</h4><span id="explanationZoom" title="{{ lang('admin/common.fold_tips') }}"></span>
            </div>
            <ul>
                @if(isset($lang['mass_history_tips']) && !empty($lang['mass_history_tips']))

                    @foreach($lang['mass_history_tips'] as $v)
                        <li>{{ $v }}</li>
                    @endforeach

                @endif
            </ul>
        </div>
        <div class="flexilist" style="line-height:18px;">
            <div class="main-info">
                <ul class="list-div">
                    <table cellspacing="0" cellpadding="0" border="0">
                        <tr>
                            <th width="35%">
                                <div class="tDiv">{{ $lang['mass_title'] }}</div>
                            </th>
                            <th width="35%">
                                <div class="tDiv">{{ $lang['mass_status'] }}</div>
                            </th>
                            <th width="10%">
                                <div class="tDiv">{{ $lang['mass_send_time'] }}</div>
                            </th>
                            <th>
                                <div class="tDiv" style="text-align: center;">{{ $lang['handler'] }}</div>
                            </th>
                        </tr>
                    </table>

                    @if(isset($list) && $list)

                        @foreach($list as $val)

                            <li class="list-group-item" style="overflow:hidden;">
                                <div class="col-md-1 line-center" style="padding-left:0;"><img src="{{ $val['artinfo']['file'] }}"/></div>
                                <div class="col-md-3 admin-top">
                                    <h4 class="onelist-hidden">[{{ $val['artinfo']['type'] }}]{{ $val['artinfo']['title'] }}</h4>
                                    <p class="text-muted twolist-hidden">{{ $val['artinfo']['content'] ?? '' }}</p>
                                </div>
                                <div class="col-md-4">
                                    <p>{{ $val['status'] }}</p>
                                    <p>{{ $lang['mass_totalcount'] }}：{{ $val['totalcount'] }}人</p>
                                    <p>{{ $lang['mass_sentcount'] }}：{{ $val['sentcount'] }}人</p>
                                    <p>{{ $lang['mass_filtercount'] }}：{{ $val['filtercount'] }}人</p>
                                    <p>{{ $lang['mass_errorcount'] }}：{{ $val['errorcount'] }}人</p>
                                </div>
                                <div class="col-md-3 line-center">{{ $lang['mass_send_time'] }}：{{ date('Y年m月d日', $val['send_time']) }}</div>
                                <div class="col-md-1 line-center"><a href="javascript:;" data-href="{{ route('admin/wechat/mass_del', array('id'=>$val['id'])) }}" class="btn button btn-danger bg-red delete">{{ $lang['drop'] }}</a></div>

                            </li>

                        @endforeach

                    @else

                        <li class="no-records" >{{ $lang['no_records'] }}</li>

                    @endif

                </ul>
            </div>
        </div>
        <div class="list-div of">
            <table cellspacing="0" cellpadding="0" border="0">
                <tfoot>
                <tr>
                    <td colspan="4">
                        @include('admin.wechat.pageview')
                    </td>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

@include('admin.wechat.pagefooter')