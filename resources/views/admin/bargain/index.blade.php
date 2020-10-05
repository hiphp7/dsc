@include('admin.bargain.admin_pageheader')

<div class="wrapper">
    {{--商品列表--}}
    <div class="title">{{ $lang['bargain_menu'] }} - {{ $lang['bargain_goods_list'] }}</div>
    <div class="content_tips">
        <div class="explanation" id="explanation">
            <div class="ex_tit"><i class="sc_icon"></i><h4>{{ $lang['operating_hints'] }}</h4><span id="explanationZoom"
                                                                                                    title="{{ $lang['fold_tips'] }}"></span>
            </div>
            <ul>
                <li>{{ $lang['bargain_goods_list_tips']['0'] }}</li>
                <li>{{ $lang['bargain_goods_list_tips']['1'] }}</li>
            </ul>
        </div>
        <div class="flexilist">

            <div class="common-head">
                <div class="fl">
                    <a href="{{ route('admin/bargain/addgoods') }}">
                        <div class="fbutton">
                            <div class="add" title="{{ $lang['bargain_goods_add'] }}"><span><i
                                            class="fa fa-plus"></i>{{ $lang['bargain_goods_add'] }}</span></div>
                        </div>
                    </a>
                </div>
                <form action="{{ route('admin/bargain/index') }}" method="post">
                    <div class="search">
                        <select name="is_audit" class="text">
                            {{--所有--}}
                            <option value="3"
                                    @if($audit == 3)
                                    selected
                                    @endif
                            >{{ $lang['adopt_status'] }}
                            </option>
                            {{--未审核--}}
                            <option value="0"
                                    @if($audit == 0)
                                    selected
                                    @endif
                            >{{ $lang['not_audited'] }}
                            </option>
                            {{--审核未通过--}}
                            <option value="1"
                                    @if($audit == 1)
                                    selected
                                    @endif
                            >{{ $lang['not_through'] }}
                            </option>
                            {{--审核已通过--}}
                            <option value="2"
                                    @if($audit == 2)
                                    selected
                                    @endif
                            >{{ $lang['yes_through'] }}
                            </option>
                        </select>
                        <div class="input">
                            @csrf
                            <input type="text" name="keyword" class="text nofocus" placeholder="{{ $lang['keyword'] }}"
                                   autocomplete="off">
                            <input type="submit" value="" class="btn" name="export">
                        </div>
                    </div>
                </form>
            </div>
            <div class="common-content">
                <div class="list-div  ht_goods_list" id="listDiv">
                    <table cellspacing="0" cellpadding="0" border="0">
                        <tr class="active">
                            <th width="3%">
                                <div class="tDiv" style="min-width:40px">{{ $lang['record_id'] }}</div>
                            </th>
                            <th width="12%">
                                <div class="tDiv">{{ $lang['bargain_goods_name'] }}</div>
                            </th>
                            <th width="7%">
                                <div class="tDiv">{{ $lang['user_name'] }}</div>
                            </th>
                            <th width="14%">
                                {{--活动时间--}}
                                <div class="tDiv">{{ $lang['bargain_time'] }}</div>
                            </th>
                            <th width="8%">
                                {{--原价/底价--}}
                                <div class="tDiv" style="min-width:100px">{{ $lang['bargain_old_and_final'] }}</div>
                            </th>
                            <th width="10%">
                                <div class="tDiv">{{ $lang['is_hot'] }}</div>
                            </th>
                            <th width="10%">
                                {{--SKU/库存--}}
                                <div class="tDiv">{{ $lang['goods_sku_and_stock'] }}</div>
                            </th>
                            <th width="10%">
                                <div class="tDiv">{{ $lang['bargain_status'] }}</div>
                            </th>
                            <th width="8%">
                                <div class="tDiv">{{ $lang['adopt_status'] }}</div>
                            </th>
                            <th width="14%" class="handle">
                                <div style="min-width:84px">{{ $lang['handler'] }}</div>
                            </th>
                        </tr>

                        @if($list)

                            @foreach($list as $list)

                                <tr>
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
                                        <div class="tDiv" title="{{ $list['user_name'] }}" data-toggle="tooltip"><font
                                                    class="red">{{ $list['user_name'] }}</font></div>
                                    </td>
                                    <td>
                                        <div class="tDiv">
                                            <p>{{ $lang['bargain_start_time'] }}：{{ $list['start_time'] }}</p>
                                            <p>{{ $lang['bargain_end_time'] }}：{{ $list['end_time'] }}</p>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="tDiv">
                                            <p>{{ $lang['shop_price'] }}：{{ $list['shop_price'] }}</p>
                                            <p>{{ $lang['final_price'] }}：{{ $list['target_price'] }}</p>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="tDiv">
                                            <div style="line-height:15px;"><span class="fl">{{ $lang['is_hot'] }}</span>
                                                <div class="switch fl ml10
