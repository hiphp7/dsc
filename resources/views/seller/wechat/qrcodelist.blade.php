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
                                                                      data-toggle="tab">{{ $lang['qrcode'] }}</a></li>
                        </ul>
                    </div>
                    <div class="explanation" id="explanation">
                        <div class="ex_tit"><i class="sc_icon"></i><h4>{{ $lang['operating_hints'] }}</h4></div>
                        <ul>
                            @if(isset($lang['qrcode_list_tips']) && !empty($lang['qrcode_list_tips']))

                                @foreach($lang['qrcode_list_tips'] as $v)
                                    <li>{{ $v }}</li>
                                @endforeach

                            @endif
                        </ul>
                    </div>
                    <div class="common-head mt20">
                        <div class="fl mb20">
                            <a href="{{ route('seller/wechat/qrcode_edit') }}" class="sc-btn sc-blue-btn fancybox "><i
                                        class="fa fa-plus"></i>{{  $lang['qrcode_edit'] }}</a>
                        </div>
                    </div>
                    <div class="wrapper-content">
                        <div class="list-div" id="listDiv">
                            <table class="ecsc-default-table goods-default-table mt20">
                                <thead>
                                <tr>
                                    <th width="15%">{{ $lang['qrcode_scene_value'] }}</th>
                                    <th width="10%">{{ $lang['qrcode_type'] }}</th>
                                    <th width="20%">{{ $lang['qrcode_function'] }}</th>
                                    <th width="5%">{{ $lang['sort_order'] }}</th>
                                    <th>{{ $lang['handler'] }}</th>
                                </tr>
                                </thead>
                                <tbody>

                                @if($list)

                                    @foreach($list as $key=>$val)

                                        <tr>
                                            <td>{{ $val['scene_id'] }}</td>
                                            <td>
                                                @if($val['type'] == 0)
                                                    {{ $lang['qrcode_short'] }}
                                                @else
                                                    {{ $lang['qrcode_forever'] }}
                                                @endif
                                            </td>
                                            <td>{{ $val['function'] }}</td>
                                            <td>{{ $val['sort'] }}</td>
                                            <td class="handle">
                                                <div class="tDiv a3">
                                                    <a href="{{ route('seller/wechat/qrcode_get', array('id'=>$val['id'])) }}"
                                                       class="btn_region fancybox fancybox.iframe getqr"><i
                                                                class="fa fa-qrcode"></i>{{ $lang['qrcode_get'] }}</a>


                                                    @if($val['status'] == 1)

                                                        <a href="{{ route('seller/wechat/qrcode_edit', array('id'=>$val['id'],'status'=> 0)) }}"
                                                           class="btn_trash" title="{{ $lang['to_disabled'] }}"><i
                                                                    class="fa fa-toggle-on"></i>{{ $lang['already_enabled'] }}
                                                        </a>

                                                    @else

                                                        <a href="{{ route('seller/wechat/qrcode_edit', array('id'=>$val['id'],'status'=> 1)) }}"
                                                           class="btn_trash" title="{{ $lang['to_enabled'] }}"><i
                                                                    class="fa fa-toggle-off"></i>{{ $lang['already_disabled'] }}
                                                        </a>

                                                    @endif


                                                    <a href="javascript:;"
                                                       data-href="{{ route('seller/wechat/qrcode_del', array('id'=>$val['id'])) }}'}"
                                                       class="btn_trash delete_confirm"><i
                                                                class="fa fa-trash-o"></i>{{ $lang['drop'] }}</a>
                                                </div>
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

<script type="text/javascript">
    $(function () {
        $(".getqr").click(function () {
            var url = $(this).attr("href");
            $.get(url, '', function (data) {
                if (data.status <= 0) {
                    layer.msg(data.msg);
                    $.fancybox.close();
                    return false;
                }
            }, 'json');
        });
    })
</script>

@include('seller.base.seller_pagefooter')
