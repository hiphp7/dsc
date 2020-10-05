@include('admin.drp.pageheader')

<style>
    ul, li {
        overflow: hidden;
    }

    .dates_box_top {
        height: 32px;
    }

    .dates_bottom {
        height: auto;
    }

    .dates_hms {
        width: auto;
    }

    .dates_btn {
        width: auto;
    }

    .dates_mm_list span {
        width: auto;
    }
</style>

<div class="wrapper">
    <div class="title">{{ $lang['distribution_order'] }}</div>
    <div class="content_tips">
        <div class="tabs_info">
            <ul>
                <li class="@if(!isset($filter['log_type']) || empty($filter['log_type'])) curr @endif ">
                    <a href="{{ route('admin/drp/drp_order_list') }}">{{ lang('admin/drp.log_type_0') }}</a>
                </li>
                <li class="@if(isset($filter['log_type']) && $filter['log_type'] == 1) curr @endif ">
                    <a href="{{ route('admin/drp/drp_order_list_buy') }}">{{ lang('admin/drp.log_type_1') }}</a>
                </li>
                <li class="@if(isset($filter['log_type']) && $filter['log_type'] == 2) curr @endif ">
                    <a href="{{ route('admin/drp/drp_order_list', ['log_type' => 2]) }}">{{ lang('admin/drp.log_type_2') }}</a>
                </li>
            </ul>
        </div>
        <div class="explanation" id="explanation">
            <div class="ex_tit">
                <i class="sc_icon"></i><h4>{{ lang('admin/common.operating_hints') }}</h4><span id="explanationZoom" title="{{ lang('admin/common.fold_tips') }}"></span>
            </div>
            <ul>
                @if(isset($lang['drp_order_list_buy_tips']) && !empty($lang['drp_order_list_buy_tips']))

                    @foreach($lang['drp_order_list_buy_tips'] as $v)
                        <li>{!! $v !!}</li>
                    @endforeach

                @endif
            </ul>
        </div>
        <div class="flexilist">
            <div class="tabs_info">
                <ul>
                    <li class="@if($status =='' && $able == '') curr @endif">
                        <a href="{{ route('admin/drp/drp_order_list_buy') }}">{{ $lang['sch_stats']['all'] }}</a>
                    </li>
                    <li class="@if($status == '0' && $able == '1') curr @endif ">
                        <a href="{{ route('admin/drp/drp_order_list_buy',array('status' => 0,'able' => 1, 'log_type' => $filter['log_type'])) }}">{{ $lang['sch_stats']['0'] }}</a>
                    </li>
                    <li class="@if($status == '0' && $able == '2') curr @endif">
                        <a href="{{ route('admin/drp/drp_order_list_buy',array('status' => 0,'able' => 2, 'log_type' => $filter['log_type'])) }}">{{ $lang['be_short_of'] }}{{ $able_day }} {{ $lang['day'] }}</a>
                    </li>
                    <li class="@if($status == '1') curr @endif">
                        <a href="{{ route('admin/drp/drp_order_list_buy',array('status' => 1, 'log_type' => $filter['log_type'])) }}">{{ $lang['sch_stats']['1'] }}</a>
                    </li>
                    <li class="@if($status == '2') curr @endif">
                        <a href="{{ route('admin/drp/drp_order_list_buy',array('status' => 2, 'log_type' => $filter['log_type'])) }}">{{ $lang['sch_stats']['2'] }}</a>
                    </li>
                </ul>
            </div>
            <div class="common-head">
                <div class="fl">
                    <div class="label-t fl pr5">{{ $lang['pay_time'] }}  </div>
                    <form action="{{ route('admin/drp/export_buy') }}" method="post">
                        <div class="label_value">
                            <div class="text_time" id="text_time1" style="float:left;">
                                <input type="text" name="starttime" value="{{ date('Y-m-d H:i', mktime(0,0,0,date('m'), date('d')-7, date('Y'))) }}" id="promote_start_date" class="text" readonly>
                            </div>

                            <div class="text_time" id="text_time2" style="float:left;">
                                <input type="text" name="endtime" value="{{ date('Y-m-d H:i') }}" id="promote_end_date" class="text" readonly>
                            </div>
                            @csrf
                            <input type="submit" value="{{ $lang['export_excel'] }}" class="button bg-green"/>
                        </div>
                    </form>
                </div>
                <div class="search">
                    <form action="{{ route('admin/drp/drp_order_list_buy') }}" method="post">
                        <div class="input">
                            @csrf
                            <input type="text" name="user_name" class="text nofocus" placeholder="{{ lang('admin/drp.search_name') }}" autocomplete="off">
                            <input type="submit" value="" class="btn" >
                        </div>
                    </form>
                </div>

            </div>
            <div class="common-content">
                <div class="list-div" id="listDiv">
                    <table cellspacing="0" cellpadding="0" border="0">
                        <thead>
                        <tr>
                            <th width="3%" class="sign">
                                <div class="tDiv">
                                    <input type="checkbox" class="checkbox" name="all_list" id="all_list"/>
                                    <label for="all_list" class="checkbox_stars"></label>
                                </div>
                            </th>
                            <th>
                                <div class="tDiv">{{ $lang['user_name'] }}</div>
                            </th>
                            <th>
                                <div class="tDiv">{{ $lang['shop_name'] }}</div>
                            </th>
                            <th>
                                <div class="tDiv">{{ $lang['order_stats']['name'] }}</div>
                            </th>
                            <th>
                                <div class="tDiv">{{ $lang['pay_time'] }}</div>
                            </th>
                            <th>
                                <div class="tDiv">{{ $lang['sch_stats']['name'] }}</div>
                            </th>
                            <th>
                                <div class="tDiv">{{ $lang['drp_info'] }}</div>
                            </th>
                            <th>
                                <div class="tDiv">{{ lang('admin/common.memo_info') }}</div>
                            </th>
                            <th width="10%">
                                <div class="tDiv">{{ $lang['drp_action'] }}</div>
                            </th>
                        </tr>
                        </thead>

                        <tbody>

                        @if(isset($list) && $list)

                            @foreach($list as $value)

                                <tr>
                                    <td class="sign">
                                        <div class="tDiv">
                                            <input type="checkbox" class="checkbox" value="{{ $value['drp_log_id'] ?? 0 }}" id="checkbox_{{ $value['drp_log_id'] }}"
                                               @if(isset($value['drp_is_separate']) && $value['drp_is_separate'] == 0 && isset($value['separate_able']) && $value['separate_able'] == 1 && $on == 1)
                                               name="checkboxes[]"
                                               @else
                                               disabled="disabled"
                                                @endif
                                                >
                                            <label for="checkbox_{{ $value['drp_log_id'] }}" class="checkbox_stars @if($value['drp_is_separate'] == 0 && isset($value['separate_able']) && $value['separate_able'] == 1 && $on == 1) @else disabled @endif "></label>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="tDiv">
                                            <div class="drp-img fl">
                                                @if(isset($value['shop_portrait']) && $value['shop_portrait'])
                                                    <img class="img-rounded" src="{{ $value['shop_portrait'] }}" width="50" height="50" alt="{{ $val['user_name'] ?? '' }}"/>
                                                @endif
                                            </div>
                                            <div class="drp-name fl">
                                                {{ $value['user_name'] ?? '' }}
                                                <br>{{ $value['mobile'] }}
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="tDiv">{{ $value['shop_name'] ?? '' }}</div>
                                    </td>
                                    <td>
                                        <div class="tDiv">
                                            {{ $value['is_paid_format'] ?? '' }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="tDiv">{{ $value['pay_time_format'] ?? '' }}</div>
                                    </td>
                                    <td>
                                        <div class="tDiv">{{ $value['sch_status'] ?? '' }}</div>
                                    </td>

                                    <td>
                                        @if(isset($value['info']) && $value['info'])

                                            @if(isset($value['drp_is_separate']) && $value['drp_is_separate'] == 3)
                                                @foreach($value['info'] as $info)
                                                    <div class="tDiv"><s>{{ $info['info'] }}</s></div>
                                                @endforeach
                                            @else
                                                @foreach($value['info'] as $info)
                                                    <div class="tDiv">{{ $info['info'] }}</div>
                                                @endforeach
                                            @endif

                                        @else
                                                <div class="tDiv">{{ $lang['no_operate_wait'] }}</div>
                                        @endif

                                    </td>
                                    <td>
                                        <div class="tDiv">{{ $value['user_note'] ?? '' }}</div>
                                    </td>

                                    <td>
                                        <div class="tDiv">

                                            @if(isset($value['drp_is_separate']) && $value['drp_is_separate'] == 0 && isset($value['separate_able']) && $value['separate_able'] == 1 && $on == 1)

                                                {{--<a href="{{ route('admin/drp/separate_drp_order_buy',array('drp_log_id'=>$value['drp_log_id'])) }}">{{ $lang['drp_affiliate_separate'] }}</a>&nbsp;|
                                                <a href="{{ route('admin/drp/del_drp_order',array('account_log_id'=>$value['id'])) }}">&nbsp;{{ $lang['drp_affiliate_cancel'] }}</a>--}}

                                                <a href='javascript:void(0);' data-href="{{route('admin/drp/separate_drp_order_buy', ['drp_log_id' => $value['drp_log_id']]) }}" class="btn_trash js-separate_drp_order_buy">{{ $lang['drp_affiliate_separate'] }}</a>&nbsp;|
                                                <a href='javascript:void(0);' onclick="if(confirm('{{ $lang['is_drp_affiliate_cancel'] }}')){window.location.href='{{route('admin/drp/del_drp_order_buy', ['account_log_id' => $value['id']]) }}'}" class="btn_trash">{{ $lang['drp_affiliate_cancel'] }}</a>

                                            @elseif(isset($value['drp_is_separate']) && $value['drp_is_separate'] == 1)

                                                {{ $lang['sch_stats']['1'] }}&nbsp;
                                                {{--<a href="{{ route('admin/drp/rollback_drp_order',array('account_log_id'=>$value['id'])) }}">&nbsp;{{ $lang['drp_affiliate_rollback'] }}</a>--}}

                                            @else

                                                @if(isset($value['drp_is_separate']) && $value['drp_is_separate'] == 0)

                                                    {{ $lang['be_short_of'] }}{{ $able_day }}{{ $lang['day'] }}

                                                @else

                                                    -

                                                @endif

                                            @endif

                                        </div>
                                    </td>
                                </tr>

                            @endforeach

                        @else

                            <tr>
                                <td class="no-records" colspan="9">{{ $lang['no_records'] }}</td>
                            </tr>

                        @endif

                        </tbody>

                        <tfoot>
                        <tr>
                            <td >
                                <div class="tDiv of">
                                    <div class="tfoot_btninfo">
                                        <input type="submit" onclick="confirm_bath()" ectype="btnSubmit" value="{{ $lang['drp_affiliate_batch'] }}" class="button bg-green btn_disabled"  disabled="true">
                                    </div>
                                </div>
                            </td>
                            <td colspan="9">
                                <div class="list-page">
                                    @include('admin.drp.pageview')
                                </div>
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

    // 分成
    $(".js-separate_drp_order_buy").on('click', function () {
        var url = $(this).attr("data-href");
        //询问框
        layer.confirm('{{ lang('admin/drp.is_drp_affiliate_separate')}}', {
            btn: ['{{ lang('admin/common.ok') }}', '{{ lang('admin/common.cancel')}}'] //按钮
        }, function () {
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

    });

    // 全选切换效果
    $(document).on("click", "input[name='all_list']", function () {
        if ($(this).prop("checked") == true) {
            $(".list-div").find("input[type='checkbox']:not(:disabled)").prop("checked", true);
            $(".list-div").find("input[type='checkbox']").parents("tr").addClass("tr_bg_org");
        } else {
            $(".list-div").find("input[type='checkbox']:not(:disabled)").prop("checked", false);
            $(".list-div").find("input[type='checkbox']").parents("tr").removeClass("tr_bg_org");
        }

        btnSubmit();
    });

    // 单选切换效果
    $(document).on("click", ".sign .checkbox", function () {
        if ($(this).is(":checked")) {
            $(this).parents("tr").addClass("tr_bg_org");
        } else {
            $(this).parents("tr").removeClass("tr_bg_org");
        }

        btnSubmit();
    });

    // 禁用启用提交按钮
    function btnSubmit() {
        var length = $(".list-div").find("input[name='checkboxes[]']:checked").length;

        if ($("#listDiv *[ectype='btnSubmit']").length > 0) {
            if (length > 0) {
                $("#listDiv *[ectype='btnSubmit']").removeClass("btn_disabled");
                $("#listDiv *[ectype='btnSubmit']").attr("disabled", false);
            } else {
                $("#listDiv *[ectype='btnSubmit']").addClass("btn_disabled");
                $("#listDiv *[ectype='btnSubmit']").attr("disabled", true);
            }
        }
    }

    // 批量分成
    function confirm_bath() {

        //选中记录
        var ids = new Array();
        $("input[name='checkboxes[]']:checked").each(function () {
            ids.push($(this).val());
        })

        if (order_ids) {
            $.post("{{ route('admin/drp/separate_drp_order_buy') }}", {drp_log_id: ids}, function (data) {
                if (data.url) {
                    window.location.href = data.url;
                }
            }, 'json');
        }

        return false;
    }

    var opts1 = {
        'targetId': 'promote_start_date',
        'triggerId': ['promote_start_date'],
        'alignId': 'text_time1',
        'format': '-',
        'hms': 'off'
    }, opts2 = {
        'targetId': 'promote_end_date',
        'triggerId': ['promote_end_date'],
        'alignId': 'text_time2',
        'format': '-',
        'hms': 'off'
    }

    xvDate(opts1);
    xvDate(opts2);

});

</script>

@include('admin.drp.pagefooter')
