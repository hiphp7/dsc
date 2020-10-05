@include('admin.app.pageheader')

<div class="wrapper">
    <div class="title">{{ lang('admin/app.menu') }} - {{ lang('admin/app.ad_position_list') }}</div>
    <div class="content_tips">
        <div class="explanation" id="explanation">
            <div class="ex_tit"><i class="sc_icon"></i><h4>{{ lang('admin/common.operating_hints') }}</h4><span
                        id="explanationZoom" title="{{ lang('admin/common.fold_tips') }}"></span></div>
            <ul>
                <li></li>
            </ul>
        </div>

        <div class="flexilist">
            <div class="common-head">

                <div class="fl">
                    <a href="{{ route('admin/app/ad_position_info') }}">
                        <div class="fbutton">
                            <div class="add" title="{{ lang('admin/app.update_position') }}"><span><i
                                            class="fa fa-plus"></i>{{ lang('admin/app.update_position') }}</span></div>
                        </div>
                    </a>
                </div>

                <div class="search">
                    <form action="{{ route('admin/app/ad_position_list') }}" name="searchForm" method="post">
                        <div class="input" style="margin-left:10px;">
                            <input type="text" name="search_keyword" class="text nofocus"
                                   placeholder="{{ lang('admin/app.search_position_name') }}" autocomplete="off"/>
                            @csrf
                            <input type="submit" class="btn" ectype="secrch_btn" value=""/>
                        </div>
                    </form>
                </div>
            </div>
            <div class="common-content">
                <div class="list-div" id="listDiv">
                    <table cellpadding="0" cellspacing="0" border="0">
                        <thead>
                        <tr>
                            <th width="30%">
                                <div class="tDiv">{{ lang('admin/app.position_name') }}</div>
                            </th>
                            <th width="10%">
                                <div class="tDiv">{{ lang('admin/app.posit_width') }}</div>
                            </th>
                            <th width="10%">
                                <div class="tDiv">{{ lang('admin/app.posit_height') }}</div>
                            </th>
                            <th width="20%">
                                <div class="tDiv">{{ lang('admin/app.position_desc') }}</div>
                            </th>
                            <th width="20%" class="handle">{{ lang('admin/app.handler') }}</th>
                        </tr>
                        </thead>

                        @if($position_list)
                            <tbody>

                            @foreach($position_list as $key => $list)

                                <tr>
                                    <td>
                                        <div class="tDiv">{{ $list['position_name'] }}</div>
                                    </td>
                                    <td>
                                        <div class="tDiv">{{ $list['ad_width'] ?? '' }}</div>
                                    </td>
                                    <td>
                                        <div class="tDiv">{{ $list['ad_height'] ?? '' }}</div>
                                    </td>
                                    <td>
                                        <div class="tDiv">{{ $list['position_desc'] ?? '' }}</div>
                                    </td>
                                    <td class="handle">
                                        <div class="tDiv a3">
                                            <a href="{{ route('admin/app/ads_list', ['position_id' => $list['position_id']]) }}"
                                               title="{{ lang('admin/app.view_ad') }}" class="btn_see"><i
                                                        class="sc_icon sc_icon_see"></i>{{ lang('admin/app.view_ad') }}
                                            </a>
                                            <a href="{{ route('admin/app/ad_position_info', ['position_id' => $list['position_id']]) }}"
                                               title="{{ lang('admin/app.edit') }}" class="btn_edit"><i
                                                        class="fa fa-edit"></i>{{ lang('admin/app.edit') }}</a>
                                            <a href="javascript:;" title="{{ lang('admin/app.delete') }}"
                                               class="btn_trash delete-ad"
                                               data-href="{{ route('admin/app/delete_position', ['position_id' => $list['position_id']]) }}"><i
                                                        class="fa fa-trash-o"></i>{{ lang('admin/app.delete') }}</a>
                                        </div>
                                    </td>
                                </tr>

                            @endforeach

                            </tbody>

                        @else

                            <tbody>
                            <tr>
                                <td class="no-records" colspan="5">{{ lang('admin/app.no_records') }}</td>
                            </tr>
                            </tbody>

                        @endif

                        <tfoot>
                        <tr>
                            <td colspan="5">
                                @include('admin.app.pageview')
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

        // 删除广告位
        $(".delete-ad").click(function () {
            var url = $(this).attr("data-href");
            //询问框
            layer.confirm('{{ $lang['confirm_delete'] }}', {
                btn: ['{{ $lang['ok'] }}', '{{ $lang['cancel'] }}'] //按钮
            }, function () {
                $.post(url, '', function (data) {
                    layer.msg(data.msg);
                    if (data.error == 0) {
                        if (data.url) {
                            window.location.href = data.url;
                        } else {
                            window.location.reload();
                        }
                    }
                    return false;
                }, 'json');
            });
        });

    });
</script>

@include('admin.app.pagefooter')
