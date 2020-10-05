@include('seller.base.seller_pageheader')

@include('seller.base.seller_nave_header')


<div class="ecsc-layout">
    <div class="site wrapper">
        @include('seller.base.seller_menu_left')

        <div class="ecsc-layout-right">
            <div class="main-content" id="mainContent">
                @include('seller.base.seller_nave_header_title')

                {{--<div class="ecsc-path"><span>{{ $menu_select['action_label'] ?? '' }} - {{ $lang['seller_activity_list'] ?? '' }}</span></div>--}}

                <div class="wrapper-right of">

                <div class="explanation" id="explanation">
                    <div class="ex_tit"><i class="sc_icon"></i><h4>{{ $lang['operating_hints'] }}</h4></div>
                    <ul>
                        @if(isset($lang['seller_activity_list_tips']) && !empty($lang['seller_activity_list_tips']))

                            @foreach($lang['seller_activity_list_tips'] as $v)
                                <li>{{ $v }}</li>
                            @endforeach

                        @endif
                    </ul>
                </div>

                <div class="common-head mt20 fl">
                    <!-- 搜索 -->
                    <div class="search-info ">
                        <form action="{{ route('distribute.seller.activity_list') }}" name="searchForm" method="post"
                              role="search">
                            <div class="search-form">
                                <div class="search-key">
                                    @csrf
                                    <input type="text" name="keyword" class="text nofocus"
                                           placeholder="{{ $lang['seller_activity_search_name'] }}" autocomplete="off" value="{{ $keywords ?? '' }}" >
                                    <input type="submit" value="" class="submit search_button">
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="search-info mt20">

                    <a class="sc-btn sc-blue-btn" href="{{ route('distribute.seller.activity_info') }}"><i class="fa fa-plus"></i>{{ $lang['seller_add_activity'] }}</a>

                </div>

                    <div class="wrapper-list mt20">
                        <div class="list-div">
                            <table class="ecsc-default-table goods-default-table">
                                <thead>
                                <tr ectype="table_header">
                                    <th width="10%">{{ $lang['seller_activity_id'] }}</th>
                                    <th width="10%" class="tl">{{ $lang['seller_activity_name'] }}</th>
                                    <th width="15%">{{ $lang['seller_activity_start_time'] }}</th>
                                    <th width="15%">{{ $lang['seller_activity_end_time'] }}</th>
                                    <th width="10%">{{ $lang['seller_activity_reward_money'] }}</th>
                                    <th width="10%">{{ $lang['seller_activity_reward_type'] }}</th>
                                    <th width="10%">{{ $lang['seller_activity_reward_status'] }}</th>
                                    <th>{{ $lang['handler'] }}</th>
                                </tr>
                                </thead>
                                <tbody>

                                @if(isset($all_activity))

                                    @foreach($all_activity as $val)

                                        <tr>
                                            <td>
                                                <span>{{ $val['id'] }}</span>
                                            </td>
                                            <td>
                                                <span>{{ $val['act_name'] }}</span>
                                            </td>
                                            <td>
                                                <em class="green">{{ $val['start_time'] }}</em>
                                            </td>
                                            <td>
                                                <em class="green">{{ $val['end_time'] }}</em>
                                            </td>
                                            <td>
                                                <span>{{ $val['raward_money'] }}</span>
                                            </td>
                                            <td>
                                                @if(empty($val['raward_type']))
                                                <span>{{ $lang['seller_reward_type_0'] }}</span>
                                                @elseif($val['raward_type'] == 1)
                                                    <span>{{ $lang['seller_reward_type_1'] }}</span>
                                                @elseif($val['raward_type'] == 2)
                                                    <span>{{ $lang['seller_reward_type_2'] }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if(empty($val['is_finish']))
                                                    <span>{{ $lang['seller_status_0'] }}</span>
                                                @else
                                                    <span>{{ $lang['seller_status_1'] }}</span>
                                                @endif
                                            </td>
                                            <td class="ecsc-table-handle">
                                                <span><a href="javascript:;" class="btn-red  delete_confirm" data-href="{{ route('distribute.seller.activity_remove', array('id'=>$val['id'])) }} "><p>{{ $lang['drop'] }}</p></a></span>
                                                <span><a href="{{ route('distribute.seller.activity_details', array('id'=>$val['id'])) }}" title="{{ $lang['seller_check_activity'] }}" class="btn-orange"><p>{{ $lang['seller_check_activity'] }}</p></a></span>
                                                <span><a href="{{ route('distribute.seller.activity_info', array('id'=>$val['id'])) }}" title="{{ $lang['seller_compile_act'] }}" class="btn-green"><p>{{ $lang['seller_compile_act'] }}</p></a></span>
                                            </td>
                                        </tr>

                                    @endforeach

                                @else

                                    <tr>
                                        <td class="no-records" colspan="5">{{ $lang['no_records'] }}</td>
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

