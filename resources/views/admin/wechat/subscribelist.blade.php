@include('admin.wechat.pageheader')
<style>
    #footer {
        position: static;
        bottom: 0px;
    }
</style>
<div class="wrapper">
    <div class="title">{{ $lang['wechat_menu'] }} - {{ $lang['sub_title'] }}</div>
    <div class="content_tips">
        <div class="tabs_info">
            <ul>
                <li role="presentation" class="curr"><a
                            href="{{ route('admin/wechat/subscribe_list') }}">{{ $lang['sub_list'] }}</a></li>
            <!--<li role="presentation" ><a href="{{ route('admin/wechat/share_count') }}">{{ $lang['share_list'] }}</a></li>-->
            </ul>
        </div>
        <div class="explanation" id="explanation">
            <div class="ex_tit"><i class="sc_icon"></i><h4>{{ $lang['operating_hints'] }}</h4><span id="explanationZoom"
                                                                                                    title="{{ $lang['fold_tips'] }}"></span>
            </div>
            <ul>
                @if(isset($lang['sub_list_tips']) && !empty($lang['sub_list_tips']))

                    @foreach($lang['sub_list_tips'] as $v)
                        <li>{{ $v }}</li>
                    @endforeach

                @endif
            </ul>
        </div>
        <div class="flexilist subscribe_head">
            <div class="common-head">
                <div class="fl">
                    <a href="{{ route('admin/wechat/subscribe_update') }}">
                        <div class="fbutton">
                            <div class="csv" title="{{ $lang['sub_update_user'] }}"><span><i
                                            class="fa fa-refresh"></i>{{ $lang['sub_update_user'] }}</span></div>
                        </div>
                    </a>
                </div>
                <div class="search">
                    <form action="{{ route('admin/wechat/subscribe_search') }}" name="searchForm" method="post"
                          role="search">
                        <div class="input">
                            @csrf
                            <input type="text" name="keywords" class="text nofocus"
                                   placeholder="{{ $lang['sub_search'] }}" autocomplete="off" style="width:280px;">
                            <input type="hidden" value="{{ $group_id ?? '' }}" name="group_id">
                            <input type="submit" value="" class="btn search_button">
                        </div>
                    </form>
                </div>
            </div>
            <div class="fl tags_button">
                <a href="{{ route('admin/wechat/sys_tags') }}" class="">
                    <div class="fbutton update">
                        <div class="" title="{{ $lang['tag_sys'] }}"><span><i
                                        class="fa fa-refresh"></i>{{ $lang['tag_sys'] }}</span></div>
                    </div>
                </a>

                <a href="{{ route('admin/wechat/tags_edit') }}" class="fancybox fancybox.iframe">
                    <div class="fbutton add">
                        <div class="" title="{{ $lang['tag_add'] }}"><span><i
                                        class="fa fa-plus"></i>{{ $lang['tag_add'] }}</span></div>
                    </div>
                </a>
            </div>
            <div class="common-content">
                <div class="list-div" id="listDiv">
                    <form action="{{ route('admin/wechat/batch_tagging') }}" method="post" class="form-inline"
                          role="form">
                        <table cellspacing="0" cellpadding="0" border="0" class="sub-list">
                            <thead>
                            <tr>
                                <th width="5%" class="sign">
                                    <div class="tDiv">
                                        <input type="checkbox" class="checkbox" name="all_list" id="all_list"/>
                                        <label for="all_list" class="checkbox_stars"></label>
                                    </div>
                                </th>
                                <th width="5%">
                                    <div class="tDiv">{{ $lang['sub_headimg'] }}</div>
                                </th>
                                <th>
                                    <div class="tDiv">{{ $lang['sub_nickname'] }}/{{ $lang['sub_area'] }}</div>
                                </th>
                                <th width="15%">
                                    <div class="tDiv">{{ $lang['sub_time'] }}</div>
                                </th>
                                <th width="10%">
                                    <div class="tDiv">{{ $lang['sub_from'] }}</div>
                                </th>
                                <th width="25%" class="handle text-center">{{ $lang['handler'] }}</th>
                            </tr>
                            </thead>

                            @if($list)

                                @foreach($list as $key => $val)

                                    <tr>
                                        <td class="sign">
                                            <div class="tDiv">
                                                <input type="checkbox" class="checkbox" id="checkbox_{{ $val['uid'] }}" name="id[]" value="{{ $val['openid'] }}" >
                                                <label for="checkbox_{{ $val['uid'] }}" class="checkbox_stars"></label>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="user_img_box">
                                                @if($val['headimgurl'])
                                                    <img src="{{ $val['headimgurl'] }}" width="70" alt="{{ $val['nickname'] }}"/>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <div class="tDiv">
                                                <span class="wei-nickname">{{ $val['nickname'] }}
                                                    @if($val['remark'])

                                                        (<a href="javascript:;" class="user_remark" uidAttr="{{ $val['uid'] }}" title="{{ $lang['edit_remark_name'] }}">{{ $val['remark'] }}</a>)
                                                    @endif
                                                </span><br>
                                                <span class="wei-area">
                                                    @foreach($val['taglist'] as $k=>$v)

                                                        <a href="javascript:;" class="user_tag" tagAttr="{{ $v['tag_id'] }}" openidAttr="{{ $val['openid'] }}" title="{{ $lang['tag_delete'] }}">{{ $v['name'] }}</a>

                                                    @endforeach
                                                </span>
                                                <br><span class="wei-area">{{ $val['province'] }} - {{ $val['city'] }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="tDiv">{{ $val['subscribe_time_format'] }}</div>
                                        </td>
                                        <td>
                                            <div class="tDiv">{{ $val['from'] }}</div>
                                        </td>
                                        <td class="handle text-center">
                                            <div class="tDiv a2">

                                                @if(isset($val['look_user_url']) && $val['look_user_url'])

                                                    <a href="{{ $val['look_user_url'] ?? '' }}" class="btn_see" title=""><i class="sc_icon sc_icon_see"></i>{{ $lang['sub_user'] }}</a>

                                                @endif

                                                {{--<a href="{{ route('admin/wechat/custom_message_list', array('uid'=>$val['uid'])) }}" class="btn_see" title="{{ $lang['custom_message_list'] }}"><i class="sc_icon sc_icon_see"></i>查看消息</a>--}}
                                                <a href="{{ route('admin/wechat/send_custom_message', array('uid'=>$val['uid'])) }}" class="btn_region fancybox80 fancybox.iframe" title="{{ $lang['send_custom_message'] }}"><i class="fa fa-weixin"></i>{{ $lang['send_custom_message'] }}
                                                </a>
                                            </div>
                                        </td>
                                    </tr>

                                @endforeach

                            @else

                                <tbody>
                                <tr>
                                    <td class="no-records" colspan="6">{{ $lang['no_records'] }}</td>
                                </tr>
                                </tbody>

                            @endif

                            <tfoot>
                            <tr>
                                <td colspan="3">
                                    <div class="tDiv of">
                                        <div class="tfoot_btninfo">
                                            <span class="fl" style="line-height:30px;margin-right:20px;">{{ $lang['tag_move'] }}</span>
                                            <select name="tag_id" style="padding:5px;height:30px;" class="imitate_select select_w120 fl">

                                                @foreach($tag_list as $k=>$v)

                                                    <option value="{{ $v['tag_id'] }}">{{ $v['name'] }}</option>

                                                @endforeach

                                            </select>
                                            @csrf
                                            <input type="submit" class="btn button btn_disabled" value="{{ $lang['tag_join'] }}" disabled="disabled" ectype='btnSubmit'>
                                        </div>
                                    </div>
                                </td>
                                <td colspan="3">
                                    @include('admin.wechat.pageview')
                                </td>
                            </tr>
                            </tfoot>
                        </table>
                    </form>

                    <table cellspacing="0" cellpadding="0" border="0" class="group-list">
                        <thead>
                        <tr>
                            <th>
                                <div class="tDiv">{{ $lang['tag_title'] }}</div>
                            </th>
                            <th></th>
                        </tr>
                        </thead>

                        @foreach($tag_list as $key=>$val)

                            <tr>
                                <td>
                                    <div class="handle">
                                        <div class="tDiv"><a class="btn_see" href="{{ route('admin/wechat/subscribe_search', array('tag_id'=>$val['tag_id'])) }}">{{ $val['name'] }} </a><span class="badge">{{ $val['count'] }}</span></div>
                                    </div>
                                </td>
                                <td>
                                    <div class="handle">

                                        @if($val['tag_id'] != 0  && $val['tag_id'] != 1 && $val['tag_id'] != 2)

                                            <div class="tDiv a2">
                                                <a href="{{ route('admin/wechat/tags_edit', array('id'=> $val['id'])) }}" class="btn_edit fancybox fancybox.iframe"><i class="fa fa-edit"></i>{{ $lang['wechat_editor'] }}</a>
                                                <a class="btn_trash delete_tags" data-href="{{ route('admin/wechat/tags_delete', array('id'=> $val['id'])) }}" href="javascript:;" class="btn_trash"><i class="fa fa-trash-o"></i>{{ $lang['drop'] }}</a>
                                            </div>

                                        @endif

                                    </div>
                                </td>
                            </tr>

                        @endforeach

                    </table>

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
                    $.post("{{ route('admin/wechat/batch_untagging') }}", {
                        tagid: tag_id,
                        openid: open_id
                    }, function (data) {
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

                // 修改用户备注
                $('.user_remark').click(function () {
                    var uid = $(this).attr("uidAttr");
                    var remark = $(this).html();
                    layer.open({
                        type: 1,
                        closeBtn: false,
                        shift: 7,
                        shadeClose: true,
                        title: "{{ $lang['input_remark_name'] }}",
                        content: "<div style='width:320px;padding:10px;' class='form-group has-feedback'><input id='remarkName' class='form-control' type='text' value='" + remark + "'/>" +
                        "<button style='margin-top:10px;right:0;' type='button' class='button btn-danger bg-red' onclick='editRemark(" + uid + ")'>确定</button></div>"
                    });
                });

            });

            function editRemark(uid) {
                var remark = $("#remarkName").val();
                if (!remark) {
                    layer.msg('{{ $lang['remark_name_empty'] }}');
                    return false;
                }
                $.post("{{ route('admin/wechat/edit_user_remark') }}", {remark: remark, uid: uid}, function (data) {
                    if (data.status > 0) {
                        window.location.reload();
                    } else {
                        layer.msg(data.msg);
                        return false;
                    }
                }, 'json');
                layer.closeAll();
            }
        </script>

    </div>
</div>

@include('admin.wechat.pagefooter')
