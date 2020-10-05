@include('seller.base.seller_pageheader')

@include('seller.base.seller_nave_header')

<div class="ecsc-layout">
    <div class="site wrapper">
        @include('seller.base.seller_menu_left')

        <div class="ecsc-layout-right">
            <div class="main-content" id="mainContent">
                @include('seller.base.seller_nave_header_title')
                <div class="wrapper-right of">
                    <div class="tabmenu">
                        <ul class="tab">
                            <li role="presentation" class="active"><a href="#home" role="tab"
                                                                      data-toggle="tab">{{ $lang['qrcode_edit'] }}</a>
                            </li>
                        </ul>
                    </div>
                    <div class="explanation" id="explanation">
                        <div class="ex_tit"><i class="sc_icon"></i><h4>{{ $lang['operating_hints'] }}</h4></div>
                        <ul>
                            @if(isset($lang['qrcode_edit_tips']) && !empty($lang['qrcode_edit_tips']))

                                @foreach($lang['qrcode_edit_tips'] as $v)
                                    <li>{{ $v }}</li>
                                @endforeach

                            @endif
                        </ul>
                    </div>

                    <div class="wrapper-list mt20">
                        <form action="{{ route('seller/wechat/qrcode_edit') }}" method="post" class="form-horizontal"
                              role="form" onsubmit="return false;">
                            @csrf
                            <div class="account-setting ecsc-form-goods">
                                <dl>
                                    <dt>{{ $lang['qrcode_type'] }}：</dt>
                                    <dd>
                                        <select class="input-sm select" name="data[type]" style="border-radius:2px">
                                            <option value="0">{{ $lang['qrcode_short'] }}</option>
                                            <option value="1">{{ $lang['qrcode_forever'] }}</option>
                                        </select>
                                    </dd>
                                </dl>
                                <dl>
                                    <dt>{{ $lang['qrcode_valid_time'] }}：</dt>
                                    <dd>
                                        <input type="text" name="data[expire_seconds]" class="text"
                                               placeholder=""/>
                                        <div class="form_prompt"></div>
                                        <div class="notic">{{ $lang['qrcode_help1'] }}</div>
                                    </dd>
                                </dl>
                                <dl>
                                    <dt>{{ $lang['qrcode_function'] }}：</dt>
                                    <dd>
                                        <select name="data[function]" class="text">
                                            <option value="0">{{ $lang['qrcode_function_desc'] }}</option>

                                            @foreach($keywords_list as $v)

                                                <option value="{{ $v }}"
                                                        @if(isset($info['function']) && $info['function'] == $v)
                                                        selected
                                                        @endif
                                                >{{ $v }}</option>

                                            @endforeach

                                        </select>
                                    </dd>
                                </dl>
                                <dl>
                                    <dt>{{ $lang['qrcode_scene_value'] }}：</dt>
                                    <dd>
                                        <input type="text" name="data[scene_id]" class="text"
                                               placeholder=""/>
                                        <div class="form_prompt"></div>
                                        <div class="notic">{{ $lang['qrcode_help2'] }}</div>
                                    </dd>
                                </dl>
                                <dl>
                                    <dt>{{ $lang['wechat_status'] }}：</dt>
                                    <dd>
                                        <div class="checkbox_items" style="line-height:30px">
                                            <div class="checkbox_item">
                                                <input type="radio" name="data[status]" class="ui-radio event_zhuangtai"
                                                       id="value_118_0" value="1" checked="">
                                                <label for="value_118_0"
                                                       class="ui-radio-label active">{{ $lang['enabled'] }}</label>
                                            </div>
                                            <div class="checkbox_item">
                                                <input type="radio" name="data[status]" class="ui-radio event_zhuangtai"
                                                       id="value_118_1" value="0">
                                                <label for="value_118_1"
                                                       class="ui-radio-label ">{{ $lang['disabled'] }}</label>
                                            </div>
                                        </div>
                                    </dd>
                                </dl>
                                <dl>
                                    <dt>{{ $lang['sort_order'] }}：</dt>
                                    <dd>
                                        <input type="text" name="data[sort]" class="text"/>
                                    </dd>
                                </dl>
                                <dl>
                                    <dt>&nbsp;</dt>
                                    <dd class="button_info">
                                        <input type="submit" value="{{ $lang['button_submit'] }}"
                                               class="sc-btn sc-blueBg-btn btn35"/>
                                        <input type="reset" value="{{ $lang['button_reset'] }}"
                                               class="sc-btn sc-blue-btn btn35"/>
                                    </dd>
                                </dl>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
<script type="text/javascript">
    $(function () {
        $(".form-horizontal").submit(function () {
            var ajax_data = $(this).serialize();
            $.post("{{ route('seller/wechat/qrcode_edit') }}", ajax_data, function (data) {
                if (data.status > 0) {
                    layer.msg(data.msg);
                    window.location = "{{ route('seller/wechat/qrcode_list') }}";
                }
                else {
                    layer.msg(data.msg);
                    return false;
                }
            }, 'json');
        });
    })
</script>

@include('seller.base.seller_pagefooter')

