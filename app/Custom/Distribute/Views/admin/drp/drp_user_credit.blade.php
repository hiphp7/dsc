@include('admin.drp.pageheader')

<div class="wrapper">
    {{--分销商等级--}}
    <div class="title">{{ $lang['drp_manage'] }} - {{ $lang['drp_credit'] }}</div>
    <div class="content_tips">
        <div class="tabs_info">
            <ul>
                <li><a href="{{ route('admin/drp/shop') }}">{{ $lang['drp_shop_list'] }}</a></li>
                <li class="curr"><a href="{{ route('admin/drp/drp_user_credit') }}">{{ $lang['drp_credit'] }}</a></li>
            </ul>
        </div>
        <div class="explanation" id="explanation">
            <div class="ex_tit"><i class="sc_icon"></i><h4>{{ $lang['operating_hints'] }}</h4><span id="explanationZoom"
                                                                                                    title="{{ $lang['fold_tips'] }}"></span>
            </div>
            <ul>
                <li>{{ $lang['drp_credit_tips']['0'] }}</li>
                <li>{{ $lang['drp_credit_tips']['1'] }}</li>
            </ul>
        </div>
        <div class="flexilist">
            <div class="common-content">
                <div class="list-div" id="listDiv">
                    <table cellpadding="0" cellspacing="0" border="0">
                        <thead>
                        <tr>
                            <th>
                                <div class="tDiv">{{ $lang['drp_shop_name'] }}</div>
                            </th>
                            <th>
                                <div class="tDiv">{{ $lang['money_lower_limit'] }}</div>
                            </th>
                            <th>
                                <div class="tDiv">{{ $lang['money_up_limit'] }}</div>
                            </th>
                            <th>
                                <div class="tDiv text-center">{{ $lang['handler'] }}</div>
                            </th>
                        </tr>
                        </thead>

                        @foreach($list as $key=>$val)

                            <tr>
                                <td>
                                    <div class="tDiv">{{ $val['credit_name'] }}</div>
                                </td>
                                <td>
                                    <div class="tDiv">{{ $val['min_money'] }}</div>
                                </td>
                                <td>
                                    <div class="tDiv">{{ $val['max_money'] }}</div>
                                </td>
                                <td class="handle text-center">
                                    <div class="tDiv a1">
                                        <a href="{{ route('admin/drp/drp_user_credit_edit', array('id' => $val['id'])) }}"
                                           class="btn_edit fancybox fancybox.iframe"><i
                                                    class="fa fa-edit"></i>{{ $lang['edit'] }}</a>
                                        <a href="{{ route('admin/drp/drp_user_credit_condition', array('id' => $val['id'])) }}"
                                           class="btn_edit"><i
                                                    class="fa fa-edit"></i>{{ $lang['edit_credit_condition'] }}</a>
                                    </div>
                                </td>
                            </tr>

                        @endforeach

                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

</div>

@include('admin.drp.pagefooter')
