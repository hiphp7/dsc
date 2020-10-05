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
                            <li role="presentation" class="active"><a href="#home" role="tab"
                                                                      data-toggle="tab">{{ $postion['ur_here'] ?? '' }}</a>
                            </li>
                        </ul>
                    </div>
                    <div class="explanation" id="explanation">
                        <div class="ex_tit"><i class="sc_icon"></i><h4>{{ $lang['operating_hints'] }}</h4></div>
                        <ul>
                            @if(isset($lang['wechat_market_tips']) && !empty($lang['wechat_market_tips']))

                                @foreach($lang['wechat_market_tips'] as $v)
                                    <li>{{ $v }}</li>
                                @endforeach

                            @endif
                        </ul>
                    </div>
                    <div class="wrapper-list market-index mt20">
                        <ul class="items-box seller-extend ">

                            @foreach($list as $val)

                                <a href="{{ $val['url'] }}">
                                    <li class="item_wrap">
                                        <div class="plugin_item">
                                            <div class="plugin_icon">
                                                <i class="icon iconfont icon-{{ $val['keywords'] }} bg-{{ $val['keywords'] }}"></i>
                                            </div>
                                            <div class="plugin_status">
                                        <span class="status_txt">
                                           <div class="list-div">
                                                <div class="handle">
                                                    <div class="tDiv">
                                                        <p class="btn_inst"><i
                                                                    class="sc_icon sc_icon_inst"></i>{{ $lang['manage'] }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </span>
                                            </div>
                                            <div class="plugin_content"><h3 class="title">{{ $val['name'] }}</h3>
                                                <p class="desc">{{ $val['desc'] }}</p></div>
                                        </div>
                                    </li>
                                </a>

                            @endforeach

                        </ul>
                    </div>
                </div>
            </div>
        </div>


    </div>
</div>


@include('seller.base.seller_pagefooter')

