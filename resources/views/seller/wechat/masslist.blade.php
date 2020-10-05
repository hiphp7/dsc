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
                        <ul class="tab">
                            <li><a href="{{ route('seller/wechat/mass_message') }}">{{ $lang['mass_message'] }}</a></li>
                            <li class="active"><a
                                        href="{{ route('seller/wechat/mass_list') }}">{{ $lang['mass_history'] }}</a>
                            </li>
                        </ul>
                    </div>
                    <div class="explanation" id="explanation">
                        <div class="ex_tit"><i class="sc_icon"></i><h4>{{ $lang['operating_hints'] }}</h4></div>
                        <ul>
                            @if(isset($lang['mass_history_tips']) && !empty($lang['mass_history_tips']))

                                @foreach($lang['mass_history_tips'] as $v)
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
                                    <th width="5%">{{ $lang['record_id'] }}</th>
                                    <th width="35%" class="tl">{{ $lang['mass_title'] }}</th>
                                    <th width="30%">{{ $lang['mass_status'] }}</th>
                                    <th width="10%">{{ $lang['mass_send_time'] }}</th>
                                    <th>{{ $lang['handler'] }}</th>
                                </tr>
                                </thead>
                                <tbody>

                                @if($list)

                                    @foreach($list as $val)

                                        <tr>
                                            <td><span>{{ $val['id'] }}</span></td>
                                            <td class="tl">
                                                <div class="goods-info">
                                                    <div class="goods-img"><img src="{{ $val['artinfo']['file'] }}">
                                                    </div>
                                                    <div class="goods-desc">
                                                        <div class="name"><em
                                                                    class="fl blue mr5">[ {{ $val['artinfo']['type'] }}
                                                                ]</em> {{ $val['artinfo']['title'] }}</div>
                                                        <div class="goods-tag">
                                                            {{ sub_str($val['artinfo']['content'], 60) }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <p>{{ $val['status'] }}</p>
                                                <p>{{ $lang['mass_totalcount'] }}：{{ $val['totalcount'] }}人</p>
                                                <p>{{ $lang['mass_sentcount'] }}：{{ $val['sentcount'] }}人</p>
                                                <p>{{ $lang['mass_filtercount'] }}：{{ $val['filtercount'] }}人</p>
                                                <p>{{ $lang['mass_errorcount'] }}：{{ $val['errorcount'] }}人</p>
                                            </td>
                                            <td class="audit_status"><em
                                                        class="green">{{ date('Y年m月d日', $val['send_time']) }}</em>
                                            </td>
                                            <td class="ecsc-table-handle">
                                                <span><a href="javascript:;" class="btn-red  delete_confirm"
                                                         data-href="{{ route('seller/wechat/mass_del', array('id'=>$val['id'])) }}'}"><i
                                                                class="icon-trash"></i><p>{{ $lang['drop'] }}</p></a></span>
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

