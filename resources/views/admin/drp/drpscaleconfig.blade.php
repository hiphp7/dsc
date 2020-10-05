@include('admin.drp.pageheader')

<div class="wrapper">
    <div class="title">{{ $lang['drp_manage'] }} - {{ $lang['drp_scale_config'] }}</div>
    <div class="content_tips">
        <div class="tabs_info">
            <ul>
                <li><a href="{{ route('admin/drp/config') }}">{{ $lang['drp_config'] }}</a></li>
                <li><a href="{{ route('admin/drp/config', ['group' => 'show']) }}">{{ lang('admin/drp.drp_show_config') }}</a></li>
                <li class="curr"><a href="{{ route('admin/drp/drp_scale_config') }}">{{ $lang['drp_scale_config'] }}</a></li>
                <li><a href="{{ route('admin/drp/drp_set_qrcode') }}">{{ $lang['drp_qrcode_config'] }}</a></li>
                <li><a href="{{ route('admin/drp/config', ['group' => 'message']) }}">{{ lang('admin/drp.drp_message_config') }}</a></li>
            </ul>
        </div>
        <div class="explanation" id="explanation">
            <div class="ex_tit">
                <i class="sc_icon"></i><h4>{{ lang('admin/common.operating_hints') }}</h4><span id="explanationZoom" title="{{ lang('admin/common.fold_tips') }}"></span>
            </div>
            <ul>
                @if(isset($lang['drp_scale_config_tips']) && !empty($lang['drp_scale_config_tips']))

                    @foreach($lang['drp_scale_config_tips'] as $v)
                        <li>{!! $v !!}</li>
                    @endforeach

                @endif
            </ul>
        </div>

        <div class="flexilist">
            <div class="main-info">
                <div class="switch_info">
                    <form action="{{ route('admin/drp/drp_scale_config') }}" method="post" class="form-horizontal" role="form">

                        {{--结算规则--}}
                        @if(isset($settlement_rules) && !empty($settlement_rules))

                            <div class="item_title">
                                <div class="vertical"></div>
                                <div class="f15">{{ lang('admin/drp.drp_scale_config') }}</div>
                            </div>

                            @foreach($settlement_rules as $config)

                                <div class="item">
                                    <div class="label-t">{{ $config['name'] }}</div>

                                        @if($config['type'] == 'text')

                                            @if(isset($config['style']) && $config['style'] == 'number')

                                            <div class="label_value ">
                                                <div class="input-group w150">
                                                    <input type="number" min="0" name="data[{{ $config['code'] }}]" class="form-control" value="{{ $config['value'] }}">
                                                    @if(isset($config['unit']) && !empty($config['unit']))<span class="input-group-addon">{{ $config['unit'] }}</span>@endif
                                                </div>
                                                <p class="notic">{{ $config['warning'] }}</p>
                                            </div>

                                            @else

                                            <div class="label_value">

                                                <input type="text" name="data[{{ $config['code'] }}]" class="text" value="{{ $config['value'] }}">

                                                <p class="notic">{{ $config['warning'] }}</p>
                                            </div>

                                            @endif

                                        @elseif($config['type'] == 'textarea')

                                        <div class="label_value">
                                            <textarea name="data[{{ $config['code'] }}]" class="form-control w500" rows="5" cols="10" >{{ $config['value'] }}</textarea>

                                            <p class="notic">{{ $config['warning'] }}</p>
                                        </div>

                                        @elseif($config['type'] == 'radio')

                                        <div class="label_value">
                                            <div class="checkbox_items">
                                                <div class="checkbox_item">
                                                    <input type="radio" class="ui-radio" id="value_1_{{ $config['code'] }}" name="data[{{ $config['code'] }}]" value="1"
                                                           @if($config['value'] == 1)
                                                           checked
                                                            @endif
                                                    >
                                                    <label for="value_1_{{ $config['code'] }}" class="ui-radio-label
                                                        @if($config['value'] == 1)
                                                            active
                                                            @endif
                                                            ">{{ $lang['enabled'] }}</label>
                                                </div>
                                                <div class="checkbox_item">
                                                    <input type="radio" class="ui-radio" id="value_0_{{ $config['code'] }}" name="data[{{ $config['code'] }}]" value="0"
                                                           @if($config['value'] == 0)
                                                           checked
                                                            @endif
                                                    >
                                                    <label for="value_0_{{ $config['code'] }}" class="ui-radio-label
                                                        @if($config['value'] == 0)
                                                            active
                                                            @endif
                                                            ">{{ $lang['disabled'] }}</label>
                                                </div>
                                            </div>
                                            <p class="notic">{!! $config['warning'] !!}</p>
                                        </div>

                                        @endif

                                </div>

                            @endforeach

                        @endif


                        <div class="item">
                            <div class="label-t">&nbsp;</div>
                        </div>

                        {{--提现配置--}}
                        @if(isset($withdraw_list) && !empty($withdraw_list))

                        <div class="item_title">
                            <div class="vertical"></div>
                            <div class="f15">{{ lang('admin/drp.drp_withdraw_config') }}</div>
                        </div>

                        @foreach($withdraw_list as $config)

                            <div class="item">
                                <div class="label-t">{{ $config['name'] }}</div>

                                @if($config['type'] == 'text')

                                    @if(isset($config['style']) && $config['style'] == 'number')

                                        <div class="label_value ">
                                            <div class="input-group w150">
                                                <input type="number" step="0.01" min="0" name="data[{{ $config['code'] }}]" class="form-control" value="{{ $config['value'] }}">
                                                @if(isset($config['unit']) && !empty($config['unit']))<span class="input-group-addon">{{ $config['unit'] }}</span>@endif
                                            </div>
                                            <p class="notic">{{ $config['warning'] }}</p>
                                        </div>

                                    @else

                                        <div class="label_value">

                                            <input type="text" name="data[{{ $config['code'] }}]" class="text" value="{{ $config['value'] }}">

                                            <p class="notic">{{ $config['warning'] }}</p>
                                        </div>

                                    @endif

                                @elseif($config['type'] == 'textarea')

                                    <div class="label_value">
                                        <textarea name="data[{{ $config['code'] }}]" class="form-control w500" rows="5" >{{ $config['value'] }}</textarea>

                                        <p class="notic">{{ $config['warning'] }}</p>
                                    </div>

                                @elseif($config['type'] == 'radio')

                                    <div class="label_value">
                                        <div class="checkbox_items">
                                            <div class="checkbox_item">
                                                <input type="radio" class="ui-radio" id="value_1_{{ $config['code'] }}" name="data[{{ $config['code'] }}]" value="1"
                                                       @if($config['value'] == 1)
                                                       checked
                                                        @endif
                                                >
                                                <label for="value_1_{{ $config['code'] }}" class="ui-radio-label
                                                        @if($config['value'] == 1)
                                                        active
                                                        @endif
                                                        ">{{ $lang['enabled'] }}</label>
                                            </div>
                                            <div class="checkbox_item">
                                                <input type="radio" class="ui-radio" id="value_0_{{ $config['code'] }}" name="data[{{ $config['code'] }}]" value="0"
                                                       @if($config['value'] == 0)
                                                       checked
                                                        @endif
                                                >
                                                <label for="value_0_{{ $config['code'] }}" class="ui-radio-label
                                                        @if($config['value'] == 0)
                                                        active
                                                        @endif
                                                        ">{{ $lang['disabled'] }}</label>
                                            </div>
                                        </div>
                                        <p class="notic">{!! $config['warning'] !!}</p>
                                    </div>

                                @endif

                            </div>

                        @endforeach

                        @endif

                        <div class="item">
                            <div class="label-t">&nbsp;</div>
                            <div class="label_value info_btn">
                                @csrf
                                <input type="submit" class="button btn-danger bg-red" value="{{ lang('admin/common.button_submit') }}"/>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>


<script type="text/javascript">
    $(function () {
        //修改佣金可分成时间
        $('input[name="data[settlement_time]"]').blur(function () {
            var day_val = $(this).val();
            if (day_val < 7) {
                layer.msg('{{ lang('admin/drp.time_not_less_than_seven') }}');
                $(this).val(7);
                return false;
            }
        });

        //验证表单
        $('input[type="submit"]').click(function () {
            var draw_money = $('input[name="data[draw_money]"]').val(); // 提现金额

            if (!isFloatNum(draw_money)) {
                layer.msg('请输入非负数字');
                return false;
            }
        });

        /**
         * 校验非负浮点数 且小数点后两位 就返回true
         * @param val
         * @returns {boolean}
         */
        function isFloatNum(val) {
            if (val) {
                var reg = /^\+{0,1}\d+(\.\d{1,2})?$/; // 非负浮点数 小数点后2位
                if (reg.test(val)){
                    return true;
                } else {
                    return false;
                }
            }

            return true;
        }
    });
</script>

@include('admin.drp.pagefooter')
