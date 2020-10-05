@include('admin.drp.pageheader')

<div class="wrapper">
    <div class="title">{{ $lang['drp_list'] }}</div>
    <div class="content_tips">
        <div class="tabs_info">
            <ul>
                <li class="
@if($act =='')
                        curr
                        @endif
                        "><a href="{{ route('admin/drp/drp_list',array('where'=>'')) }}">{{ $lang['all'] }}</a></li>
                <li class="
@if($act =='1')
                        curr
                        @endif
                        "><a href="{{ route('admin/drp/drp_list',array('where'=>1)) }}">{{ $lang['one_year'] }}</a></li>
                <li class="
@if($act =='2')
                        curr
                        @endif
                        "><a href="{{ route('admin/drp/drp_list',array('where'=>2)) }}">{{ $lang['half_year'] }}</a>
                </li>
                <li class="
@if($act =='3')
                        curr
                        @endif
                        "><a href="{{ route('admin/drp/drp_list',array('where'=>3)) }}">{{ $lang['one_month'] }}</a>
                </li>
            </ul>
        </div>
        <div class="explanation" id="explanation">
            <div class="ex_tit"><i class="sc_icon"></i><h4>{{ $lang['operating_hints'] }}</h4><span id="explanationZoom"
                                                                                                    title="{{ $lang['fold_tips'] }}"></span>
            </div>
            <ul>
                <li>{{ $lang['drp_list_tips']['0'] }}</li>
                <li>{{ $lang['drp_list_tips']['1'] }}</li>
            </ul>
        </div>
        <div class="flexilist">
            <div class="common-content">
                <div class="list-div">

                    <div class="fl">
                        <form action="{{ route('distribute.admin.drp_list_export',array('act'=>$act)) }}" method="post">
                            <div>
                                <div class="input" class="search" style="border: 0px solid #dbdbdb;">
                                    @csrf
                                    <input type="submit" name="export" value="{{ $lang['export'] }}" class="button bg-green"/>
                                </div>
                            </div>
                        </form>
                    </div>

                    <form action="{{ route('admin/drp/shop') }}" method="post" class="form-horizontal" role="form">
                        <table cellspacing="0" cellpadding="0" border="0">

                            <tr class="active">
                                <th>
                                    <div class="tDiv">{{ $lang['record_id'] }}</div>
                                </th>
                                <th>
                                    <div class="tDiv">{{ $lang['shop_name'] }}</div>
                                </th>
                                <th>
                                    <div class="tDiv">{{ $lang['rely_name'] }}</div>
                                </th>
                                <th>
                                    <div class="tDiv">{{ $lang['credit_name'] }}</div>
                                </th>
                                <th>
                                    <div class="tDiv">{{ $lang['drp_money'] }}</div>
                                </th>
                                <th>
                                    <div class="tDiv">{{ $lang['team_drp_money'] }}</div>
                                </th>
                                <th>
                                    <div class="tDiv">{{ $lang['team_order_money'] }}</div>
                                </th>
                                <th>
                                    <div class="tDiv">{{ $lang['mobile'] }}</div>
                                </th>
                                <th>
                                    <div class="tDiv">{{ $lang['open_time'] }}</div>
                                </th>
                            </tr>

                            @if($list)

                                @foreach($list as $val)

                                    <tr>
                                        <td>
                                            <div class="tDiv">{{ $val['id'] }}</div>
                                        </td>
                                        <td>
                                            <div class="tDiv">{{ $val['shop_name'] }}</div>
                                        </td>
                                        <td>
                                            <div class="tDiv">{{ $val['name'] }}</div>
                                        </td>
                                        <td>
                                            <div class="tDiv">{{ $val['credit_name'] }}</div>
                                        </td>
                                        <td>
                                            <div class="tDiv">{{ $val['money'] }}</div>
                                        </td>
                                        <td>
                                            <div class="tDiv">{{ $val['team_money'] }}</div>
                                        </td>
                                        <td>
                                            <div class="tDiv">{{ $val['all_order_money'] }}</div>
                                        </td>
                                        <td>
                                            <div class="tDiv">{{ $val['mobile'] }}</div>
                                        </td>
                                        <td>
                                            <div class="tDiv">{{ $val['create_time'] }}</div>
                                        </td>
                                    </tr>

                                @endforeach

                            @else

                                <tbody>
                                <tr>
                                    <td class="no-records" colspan="7">{{ $lang['no_records'] }}</td>
                                </tr>
                                </tbody>

                            @endif

                            <tfoot>
                            <tr>
                                <td colspan="9">
                                    <div class="list-page">
                                        @include('admin.drp.pageview')
                                    </div>
                                </td>
                            </tr>
                            </tfoot>
                        </table>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>


@include('admin.drp.pagefooter')
