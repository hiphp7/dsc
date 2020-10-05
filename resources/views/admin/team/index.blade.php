@include('admin.team.admin_pageheader')

<div class="wrapper">
    <div class="title">{{ $lang['team_menu'] }} - {{ $lang['team_goods_list'] }}</div>
    <div class="content_tips">
        <div class="tabs_info">
            <ul>
                <li class="curr"><a href="{{ route('admin/team/index') }}">{{ $lang['team_goods'] }}</a></li>
                <li><a href="{{ route('admin/team/category') }}">{{ $lang['team_category'] }}</a></li>
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
                <li>{{ $lang['team_goods_list_tips']['0'] }}</li>
                <li>{{ $lang['team_goods_list_tips']['1'] }}</li>
            </ul>
        </div>
        <div class="flexilist">

            <div class="common-head">
                <div class="fl">
                    <a href="{{ route('admin/team/addgoods') }}">
                        <div class="fbutton">
                            <div class="add" title="{{ $lang['team_goods_add'] }}"><span><i
                                            class="fa fa-plus"></i>{{ $lang['team_goods_add'] }}</span></div>
                        </div>
                    </a>
                </div>
                <form action="{{ route('admin/team/index') }}" method="post">
                    <div class="search">
						<select name="tc_id" class="text">
                            <option value="0" @if($tc_id == 0) selected  @endif >{{ $lang['team_category'] }}</option>
							@foreach($team_list as $cat)
								<option value="{{ $cat['tc_id'] ?? '' }}"
										@if(isset($tc_id) && $tc_id == $cat['tc_id'])
										selected
										@endif
								>{{ $cat['name'] ?? '' }}</option>
								@if(isset($cat['id']))
									@foreach($cat['id'] as $val)
										<option value="{{ $val['tc_id'] ?? '' }}"
												@if(isset($tc_id) && $tc_id == $val['tc_id'])
												selected
												@endif
										>&nbsp;&nbsp;&nbsp;{{ $val['name'] ?? '' }}</option>
									@endforeach
								@endif
							@endforeach                            
                        </select>
					
                        <select name="is_audit" class="text">
                            <option value="3"
                                    @if($audit == 3)
                                    selected
                                    @endif
                            >{{ $lang['audit_status'] }}</option>
                            <option value="0"
                                    @if($audit == 0)
                                    selected
                                    @endif
                            >{{ $lang['no_audit'] }}
                            </option>
                            <option value="1"
                                    @if($audit == 1)
                                    selected
                                    @endif
                            >{{ $lang['refuse_audit'] }}
                            </option>
                            <option value="2"
                                    @if($audit == 2)
                                    selected
                                    @endif
                            >{{ $lang['already_audit'] }}
                            </option>
                        </select>
                        <div class="input">
                            @csrf
                            <input type="text" name="keyword" class="text nofocus"
                                   placeholder="{{ $lang['button_search'] }}" autocomplete="off">
                            <input type="submit" value="" class="btn" name="export">
                        </div>
                    </div>
                </form>
            </div>
            <div class="common-content">
                <div class="list-div  ht_goods_list" id="listDiv">
                    <table cellspacing="0" cellpadding="0" border="0">
                        <tr class="active">
                            <th width="3%"></th>
                            <th width="3%">
                                <div class="tDiv" style="min-width:40px">{{ $lang['record_id'] }}</div>
                            </th>
                            <th width="12%">
                                <div class="tDiv">{{ $lang['goods_name'] }}</div>
                            </th>
                            <th width="7%">
                                <div class="tDiv">{{ $lang['shop_name'] }}</div>
                            </th>
                            <th width="16%">
                                <div class="tDiv" style="min-width:100px">{{ $lang['price_team_products'] }}</div>
                            </th>
                            <th width="12%">
                                <div class="tDiv">{{ $lang['team_ranking_add'] }}</div>
                            </th>
                        <!-- <th width="5%"><div class="tDiv">{{ $lang['sort_order'] }}</div></th> -->
                            <th width="10%">
                                <div class="tDiv">{{ $lang['team_sku_stock'] }}</div>
                            </th>
                            <th width="9%">
                                <div class="tDiv">{{ $lang['team_num'] }}</div>
                            </th>
                            <th width="9%">
                                <div class="tDiv">{{ $lang['team_limit_num'] }}</div>
                            </th>
                            <th width="8%">
                                <div class="tDiv">{{ $lang['audit_status'] }}</div>
                            </th>
                            <th width="14%" class="handle">
                                <div style="min-width:84px">{{ $lang['handler'] }}</div>
                            </th>
                        </tr>

                        @if($list)

                            @foreach($list as $list)

                                <tr>
                                    <td>
                                        <div class="tDiv">
                                            <div class="checkbox">
                                                <label>
                                                    <input type="checkbox" value="{{ $list['id'] }}"
                                                           name="checkboxes[]">
                                                </label>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="tDiv">{{ $list['id'] }}</div>
                                    </td>
                                    <td>
                                        <div class="tDiv goods_list_info">
                                            <div class="img">
                                                <img src="{{ $list['goods_thumb'] }}" width="68" height="68">
                                            </div>
                                            <div class="desc">
                                                <div class="name">
                                                    <span title="{{ $list['goods_name'] }}" data-toggle="tooltip"
                                                          class="span">{{ $list['goods_name'] }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="tDiv" title="{{ $list['user_name'] }}" data-toggle="tooltip"><em
                                                    class="red">{{ $list['user_name'] }}</em></div>
                                    </td>
                                    <td>
                                        <div class="tDiv">
                                            <p>{{ $lang['team_price'] }}：{{ $list['team_price'] }}</p>
                                            <p>{{ $lang['shop_price'] }}：{{ $list['shop_price'] }}</p>
                                            <p>{{ $lang['good_sn'] }}：{{ $list['goods_sn'] }}</p>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="tDiv">
                                            <div style="line-height:15px;">
                                                <span class="fl">{{ $lang['is_best'] }}</span>
                                                <div class="switch fl ml10
@if($list['is_best'])
                                                        active
                                                        @endif
                                                        " id="goodsis_best{{ $list['goods_id'] }}" title="
@if($list['is_best'])
                                                {{ $lang['yes'] }}
                                                @else
                                                {{ $lang['no'] }}
                                                @endif
                                                        " onclick="edit_goods('is_best', {{ $list['goods_id'] }})">
                                                    <div class="circle"></div>
                                                </div>
                                                <input type="hidden" value="0" name="">
                                            </div>
                                            </br>
                                            <div style="line-height:15px;">
                                                <span class="fl">{{ $lang['is_new'] }}</span>
                                                <div class="switch fl ml10
@if($list['is_new'])
                                                        active
                                                        @endif
                                                        " id="goodsis_new{{ $list['goods_id'] }}" title="
@if($list['is_new'])
                                                {{ $lang['yes'] }}
                                                @else
                                                {{ $lang['no'] }}
                                                @endif
                                                        " onclick="edit_goods('is_new', {{ $list['goods_id'] }})">
                                                    <div class="circle"></div>
                                                </div>
                                                <input type="hidden" value="0" name="">
                                            </div>
                                            </br>
                                            <div style="line-height:15px;"><span class="fl">{{ $lang['is_hot'] }}</span>
                                                <div class="switch fl ml10
@if($list['is_hot'])
                                                        active
                                                        @endif
                                                        " id="goodsis_hot{{ $list['goods_id'] }}" title="
@if($list['is_hot'])
                                                {{ $lang['yes'] }}
                                                @else
                                                {{ $lang['no'] }}
                                                @endif
                                                        " onclick="edit_goods('is_hot', {{ $list['goods_id'] }})">
                                                    <div class="circle"></div>
                                                </div>
                                                <input type="hidden" value="0" name="">
                                            </div>
                                        </div>
                                    </td>
                                <!-- <td><div class="tDiv">{{ $list['sort_order'] }}</div></td> -->
                                    <td>
                                        <div class="tDiv">{{ $list['goods_number'] }}</div>
                                    </td>
                                    <td>
                                        <div class="tDiv">{{ $list['team_num'] }}{{ $lang['of_team_num'] }}</div>
                                    </td>
                                    <td>
                                        <div class="tDiv">{{ $list['limit_num'] }}{{ $lang['of_limit_num'] }}</div>
                                    </td>
                                    <td>
                                        <div class="tDiv">
                                            <em class="blue">{{ $list['is_audit'] }}</em>
                                        </div>
                                    </td>
                                    <td class="handle">
                                        <div class="tDiv ht_tdiv" style="padding-bottom:0px;">
                                            {{--<a href="{{ route('team/goods', ['id' => $list['goods_id']]) }}" target="_blank" class="btn_see"><i class="sc_icon sc_icon_see"></i>查看</a>--}}
                                            <a href="{{ route('admin/team/addgoods', ['id' => $list['id']]) }}"
                                               class="btn_edit"><i class="fa fa-edit"></i>{{ $lang['edit'] }}</a>
                                            <a href='javascript:void(0);'
                                               onclick="if(confirm('{{ $lang['confirm_close_team'] }}')){window.location.href='{{ route('admin/team/removegoods', ['id' => $list['id']]) }}'}"
                                               class="btn_trash"><i class="fa fa-trash"></i>{{ $lang['closed'] }}</a>
                                        </div>
                                    </td>
                                </tr>

                            @endforeach

                        @else

                            <tbody>
                            <tr>
                                <td class="no-records" colspan="11">{{ $lang['no_records'] }}</td>
                            </tr>
                            </tbody>

                        @endif

                        <tfoot>
                        <tr>
                            <td colspan="4">
                                <div class="tDiv of">
                                    <div class="tfoot_btninfo">
                                        <select id="group_id" name="group_id" class="imitate_select select_w120 fl">
                                            <option value="0">{{ $lang['please_select'] }}</option>
                                            <option value="1">{{ $lang['closed'] }}</option>
                                            <option value="2">{{ $lang['cancel_best'] }}</option>
                                            <option value="3">{{ $lang['cancel_new'] }}</option>
                                            <option value="4">{{ $lang['cancel_hot'] }}</option>
                                        </select>
                                        <input type="submit" onclick="confirm_bath()" id="btnSubmit"
                                               value="{{ $lang['button_submit'] }}" class="button">
                                    </div>
                                </div>
                            </td>
                            <td colspan="9">
                                <div class="list-page">
                                    @include('admin.team.admin_pageview')
                                </div>
                            </td>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function edit_goods(type, goods_id) {
        $.post("{{ route('admin/team/editgoods') }}", {type: type, goods_id: goods_id}, function (data) {
            if ($("#goods" + type + goods_id).hasClass("active")) {
                $("#goods" + type + goods_id).removeClass("active");
            } else {
                $("#goods" + type + goods_id).addClass("active");
            }
        }, 'json');
    }

    function confirm_bath() {
        Items = document.getElementsByName('checkboxes[]');
        var arr = new Array();
        for (i = 0; Items[i]; i++) {
            if (Items[i].checked) {
                var selected = 1;
                arr.push(Items[i].value);
            }
        }
        var options = $("#group_id option:selected");  //获取选中的项
        var group = (options.val());   //拿到选中项的值
        if (group == 0) {
            return false;
        }
        if (selected != 1) {
            return false;
        } else {
            $.post("{{ route('admin/team/removegoods') }}", {id: arr, group: group}, function (data) {
                if (data.url) {
                    window.location.href = data.url;
                }
            }, 'json');
        }
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
