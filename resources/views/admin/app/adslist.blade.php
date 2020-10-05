@include('admin.app.pageheader')

<style>
    /*nyroModal*/
    .nyroModal {
        color: #aaa;
        font-size: 16px;
    }

    .nyroModalBg {
        position: fixed;
        overflow: hidden;
        z-index: 100009;
        top: 0;
        left: 0;
        height: 100%;
        width: 100%;
        background: #fff;
        opacity: 0.7;
    }

    .nmReposition {
        position: absolute;
        z-index: 100011;
    }

    .nyroModalCloseButton {
        top: -9px;
        right: -9px;
        width: 18px;
        height: 18px;
        text-indent: -9999em;
        background: url({{ asset('assets/admin/images/close.png ') }});
    }

    .nyroModalCont {
        background-color: #FFF;
        position: absolute;
        z-index: 100010;
        margin: 25px;
        background: #fff;
        box-shadow: 0 0 5px rgba(0, 0, 0, 0.9);
    }

    .ui-tooltipImg .ui-tooltip-content img {
        background: #ddd;
    }
</style>


<div class="wrapper">
    <div class="title">{{ lang('admin/app.menu') }} - {{ lang('admin/app.ad_list') }}</div>
    <div class="content_tips">
        <div class="explanation" id="explanation">
            <div class="ex_tit"><i class="sc_icon"></i><h4>{{ lang('admin/common.operating_hints') }}</h4><span
                        id="explanationZoom" title="{{ lang('admin/common.fold_tips') }}"></span></div>
            <ul>
                <li></li>
            </ul>
        </div>

        <div class="flexilist" style="z-index:0">
            <div class="common-head">
                <div class="fl">
                    <a href="{{ route('admin/app/ads_info', ['position_id' => $position_id]) }}">
                        <div class="fbutton">
                            <div class="add" title="{{ lang('admin/app.update_ad') }}"><span><i
                                            class="fa fa-plus"></i>{{ lang('admin/app.update_ad') }}</span></div>
                        </div>
                    </a>
                </div>

                <div class="search">
                    <form action="{{ route('admin/app/ads_list') }}" name="searchForm" method="post">
                        <div class="input" style="margin-left:10px;">
                            <input type="text" name="keyword" class="text nofocus"
                                   placeholder="{{ lang('admin/app.search_ad_name') }}" autocomplete="off"/>
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
                            <th width="5%">
                                <div class="tDiv">{{ lang('admin/app.ad_id') }}</div>
                            </th>
                            <th width="14%">
                                <div class="tDiv">{{ lang('admin/app.ad_name') }}</div>
                            </th>
                            <th width="15%">
                                <div class="tDiv">{{ lang('admin/app.position_name') }}</div>
                            </th>
                            <th width="8%">
                                <div class="tDiv">{{ lang('admin/app.media_type') }}</div>
                            </th>
                            {{--<th width="8%"><div class="tDiv tc">{{ lang('admin/app.click_count') }}</div></th>--}}
                            <th width="8%">
                                <div class="tDiv tc">{{ lang('admin/app.status') }}</div>
                            </th>
                            <th width="8%">
                                <div class="tDiv tc">{{ lang('admin/app.sort_order') }}</div>
                            </th>
                            <th width="12%" class="handle">{{ lang('admin/app.handler') }}</th>
                        </tr>
                        </thead>

                        @if($ad_list)

                            <tbody>

                            @foreach($ad_list as $key => $list)

                                <tr>
                                    <td>
                                        <div class="tDiv">{{ $list['ad_id']}}</div>
                                    </td>
                                    <td>
                                        <div class="tDiv">{{ $list['ad_name'] ?? '' }}</div>
                                    </td>
                                    <td>
                                        <div class="tDiv">{{ $list['position_name'] ?? '' }}</div>
                                    </td>

                                    @if($list['media_type'] == 0)
                                        <td>
                                            <div class="tDiv">
									<span class="show">
										<a href="{{ $list['ad_code'] ?? '' }}" class="nyroModal"><i
                                                    class="fa fa-picture-o"
                                                    data-tooltipimg="{{ $list['ad_code'] ?? '' }}" ectype="tooltip"
                                                    title="tooltip"></i></a>
									</span>
                                            </div>
                                        </td>
                                    @elseif($list['media_type'] == 3)
                                        <td>
                                            <div class="tDiv">{{ $list['ad_code'] ?? '' }}</div>
                                        </td>
                                    @endif

                                    {{--<td><div class="tDiv tc">{{ $list['click_count'] ?? '' }}</div></td>--}}
                                    <td>
                                        <div class="tDiv tc">

                                            @if(isset($list['enabled']) && $list['enabled'] == 1)

                                                <a href="javascript:;" class="btn_trash change-ad"
                                                   data-href="{{ route('admin/app/change_ad_status', ['ad_id'=> $list['ad_id'], 'status' => 0]) }}"
                                                   title="{{ $lang['to_disabled'] }}"><i
                                                            class="fa fa-toggle-on"></i>{{ $lang['already_enabled'] }}
                                                </a>

                                            @else

                                                <a href="javascript:;" class="btn_trash change-ad"
                                                   data-href="{{ route('admin/app/change_ad_status', ['ad_id'=> $list['ad_id'], 'status' => 1]) }}"
                                                   title="{{ $lang['to_enabled'] }}"><i
                                                            class="fa fa-toggle-off"></i>{{ $lang['already_disabled'] }}
                                                </a>

                                            @endif

                                        </div>
                                    </td>
                                    <td>
                                        <div class="tDiv tc">{{ $list['sort_order'] ?? '' }}</div>
                                    </td>
                                    <td class="handle">
                                        <div class="tDiv a3">
                                            <a href="{{ route('admin/app/ads_info', ['ad_id' => $list['ad_id']]) }}"
                                               title="{{ lang('admin/app.edit') }}" class="btn_edit"><i
                                                        class="fa fa-edit"></i>{{ lang('admin/app.edit') }}</a>
                                            <a href="javascript:;" title="{{ lang('admin/app.delete') }}"
                                               class="btn_trash delete-ad"
                                               data-href="{{ route('admin/app/delete_ad', ['ad_id' => $list['ad_id']]) }}"><i
                                                        class="fa fa-trash-o"></i>{{ lang('admin/app.delete') }}</a>
                                        </div>
                                    </td>
                                </tr>

                            @endforeach

                            </tbody>

                        @else

                            <tbody>

                            <tr>
                                <td class="no-records" colspan="7">{{ lang('admin/app.no_records') }}</td>
                            </tr>

                            </tbody>

                        @endif

                        <tfoot>
                        <tr>
                            <td colspan="7">
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

