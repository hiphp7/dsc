@include('admin.wechat.pageheader')
<style>
    ul, li {
        overflow: hidden;
    }

    .dates_box_top {
        height: 32px;
    }

    .dates_bottom {
        height: auto;
    }

    .dates_hms {
        width: auto;
    }

    .dates_btn {
        width: auto;
    }

    .dates_mm_list span {
        width: auto;
    }

</style>
<div class="wrapper">
    <div class="title"> {{ $lang['drp_manage'] }} - {{ $lang['transfer_log_menu'] }}</div>
    <div class="content_tips">
        <div class="explanation" id="explanation">
            <div class="ex_tit"><i class="sc_icon"></i><h4>{{ $lang['operating_hints'] }}</h4><span id="explanationZoom" title="{{ $lang['fold_tips'] }}"></span>
            </div>
            <ul>
                @if(isset($lang['transfer_log_tips']) && !empty($lang['transfer_log_tips']))

                    @foreach($lang['transfer_log_tips'] as $v)
                        <li>{!! $v !!}</li>
                    @endforeach

                @endif
            </ul>
        </div>
        <div class="flexilist">
            <div class="common-head">

                <div class="fl">
                    <form action="{{ route('distribute.admin.export_transfer_log') }}" method="post">
                        <div class="label_value">
                            <div class="text_time" id="text_time1" style="float:left;">
                                <input type="text" name="starttime" class="text"
                                       value="{{ date('Y-m-d H:i', mktime(0,0,0,date('m'), date('d')-7, date('Y'))) }}"
                                       id="promote_start_date" class="text mr0" readonly>
                            </div>

                            <div class="text_time" id="text_time2" style="float:left;">
                                <input type="text" name="endtime" class="text" value="{{ date('Y-m-d H:i') }}"
                                       id="promote_end_date" class="text" readonly>
                            </div>
                            @csrf
                            <input type="submit" name="export" value="{{ $lang['export'] }}" class="button bg-green"/>
                        </div>
                    </form>
                </div>

                <div class="search">
                    <form action="{{ route('distribute.admin.transfer_log') }}"
                          name="searchForm" method="post" role="search">
                        <div class="input">
                            @csrf
                            <input type="text" name="keywords" class="text nofocus"
                                   placeholder="{{ $lang['search_for'] }}"
                                   autocomplete="off">
                            <input type="submit" value="" class="btn search_button">
                        </div>
                    </form>
                </div>
            </div>
            <div class="common-content">
                <div class="list-div" id="min-h300">
                    <table cellspacing="0" cellpadding="0" border="0">
                        <tr>

                            <th>
                                <div class="tDiv">{{ $lang['record_id'] }}</div>
                            </th>
                            <th>
                                <div class="tDiv">{{ $lang['shop_name'] }}</div>
                            </th>
                            <th>
                                <div class="tDiv">{{ $lang['trans_money'] }}</div>
                            </th>
                            <th>
                                <div class="tDiv">{{ $lang['add_time'] }}</div>
                            </th>
                            <th>
                                <div class="tDiv">{{ $lang['check_status'] }}</div>
                            </th>
                            <th>
                                <div class="tDiv">{{ $lang['deposit_status'] }}</div>
                            </th>
                            <th>
                                <div class="tDiv">{{ $lang['finish_status'] }}</div>
                            </th>
                            <th width="20%">
                                <div class="tDiv">{{ $lang['handler'] }}</div>
                            </th>
                        </tr>

                        @if(!empty($list))

                            @foreach($list as $val)

                                <tr>
                                    <td>
                                        <div class="tDiv">{{ $val['id'] }}</div>
                                    </td>
                                    <td>
                                        <div class="tDiv">{{ $val['shop_name'] }}</div>
                                    </td>
                                    <td>
                                        <div class="tDiv">{{ $val['money'] }}</div>
                                    </td>
                                    <td>
                                        <div class="tDiv">{{ $val['add_time_format'] }}</div>
                                    </td>
                                    <td>
                                        <div class="tDiv">{{ $val['check_status_format'] }}</div>
                                    </td>
                                    <td>
                                        <div class="tDiv">{{ $val['deposit_status_format'] }}</div>
                                    </td>
                                    <td>
                                        <div class="tDiv">{{ $val['finish_status_format'] }}</div>
                                    </td>
                                    <td class="handle">
                                        <div class="tDiv a3">
                                            {{--审核--}}
                                            <a href="{{ route('distribute.admin.transfer_log_check', array('id'=>$val['id'])) }}" class="btn_edit fancybox fancybox.iframe"><i class="fa fa-edit"></i>{{ $lang['check'] }}</a>
                                            {{--已审核可查看--}}
                                            @if(isset($val['check_status']) && $val['check_status'] == 1)
                                            <a href="javascript:;" data-href="{{ route('distribute.admin.transfer_log_see', array('id'=>$val['id'])) }}" class="btn_see transfer-see"><i class="fa fa-eye"></i>{{ $lang['see'] }}</a>
                                            @endif
                                            {{--删除--}}
                                            <a href="javascript:;" data-href="{{ route('distribute.admin.transfer_log_delete', array('id'=>$val['id'])) }}" class="btn_trash transfer-delete"><i class="fa fa-trash-o"></i>{{ $lang['delete'] }}</a>
                                        </div>
                                    </td>
                                </tr>

                            @endforeach

                        @else

                            <tbody>
                            <tr>
                                <td class="no-records" colspan="8">{{ $lang['no_records'] }}</td>
                            </tr>
                            </tbody>

                        @endif

                        <tfoot>
                        <tr>
                            <td colspan="8">
                                @include('admin.drp.pageview')
                            </td>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>


