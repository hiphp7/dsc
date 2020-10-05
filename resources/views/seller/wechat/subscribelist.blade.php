@include('seller.base.seller_pageheader')

@include('seller.base.seller_nave_header')

<div class="ecsc-layout">
    <div class="site wrapper">
        @include('seller.base.seller_menu_left')

        <div class="ecsc-layout-right">
            <div class="main-content" id="mainContent">
                @include('seller.base.seller_nave_header_title')
                <div class="wrapper-right of subscribe_head">
                    <div class="tabmenu">
                        <ul class="tab ">
                            <li role="presentation" class="active"><a href="#home" role="tab" data-toggle="tab">{{ $lang['sub_title'] }}</a>
                            </li>
                        </ul>
                    </div>
                    <div class="explanation" id="explanation">
                        <div class="ex_tit"><i class="sc_icon"></i><h4>{{ $lang['operating_hints'] }}</h4></div>
                        <ul>
                            @if(isset($lang['sub_list_tips']) && !empty($lang['sub_list_tips']))

                                @foreach($lang['sub_list_tips'] as $v)
                                    <li>{{ $v }}</li>
                                @endforeach

                            @endif
                        </ul>
                    </div>
                    <div class="common-head mt20 fl">
                        <div class="fl">
                            <a href="{{ route('seller/wechat/sys_tags') }}" class="sc-btn sc-blue-btn"><i class="fa fa-refresh"></i>{{ $lang['group_update'] }}</a>
                        </div>
                        <!-- 搜索 -->
                        <div class="search-info ">
                            <form action="{{ route('seller/wechat/subscribe_search') }}" name="searchForm" method="post" role="search">
                                <div class="search-form">
                                    <div class="search-key">
                                        @csrf
                                        <input type="text" name="keywords" class="text nofocus" placeholder="{{ $lang['sub_search'] }}" autocomplete="off">
                                        <input type="hidden" value="{{ $group_id ?? '' }}" name="group_id">
                                        <input type="submit" value="" class="submit search_button">
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="fl tags_button mt20">
                        <a href="{{ route('seller/wechat/tags_edit') }}" class="sc-btn sc-blue-btn fancybox fancybox.iframe">{{ $lang['tag_add'] }}</a>
                    </div>
                    <div class="wrapper-list">
                        <form action="{{ route('seller/wechat/batch_tagging') }}" method="post" class="form-inline" role="form">
                            <div class="list-div" id="listDiv">
                                <table class="ecsc-default-table goods-default-table pull-left" style="width:75%;">
                                    <thead>
                                    <tr>
                                        <th width="5%" class="sign">
                                            <div class="tDiv">
                                                <input type="checkbox" class="checkbox" name="all_list" id="all_list"/>
                                                <label for="all_list" class="checkbox_stars"></label>
                                            </div>
                                        </th>
                                        <th width="50%">{{ $lang['sub_list'] }}</th>
                                        <th width="30%">{{ $lang['handler'] }}</th>
                                    </tr>
                                    </thead>
                                    <tbody>

                                    @if($list)

                                        @foreach($list as $key=>$val)

                                            <tr>
                                                <td width="2%" class="sign" style="text-align:center;">
                                                    <div class="tDiv">
                                                        <input type="checkbox" class="checkbox" id="checkbox_{{ $val['uid'] }}" name="id[]" value="{{ $val['openid'] }}" >
                                                        <label for="checkbox_{{ $val['uid'] }}" class="checkbox_stars"></label>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class=" user_img_box" style="position:relative">
                                                        <div class="pull-left fan"><img src="{{ $val['headimgurl'] }}" width="70" alt="{{ $val['nickname'] }}"/></div>
                                                        <div class="pull-left ml10 names">
                                                            <p>{{ $val['nickname'] }}</p>
                                                            <p class="wei-area">

                                                                @foreach($val['taglist'] as $k=>$v)

                                                                    <a href="javascript:;" class="user_tag" tagAttr="{{ $v['tag_id'] }}" openidAttr="{{ $val['openid'] }}" title="{{ $lang['tag_delete'] }}">{{ $v['name'] }}</a>

                                                                @endforeach

                                                            </p>
                                                        </div>

                                                        <div class="person-info hidden">
                                                            <div class="pull-left"><img src="{{ $val['headimgurl'] }}"></div>
                                                            <div class="pull-left person-con">
                                                                <p class="title">{{ $lang['sub_nickname'] }}:{{ $val['nickname'] }}</p>
                                                                <p class="wei-area">

                                                                    @foreach($val['taglist'] as $k=>$v)

                                                                        <a href="javascript:;" class="user_tag" tagAttr="{{ $v['tag_id'] }}" openidAttr="{{ $val['openid'] }}" title="{{ $lang['tag_delete'] }}">{{ $v['name'] }}</a>

                                                                    @endforeach

                                                                </p>
                                                                <p class="bang">{{ $lang['sub_area'] }}:{{ $val['province'] }} - {{ $val['city'] }}</p>

                                                                <p class="time">{{ $lang['sub_time'] }}:{{ $val['subscribe_time_format'] }}</p>
                                                            </div>
                                                        </div>
                                                    </div>

                                                </td>
                                                <td>
                                                    <div class="handle">
                                                        <div class="tDiv">
                                                            <a href="{{ route('seller/wechat/send_custom_message', array('uid'=>$val['uid'])) }}" class="btn_region fancybox80 fancybox.iframe" title="{{ $lang['send_custom_message'] }}"><i class="fa fa-weixin"></i>{{ $lang['send_custom_message'] }}
                                                            </a>
                                                            <a href="{{ route('seller/wechat/custom_message_list', array('uid'=>$val['uid'])) }}" class="btn_see" title="{{ $lang['custom_message_list'] }}"><i class="sc_icon sc_icon_see"></i>{{ $lang['custom_message_list'] }}
                                                            </a>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>

                                        @endforeach

                                    @else

                                        <tr>
                                            <td class="no-records" colspan="3">{{ $lang['no_records'] }}</td>
                                        </tr>

                                    @endif

                                    </tbody>
                                    <tfoot>
                                    <tr>
                                        <td class="td_border" colspan="3" style="background-color: rgb(255, 255, 255);">
                                            <span class="fl" style="line-height:30px;margin:0 20px 0 20px;">{{ $lang['tag_move'] }}</span>
                                            <select name="tag_id" class="select mr10 text" style="width:auto">

                                                @foreach($tag_list as $k=>$v)

                                                    <option value="{{ $v['tag_id'] }}">{{ $v['name'] }}</option>

                                                @endforeach

                                            </select>
                                            @csrf
                                            <input type="submit" class="sc-btn btn_disabled" value="{{ $lang['tag_join'] }}" disabled="disabled" ectype='btnSubmit'>

                                        </td>
                                    </tr>
                                    </tfoot>
                                </table>
                                <table class="ecsc-table-seller pull-left ml10" style="width:24%;">
                                    <tbody>

                                    <tr>
                                        <th>{{ $lang['tag_title'] }}</th>
                                    </tr>

                                    @foreach($tag_list as $key=>$val)

                                        <tr>
                                            <td>
                                                <a class="btn_see" href="{{ route('seller/wechat/subscribe_search', array('tag_id'=>$val['tag_id'])) }}">{{ $val['name'] }} </a>
                                                <span class="badge">{{ $val['count'] }}</span>


                                                @if($val['tag_id'] != 0  && $val['tag_id'] != 1 && $val['tag_id'] != 2)

                                                    <div class="handle fr">
                                                        <a href="{{ route('seller/wechat/tags_edit', array('id'=>$val['id'])) }}" class="btn_edit fancybox fancybox.iframe"><i class="fa fa-edit"></i>{{ $lang['wechat_editor'] }}
                                                        </a>
                                                        <a class="btn_trash delete_tags" data-href="{{ route('seller/wechat/tags_delete', array('id'=> $val['id'])) }}" href="javascript:;" class="btn_trash"><i class="fa fa-trash-o"></i>{{ $lang['drop'] }}</a>
                                                    </div>

                                                @endif

                                            </td>
                                        </tr>

                                    @endforeach

                                    </tbody>
                                </table>
                            </div>
                        </form>
                    </div>
                    @include('seller.base.seller_pageview')
                </div>

            </div>
        </div>

    </div>