<script type="text/javascript" src="{{ asset('js/jquery.nyroModal.js') }}"></script>
<link rel="stylesheet" type="text/css" href="{{ asset('js/jquery-ui/jquery-ui.min.css') }}"/>
<script type="text/javascript" src="{{ asset('js/jquery-ui/jquery-ui.min.js') }}"></script>

<script type="text/javascript">

    $(function () {

        /* jquery.ui tooltip.js title美化 */
        $("[data-toggle='tooltip']").tooltip({
            position: {
                my: "center top+5",
                at: "center bottom"
            }
        });

        /* jquery.ui tooltip.js 图片放大 */
        jQuery.tooltipimg = function () {
            $("[ectype='tooltip']").tooltip({
                content: function () {
                    var element = $(this);
                    var url = element.data("tooltipimg");
                    if (element.is('[data-tooltipImg]')) {
                        return "<img src='" + url + "' />";
                    }
                },
                position: {
                    using: function (position, feedback) {
                        $(this).css(position).addClass("ui-tooltipImg");
                    }
                }
            });
        }
        $.tooltipimg();


        //点击查看图片
        $('.nyroModal').nyroModal();

        // 修改广告状态
        $(".change-ad").click(function () {
            var url = $(this).attr("data-href");

            $.get(url, '', function (data) {
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

        // 删除广告
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
