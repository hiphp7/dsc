@include('admin.drp.pageheader')

<div class="fancy">
    <div class="title">{{ $lang['drp_manage'] }} - {{ $lang['transfer_log_menu'] }}</div>

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
            <div class="main-info">
                <form method="post" action="{{ route('distribute.admin.transfer_log_check') }}" class="form-horizontal"  role="form" onsubmit="return false;">
                    <div class="switch_info">

                        <div class="item">
                            <div class="label-t">{{ $lang['shop_name'] }}</div>
                            <div class="label_value">
                                <input type="text" name="data[shop_name]" class="text form-control" value="{{ $info['shop_name'] ?? '' }}" readonly />
                            </div>
                        </div>

                        <div class="item">
                            <div class="label-t">{{ $lang['trans_money'] }}</div>
                            <div class="label_value">
                                <input type="text" name="data[money]" class="text form-control" value="{{ $info['money'] ?? '' }}" readonly />
                                <div class="notic ">{{ $lang['trans_money_notice'] }}</div>
                            </div>
                        </div>

                        <div class="item">
                            <div class="label-t">{{ $lang['enc_bank_no'] }}</div>
                            <div class="label_value">
                                <input type="text" name="data[enc_bank_no]" class="text form-control" value="{{ $info['bank_info']['enc_bank_no'] ?? '' }}" readonly />
                            </div>
                        </div>

                        <div class="item">
                            <div class="label-t">{{ $lang['enc_true_name'] }}</div>
                            <div class="label_value">
                                <input type="text" name="data[enc_true_name]" class="text form-control" value="{{ $info['bank_info']['enc_true_name'] ?? '' }}" readonly />
                                <div class="notic ">{{ $lang['enc_true_name_notice'] }}</div>
                            </div>
                        </div>


                        <div class="item">
                            <div class="label-t">{{ $lang['check_status'] }}</div>
                            <div class="label_value col-md-10">
                                <div class="checkbox_items">
                                    {{--未审核--}}
                                    <div class="checkbox_item">
                                        <input type="radio" name="data[check_status]" class="ui-radio evnet_show clicktype" id="value_117_0" value="0"
                                               @if(isset($info['check_status']) && $info['check_status'] == 0)
                                               checked
                                                @endif
                                        >
                                        <label for="value_117_0" class="ui-radio-label
                                        @if(isset($info['check_status']) && $info['check_status'] == 0)
                                                active
                                            @endif
                                                ">{{ $lang['check_status_0'] }}</label>
                                    </div>
                                    {{--通过--}}
                                    <div class="checkbox_item">
                                        <input type="radio" name="data[check_status]" class="ui-radio evnet_show clicktype" id="value_117_1" value="1"
                                               @if(isset($info['check_status']) && $info['check_status'] == 1)
                                               checked
                                                @endif
                                        >
                                        <label for="value_117_1" class="ui-radio-label
                                                @if(isset($info['check_status']) && $info['check_status'] == 1)
                                                    active
                                                @endif
                                                ">{{ $lang['check_status_1'] }}</label>
                                    </div>
                                    {{--拒绝--}}
                                    {{--<div class="checkbox_item">--}}
                                        {{--<input type="radio" name="data[check_status]" class="ui-radio evnet_show clicktype" id="value_117_2" value="2"--}}
                                               {{--@if(isset($info['check_status']) && $info['check_status'] == 2)--}}
                                               {{--checked--}}
                                                {{--@endif--}}
                                        {{-->--}}
                                        {{--<label for="value_117_2" class="ui-radio-label--}}
                                                {{--@if(isset($info['check_status']) && $info['check_status'] == 2)--}}
                                                {{--active--}}
                                            {{--@endif--}}
                                                {{--">{{ $lang['check_status_2'] }}</label>--}}
                                    {{--</div>--}}

                                </div>
                            </div>
                        </div>

                        <div class="item">
                            <div class="label-t">{{ $lang['deposit_status'] }}</div>
                            <div class="label_value col-md-10">
                                <div class="checkbox_items">
                                    {{--未提现--}}
                                    <div class="checkbox_item">
                                        <input type="radio" class="ui-radio evnet_show disabled" disabled id="value_118_0" value="0"
                                               @if(isset($info['deposit_status']) && $info['deposit_status'] == 0)
                                               checked
                                                @endif
                                        >
                                        <label for="value_118_0" class="ui-radio-label disabled
                                        @if(isset($info['deposit_status']) && $info['deposit_status'] == 0)
                                                active
                                            @endif
                                                ">{{ $lang['deposit_status_0'] }}</label>
                                    </div>
                                    {{--已提现--}}
                                    <div class="checkbox_item">
                                        <input type="radio" class="ui-radio evnet_show disabled" disabled id="value_118_1" value="1"
                                               @if(isset($info['deposit_status']) && $info['deposit_status'] == 1)
                                               checked
                                                @endif
                                        >
                                        <label for="value_118_1" class="ui-radio-label disabled
                                                @if(isset($info['deposit_status']) && $info['deposit_status'] == 1)
                                                active
                                            @endif
                                                ">{{ $lang['deposit_status_1'] }}</label>
                                    </div>

                                </div>
                            </div>
                        </div>

                        @if(isset($info['deposit_status']) && $info['deposit_status'] == 0)

                            <div class="item online_transfer
                                @if(isset($info['check_status']) && $info['check_status'] != 1)
                                hidden
                                @endif
                                    ">
                                {{--微信企业付款--}}
                                <div class="label-t">{{ $lang['online_transfer'] }}</div>
                                <div class="label_value col-md-10">
                                    <div class="checkbox_items">
                                        {{--付款至银行卡--}}
                                        <div class="checkbox_item">
                                            <input type="radio" name="data[deposit_type]" class="ui-radio evnet_show "  id="value_119_2" value="2" checked />
                                            <label for="value_119_2" class="ui-radio-label active">{{ $lang['online_transfer_1'] }}</label>
                                        </div>
                                        {{--付款至零钱--}}
                                        @if(isset($info['openid']) && $info['openid'])
                                        <div class="checkbox_item">
                                            <input type="radio" name="data[deposit_type]" class="ui-radio evnet_show "  id="value_119_1" value="1" />
                                            <label for="value_119_1" class="ui-radio-label">{{ $lang['online_transfer_0'] }}</label>
                                        </div>
                                        @endif

                                    </div>
                                </div>
                            </div>

                            <div class="item online_transfer
                                @if(isset($info['check_status']) && $info['check_status'] != 1)
                                    hidden
                                @endif
                                    ">
                                <div class="label-t">{{ $lang['remark'] }}</div>
                                <div class="label_value">
                                    <input type="text" name="data[desc]" class="text form-control" value="{{ $info['desc'] ?? '提现' }}"  />
                                    <div class="notic ">{{ $lang['remark_notice'] }}</div>
                                </div>
                            </div>

                        @endif

                        <div class="item">
                            <div class="label-t">&nbsp;</div>
                            <div class="lable_value info_btn">
                                @csrf
                                <input type="hidden" name="id" value="{{ $info['id'] ?? 0 }}">
                                <input type="hidden" name="user_id" value="{{ $info['user_id'] ?? 0 }}">
                                <input type="hidden" name="data[bank_code]" value="{{ $info['bank_info']['bank_code'] ?? '' }}">
                                <input type="submit" name="submit" value="{{ $lang['button_submit'] }}" class="button btn-danger bg-red" style="margin:0 auto;"/>
                            </div>
                        </div>

                    </div>
                </form>
            </div>
        </div>

    </div>
</div>
<script type="text/javascript">
    $(function () {

        // 切换显示 微信企业付款
        $(".clicktype").click(function () {
            // var val = $(this).find("input[type=radio]").val();
            var val = $(this).val();

            if ('1' == val) {
                $(".online_transfer").show().removeClass("hidden");
            } else {
                $(".online_transfer").hide().addClass("hidden");
            }
        });

        // 提交
        $(".form-horizontal").submit(function () {
            var ajax_data = $(this).serialize();
            $.post("{{ route('distribute.admin.transfer_log_check') }}", ajax_data, function (data) {
                layer.msg(data.msg);
                if (data.status == 0) {

                    setTimeout(function(){
                        // 关闭弹窗
                        parent.$.fancybox.close();
                        window.parent.location = data.url;
                    }, 1000);
                } else {
                    return false;
                }
            }, 'json');
        });
    })
</script>

@include('admin.drp.pagefooter')