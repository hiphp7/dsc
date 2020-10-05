@include('admin.team.admin_pageheader')
<div class="wrapper">
    <div class="title">{{ $lang['team_menu'] }} - {{ $lang['team_recycle'] }}</div>
    <div class="content_tips">
        <div class="tabs_info">
            <ul>
                <li><a href="{{ route('admin/team/index') }}">{{ $lang['team_goods'] }}</a></li>
                <li><a href="{{ route('admin/team/category') }}">{{ $lang['team_category'] }}</a></li>
                <li><a href="{{ route('admin/team/teaminfo') }}">{{ $lang['team_info'] }}</a></li>
                <li style="display:none" class="curr"><a
                            href="{{ route('admin/team/teamrecycle') }}">{{ $lang['team_recycle'] }}</a></li>
            </ul>
        </div>
        <div class="explanation" id="explanation">
            <div class="ex_tit"><i class="sc_icon"></i><h4>{{ $lang['operating_hints'] }}</h4><span id="explanationZoom"
                                                                                                    title="{{ $lang['fold_tips'] }}"></span>
            </div>
            <ul>
                <li>{{ $lang['team_recycle_tips']['0'] }}</li>
            </ul>
        </div>
        <div class="flexilist">

            <div class="common-head">
                <form action="{{ route('admin/team/teamrecycle') }}" method="post">
                    @csrf
                    <div class="search">
                        <div class="input">
                            <input type="text" name="keyword" class="text nofocus"
                                   placeholder="{{ $lang['button_search'] }}" autocomplete="off">
                            <input type="submit" value="" class="btn" name="export">
                        </div>
                    </div>
                </form>
            </div>
            <div class="common-content">
                <div class="list-div" id="listDiv">
                    <table cellspacing="0" cellpadding="0" border="0">
                        <tr class="active">
                            <th></th>
                            <th>
                                <div class="tDiv">{{ $lang['record_id'] }}</div>
                            </th>
                            <th width="20%">
                                <div class="tDiv">{{ $lang['goods_name'] }}</div>
                            </th>
                            <th>
                                <div class="tDiv">{{ $lang['goods_img'] }}</div>
                            </th>
                            <th>
                                <div class="tDiv">{{ $lang['shop_name'] }}</div>
                            </th>
                            <th>
                                {{--原价/拼团/货号--}}
                                <div class="tDiv">{{ $lang['price_team_products'] }}</div>
                            </th>
                            <th>
                                {{--添加排行(按钮)--}}
                                <div class="tDiv">{{ $lang['team_ranking_add'] }}</div>
                            </th>
                            <th>
                                <div class="tDiv">{{ $lang['sort_order'] }}</div>
                            </th>
                            <th>
                                {{--SKU/库存--}}
                                <div class="tDiv">{{ $lang['team_sku_stock'] }}</div>
                            </th>
                            <th>
                                <div class="tDiv">{{ $lang['team_limit_num'] }}</div>
                            </th>
                            <th>
                                <div class="tDiv">{{ $lang['handler'] }}</div>
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
                                        <div class="tDiv clamp2">{{ $list['goods_name'] }}</div>
                                    </td>
                                    <td>
                                        <div class="tDiv">
                                  <span class="show">
                                <img style="width: 60px;height: 60px" src="{{ $list['goods_thumb'] }}"/>
                                </span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="tDiv">{{ $list['user_name'] }}</div>
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
                                                        " title="
@if($list['is_best'])
                                                {{ $lang['yes'] }}
                                                @else
                                                {{ $lang['no'] }}
                                                @endif
                                                        "
                                                     onclick="listTable.switchBt(this, 'toggle_best', {{ $list['goods_id'] }})">
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
                                                        " title="
@if($list['is_new'])
                                                {{ $lang['yes'] }}
                                                @else
                                                {{ $lang['no'] }}
                                                @endif
                                                        "
                                                     onclick="listTable.switchBt(this, 'toggle_best', {{ $list['goods_id'] }})">
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
                                                        " title="
@if($list['is_hot'])
                                                {{ $lang['yes'] }}
                                                @else
                                                {{ $lang['no'] }}
                                                @endif
                                                        "
                                                     onclick="listTable.switchBt(this, 'toggle_best', {{ $list['goods_id'] }})">
                                                    <div class="circle"></div>
                                                </div>
                                                <input type="hidden" value="0" name="">
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="tDiv">{{ $list['sort_order'] }}</div>
                                    </td>
                                    <td>
                                        <div class="tDiv">{{ $list['goods_number'] }}</div>
                                    </td>
                                    <td>
                                        {{--人次--}}
                                        <div class="tDiv">{{ $list['limit_num'] }}{{ $lang['of_limit_num'] }}</div>
                                    </td>
                                    <td>
                                        <div class="tDiv">
                                            <a href='javascript:void(0);'
                                               onclick="if(confirm('{{ $lang['confirm_recover_goods'] }}')){window.location.href='{{ route('admin/team/recycleegoods', array('id'=>$list['id'])) }}'}"
                                               class="btn_trash"><i class="icon icon-trash"></i>{{ $lang['recover'] }}
                                            </a>
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
                                        {{--批量恢复--}}
                                        <input type="submit" onclick="confirm_bath()" id="btnSubmit"
                                               value="{{ $lang['batch_recover'] }}"
                                               class="button">
                                    </div>
                                </div>
                            </td>
                            <td colspan="7">
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
            $.post("{{ route('admin/team/recycleegoods') }}", {id: arr}, function (data) {
                if (data.url) {
                    window.location.href = data.url;
                }
            }, 'json');
        }
    }


</script>
<script>
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
