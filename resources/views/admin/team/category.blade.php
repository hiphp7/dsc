@include('admin.team.admin_pageheader')

<div class="wrapper">
    <div class="title">{{ $lang['team_menu'] }} - {{ $lang['team_category_list'] }}</div>
    <div class="content_tips">
        <div class="tabs_info">
            <ul>
                <li><a href="{{ route('admin/team/index') }}">{{ $lang['team_goods'] }}</a></li>
                <li class="curr"><a href="{{ route('admin/team/category') }}">{{ $lang['team_category'] }}</a></li>
                <li><a href="{{ route('admin/team/teaminfo') }}">{{ $lang['team_info'] }}</a></li>
                <li style="display:none"><a href="{{ route('admin/team/teamrecycle') }}">{{ $lang['team_recycle'] }}</a>
                </li>
            </ul>
        </div>
        <div class="explanation" id="explanation">
            <div class="ex_tit"><i class="sc_icon"></i><h4>{{ $lang['operating_hints'] }}</h4><span id="explanationZoom"
                                                                                                    title="{{ $lang['fold_tips'] }}"></span>
            </div>
            <ul>
                <li>{{ $lang['team_category_list_tips']['0'] }}</li>
                <li>{{ $lang['team_category_list_tips']['1'] }}</li>
            </ul>
        </div>
        <div class="flexilist">
            <div class="common-head">
                <div class="fl">
                    <a href="{{ route('admin/team/addcategory') }}">
                        <div class="fbutton">
                            <div class="add" title="{{ $lang['team_category_add'] }}"><span><i
                                            class="fa fa-plus"></i>{{ $lang['team_category_add'] }}</span></div>
                        </div>
                    </a>
                </div>
            </div>
            <div class="common-content">
                <div class="list-div" id="listDiv">
                    <table cellspacing="0" cellpadding="0" border="0">
                        <tr class="active">
                            <th></th>
                            <th>
                                <div class="tDiv">{{ $lang['team_category_name'] }}</div>
                            </th>
                            <th>
                                <div class="tDiv">{{ $lang['team_goods_number'] }}</div>
                            </th>
                            <th>
                                <div class="tDiv">{{ $lang['is_show'] }}</div>
                            </th>
                            <th>
                                <div class="tDiv">{{ $lang['sort_order'] }}</div>
                            </th>
                            <th>
                                <div class="tDiv">{{ $lang['handler'] }}</div>
                            </th>
                        </tr>

                        @if($list)

                            @foreach($list as $list)

                                <tr>
                                    <td>
                                        <div class="tDiv first_setup">
                                            <div class="setup_span">
                                                <em><i class="fa fa-cog"
                                                       style="margin-top:-5px"></i>{{ $lang['team_category_set'] }}<i
                                                            class="arrow"></i></em>
                                                <ul>

                                                    @if($list['parent_id'] <= 0)

                                                        <li>
                                                            {{--新增下一级--}}
                                                            <a href="{{ route('admin/team/addcategory', array('parent_id'=>$list['id'])) }}">{{ $lang['team_category_add_child'] }}</a>
                                                        </li>
                                                        <li>
                                                            {{--查看下一级--}}
                                                            <a href="{{ route('admin/team/category', array('tc_id'=>$list['id'])) }}">{{ $lang['team_category_child_list'] }}</a>
                                                        </li>

                                                    @endif

                                                </ul>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="tDiv">{{ $list['name'] }}</div>
                                    </td>
                                    <td>
                                        <div class="tDiv">{{ $list['goods_number'] }}</div>
                                    </td>
                                    <td>
                                        <div class="tDiv">
                                            <div style="line-height:15px;">
                                                <div class="switch fl ml10
@if($list['status'])
                                                        active
                                                        @endif
                                                        " id="category{{ $list['id'] }}" title="
@if($list['status'])
                                                {{ $lang['yes'] }}
                                                @else
                                                {{ $lang['no'] }}
                                                @endif
                                                        " onclick="edit_status({{ $list['id'] }})">
                                                    <div class="circle"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="tDiv">{{ $list['sort_order'] }}</div>
                                    </td>
                                    <td>
                                        <div class="tDiv">
                                            <a href="{{ route('admin/team/addcategory', array('tc_id'=>$list['id'])) }}">{{ $lang['edit'] }}</a>
                                            &nbsp;|
                                            <a href='javascript:void(0);'
                                               onclick="if(confirm('{{ $lang['confirm_delete_category'] }}')){window.location.href='{{ route('admin/team/removecategory', array('tc_id'=>$list['id'])) }}'}"
                                               class="btn_trash"><i class="icon icon-trash"></i>{{ $lang['drop'] }}</a>
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

                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function confirm_bath() {
        Items = document.getElementsByName('checkboxes[]');
        var arr = new Array();
        for (i = 0; Items[i]; i++) {
            if (Items[i].checked) {
                var selected = 1;
                arr.push(Items[i].value);
            }
        }
        if (selected != 1) {
            return false;
        } else {
            $.post("{{ route('admin/team/removeteam') }}", {team_id: arr}, function (data) {
                if (data.url) {
                    window.location.href = data.url;
                }
            }, 'json');
        }

    }

    //ajax 修改频道状态
    function edit_status(cat_id) {
        $.post("{{ route('admin/team/editstatus') }}", {cat_id: cat_id}, function (data) {
            if ($("#category" + cat_id).hasClass("active")) {
                $("#category" + cat_id).removeClass("active");
            } else {
                $("#category" + cat_id).addClass("active");
            }
        }, 'json');
    }

    $("#explanationZoom").on("click", function () {
        var explanation = $(this).parents(".explanation");
        var width = $(".content_tips").width();
        if ($(this).hasClass("shopUp")) {
            $(this).removeClass("shopUp");
            $(this).attr("title", "{{ $lang['fold_tips'] }}");
            explanation.find(".ex_tit").css("margin-bottom", 10);
            explanation.animate({
                width: width
            }, 300, function () {
                $(".explanation").find("ul").show();
            });
        } else {
            $(this).addClass("shopUp");
            $(this).attr("title", "提示相关设置操作时应注意的要点");
            explanation.find(".ex_tit").css("margin-bottom", 0);
            explanation.animate({
                width: "118"
            }, 300);
            explanation.find("ul").hide();
        }
    });
</script>
@include('admin.base.footer')
