@include('admin.bargain.admin_pageheader')

<div class="wrapper">
    <div class="title">{{ $lang['bargain_menu'] }} - {{ $lang['bargain_ing_list'] }}</div>
    <div class="content_tips">
        <div class="tabs_info">
            <ul>
                <li class="
@if($status =='1' )
                        curr
                        @endif
                        "><a
                            href="{{ route('admin/bargain/bargainlog',array('bargain_id'=>$bargain_id)) }}">{{ $lang['bargain_all'] }}</a>
                </li>

                {{--正在砍价--}}
                <li class="
@if($status == '2')
                        curr
                        @endif
                        "><a
                            href="{{ route('admin/bargain/bargainlog',array('bargain_id'=>$bargain_id,'status'=>2,)) }}">{{ $lang['bargain_list_ing'] }}</a>
                </li>
                {{--砍价成功--}}
                <li class="
@if($status == '3')
                        curr
                        @endif
                        "><a
                            href="{{ route('admin/bargain/bargainlog',array('bargain_id'=>$bargain_id,'status'=>3)) }}">{{ $lang['bargain_list_success'] }}</a>
                </li>
                {{--砍价失败--}}
                <li class="
@if($status == '4')
                        curr
                        @endif
                        "><a
                            href="{{ route('admin/bargain/bargainlog',array('bargain_id'=>$bargain_id,'status'=>4)) }}">{{ $lang['bargain_list_fail'] }}</a>
                </li>
            </ul>
        </div>
        <div class="explanation" id="explanation">
            <div class="ex_tit"><i class="sc_icon"></i><h4>{{ $lang['operating_hints'] }}</h4><span id="explanationZoom"
                                                                                                    title="{{ $lang['fold_tips'] }}"></span>
            </div>
            <ul>
                <li>{{ $lang['bargain_ing_list_tips']['0'] }}</li>

            </ul>
        </div>
        <div class="flexilist">

            <div class="common-content">
                <div class="list-div" id="listDiv">
                    <table cellspacing="0" cellpadding="0" border="0">
                        <tr class="active">
                            <th>
                                <div class="tDiv">{{ $lang['record_id'] }}</div>
                            </th>
                            <th>
                                <div class="tDiv">{{ $lang['user_name'] }}</div>
                            </th>
                            <th>
                                {{--砍价目标--}}
                                <div class="tDiv">{{ $lang['bargain_target'] }}</div>
                            </th>
                            <th>
                                {{--已砍价到--}}
                                <div class="tDiv">{{ $lang['bargain_final_price'] }}</div>
                            </th>
                            <th>
                                <div class="tDiv">{{ $lang['bargain_add_time'] }}</div>
                            </th>
                            <th>
                                {{--参与砍价人次--}}
                                <div class="tDiv">{{ $lang['bargain_count_num'] }}</div>
                            </th>
                            <th>
                                <div class="tDiv">{{ $lang['bargain_status'] }}</div>
                            </th>
                            <th>
                                <div class="tDiv">{{ $lang['handler'] }}</div>
                            </th>
                        </tr>

                        @if($list)

                            @foreach($list as $v)

                                <tr>
                                    <td>
                                        <div class="tDiv">{{ $v['id'] }}</div>
                                    </td>
                                    <td>
                                        <div class="tDiv clamp2">{{ $v['user_name'] }}</div>
                                    </td>
                                    <td>
                                        <div class="tDiv">{{ $v['target_price'] }} </div>
                                    </td>
                                    <td>
                                        <div class="tDiv">{{ $v['final_price'] }}</div>
                                    </td>
                                    <td>
                                        <div class="tDiv">{{ $v['add_time'] }}</div>
                                    </td>
                                    <td>
                                        <div class="tDiv">{{ $v['count_num'] }}</div>
                                    </td>

                                    <td>
                                        <div class="tDiv">{{ $v['status'] }}</div>
                                    </td>
                                    <td class="handle">
                                        <div class="tDiv ht_tdiv" style="padding-bottom:0px;">
                                            <a href="{{ route('admin/bargain/bargain_statistics',array('id'=>$v['id'])) }}"
                                               class="btn_see fancybox fancybox.iframe"><i
                                                        class="sc_icon sc_icon_see"></i>{{ $lang['bargain_help'] }}</a>
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
                            <td colspan="10">
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

    //弹出框
    $(".fancybox").fancybox({
        width: '60%',
        height: '60%',
        closeBtn: true,
        title: ''
    });

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
