@include('admin.wechat.pageheader')
<div class="wrapper">
    <div class="title">{{ $lang['wechat_menu'] }} - {{ $lang['sub_title'] }}</div>
    <div class="content_tips">
        <div class="tabs_info">
            <ul>
                <li role="presentation"><a href="{{ route('admin/wechat/subscribe_list') }}">{{ $lang['sub_list'] }}</a>
                </li>
                <li role="presentation" class="curr"><a
                            href="{{ route('admin/wechat/share_count') }}">{{ $lang['share_list'] }}</a></li>
            </ul>
        </div>
        <div class="explanation" id="explanation">
            <div class="ex_tit"><i class="sc_icon"></i><h4>{{ $lang['operating_hints'] }}</h4><span id="explanationZoom"
                                                                                                    title="{{ $lang['fold_tips'] }}"></span>
            </div>
            <ul>
                <li></li>
            </ul>
        </div>
        <div class="flexilist">
            <div class="common-head">
                <form action="{{ route('admin/wechat/share_count') }}" name="searchForm" method="post" role="search">
                    <div class="search">
                        <div class="input">
                            @csrf
                            <input type="text" name="keywords" class="text nofocus" placeholder="{{ $lang['search_for'] }}"
                                   autocomplete="off">
                            <input type="submit" value="" class="btn search_button">
                        </div>
                    </div>
                </form>
            </div>
            <div class="common-content">
                <div class="list-div">
                    <table cellspacing="0" cellpadding="0" border="0">
                        <tr>
                            <th width="5%">
                                <div class="tDiv">ID</div>
                            </th>
                            <th width="15%">
                                <div class="tDiv">{{ $lang['sub_nickname'] }}</div>
                            </th>
                            <th width="10%">
                                <div class="tDiv">分享类型</div>
                            </th>
                            <th>
                                <div class="tDiv">分享地址</div>
                            </th>
                            <th width="10%">
                                <div class="tDiv">分享时间</div>
                            </th>
                            <th width="20%">
                                <div class="tDiv">{{ $lang['handler'] }}</div>
                            </th>
                        </tr>

                        @if($list)

                            @foreach($list as $val)

                                <tr>
                                    <td>
                                        <div class="tDiv">{{ $val['id'] }}</div>
                                    </td>
                                    <td>
                                        <div class="tDiv">{{ $val['nickname'] }}</div>
                                    </td>
                                    <td>
                                        <div class="tDiv">{{ $val['share_type'] }}</div>
                                    </td>
                                    <td>
                                        <div class="tDiv">{{ $val['link'] }}</div>
                                    </td>
                                    <td>
                                        <div class="tDiv">{{ $val['share_time'] }}</div>
                                    </td>
                                    <td class="handle">
                                        <div class="tDiv a1">
                                            <a href="javascript:if(confirm('{{ $lang['confirm_delete'] }}')){window.location.href='{{ route('admin/wechat/share_count_delete', array('id' => $val['id'])) }}'};"
                                               class="btn_trash"><i class="fa fa-trash-o"></i>{{ $lang['drop'] }}</a>
                                        </div>
                                    </td>
                                </tr>

                            @endforeach

                        @else

                            <tbody>
                            <tr>
                                <td class="no-records" colspan="6">{{ $lang['no_records'] }}</td>
                            </tr>
                            </tbody>

                        @endif

                        <tfoot>
                        <tr>
                            <td colspan="6">
                                @include('admin.wechat.pageview')
                            </td>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<script type="text/javascript">
    $(function () {

        // 搜索验证
        $('.search_button').click(function () {
            var search_keywords = $("input[name=keywords]").val();
            if (!search_keywords) {
                layer.msg('{{ $lang['keywords_empty'] }}');
                return false;
            }
        });

    })
</script>

@include('admin.wechat.pagefooter')
