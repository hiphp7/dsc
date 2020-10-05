@include('admin.base.header')
<style>
    .label {
        color: #060202;
    }
    .indBlock .item .value {
        float: left;
        width: 325px;
        padding-left: 50px;
        color: rgb(51, 51, 51);
    }
    .indBlock .item .label {
        width: 120px;
        float: left;
        text-align: right;
    }

    .indBlock {
        width: 510px;
    }
    .label {
        line-height: 2;
    }


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
<div class="warpper">
    <div class="title">{{ $lang['activity_list'] }} - {{ $lang['activity_details'] }}</div>
    <div class="content">

        <div class="explanation" id="explanation">
            <div class="ex_tit"><i class="sc_icon"></i><h4>{{ $lang['operating_hints'] }}</h4><span id="explanationZoom" title="{{ $lang['activity_list_menu'] }}"></span>
            </div>
            <ul>
                @if(isset($lang['activity_details_tips']) && !empty($lang['activity_details_tips']))

                    @foreach($lang['activity_details_tips'] as $v)
                        <li>{!! $v !!}</li>
                    @endforeach
                @endif
            </ul>
        </div>

        <div class="flexilist">
            <div class="common-content">
                <div class="act-div">
                    <div class="indBlock">
                        <i class="sc_icon sc_icon_lt"></i>
                        <i class="sc_icon sc_icon_rb"></i>
                        <div class="item">
                            <div class="label">{{$lang['activity_name']}}：</div>
                            <div class="value"><h4>{{$activity_res['act_name']}}</h4></div>
                        </div>

                        <div class="item">
                            <div class="label">{{$lang['seller_user_name']}}：</div>
                            <div class="value"><h4>{{ isset($activity_res['ru_user_name']) ? $activity_res['ru_user_name'] : $lang['platform_self_activity'] }}</h4></div>
                        </div>

                        <div class="item">
                            <div class="label">{{$lang['start_end_time']}}：</div>
                            <div class="value">{{ $activity_res['start_time_format']}}&nbsp;~&nbsp;{{ $activity_res['end_time_format'] }}</div>
                        </div>
                        <div class="item">
                            <div class="label">{{$lang['activity_details']}}：</div>
                            <div class="value">{{$activity_res['act_dsc']}}</div>
                        </div>

                        <div class="item">
                            <div class="label">{{$lang['act_type_share_details']}}：</div>
                            <div class="value">{{$activity_res['act_type_share']}}</div>
                        </div>

                        <div class="item">
                            <div class="label">{{$lang['act_type_place_details']}}：</div>
                            <div class="value">{{$activity_res['act_type_place']}}</div>
                        </div>

                        <div class="item">
                            <div class="label">{{$lang['detail_type']}}：</div>
                            <div class="value">
                                @if($activity_res['is_finish'] == 1)
                                    {{ $lang['open_activity'] }}
                                @else
                                    {{ $lang['close_activity'] }}
                                @endif
                            </div>
                        </div>

                        <div class="item">
                            <div class="value">
                            <div class="fl">
                                <form action="{{ route('distribute.admin.activity_grant_award',array('id'=>$activity_res['id'])) }}" method="post">
                                    <div>
                                        <div class="input" class="search" style="border: 0px solid #dbdbdb;">
                                            @csrf
                                            <input type="submit" name="export" value="{{ $lang['grant_award'] }}" class="button bg-green" style="background: #ff0000;"/>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <div class="fl">
            <form action="{{ route('distribute.admin.activity_details_export',array('id'=>$activity_res['id'])) }}" method="post">
                <div>

                    <div class="input" class="search" style="border: 0px solid #dbdbdb;">
                        @csrf
                        <input type="submit" name="export" value="{{ $lang['export'] }}" class="button bg-green"/>

                            <div class="label_value text_time">
                                <div class="text_time" id="text_time1" style="float:left;">
                                    <input type="text" name="start_time" value="{{$activity_detail['start_time'] ?? date('Y-m-d H:i', mktime(0,0,0,date('m') - 1 , date('d'), date('Y'))) }}" id="start_time" class="text mr0 w150" readonly />
                                </div>
                                <span class="bolang">&nbsp;&nbsp;~&nbsp;&nbsp;</span>
                                <div class="text_time" id="text_time2" style="float:left;">
                                    <input type="text" name="end_time" value="{{$activity_detail['end_time'] ?? date('Y-m-d H:i') }}" id="end_time" class="text w150" readonly />
                                </div>
                                <div class="form_prompt"></div>
                            </div>

                    </div>

                </div>
            </form>
        </div>



        <div class="mt10"></div>

        <div class="list-div" id="listDiv">
            <table cellpadding="0" cellspacing="0" border="0">

                <thead>
                <tr>
                    <th width="5%"><div class="tDiv">{{$lang['details_user_id']}}</div></th>
                    <th width="15%"><div class="tDiv">{{$lang['details_user_name']}}</div></th>
                    <th width="15%"><div class="tDiv">{{$lang['details_user_num_one']}}</div></th>
                    <th width="15%"><div class="tDiv">{{$lang['details_user_num_two']}}</div></th>
                    <th width="15%"><div class="tDiv">{{$lang['yet_delivery_num_one']}}</div></th>
                    <th width="15%"><div class="tDiv">{{$lang['not_yet_delivery_num_one']}}</div></th>
                    <th width="5%"><div class="tDiv">{{$lang['details_user_type']}}</div></th>
                    <th width="25%"><div class="tDiv">{{$lang['details_user_time']}}</div></th>
                </tr>
                </thead>

                <tbody>
                @if( empty($all_activity_user))
                    <tr><td class="no-records" align="center" colspan="10">{{$lang['no_records']}}</td></tr>
                @else
                @foreach($all_activity_user as $val)
                    <tr>
                        <td><div class="tDiv">{{$val['reward_id']}}</div></td>
                        <td><div class="tDiv">{{$val['user_name']}}</div></td>
                        <td><div class="tDiv">{{$val['completeness_share']}}</div></td>
                        <td><div class="tDiv">{{$val['completeness_place']}}</div></td>
                        <td><div class="tDiv">{{isset($all_order_statistics['finish_order_num'][$val['user_id']]) ? $all_order_statistics['finish_order_num'][$val['user_id']] : 0}}</div></td>
                        <td><div class="tDiv">{{isset($all_order_statistics['unfinish_order_num'][$val['user_id']]) ? $all_order_statistics['unfinish_order_num'][$val['user_id']] : 0}}</div></td>
                        <td>
                            <div class="tDiv">
                                @if($val['participation_status'] == 1)
                                    {{ $lang['details_type_success'] }}
                                @else
                                    {{ $lang['details_type_loss'] }}
                                @endif
                            </div>
                        </td>
                        <td><div class="tDiv">{{date('Y-m-d H:i:s',$val['add_time'])}}</div></td>
                    </tr>
                @endforeach
                @endif
                </tbody>
                <tfoot>
                <tr>
                    <td colspan="8">
                            @include('admin.drp.pageview')
                    </td>
                </tr>
                </tfoot>
            </table>
        </div>

    </div>
 </div>
<script type="text/javascript">
    //时间选择
    var opts1 = {
        'targetId':'start_time',
        'triggerId':['start_time'],
        'alignId':'text_time1',
        'format':'-',
        'min':''
    },opts2 = {
        'targetId':'end_time',
        'triggerId':['end_time'],
        'alignId':'text_time2',
        'format':'-',
        'min':''
    }
    xvDate(opts1);
    xvDate(opts2);
</script>
@include('admin.drp.pagefooter')