</div>
<script type="text/javascript">
    $(function () {
        //弹出框
        $(".fancybox80").fancybox({
            width: '80%',
            height: '80%',
            closeBtn: true,
            title: ''
        });

        // 全选切换效果
        $(document).on("click", "input[name='all_list']", function () {
            if ($(this).prop("checked") == true) {
                $(".list-div").find("input[type='checkbox']").prop("checked", true);
                $(".list-div").find("input[type='checkbox']").parents("tr").addClass("tr_bg_org");
            } else {
                $(".list-div").find("input[type='checkbox']").prop("checked", false);
                $(".list-div").find("input[type='checkbox']").parents("tr").removeClass("tr_bg_org");
            }

            btnSubmit();
        });

        // 单选切换效果
        $(document).on("click", ".sign .checkbox", function () {
            if ($(this).is(":checked")) {
                $(this).parents("tr").addClass("tr_bg_org");
            } else {
                $(this).parents("tr").removeClass("tr_bg_org");
            }

            btnSubmit();
        });

        // 禁用启用提交按钮
        function btnSubmit() {
            var length = $(".list-div").find("input[name='id[]']:checked").length;

            if ($("#listDiv *[ectype='btnSubmit']").length > 0) {
                if (length > 0) {
                    $("#listDiv *[ectype='btnSubmit']").removeClass("btn_disabled");
                    $("#listDiv *[ectype='btnSubmit']").attr("disabled", false);
                } else {
                    $("#listDiv *[ectype='btnSubmit']").addClass("btn_disabled");
                    $("#listDiv *[ectype='btnSubmit']").attr("disabled", true);
                }
            }
        }

        // 删除标签
        $(".delete_tags").click(function () {
            var url = $(this).attr("data-href");
            //询问框
            layer.confirm('{{ $lang['confirm_delete_tag'] }}', {
                btn: ['{{ $lang['ok'] }}', '{{ $lang['cancel'] }}'] //按钮
            }, function () {
                $.get(url, '', function (data) {
                    layer.msg(data.msg);
                    if (data.error == 0) {
                        if (data.url) {
                            window.location.href = data.url;
                        }
                    }
                    return false;
                }, 'json');
            });
        });

        // 批量加入用户标签验证
        $("input[ectype='btnSubmit']").bind("click", function () {
            var item = $("select[name=tag_id]").val();
            if (!item) {
                layer.msg('{{ $lang['tag_empty'] }}');
                return false;
            }
            ;
            var num = $("input[name='id[]']:checked").length;
            if (num >= 50) {
                layer.msg('{{ $lang['batch_tagging_limit'] }}');
                return false;
            }
        });

        // 移除用户标签
        $('.user_tag').click(function () {
            var tag_id = $(this).attr("tagAttr");
            var open_id = $(this).attr("openidAttr");
            $.post("{{ route('seller/wechat/batch_untagging') }}", {tagid: tag_id, openid: open_id}, function (data) {
                if (data.status > 0) {
                    window.location.reload();
                } else {
                    layer.msg(data.msg);
                    return false;
                }
            }, 'json');
        });

        // 搜索验证
        $('.search_button').click(function () {
            var search_keywords = $("input[name=keywords]").val();
            if (!search_keywords) {
                layer.msg('{{ $lang['keywords_empty'] }}');
                return false;
            }
        });

        // 查看粉丝详细信息
        $(".fan img").hover(function () {
            $(this).parent().siblings(".person-info").removeClass("hidden");
        }, function () {
            $(this).parent().siblings(".person-info").addClass("hidden");
        });


    })
</script>

@include('seller.base.seller_pagefooter')
