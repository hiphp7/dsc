@include('admin.drp.pageheader')
<div class="wrapper">
    <div class="title">{{ $lang['activity_list'] }} - {{ $lang['activity_list_menu'] }}</div>
    <div class="content_tips">
        @include('base.common_tabs_info')
        <div class="explanation" id="explanation">
            <div class="ex_tit"><i class="sc_icon"></i><h4>{{ $lang['operating_hints'] }}</h4><span id="explanationZoom" title="{{ $lang['activity_list_menu'] }}"></span>
            </div>
            <ul>
                @if(isset($lang['activity_list_tips']) && !empty($lang['activity_list_tips']))

                    @foreach($lang['activity_list_tips'] as $v)
                        <li>{!! $v !!}</li>
                    @endforeach
                @endif
            </ul>
        </div>

        <div class="flexilist">

            <div class="tabs_info">
                <ul>
                    <li class="curr"><a href="{{ route('distribute.admin.activity_list',array('seller_list'=>$seller_list)) }}">{{$lang['activity_lists']}}</a></li>
                    <li><a href="{{ route('distribute.admin.user_activity_list',array('seller_list'=>$seller_list))  }}">{{$lang['user_activity_list']}}</a></li>
                </ul>
            </div>

            <div class="common-head">

                <div class="fl">
                    <a href="{{route('distribute.admin.activity_info')}}"><div class="fbutton"><div class="add" title="{{$lang['activity_list_add']}}"><span><i class="icon icon-plus"></i>{{$lang['activity_list_add']}}</span></div></div></a>
                </div>

                <div class="search">
                    <form action="{{ route('distribute.admin.activity_list') }}" method="post">
                        <div class="search">
                            <div class="input">
                                @csrf
                                <input type="text" name="keyword" class="text nofocus"
                                       placeholder="{{ $lang['activity_name'] }}" autocomplete="off" value="{{ $keywords }}">
                                <input type="submit" value="" class="btn" name="export">
                            </div>
                        </div>
                    </form>
                </div>

            </div>

            <div class="common-content">
                    <div class="list-div" id="listDiv">
                        <div class="flexigrid ht_goods_list">
                            <table cellpadding="0" cellspacing="0" border="0" class="table_layout">
                                <thead>
                                <tr>
                                    {{--<th width="3%" class="sign"><div style=" line-height: 0px"><input type="checkbox" name="all_list" class="checkbox" id="all_list" /><label for="all_list" class="checkbox_stars"></label></div></th>--}}
                                    <th width="5%"><div class="tDiv">{{$lang['activity_id']}}</div></th>
                                    <th width="15%"><div class="tDiv">{{$lang['activity_name']}}</div></th>
                                    <th width="15%"><div class="tDiv">{{$lang['seller_user_name']}}</div></th>
                                    <th width="8%"><div class="tDiv">{{$lang['goods_name']}}</div></th>
                                    <th width="5%"><div class="tDiv">{{$lang['time_start']}}</div></th>
                                    <th width="5%"><div class="tDiv">{{$lang['time_end']}}</div></th>
                                    <th width="5%"><div class="tDiv">{{$lang['activity_reward_money']}}</div></th>
                                    <th width="6%"><div class="tDiv">{{$lang['activity_reward_type']}}</div></th>
                                    <th width="6%"><div class="tDiv">{{$lang['is_finish']}}</div></th>
                                    <th width="10%"><div class="tDiv" style="margin-left: 28%">{{$lang['activity_operation']}}</div></th>
                                </tr>
                                </thead>
                                <tbody>
                            @if( empty($all_activity))
                                    <tr><td class="no-records" colspan="11">{{$lang['no_records']}}</td></tr>
                            @else
                            @foreach($all_activity as $val)
                                <tr>
                                    {{--<td class="sign">--}}
                                        {{--<div class="tDiv" style=" line-height: 0px">--}}
                                            {{--<input type="checkbox" name="checkboxes[]" value="{{$val['id']}}" class="checkbox" id="checkbox_{{$val['id']}}" />--}}
                                            {{--<label for="checkbox_{{$val['id']}}" class="checkbox_stars"></label>--}}
                                        {{--</div>--}}
                                    {{--</td>--}}
                                    <td><div class="tDiv">{{$val['id']}}</div></td>
                                    <td><div class="tDiv overflow_view"><span title="{{$val['act_name']}}" data-toggle="tooltip">{{$val['act_name']}}</span></div></td>
                                    <td><div class="tDiv">{{ isset($val['ru_user_name']) ? $val['ru_user_name'] : $lang['platform_self_activity'] }}</div></td>
                                    <td>
                                        <div class="tDiv clamp2">{{ $val['goods']['goods_name'] }}</div>
                                    </td>
                                    <td><div class="tDiv overflow_view"><span title="{{$val['start_time']}}" data-toggle="tooltip">{{$val['start_time']}}</span></div></td>
                                    <td><div class="tDiv overflow_view"><span title="{{$val['end_time']}}" data-toggle="tooltip">{{$val['end_time']}}</span></div></td>
                                    <td><div class="tDiv">{{$val['raward_money']}}</div></td>
                                    <td><div class="tDiv">@if( empty($val['raward_type'])) {{$lang['integral']}} @elseif($val['raward_type'] == 1) {{$lang['balance']}} @elseif($val['raward_type'] == 2) {{$lang['commission']}} @endif</div></td>
                                    <td>
                                        <div class="tDiv">
                                            @if( !empty($val['is_finish'])) {{$lang['open_activity']}} @else {{$lang['close_activity']}} @endif
                                            {{--<div class="switch mauto @if( !empty($val['is_finish'])) active @endif" onclick="switchBt(this, 'toggle_hot', {{$val['id']}})" title=" @if( !empty($val['is_finish'])) {{$lang['open_activity']}} @else {{$lang['close_activity']}} @endif">--}}
                                                {{--<div class="circle"></div>--}}
                                            {{--</div>--}}
                                            {{--<input type="hidden" value="0" name="">--}}
                                        </div>
                                    </td>
                                    <td class="handle">
                                        <div class="tDiv ht_tdiv">
                                            <a href="{{ route('distribute.admin.activity_details', array('id'=>$val['id'])) }}" title="{{$lang['examine_activity']}}" class="btn_see"><i class="sc_icon sc_icon_see"></i>{{$lang['examine_activity']}}</a>
                                            <a href="{{ route('distribute.admin.activity_info', array('id'=>$val['id'])) }}" title="{{$lang['compile_activity']}}" class="btn_edit"><i class="icon icon-edit"></i>{{$lang['compile_activity']}}</a>
                                            <a href="javascript:;" onclick="remove_activity({{$val['id']}})" title="{{$lang['drop_activity']}}" class="btn_trash"><i class="icon icon-trash"></i>{{$lang['drop_activity']}}</a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            @endif
                                <tfoot>
                                <tr>
                                    <td colspan="10">
                                        @include('admin.drp.pageview')
                                    </td>
                                </tr>
                                </tfoot>
                                </tbody>

                            </table>

                        </div>
                    </div>
            </div>

        </div>
    </div>
</div>
<script>
    /* 按钮切换 by wu */
    function switchBt(obj, act, id)
    {
        var obj = $(obj);
        $.post("{{ route('distribute.admin.activity_finish') }}", {id: id}, function (result) {
            if(result.status == 0){
                layer.msg(result.msg);
            }else{
                if (result) {
                    if (obj.hasClass("active")) {
                        obj.removeClass("active");
                        obj.next("input[type='hidden']").val(0);
                        obj.attr("title", "否");
                    } else {
                        obj.addClass("active");
                        obj.next("input[type='hidden']").val(1);
                        obj.attr("title", "是");
                    }
                }
            }
        }, 'json')
    }

    /* 删除活动 */
    function remove_activity(id){
        $.post("{{ route('distribute.admin.activity_remove') }}", {id: id}, function (result) {
            layer.msg(result.msg);
            if (result.status == 1) {
                if (result.url) {
                    window.location.href = result.url;
                }
            }
            return false;
        }, 'json')
    }
</script>
@include('admin.drp.pagefooter')
