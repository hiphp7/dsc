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
                            @if(isset($lang['wechat_extend_tips']) && !empty($lang['wechat_extend_tips']))

                                @foreach($lang['wechat_extend_tips'] as $v)
                                    <li>{{ $v }}</li>
                                @endforeach

                            @endif
                        </ul>
                    </div>
                    <div class="wrapper-list mt20">
                        <ul class="items-box seller-extend">

                            @foreach($plugins as $val)

                                <li class="item_wrap">
                                    <div class="plugin_item">
                                        <div class="plugin_icon">
                                            <i class="icon iconfont icon-{{ $val['command'] }} bg-{{ $val['command'] }}"></i>
                                        </div>
                                        <div class="plugin_status">
                                        <span class="status_txt">
                                           <div class="list-div">
                                                <div class="handle">
                                                    <div class="tDiv">

@if(isset($val['enable']) && !empty($val['enable']))

                                                            <a href="{{ route('seller/wechat/extend_edit', array('ks'=>$val['command'], 'handler'=>'edit')) }}"
                                                               class="btn_edit fancybox"><i
                                                                        class="fa fa-edit"></i>{{ $lang['edit'] }}</a>
                                                            <a href="javascript:;"
                                                               data-href="{{ route('seller/wechat/extend_uninstall', array('ks'=>$val['command'])) }}"
                                                               class="btn_trash delete_extend_confirm"><i
                                                                        class="fa fa-trash-o"></i>{{ $lang['uninstall'] }}</a>

                                                        @else

                                                            <a href="{{ route('seller/wechat/extend_edit', array('ks'=>$val['command'])) }}"
                                                               class="btn_inst fancybox"><i
                                                                        class="fa fa-gear"></i>{{ $lang['install'] }}</a>

                                                        @endif


                                                        @if(isset($val['enable']) && $val['enable'] == 1 && isset($val['config']['haslist']) && $val['config']['haslist'] == 1)
                                                            <a href="{{ route('seller/wechat/winner_list', array('ks'=>$val['command'])) }}"
                                                               class="btn_see"><i
                                                                        class="fa fa-eye"></i>{{ $lang['view_record'] }}</a>
                                                        @endif

                                                    </div>
                                                </div>
                                            </div>
                                        </span>
                                        </div>
                                        <div class="plugin_content"><h3 class="title">{{ $val['name'] }}</h3>
                                            <p class="desc">{{ $val['command'] }}</p></div>
                                    </div>
                                </li>

                            @endforeach

                        </ul>
                    </div>
                </div>
            </div>
        </div>


    </div>
</div>
<script type="text/javascript">
    $(function () {
        // 卸载提示
        $(".delete_extend_confirm").click(function () {
            var url = $(this).attr("data-href");
            //询问框
            layer.confirm('{{ $lang['confirm_uninstall'] }}', {
                btn: ['{{ $lang['ok'] }}', '{{ $lang['cancel'] }}'] //按钮
            }, function () {
                window.location.href = url;
            });
        });

    })
</script>

@include('seller.base.seller_pagefooter')
