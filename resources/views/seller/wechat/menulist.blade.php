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
                                                                      data-toggle="tab"> {{ $lang['menu'] }}</a></li>
                        </ul>
                    </div>
                    <div class="explanation" id="explanation">
                        <div class="ex_tit"><i class="sc_icon"></i><h4>{{ $lang['operating_hints'] }}</h4></div>
                        <ul>
                            @if(isset($lang['menu_tips']) && !empty($lang['menu_tips']))

                                @foreach($lang['menu_tips'] as $v)
                                    <li>{{ $v }}</li>
                                @endforeach

                            @endif
                        </ul>
                    </div>

                    <div class="wrapper-list mt20">
                        <div class="common-head">
                            <div class="fl mb20">
                                <a href="{{ route('seller/wechat/menu_edit') }}" class="sc-btn sc-blue-btn fancybox "><i
                                            class="fa fa-plus"></i>{{ $lang['menu_add'] }}</a>
                            </div>
                        </div>

                        <div class="list-div" id="listDiv">
                            <table id="list-table" class="ecsc-default-table" style="">
                                <thead>
                                <tr>
                                    <th width="20%">{{ $lang['menu_name'] }}</th>
                                    <th width="10%">{{ $lang['menu_keyword'] }}</th>
                                    <th>{{ $lang['menu_url'] }}</th>
                                    <th width="5%">{{ $lang['sort_order'] }}</th>
                                    <th width="20%">{{ $lang['handler'] }}</th>
                                </tr>
                                </thead>
                                <tbody>

                                @foreach($list as $key=>$val)

                                    <tr>
                                        <td class="tl">{{ $val['name'] }}</td>
                                        <td>{{ $val['key'] }}</td>
                                        <td class="p0">{{ $val['url'] }}</td>
                                        <td>{{ $val['sort'] }}</td>
                                        <td align="center" class="handle">
                                            <div class="tDiv">
                                                <a href="{{ route('seller/wechat/menu_edit', array('id'=>$val['id'])) }}"
                                                   class="btn_edit "><i
                                                            class="fa fa-edit"></i>{{ $lang['wechat_editor'] }}</a>
                                                <a href="javascript:;"
                                                   data-href="{{ route('seller/wechat/menu_del', array('id'=>$val['id'])) }}'}"
                                                   class="btn_trash delete_confirm"><i
                                                            class="fa fa-trash-o"></i>{{ $lang['drop'] }}</a>
                                            </div>
                                        </td>
                                    </tr>

                                    @foreach($val['sub_button'] as $k=>$v)

                                        <tr>
                                            <td class="tl">&nbsp;|---- &nbsp;&nbsp;{{ $v['name'] }}</td>
                                            <td>{{ $v['key'] }}</td>
                                            <td class="p0">{{ $v['url'] }}</td>
                                            <td>{{ $v['sort'] }}</td>
                                            <td align="center" class="handle">
                                                <div class="tDiv">
                                                    <a href="{{ route('seller/wechat/menu_edit', array('id'=>$v['id'])) }}"
                                                       class="btn_edit "><i
                                                                class="fa fa-edit"></i>{{ $lang['wechat_editor'] }}</a>
                                                    <a href="javascript:;"
                                                       data-href="{{ route('seller/wechat/menu_del', array('id'=>$v['id'])) }}'}"
                                                       class="btn_trash delete_confirm"><i
                                                                class="fa fa-trash-o"></i>{{ $lang['drop'] }}</a>
                                                </div>
                                            </td>
                                        </tr>

                                    @endforeach


                                @endforeach

                                <tr>
                                    <td colspan="5">
                                        <div class="btn text-center"><a href="{{ route('seller/wechat/sys_menu') }}"
                                                                        class="sc-btn sc-blue-btn creat-menu">{{ $lang['menu_create'] }}</a>
                                        </div>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
<script type="text/javascript">
    $(document).on("mouseenter", ".list-div tbody td", function () {
        $(this).parents("tr").addClass("tr_bg_blue");
    });

    $(document).on("mouseleave", ".list-div tbody td", function () {
        $(this).parents("tr").removeClass("tr_bg_blue");
    });
</script>

@include('seller.base.seller_pagefooter')

