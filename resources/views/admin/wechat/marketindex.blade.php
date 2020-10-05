@include('admin.wechat.pageheader')
<style>
    /*#footer {position: static;bottom:0px;}*/
</style>
<div class="wrapper">
    <div class="title">{{ $lang['wechat_menu'] }} - {{ $lang['wechat_market'] }}</div>
    <div class="content_tips">
        <div class="explanation" id="explanation">
            <div class="ex_tit"><i class="sc_icon"></i><h4>{{ $lang['operating_hints'] }}</h4><span id="explanationZoom"
                                                                                                    title="{{ $lang['fold_tips'] }}"></span>
            </div>
            <ul>
                @if(isset($lang['wechat_market_tips']) && !empty($lang['wechat_market_tips']))

                    @foreach($lang['wechat_market_tips'] as $v)
                        <li>{{ $v }}</li>
                    @endforeach

                @endif
            </ul>
        </div>
        <div class="flexilist">
            <div class="common-content market-index">
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

@include('admin.wechat.pagefooter')