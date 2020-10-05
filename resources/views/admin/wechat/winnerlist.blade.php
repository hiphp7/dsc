@include('admin.wechat.pageheader')
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
    <div class="title"><a href="{{ route('admin/wechat/extend_index') }}" class="s-back">{{ $lang['back'] }}</a> {{ $lang['winner_list'] }}
    </div>
    <div class="content_tips">
        <div class="explanation" id="explanation">
            <div class="ex_tit"><i class="sc_icon"></i><h4>{{ $lang['operating_hints'] }}</h4><span id="explanationZoom"
                                                                                                    title="{{ $lang['fold_tips'] }}"></span>
            </div>
            <ul>
                @if(isset($lang['winner_list_tips']) && !empty($lang['winner_list_tips']))

                    @foreach($lang['winner_list_tips'] as $v)
                        <li>{{ $v }}</li>
                    @endforeach

                @endif
            </ul>
        </div>
        <div class="flexilist">
            <div class="common-head">
                <div class="fl">
                    <form action="{{ route('admin/wechat/export_winner', array('ks' => $activity_type)) }}"
                          method="post">
                        <div class="label_value">
                            <div class="text_time" id="text_time1" style="float:left;">
                                <input type="text" name="starttime" class="text"
                                       value="{{ date('Y-m-d H:i', mktime(0,0,0,date('m'), date('d')-7, date('Y'))) }}"
                                       id="promote_start_date" class="text mr0" readonly>
                            </div>

                            <div class="text_time" id="text_time2" style="float:left;">
                                <input type="text" name="endtime" class="text" value="{{ date('Y-m-d H:i') }}"
                                       id="promote_end_date" class="text" readonly>
                            </div>
                            @csrf
                            <input type="submit" name="export" value="{{ $lang['export_excel'] }}"
                                   class="button bg-green"/>
                        </div>
                    </form>
                </div>

                <div class="search">
                    <form action="{{ route('admin/wechat/winner_list', array('ks' => $activity_type)) }}"
                          name="searchForm" method="post" role="search">
                        <div class="input">
                            @csrf
                            <input type="text" name="keywords" class="text nofocus" placeholder="{{ $lang['search_for'] }}"
                                   autocomplete="off">
                            <input type="submit" value="" class="btn search_button">
                        </div>
                    </form>
                </div>
            </div>
            <div class="common-content">
                <div class="list-div" id="min-h300">
                    <table cellspacing="0" cellpadding="0" border="0">
                        <tr>
                            <th>
                                <div class="tDiv">{{ $lang['sub_nickname'] }}</div>
                            </th>
                            <th>
                                <div class="tDiv">{{ $lang['prize_name'] }}</div>
                            </th>
                            <th>
                                <div class="tDiv">{{ $lang['issue_status'] }}</div>
                            </th>
                            <th>
                                <div class="tDiv">{{ $lang['winner_info'] }}</div>
                            </th>
                            <th>
                                <div class="tDiv">{{ $lang['prize_time'] }}</div>
                            </th>
                            <th>
                                <div class="tDiv">{{ $lang['handler'] }}</div>
                            </th>
                        </tr>

                        @foreach($list as $val)

                            <tr>
                                <td>
                                    <div class="tDiv">{{ $val['nickname'] }}</div>
                                </td>
                                <td>
                                    <div class="tDiv">{{ $val['prize_name'] }}</div>
                                </td>
                                <td>
                                    <div class="tDiv">
                                        @if($val['issue_status'])
                                            {{ $lang['already_issue'] }}
                                        @else
                                            {{ $lang['no_issue'] }}
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="tDiv">
                                        @if(!empty($val['winner']) && is_array($val['winner']))
                                            {{ $lang['user_name'] }}：{{ $val['winner']['name'] }}<br/>{{ $lang['user_mobile'] }}：{{ $val['winner']['phone'] }}<br/>
                                            {{ $lang['user_address'] }}：{{ $val['winner']['address'] }}
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="tDiv">{{ $val['dateline'] }}</div>
                                </td>
                                <td class="handle">
                                    <div class="tDiv a3">

                                        @if($val['issue_status'])

                                            <a href="{{ route('admin/wechat/winner_issue', array('id'=>$val['id'], 'cancel'=>1, 'ks'=>$activity_type)) }}"
                                               class="btn_region"><i class="fa fa-send"></i>{{ $lang['unset_issue_status'] }}</a>

                                        @else

                                            <a href="{{ route('admin/wechat/winner_issue', array('id'=>$val['id'], 'cancel'=>0, 'ks'=>$activity_type)) }}"
                                               class="btn_region"><i class="fa fa-send-o"></i>{{ $lang['set_issue_status'] }}</a>

                                        @endif

                                        <a href="{{ route('admin/wechat/send_custom_message', array('openid'=>$val['openid'])) }}"
                                           class="btn_inst fancybox fancybox.iframe"><i class="fa fa-bullhorn"></i>{{ $lang['send_message'] }}</a>
                                        <a href="javascript:if(confirm('{{ $lang['confirm_delete'] }}'))window.location.href='{{ route('admin/wechat/winner_del', array('id'=>$val['id'], 'ks'=>$activity_type)) }}';"
                                           class="btn_trash"><i class="fa fa-trash-o"></i>{{ $lang['drop'] }}</a>
                                    </div>
                                </td>
                            </tr>

                        @endforeach


                        @if(empty($list))

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
</script>

@include('admin.wechat.pagefooter')