@if(isset($list['is_hot']) && $list['is_hot'])
                                                        active
                                                        @endif
                                                        " id="goodsis_hot{{ $list['id'] }}" title="
@if(isset($list['is_hot']) && $list['is_hot'])
                                                {{ $lang['yes'] }}
                                                @else
                                                {{ $lang['no'] }}
                                                @endif
                                                        " onclick="edit_goods({{ $list['id'] }})">
                                                    <div class="circle"></div>
                                                </div>
                                                <input type="hidden" value="0" name="">
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="tDiv">{{ $list['goods_number'] }}</div>
                                    </td>
                                    <td>
                                        <div class="tDiv">
                                            <font class="blue">{{ $list['is_status'] }}</font>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="tDiv">
                                            <font class="blue">{{ $list['is_audit'] }}</font>
                                        </div>
                                    </td>
                                    <td class="handle">
                                        <div class="tDiv ht_tdiv" style="padding-bottom:0px;">
                                            {{--活动详情--}}
                                            <a href="{{ route('admin/bargain/bargainlog',array('bargain_id'=>$list['id'])) }}"
                                               class="btn_see"><i
                                                        class="sc_icon sc_icon_see"></i>{{ $lang['bargain_info'] }}</a>
                                            <a href="{{ route('admin/bargain/addgoods',array('id'=>$list['id'])) }}"
                                               class="btn_edit"><i class="fa fa-edit"></i>{{ $lang['edit'] }}</a>

                                            @if(isset($list['status']) && $list['status']==1)
                                                {{--移除--}}
                                                <a href='javascript:void(0);'
                                                   onclick="if(confirm('{{ $lang['confirm_delete_bargain'] }}')){window.location.href='{{ route('admin/bargain/removegoods', array('type'=>'delete','id'=>$list['id'])) }}'}"
                                                   class="btn_trash"><i
                                                            class="fa fa-trash"></i>{{ $lang['delete_bargain'] }}</a>

                                            @else
                                                {{--关闭--}}
                                                <a href='javascript:void(0);'
                                                   onclick="if(confirm('{{ $lang['confirm_close_bargain'] }}')){window.location.href='{{ route('admin/bargain/removegoods', array('type'=>'status','id'=>$list['id'])) }}'}"
                                                   class="btn_trash"><i
                                                            class="fa fa-trash"></i>{{ $lang['close_bargain'] }}</a>

                                            @endif

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
                            <td colspan="12">
                                <div class="list-page">
                                    @include('admin.bargain.admin_pageview')
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
    function edit_goods(id) {
        $.post("{{ route('admin/bargain/editgoods') }}", {id: id}, function (data) {
            if ($("#goodsis_hot" + id).hasClass("active")) {
                $("#goodsis_hot" + id).removeClass("active");
            } else {
                $("#goodsis_hot" + id).addClass("active");
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
            $.post("{{ route('admin/bargain/removegoods') }}", {id: arr, group: group}, function (data) {
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
