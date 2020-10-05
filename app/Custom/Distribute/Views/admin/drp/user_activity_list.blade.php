@include('admin.drp.pageheader')
<style>
    .bg-green {
        margin-left: 3em;
        margin-top: 0.1em;
    }
</style>
<div class="wrapper">
    <div class="title">{{ $lang['activity_list'] }} - {{ $lang['activity_list_menu'] }}</div>
    <div class="content_tips">
        @include('base.common_tabs_info')
        <div class="explanation" id="explanation">
            <div class="ex_tit"><i class="sc_icon"></i><h4>{{ $lang['operating_hints'] }}</h4><span id="explanationZoom" title="{{ $lang['activity_list_menu'] }}"></span>
            </div>
            <ul>
                @if(isset($lang['user_activity_list_tips']) && !empty($lang['user_activity_list_tips']))

                    @foreach($lang['user_activity_list_tips'] as $v)
                        <li>{!! $v !!}</li>
                    @endforeach
                @endif
            </ul>
        </div>

        <div class="flexilist">

            <div class="tabs_info">
                <ul>
                    <li><a href="{{ route('distribute.admin.activity_list',['seller_list'=>$seller_list]) }}">{{$lang['activity_lists']}}</a></li>
                    <li class="curr"><a href="{{ route('distribute.admin.user_activity_list',['seller_list'=>$seller_list])  }}">{{$lang['user_activity_list']}}</a></li>
                </ul>
            </div>

            <div class="common-head">

                <div class="fl">
                        <a href="{{route('distribute.admin.activity_info')}}"><div class="fbutton"><div class="add" title="{{$lang['activity_list_add']}}"><span><i class="icon icon-plus"></i>{{$lang['activity_list_add']}}</span></div></div></a>
                </div>

                <div class="fl">
                    <form action="{{ route( 'distribute.admin.user_activity_list_export',['keywords'=>$keywords,'seller_list'=>$seller_list] ) }}" method="post">
                        <div>
                            <div class="input" class="search" style="border: 0px solid #dbdbdb;">
                                @csrf
                                <input type="submit" name="export" value="{{ $lang['export'] }}" class="button bg-green"/>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="search">

                    <form action="{{ route('distribute.admin.user_activity_list',['seller_list'=>$seller_list] ) }}" method="post">
                        <div class="search">
                            <div class="input">
                                @csrf
                                <input type="text" name="keyword" class="text nofocus"
                                       placeholder="{{ $lang['user_activity_name'] }}" autocomplete="off" value="{{ $keywords }}">
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
                                    <th width="5%"><div class="tDiv">{{$lang['details_user_name']}}</div></th>
                                    <th width="15%"><div class="tDiv">{{$lang['activity_name']}}</div></th>
                                    <th width="8%"><div class="tDiv">{{$lang['activity_reward_money']}}</div></th>
                                    <th width="5%"><div class="tDiv">{{$lang['activity_reward_type']}}</div></th>
                                    <th width="5%"><div class="tDiv">{{$lang['details_user_type']}}</div></th>
                                    <th width="5%"><div class="tDiv">{{$lang['details_user_time']}}</div></th>
                                    <th width="5%"><div class="tDiv" style="margin-left: 28%">{{$lang['activity_operation']}}</div></th>
                                </tr>
                                </thead>

                                <tbody>
                                @if( empty($all_activity))
                                    <tr><td class="no-records" colspan="11">{{$lang['no_records']}}</td></tr>
                                @else
                                    @foreach($all_activity as $val)
                                        <tr>
                                            <td><div class="tDiv">{{$val['users']['user_name']}}</div></td>

                                            <td><div class="tDiv overflow_view"><span title="{{$val['activity_detailes']['act_name']}}" data-toggle="tooltip">{{$val['activity_detailes']['act_name']}}</span></div></td>

                                            <td>
                                                <div class="tDiv clamp2">{{ $val['activity_detailes']['raward_money'] }}</div>
                                            </td>

                                            <td><div class="tDiv">@if( empty($val['activity_detailes']['raward_type'])) {{$lang['integral']}} @elseif($val['activity_detailes']['raward_type'] == 1) {{$lang['balance']}} @elseif($val['activity_detailes']['raward_type'] == 2) {{$lang['commission']}} @endif</div></td>

                                            <td>
                                                <div class="tDiv overflow_view">
                                                    <span title="{{$val['activity_detailes']['end_time']}}" data-toggle="tooltip">
                                                        @if($val['award_status'] == 1)
                                                            {{ $lang['details_type_success'] }}
                                                        @else
                                                            {{ $lang['details_type_loss'] }}
                                                        @endif
                                                    </span>
                                                </div>
                                            </td>

                                            <td>
                                                <div class="tDiv clamp2">{{ date('Y-m-d H:i:s',$val['add_time'])}}</div>
                                            </td>

                                            <td class="handle">
                                                <div class="tDiv ht_tdiv">
                                                    <a href="{{ route('distribute.admin.activity_details', array('id'=>$val['activity_detailes']['id'])) }}" title="{{$lang['user_examine_activity']}}" class="btn_see"><i class="sc_icon sc_icon_see"></i>{{$lang['user_examine_activity']}}</a>
                                                    <a href="javascript:;" onclick="remove_user_activity({{$val['reward_id']}})" title="{{$lang['drop_activity']}}" class="btn_trash"><i class="icon icon-trash"></i>{{$lang['drop_activity']}}</a>
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
    /* 删除活动 */
    function remove_user_activity(id){
        $.post("{{ route('distribute.admin.user_activity_remove') }}", {id: id}, function (result) {
            layer.msg(result.msg);
            if (result.status == 1) {
                if (result.url) {
                    window.location.href = result.url;
                }
            }
            return false;
        }, 'json')
    }

    //搜索
    function searchGoodsname(){
        var form = $("#searchForm");
        var keywords = form.find("input[name='keywords']").val();
        console.log(keywords);
    }
</script>
@include('admin.drp.pagefooter')