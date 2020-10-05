@include('admin.drpcard.pageheader')

<div class="wrapper">
    <div class="title"><a href="{{ route('admin/user_rank/edit', ['id' => $info['user_rank_id'] ?? 0]) }}" class="s-back">{{ lang('common.back') }}</a> {{ lang('admin/user_rank.user_rank') }} - {{ lang('admin/drpcard.edit_rights') }}</div>

    <div class="content_tips">

        <div class="explanation" id="explanation">
            <div class="ex_tit">
                <i class="sc_icon"></i><h4>{{ lang('admin/common.operating_hints') }}</h4><span id="explanationZoom" title="{{ lang('admin/common.fold_tips') }}"></span>
            </div>
            <ul>
                @foreach(lang('admin/drpcard.edit_rights_tips') as $v)
                    <li>{!! $v !!}</li>
                @endforeach
            </ul>
        </div>

        <div class="flexilist">

            <div class="main-info">
                <form action="{{ route('admin/user_rank/edit_rights') }}" method="post" role="form" class="form-horizontal" >
                    <div class="switch_info">

                        {{--基本信息--}}
                        <div class="item_title">
                            <div class="vertical"></div>
                            <div class="f15">{{ lang('admin/users.base_title') }}</div>
                        </div>

                        <div class="item">
                            <div class="label-t">{{ lang('admin/users.rights_name') }}：</div>
                            <div class="label_value">
                                <p class="">{{ $info['user_membership_rights']['name'] ?? '' }}</p>
                            </div>
                        </div>

                        <div class="item">
                            <div class="label-t">{{ lang('admin/users.rights_icon') }}：</div>
                            <div class="label_value">
                                <p class="">
                                    <img width="50" height="50" src="{{ $info['user_membership_rights']['icon'] ?? '' }}" class="img-rounded" >
                                </p>
                            </div>
                        </div>

                        <div class="item">
                            <div class="label-t">{{ lang('admin/users.rights_desc') }}：</div>
                            <div class="label_value">
                                <textarea class="textarea readonly" readonly rows="3" style="border:none;">{{ $info['user_membership_rights']['description'] ?? '' }}</textarea>
                            </div>
                        </div>

                        {{--权益设置--}}
                        <div class="item_title @if(empty($info['user_membership_rights']['rights_configure']) && empty($info['user_membership_rights']['trigger_point'])) hide @endif ">
                            <div class="vertical"></div>
                            <div class="f15">{{ lang('admin/users.rights_config') }}</div>
                        </div>

                        {{--发放方式--}}
                        <div class="item @if(empty($info['user_membership_rights']['trigger_point'])) hide @endif ">
                            <div class="label-t">{{ lang('admin/users.trigger_point') }}：</div>
                            <div class="label_value">
                                <p class="">{{ $info['user_membership_rights']['trigger_point_format'] ?? '' }}</p>
                            </div>
                        </div>

                        <div class="item @if(isset($info['user_membership_rights']['trigger_point']) &&  $info['user_membership_rights']['trigger_point'] != 'scheduled') hide @endif ">
                            <div class="label-t">{{ lang('admin/users.trigger_configure') }}：</div>
                            <div class="label_value">
                                <p class="">{{ $info['user_membership_rights']['trigger_configure'] ?? '' }}</p>
                            </div>
                        </div>

                        @if(isset($info['user_membership_rights']['rights_configure']) && !empty($info['user_membership_rights']['rights_configure']))

                            @foreach($info['user_membership_rights']['rights_configure'] as $key => $value)

                                <div class="item">
                                    <div class="label-t">@if(isset($value['label']) && !empty($value['label'])) {{ $value['label'] }} ：@else &nbsp; @endif</div>

                                    @if(isset($value['type']) && $value['type'] == 'text')

                                        <div class="label_value">
                                            <input type="text" name="cfg_value[]" class="text" value="{{ $value['value'] ?? '' }}" autocomplete="off"  />
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

                                    @endif

                                    <input name="cfg_name[]" type="hidden" value="{{ $value['name'] ?? '' }}" />
                                    <input name="cfg_type[]" type="hidden" value="{{ $value['type'] ?? '' }}" />
                                    <input name="cfg_lang[]" type="hidden" value="{{ $value['lang'] ?? '' }}" />
                                    <input name="cfg_range[]" type="hidden" value="" />

                                </div>

                            @endforeach

                        @endif

                        <div class="item">
                            <div class="label-t">&nbsp;</div>
                            <div class="label_value info_btn">
                                @csrf
                                <input type="hidden" name="id" value="{{ $info['id'] ?? 0 }}" />
                                <input type="hidden" name="code" value="{{ $info['user_membership_rights']['code'] ?? '' }}" />
                                <input type="hidden" name="rank_id" value="{{ $info['user_rank_id'] ?? 0 }}" />
                                <input type="submit" class="button btn-danger bg-red" value="{{ lang('admin/common.button_submit') }}" />

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


    // 验证提交
    $(".form-horizontal").submit(function(){

        var code = $('input[name="code"]').val();

        // 会员特价 须验证折扣
        if (code == 'discount') {

            var cfg_value;
            // 总和
            var cfg_value_sum = 0;
            var checkbox = $('input[name="cfg_value[]"]');
            for (var i = 0; i < checkbox.length; i++) {

                cfg_value = $('input[name="cfg_value[]"]:eq('+i+')').val();

                cfg_value_sum += parseInt(cfg_value);
            }

            if (cfg_value_sum > 100) {
                layer.msg('{{ lang('admin/users.cfg_value_discount_sum_rules') }}');
                return false;
            }

        }

    });

});
</script>

@include('admin.drpcard.pagefooter')