</div>

<div class="hidden transfer-info">
    <ul class="info-list p10 m10" >
        <li > {{ $lang['trade_no'] }}： <span id="trade_no"></span></li>
        <li > {{ $lang['trans_money'] }}：<span id="money"></span></li>

    </ul>
</div>

<script type="text/javascript">
    var opts1 = {
        'targetId': 'promote_start_date',
        'triggerId': ['promote_start_date'],
        'alignId': 'text_time1',
        'format': '-',
        'hms': 'off'
    }, opts2 = {
        'targetId': 'promote_end_date',
        'triggerId': ['promote_end_date'],
        'alignId': 'text_time2',
        'format': '-',
        'hms': 'off'
    }

    xvDate(opts1);
    xvDate(opts2);


    $(function () {

        // 查看
        $('.transfer-see').bind('click', function() {
            var url = $(this).attr("data-href");

            $.post(url, '', function (data) {

                if (data.status == 0) {

                    //console.log(data.info);

                    $('#trade_no').html(data.info.trade_no);
                    $('#money').html(data.info.money);

                    var bank_info = data.info.bank_info_format;

                    var name = '{{ $lang['bank_info'] }}';
                    $('.info-list').append(" <li > "+ name + " </li>");
                    for(var k in bank_info)
                    {
                        //console.log(k + ':' + bank_info[k])
                        $('.info-list').append("<li><span >" + k + '：' + bank_info[k]+ "</span></li>");
                    }
                    var deposit_data = data.info.deposit_data_format;

                    var name1 = '{{ $lang['deposit_data'] }}';
                    $('.info-list').append(" <li > "+ name1 + " </li>");
                    for(var k in deposit_data)
                    {
                        //console.log(k + ':' + deposit_data[k])
                        $('.info-list').append("<li><span >" + k + '：' + deposit_data[k]+ "</span></li>");
                    }

                    $(".transfer-info").show().removeClass("hidden");

                    //捕获页
                    layer.open({
                        type: 1,
                        skin: 'layui-layer-rim', //加上边框
                        area: ['420px', '420px'], //宽高
                        shade: false,
                        title: data.title, //不显示标题
                        content: $('.transfer-info'), //捕获的元素
                    });

                }
                $.fancybox.close();
                return false;
            }, 'json');

        });


        // 删除
        $('.transfer-delete').bind('click', function() {
            var url = $(this).attr("data-href");

            //询问框
            layer.confirm('{{ $lang['confirm_delete'] }}', {
                btn: ['{{ $lang['ok'] }}', '{{ $lang['cancel'] }}'] //按钮
            }, function () {
                $.post(url, '', function (data) {
                    layer.msg(data.msg);
                    if (data.status == 0) {
                        if (data.url) {
                            window.location.href = data.url;
                        }
                    }
                    return false;
                }, 'json');
            });

        });

    });

</script>

@include('admin.drp.pagefooter')
