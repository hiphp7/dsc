@if(isset($sms_configure) && !empty($sms_configure))

    @foreach($sms_configure as $key => $value)

        <div class="item">
            <div class="label-t">@if(isset($value['label']) && !empty($value['label'])) {{ $value['label'] }}：@else &nbsp; @endif</div>

            @if(isset($value['type']) && $value['type'] == 'text')

                <div class="label_value">
                    <input type="text" name="cfg_value[]" class="text" value="{{ $value['value'] ?? '' }}" autocomplete="off"  />
                    @if(isset($value['desc']) && $value['desc'])<div class="notice">{!! $value['desc'] !!}</div>@endif

                </div>

            @elseif(isset($value['type']) && $value['type'] == 'password')

                <div class="label_value">
                    <input type="password" name="cfg_value[]" class="text" value="{{ $value['value'] ?? '' }}" autocomplete="off"  />

                    @if(isset($value['desc']) && $value['desc'])<div class="notice">{!! $value['desc'] !!}</div>@endif
                </div>

            @elseif(isset($value['type']) && $value['type'] == 'textarea')

                <div class="label_value">
                    <textarea class="textarea"  name="cfg_value[]" >{{ $value['value'] ?? '' }}</textarea>
                    @if(isset($value['desc']) && $value['desc'])<div class="notice">{!! $value['desc'] !!}</div>@endif

                </div>

            @elseif(isset($value['type']) && $value['type'] == 'select')

                <div class="label_value">
                    <select name="cfg_value[]" class="form-control w300 {{ $value['type'] . '-'. $value['name'] }}">

                        <option value="">{{ lang('admin/common.please_select') }}</option>
                        @if(isset($value['range']))
                            @foreach($value['range'] as $k => $options)

                                <option value="{{ $k }}" @if($value['value'] == $k) selected @endif >{{ $options }}</option>

                            @endforeach
                        @endif

                    </select>
                    @if(isset($value['desc']) && $value['desc'])<div class="notice">{!! $value['desc'] !!}</div>@endif

                </div>

            @elseif(isset($value['type']) && $value['type'] == 'radiobox')

                <div class="label_value">
                    <div class="checkbox_items ">
                        @if(isset($value['range']))

                            @foreach($value['range'] as $k => $options)

                                <div class="checkbox_item">
                                    <input type="radio" name="cfg_value[{{$key}}]" class="ui-radio event_zhuangtai" id="value_{{ $value['name'] }}_{{ $k }}" value="{{ $k }}"
                                    @if(isset($value['value']) && $k == $value['value'])
                                        checked
                                    @endif
                                    >
                                    <label for="value_{{ $value['name'] }}_{{ $k }}" class="ui-radio-label

                                        @if(isset($value['value']) && $k == $value['value'])
                                            active
                                        @endif

                                        ">{{ $options }}</label>
                                </div>

                            @endforeach

                        @endif

                        @if(isset($value['desc']) && $value['desc'])<div class="notice">{!! $value['desc'] !!}</div>@endif

                    </div>

                </div>
            @endif

            <input name="cfg_label[]" type="hidden" value="{{ $value['label'] ?? '' }}" />
            <input name="cfg_name[]" type="hidden" value="{{ $value['name'] ?? '' }}" />
            <input name="cfg_type[]" type="hidden" value="{{ $value['type'] ?? '' }}" />
            <input name="cfg_lang[]" type="hidden" value="{{ $value['lang'] ?? '' }}" />
            <input name="cfg_range[]" type="hidden" value="" />
        </div>

    @endforeach

@endif
