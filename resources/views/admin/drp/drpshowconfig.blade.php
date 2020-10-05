@include('admin.drp.pageheader')

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

                                    <div class="label_value ">

                                        <input type="text" name="data[{{ $config['code'] }}]" class="text" value="{{ $config['value'] }}">

                                        <p class="notic">{!! $config['warning'] ?? '' !!}</p>

                                    </div>

                                @elseif($config['type'] == 'textarea')

                                <div class="label_value ">

                                    <textarea name="data[{{ $config['code'] }}]" class="form-control w500" rows="5">{{ $config['value'] }}</textarea>

                                    <p class="notic">{!! $config['warning'] ?? '' !!}</p>

                                </div>

                                @elseif($config['type'] == 'radio')

                                 <div class="label_value {{ $config['code'] }}">
                                     <div class="checkbox_items">
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
                                                 </div>

                                             @endforeach

                                         @endif

                                         <p class="notic">{!! $config['warning'] ?? '' !!}</p>
                                     </div>
                                 </div>

                                @endif

                            </div>

                        @endforeach

                        @endif

                        @if(isset($content_list) && !empty($content_list))

                            <div class="item_title">
                                <div class="vertical"></div>
                                <div class="f15"> {{ lang('admin/drp.drp_show_content_config') }}</div>
                            </div>

                            @foreach($content_list as $config)

                                <div class="item">
                                    <div class="label-t">{{ $config['name'] }}</div>

                                    @if($config['type'] == 'text')

                                        @if(isset($config['style']) && $config['style'] == 'number')

                                            <div class="label_value ">
                                                <div class="input-group w150">
                                                    <input type="number" step="0.01" name="data[{{ $config['code'] }}]" class="form-control" value="{{ $config['value'] }}">
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

                                        <div class="label_value">
                                            <div class="checkbox_items">

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
                                                    </div>

                                                @endforeach

                                            @endif
                                            <p class="notic">{!! $config['warning'] !!}</p>

                                            </div>
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

<script type="text/javascript">
    $(function () {

    });
</script>

@include('admin.drp.pagefooter')
