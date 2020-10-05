@include('admin.wechat.pageheader')
<style>
    #footer {
        position: static;
        bottom: 0px;
    }
</style>
<div class="wrapper">
    <div class="title">{{ $lang['wechat_menu'] }} - {{ $lang['menu'] }}</div>
    <div class="content_tips">
        <div class="explanation" id="explanation">
            <div class="ex_tit"><i class="sc_icon"></i><h4>{{ $lang['operating_hints'] }}</h4><span id="explanationZoom"
                                                                                                    title="{{ $lang['fold_tips'] }}"></span>
            </div>
            <ul>
                @if(isset($lang['menu_tips']) && !empty($lang['menu_tips']))

                    @foreach($lang['menu_tips'] as $v)
                        <li>{{ $v }}</li>
                    @endforeach

                @endif
            </ul>
        </div>
        <div class="flexilist">
            <div class="common-head">
                <div class="fl">
                    <a href="{{ route('admin/wechat/menu_edit') }}" class="fancybox fancybox.iframe">
                        <div class="fbutton">
                            <div class="add"><span><i class="fa fa-plus"></i>{{ $lang['menu_add'] }}</span></div>
                        </div>
                    </a>
                </div>
            </div>
            <div class="common-content">

                <div class="list-div">
                    <table cellpadding="0" cellspacing="0" border="0">
                        <thead>
                        <tr>
                            <th width="10%">
                                <div class="tDiv">{{ $lang['menu_name'] }}</div>
                            </th>
                            <th width="10%">
                                <div class="tDiv">{{ $lang['menu_keyword'] }}</div>
                            </th>
                            <th width="50%">
                                <div class="tDiv">{{ $lang['menu_url'] }}</div>
                            </th>
                            <th width="10%">
                                <div class="tDiv">{{ $lang['sort_order'] }}</div>
                            </th>
                            <th width="20%">
                                <div class="tDiv">{{ $lang['handler'] }}</div>
                            </th>
                        </tr>
                        </thead>
                        <tbody>

                        @foreach($list as $key=>$val)

                            <tr>
                                <td>
                                    <div class="tDiv">{{ $val['name'] }}</div>
                                </td>
                                <td>
                                    <div class="tDiv">{{ $val['key'] }}</div>
                                </td>
                                <td>
                                    <div class="tDiv">{{ $val['url'] }}</div>
                                </td>
                                <td>
                                    <div class="tDiv">{{ $val['sort'] }}</div>
                                </td>
                                <td class="handle">
                                    <div class="tDiv a2">
                                        <a href="{{ route('admin/wechat/menu_edit', array('id'=>$val['id'])) }}"
                                           class="btn_edit fancybox fancybox.iframe"><i
                                                    class="fa fa-edit"></i>{{ $lang['wechat_editor'] }}</a>
                                        <a href="javascript:if(confirm('{{ $lang['confirm_delete'] }}')){window.location.href='{{ route('admin/wechat/menu_del', array('id'=>$val['id'])) }}'};"
                                           class="btn_trash"><i class="fa fa-trash-o"></i>{{ $lang['drop'] }}</a>
                                    </div>
                                </td>
                            </tr>

                            @foreach($val['sub_button'] as $k=>$v)

                                <tr>
                                    <td>
                                        <div class="tDiv">&nbsp;|---- &nbsp;&nbsp;{{ $v['name'] }}</div>
                                    </td>
                                    <td>
                                        <div class="tDiv">{{ $v['key'] }}</div>
                                    </td>
                                    <td>
                                        <div class="tDiv">{{ $v['url'] }}</div>
                                    </td>
                                    <td>
                                        <div class="tDiv">{{ $v['sort'] }}</div>
                                    </td>
                                    <td class="handle">
                                        <div class="tDiv a2">
                                            <a href="{{ route('admin/wechat/menu_edit', array('id'=>$v['id'])) }}"
                                               class="btn_edit fancybox fancybox.iframe"><i
                                                        class="fa fa-edit"></i>{{ $lang['wechat_editor'] }}</a>
                                            <a href="javascript:if(confirm('{{ $lang['confirm_delete'] }}')){window.location.href='{{ route('admin/wechat/menu_del', array('id'=>$v['id'])) }}'};"
                                               class="btn_trash"><i class="fa fa-trash-o"></i>{{ $lang['drop'] }}</a>
                                        </div>
                                    </td>
                                </tr>

                            @endforeach


                        @endforeach

                        <tr>
                            <td colspan="5">
                                <div class="info_btn text-center"><a href="{{ route('admin/wechat/sys_menu') }}"
                                                                     class="button btn-danger bg-red"
                                                                     style="float:none;padding:5px 20px;height:55px;line-height:55px;">{{ $lang['menu_create'] }}</a>
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
<script>
    $(document).on("mouseenter", ".list-div tbody td", function () {
        $(this).parents("tr").addClass("tr_bg_blue");
    });

    $(document).on("mouseleave", ".list-div tbody td", function () {
        $(this).parents("tr").removeClass("tr_bg_blue");
    });
</script>

@include('admin.wechat.pagefooter')
