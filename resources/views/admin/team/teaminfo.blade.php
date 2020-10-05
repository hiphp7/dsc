@include('admin.team.admin_pageheader')

<div class="wrapper">
    <div class="title">{{ $lang['team_menu'] }} - {{ $lang['teaminfo_list'] }}</div>
    <div class="content_tips">
        <div class="tabs_info">
            <ul>
                <li class="
@if($status =='1' )
                        curr
                        @endif
                        "><a href="{{ route('admin/team/teaminfo') }}">{{ $lang['teaminfo_all'] }}</a></li>
                <li class="
@if($status == '2')
                        curr
                        @endif
                        "><a
                            href="{{ route('admin/team/teaminfo',array('status'=>2,)) }}">{{ $lang['teaminfo_ing'] }}</a>
                </li>
                <li class="
@if($status == '3')
                        curr
                        @endif
                        "><a
                            href="{{ route('admin/team/teaminfo',array('status'=>3)) }}">{{ $lang['teaminfo_success'] }}</a>
                </li>
                <li class="
@if($status == '4')
                        curr
                        @endif
                        "><a
                            href="{{ route('admin/team/teaminfo',array('status'=>4)) }}">{{ $lang['teaminfo_fail'] }}</a>
                </li>
            </ul>
        </div>
        <div class="explanation" id="explanation">
            <div class="ex_tit"><i class="sc_icon"></i><h4>{{ $lang['operating_hints'] }}</h4><span id="explanationZoom"
                                                                                                    title="{{ $lang['fold_tips'] }}"></span>
            </div>
            <ul>
                <li>{{ $lang['teaminfo_list_tips']['0'] }}</li>

            </ul>
        </div>
        <div class="flexilist">
            <div class="common-head">
                <form action="{{ route('admin/team/teaminfo') }}" method="post">
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
                            <th width="30%">
                                <div class="tDiv">{{ $lang['goods_name'] }}</div>
                            </th>
                            <th>
                                <div class="tDiv">{{ $lang['shop_name'] }}</div>
                            </th>
                            <th>
                                {{--开团时间--}}
                                <div class="tDiv">{{ $lang['teaminfo_time'] }}</div>
                            </th>
                            <th>
                                {{--剩余时间--}}
                                <div class="tDiv">{{ $lang['residue_time'] }}</div>
                            </th>
                            <th>
                                {{--差几人成团--}}
                                <div class="tDiv">{{ $lang['residue_team_num'] }}</div>
                            </th>
                            <th>
                                <div class="tDiv">{{ $lang['teaminfo_status'] }}</div>
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
                                                    <input type="checkbox" value="{{ $list['team_id'] }}"
                                                           name="checkboxes[]">
                                                </label>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="tDiv">{{ $list['team_id'] }}</div>
                                    </td>
                                    <td>
                                        <div class="tDiv clamp2">{{ $list['goods_name'] }}</div>
                                    </td>
                                    <td>
                                        <div class="tDiv">{{ $list['user_name'] }} </div>
                                    </td>
                                    <td>
                                        <div class="tDiv">{{ $list['start_time'] }}</div>
                                    </td>
                                    <td>
                                        <div class="tDiv">
                                            {{--已结束--}}
                                            @if($list['cle'] < 0)

                                                {{ $lang['team_is_over'] }}

                                            @else

                                                {{ $list['time'] }}

                                            @endif

                                        </div>
                                    </td>
                                    <td>
                                        <div class="tDiv">
                                            {{--已成团--}}
                                            @if($list['surplus'] <= 0)

                                                {{ $lang['team_is_success'] }}

                                            @else

                                                {{ $list['surplus'] }}

                                            @endif

                                        </div>
                                    </td>
                                    <td>
                                        <div class="tDiv">{{ $list['status'] }}</div>
                                    </td>
                                    <td>
                                        <div class="tDiv">
                                            <a href="{{ route('admin/team/teamorder', array('team_id'=>$list['team_id'])) }}">{{ $lang['view'] }}</a>
                                            &nbsp;|
                                            <a href='javascript:void(0);'
                                               onclick="if(confirm('{{ $lang['confirm_delete_goods'] }}')){window.location.href='{{ route('admin/team/removeteam', array('team_id'=>$list['team_id'])) }}'}"
                                               class="btn_trash"><i class="icon icon-trash"></i>{{ $lang['drop'] }}</a>
                                        </div>
                                    </td>
                                </tr>

                            @endforeach

                        @else

                            <tbody>
                            <tr>
                                <td class="no-records" colspan="9">{{ $lang['no_records'] }}</td>
                            </tr>
                            </tbody>

                        @endif

                        <tfoot>
                        <tr>
                            <td colspan="3">
                                <div class="tDiv of">
                                    <div class="tfoot_btninfo">
                                        <input type="submit" onclick="confirm_bath()" id="btnSubmit"
                                               value="{{ $lang['batch_delete'] }}"
                                               class="button">
                                    </div>
                                </div>
                            </td>
                            <td colspan="6">
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
            $.post("{{ route('admin/team/removeteam') }}", {team_id: arr}, function (data) {
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
