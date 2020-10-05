@include('seller.base.seller_pageheader')

@include('seller.base.seller_nave_header')

<div class="ecsc-layout">
    <div class="site wrapper">
        @include('seller.base.seller_menu_left')

        <div class="ecsc-layout-right">
            <div class="main-content" id="mainContent">
                @include('seller.base.seller_nave_header_title')
                <div class="wrapper-right of">
                    <div class="tabmenu">
                        <ul class="tab ">
                            <li><a href="{{ route('seller/wechat/extend_index') }}"
                                   class="s-back">{{ $lang['back'] }}</a></li>
                            <li role="presentation" class="active"><a href="#home" role="tab"
                                                                      data-toggle="tab">{{ $postion['ur_here'] ?? '' }}
                                    {{ $lang['winner_list'] }}</a></li>
                        </ul>
                    </div>
                    <div class="explanation" id="explanation">
                        <div class="ex_tit"><i class="sc_icon"></i><h4>{{ $lang['operating_hints'] }}</h4></div>
                        <ul>
                            @if(isset($lang['winner_list_tips']) && !empty($lang['winner_list_tips']))

                                @foreach($lang['winner_list_tips'] as $v)
                                    <li>{{ $v }}</li>
                                @endforeach

                            @endif
                        </ul>
                    </div>
                    <div class="wrapper-list mt20">
                        <div class="list-div" id="listDiv">
                            <table class="ecsc-default-table ecsc-table-seller mt20">
                                <tbody>
                                <tr>
                                    <th width="10%">{{ $lang['sub_nickname'] }}</th>
                                    <th width="15%">{{ $lang['prize_name'] }}</th>
                                    <th width="7%">{{ $lang['issue_status'] }}</th>
                                    <th>{{ $lang['winner_info'] }}</th>
                                    <th width="13%">{{ $lang['prize_time'] }}</th>
                                    <th width="33%">{{ $lang['handler'] }}</th>
                                </tr>

                                @foreach($list as $val)

                                    <tr>
                                        <td class="tl">{{ $val['nickname'] }}</td>
                                        <td>{{ $val['prize_name'] }}</td>
                                        <td>
                                            @if($val['issue_status'])
                                                {{ $lang['already_issue'] }}
                                            @else
                                                {{ $lang['no_issue'] }}
                                            @endif
                                        </td>
                                        <td>

                                            @if(!empty($val['winner']) && is_array($val['winner']))

                                                <p>{{ $lang['user_name'] }}：{{ $val['winner']['name'] }}</p>
                                                <p>{{ $lang['user_mobile'] }}：{{ $val['winner']['phone'] }}</p>
                                                <p>{{ $lang['user_address'] }}：{{ $val['winner']['address'] }}</p>

                                            @endif

                                        </td>
                                        <td>{{ $val['dateline'] }}</td>
                                        <td class="handle">
                                            <div class="tDiv a3">

                                                @if($val['issue_status'])

                                                    <a href="{{ route('seller/wechat/winner_issue', array('id'=>$val['id'], 'cancel'=>1,'ks'=>$activity_type)) }}"
                                                       class="btn_region"><i
                                                                class="fa fa-send"></i>{{ $lang['unset_issue_status'] }}
                                                    </a>

                                                @else

                                                    <a href="{{ route('seller/wechat/winner_issue', array('id'=>$val['id'], 'cancel'=>0,'ks'=>$activity_type)) }}"
                                                       class="btn_region"><i
                                                                class="fa fa-send-o"></i>{{ $lang['set_issue_status'] }}
                                                    </a>

                                                @endif

                                                <a href="{{ route('seller/wechat/send_custom_message', array('openid'=>$val['openid'])) }}"
                                                   class="btn_inst fancybox fancybox.iframe"><i
                                                            class="fa fa-bullhorn"></i>{{ $lang['send_message'] }}</a>
                                                <a href="javascript:;"
                                                   data-href="{{ route('seller/wechat/winner_del', array('id'=>$val['id'], 'ks'=>$activity_type)) }}"
                                                   class="btn_trash delete_confirm"><i
                                                            class="fa fa-trash-o"></i>{{ $lang['drop'] }}</a>
                                            </div>
                                        </td>
                                    </tr>

                                @endforeach

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
