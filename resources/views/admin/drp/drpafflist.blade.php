@include('admin.drp.pageheader')

<div class="wrapper">
    <div class="title">
        @if($user_name)
            {{ $user_name }} {{ $lang['junior_user'] }}
        @endif
        {{ $lang['user_list'] }} </div>
    <div class="content_tips">
        <div class="tabs_info">
            <ul>

                @foreach($select as $key=>$val)

                    <li
                            @if($current_level == $val)
                            class="curr"
                            @endif
                    >
                        <a href="{{ route('admin/drp/drp_aff_list', array('auid' => $auid, 'level' => $val)) }}">{{ $lang['aff_list'] }} {{ $val }}</a>
                    </li>

                @endforeach

            </ul>
        </div>
        <div class="explanation" id="explanation">
            <div class="ex_tit"><i class="sc_icon"></i><h4>{{ lang('admin/common.operating_hints') }}</h4><span id="explanationZoom"
                                                                                                    title="{{ lang('admin/common.fold_tips') }}"></span>
            </div>
            <ul>
                <li>{{ $lang['aff_list_tips']['0'] }}</li>
                <li>{{ $lang['aff_list_tips']['1'] }}</li>
            </ul>
        </div>
        <div class="flexilist">
            <div class="common-head">
                <div class="fl">
                </div>
                <div class="search">
                    <form action="{{ route('admin/drp/drp_aff_list') }}" method="post">
                        @csrf
                        <div class="input">
                            <input type="text" placeholder="{{ $lang['search_user'] }}" name="keyword"
                                   class="text nofocus" autocomplete="off">
                            <input type="hidden" name="auid" value="{{ $auid }}">
                            <input type="hidden" name="level" value="{{ $current_level }}">
                            <input type="submit" name="export" value="" class="btn" style="font-style:normal">
                        </div>
                    </form>
                </div>
            </div>
            <div class="common-content">
                <div class="list-div" id="listDiv">
                    <table cellpadding="0" cellspacing="0" border="0">
                        <thead>
                        <tr>
                            <th>
                                <div class="tDiv">{{ $lang['user_id'] }}</div>
                            </th>
                            <th>
                                <div class="tDiv">{{ $lang['user_name'] }}</div>
                            </th>
                            <th>
                                <div class="tDiv">{{ $lang['aff_list'] }}</div>
                            </th>
                            <th>
                                <div class="tDiv">{{ $lang['user_email'] }}</div>
                            </th>
                            <th>
                                <div class="tDiv">{{ $lang['email_is_validated'] }}</div>
                            </th>
                            <th>
                                <div class="tDiv">{{ $lang['user_money'] }}</div>
                            </th>
                            <th>
                                <div class="tDiv">{{ $lang['frozen_money'] }}</div>
                            </th>
                            <th>
                                <div class="tDiv">{{ $lang['rank_point'] }}</div>
                            </th>
                            <th>
                                <div class="tDiv">{{ $lang['pay_point'] }}</div>
                            </th>
                            <th>
                                <div class="tDiv">{{ $lang['register_time'] }}</div>
                            </th>
                            <th>
                                <div class="tDiv text-center">{{ $lang['handler'] }}</div>
                            </th>
                        </tr>
                        </thead>

                        @if($user_list)

                            @foreach($user_list as $key=>$val)

                                <tr>
                                    <td>
                                        <div class="tDiv">{{ $val['user_id'] }}</div>
                                    </td>
                                    <td>
                                        <div class="tDiv">{{ $val['user_name'] }}</div>
                                    </td>
                                    <td>
                                        <div class="tDiv">{{ $val['level'] }}</div>
                                    </td>
                                    <td>
                                        <div class="tDiv">{{ $val['email'] }}</div>
                                    </td>
                                    <td>
                                        <div class="tDiv">
                                            @if($val['is_validated'] == 1)
                                                {{ $lang['already_validated'] }}
                                            @else
                                                {{ $lang['no_validated'] }}
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div class="tDiv">{{ $val['user_money'] }}</div>
                                    </td>
                                    <td>
                                        <div class="tDiv">{{ $val['frozen_money'] }}</div>
                                    </td>
                                    <td>
                                        <div class="tDiv">{{ $val['rank_points'] }}</div>
                                    </td>
                                    <td>
                                        <div class="tDiv">{{ $val['pay_points'] }}</div>
                                    </td>
                                    <td>
                                        <div class="tDiv">{{ $val['reg_time'] }}</div>
                                    </td>
                                    <td class="handle text-center">
                                        <div class="tDiv a4">
                                            <a href="{{ $val['edit_url'] }}" class="btn_edit"><i
                                                        class="fa fa-edit"></i>{{ $lang['edit'] }}</a>
                                            <a href="{{ $val['address_list'] }}" class="btn_see" title=""><i
                                                        class="sc_icon sc_icon_see"></i>{{ $lang['user_address'] }}</a>
                                            <a href="{{ $val['order_list'] }}" class="btn_see" title=""><i
                                                        class="sc_icon sc_icon_see"></i>{{ $lang['user_orders'] }}</a>
                                            <a href="{{ $val['account_log'] }}" class="btn_see" title=""><i
                                                        class="sc_icon sc_icon_see"></i>{{ $lang['user_account'] }}</a>
                                        </div>
                                    </td>
                                </tr>

                            @endforeach

                        @else

                            <tbody>
                            <tr>
                                <td class="no-records" colspan="11">{{ $lang['no_records'] }}</td>
                            </tr>
                            </tbody>

                        @endif

                        <tfoot>
                        <tr>
                            <td colspan="11">
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
</div>

@include('admin.drp.pagefooter')
