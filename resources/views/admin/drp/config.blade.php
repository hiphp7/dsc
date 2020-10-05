@include('admin.drp.pageheader')

<style>

    .drp_config .item .drp_affiliate_mode .checkbox_items .checkbox_item {float:none;padding-bottom: 5px;}
    .drp_config .item .isdistribution .checkbox_items .checkbox_item {float:none;padding-bottom: 5px;}
</style>

<div class="wrapper">
    <div class="title">{{ $lang['drp_manage'] }} - @if($group == '') {{ lang('admin/drp.drp_config') }} @else {{ lang('admin/drp.drp_' . $group . '_config') }} @endif</div>

    <div class="content_tips">
        <div class="tabs_info">
            <ul>
                <li @if($group == '') class="curr" @endif ><a href="{{ route('admin/drp/config') }}">{{ $lang['drp_config'] }}</a></li>
                <li @if($group == 'show') class="curr" @endif ><a href="{{ route('admin/drp/config', ['group' => 'show']) }}">{{ lang('admin/drp.drp_show_config') }}</a></li>
                <li><a href="{{ route('admin/drp/drp_scale_config') }}">{{ $lang['drp_scale_config'] }}</a></li>
                <li><a href="{{ route('admin/drp/drp_set_qrcode') }}">{{ $lang['drp_qrcode_config'] }}</a></li>
                <li @if($group == 'message') class="curr" @endif ><a href="{{ route('admin/drp/config', ['group' => 'message']) }}">{{ lang('admin/drp.drp_message_config') }}</a></li>
            </ul>
        </div>

        <div class="explanation" id="explanation">
            <div class="ex_tit">
                <i class="sc_icon"></i><h4>{{ lang('admin/common.operating_hints') }}</h4><span id="explanationZoom" title="{{ lang('admin/common.fold_tips') }}"></span>
            </div>
            <ul>
                @if(!empty($group))

                    @foreach(lang('admin/drp.drp_' . $group . '_config_tips') as $v)
                        <li>{!! $v !!}</li>
                    @endforeach

                @else
                    @foreach(lang('admin/drp.drp_config_tips') as $v)
                        <li>{!! $v !!}</li>
                    @endforeach
                @endif

            </ul>
        </div>

        <div class="flexilist">
            <div class="main-info drp_config">
                <form method="post" action="{{ route('admin/drp/config', ['group' => $group]) }}" class="form-horizontal" role="form">
                    <div class="switch_info">

                        <div class="item_title">
                            <div class="vertical"></div>
                            <div class="f15"> @if($group == '') {{ lang('admin/drp.drp_config') }} @else {{ lang('admin/drp.drp_' . $group . '_config') }} @endif</div>
                        </div>

                        @if(isset($list) && !empty($list))

                        @foreach($list as $config)

                            <div class="item">
                                <div class="label-t">{{ $config['name'] }}</div>

                                @if($config['type'] == 'text')

                                    @if(isset($config['style']) && $config['style'] == 'number')

                                        <div class="label_value ">
                                            <div class="input-group w150">
                                                <input type="number" step="1" min="0" name="data[{{ $config['code'] }}]" class="form-control" value="{{ $config['value'] }}">
                                                @if(isset($config['unit']) && !empty($config['unit']))<span class="input-group-addon">{{ $config['unit'] }}</span>@endif
                                            </div>
                                            <p class="notic">{!! $config['warning'] ?? '' !!}</p>
                                        </div>

                                    @else

                                        <div class="label_value">

                                            <input type="text" name="data[{{ $config['code'] }}]" class="text" value="{{ $config['value'] }}">

                                            <p class="notic">{!! $config['warning'] ?? '' !!}</p>
                                        </div>

                                    @endif

                                @elseif($config['type'] == 'textarea')

                                    <div class="label_value">
                                        <textarea name="data[{{ $config['code'] }}]" class="form-control w500" rows="5">{{ $config['value'] }}</textarea>

                                        <p class="notic">{!! $config['warning'] ?? '' !!}</p>
                                    </div>

                                @elseif($config['type'] == 'radio')

                                    <div class="label_value {{ $config['code'] }}">
                                        <div class="checkbox_items ">
                                            @if(isset($config['range_list']))

                                                @foreach($config['range_list'] as $k => $range)

                                                    <div class="checkbox_item">
                                                        <input type="radio" name="data[{{ $config['code'] }}]" class="ui-radio event_zhuangtai" id="value_{{ $k }}_{{ $config['code'] }}" value="{{ $k }}"
                                                               @if(isset($config['value']) && $k == $config['value'])
                                                               checked
                                                                @endif
                                                        >
                                                        <label for="value_{{ $k }}_{{ $config['code'] }}" class="ui-radio-label

                                                            @if(isset($config['value']) && $k == $config['value'])
                                                                active
                                                            @endif

                                                                ">{{ $range }}</label>

                                                        @if ($config['code'] == 'drp_affiliate_mode' || $config['code'] == 'isdistribution')
                                                        <p class="notic">
                                                            {!! lang('admin/drp.radio_notice_' .  $k . '_' .$config['code']) !!}
                                                        </p>
                                                        @endif
                                                    </div>

                                                @endforeach

                                            @endif

                                        </div>

                                        @if (isset($config['warning']) && !empty($config['warning']))
                                        <p class="notic">{!! $config['warning'] ?? '' !!}</p>
                                        @endif

                                    </div>

                                @endif

                            </div>

                        @endforeach

                        @endif

                        <div class="item">
                            <div class="label-t">&nbsp;</div>
                            <div class="lable_value info_btn">
                                @csrf
                                <input type="submit" value="{{ lang('admin/common.button_submit') }}" class="button btn-danger bg-red" style="margin:0 auto;"/>
                            </div>
                        </div>

                    </div>
                </form>
            </div>
        </div>

    </div>

</div>

<div class="" style="display: none;" id="isdistribution">
    <img src="{{ asset('assets/drp/img/isdistribution.jpg') }}"  />
</div>

<div class="" style="display: none;" id="drp_affiliate_mode">
    <img src="{{ asset('assets/drp/img/drp_affiliate_mode.jpg') }}"  />
</div>

<script type="text/javascript">
    $(function () {
        // 查看示例
        $('.isdistribution .notic').bind('click', '#notice_isdistribution', function () {

            //页面层-图片
            layer.open({
                type: 1,
                title: false,
                closeBtn: 0,
                area: ['auto'],
                skin: 'layui-layer-nobg', //没有背景色
                shadeClose: true,
                content: $('#isdistribution')
            });
        });

        // 查看示例
        $('.drp_affiliate_mode .notic').bind('click', '#notice_drp_affiliate_mode', function () {

            //页面层-图片
            layer.open({
                type: 1,
                title: false,
                closeBtn: 0,
                area: ['auto'],
                skin: 'layui-layer-nobg', //没有背景色
                shadeClose: true,
                content: $('#drp_affiliate_mode')
            });
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
