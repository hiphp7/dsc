@include('seller.base.seller_pageheader')

@include('base.seller_nave_header')

<div class="ecsc-layout">
    <div class="site wrapper">
        @include('seller.base.seller_menu_left')

        <div class="ecsc-layout-right">
            <div class="main-content" id="mainContent">
                <div class="ecsc-path">
                    <span>{{ $menu_select['action_label'] ?? '' }} - {{ $lang['seller_activity_user_list'] ?? '' }}</span>
                </div>
                <div class="wrapper-right of">

                    <div class="explanation" id="explanation">
                        <div class="ex_tit"><i class="sc_icon"></i><h4>{{ $lang['operating_hints'] }}</h4></div>
                        <ul>
                            @if(isset($lang['seller_activity_user_list_tips']) && !empty($lang['seller_activity_user_list_tips']))

                                @foreach($lang['seller_activity_user_list_tips'] as $v)
                                    <li>{{ $v }}</li>
                                @endforeach

                            @endif
                        </ul>
                    </div>

                    <div class="wrapper-list mt20">
                        <div class="list-div">
                            <table class="ecsc-default-table goods-default-table">
                                <thead>
                                <tr ectype="table_header">
                                    <th width="10%">{{ $lang['seller_activity_user_name'] }}</th>
                                    <th width="10%" class="tl">{{ $lang['seller_activity_user_share'] }}</th>
                                    <th width="15%">{{ $lang['seller_activity_user_order'] }}</th>
                                    <th width="15%">{{ $lang['seller_activity_user_order_finish'] }}</th>
                                    <th width="10%">{{ $lang['seller_activity_user_order_unfinish'] }}</th>
                                    <th width="10%">{{ $lang['seller_activity_add_time'] }}</th>
                                </tr>
                                </thead>
                                <tbody>

                                @if(isset($all_activity_user))

                                    @foreach($all_activity_user as $val)

                                        <tr>
                                            <td>
                                                <span>{{ $val['user_name'] }}</span>
                                            </td>
                                            <td>
                                                <span>{{ $val['completeness_share'] }}</span>
                                            </td>
                                            <td>
                                                <em>{{ $val['completeness_place'] }}</em>
                                            </td>
                                            <td>
                                                <em>{{ isset($all_order_statistics['finish_order_num'][$val['user_id']])?$all_order_statistics['finish_order_num'][$val['user_id']]:0 }}</em>
                                            </td>
                                            <td>
                                                <span class="green">{{ isset($all_order_statistics['unfinish_order_num'][$val['user_id']])?$all_order_statistics['unfinish_order_num'][$val['user_id']]:0 }}</span>
                                            </td>
                                            <td>
                                                <em>{{ date('Y-m-d H:i:s',$val['add_time']) }}</em>
                                            </td>
                                        </tr>

                                    @endforeach

                                @else

                                    <tr>
                                        <td class="no-records" colspan="6">{{ $lang['no_records'] }}</td>
                                    </tr>

                                @endif

                                </tbody>
                            </table>
                        </div>
                        @include('seller.base.seller_pageview')
                    </div>

                </div>
            </div>
        </div>

    </div>
</div>

@include('seller.base.seller_pagefooter')

