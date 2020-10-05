@include('seller.base.seller_pageheader')

@include('seller.base.seller_nave_header')

<div class="ecsc-layout">
    <div class="site wrapper">
        @include('seller.base.seller_menu_left')

        <div class="ecsc-layout-right">
            <div class="main-content" id="mainContent">
                @include('seller.base.seller_nave_header_title')
                <div class="wrapper-right of" style="background:#fff">
                    <div class="tabmenu">
                        <ul class="tab">
                            <li role="presentation" class="active"><a href="#home" role="tab"
                                                                      data-toggle="tab">{{ $lang['menu_edit'] }}</a>
                            </li>
                        </ul>
                    </div>
                    <div class="explanation" id="explanation">
                        <div class="ex_tit"><i class="sc_icon"></i><h4>{{ $lang['operating_hints'] }}</h4></div>
                        <ul>
                            @if(isset($lang['menu_tips']) && !empty($lang['menu_tips']))

                                @foreach($lang['menu_tips'] as $v)
                                    <li>{{ $v }}</li>
                                @endforeach

                            @endif
                        </ul>
                    </div>
                    <form action="{{ route('seller/wechat/menu_edit') }}" method="post" class="form-horizontal"
                          role="form" onsubmit="return false;">
                          @csrf
                        <div class="wrapper-list mt20">
                            <div class="account-setting clearfix ecsc-form-goods">
                                <dl>
                                    <dt>{{ $lang['menu_parent'] }}：</dt>
                                    <dd>
                                        <select name="data[pid]" class="input-sm select">
                                            <option value="0">{{ $lang['menu_select'] }}</option>

                                            @foreach($top_menu as $m)

                                                <option value="{{ $m['id'] }}"
                                                        @if($info['pid'] == $m['id'])
                                                        selected
                                                        @endif
                                                >{{ $m['name'] }}</option>

                                            @endforeach

                                        </select>
                                    </dd>
                                </dl>
                                <dl>
                                    <dt>{{ $lang['menu_name'] }}：</dt>
                                    <dd>
                                        <input type="text" name="data[name]" class="text"
                                               value="{{ $info['name'] ?? '' }}"/>
                                    </dd>
                                </dl>
                                <dl>
                                    <dt>{{ $lang['menu_type'] }}：</dt>
                                    <dd>
                                        <div class="checkbox_items">
                                            <div class="checkbox_item">
                                                <input type="radio" name="data[type]"
                                                       class="ui-radio evnet_shop_closed clicktype" id="value_116_0"
                                                       value="click"
                                                       @if(isset($info['type']) && $info['type'] == 'click')
                                                       checked
                                                        @endif
                                                >
                                                <label for="value_116_0" class="ui-radio-label
@if(isset($info['type']) && $info['type'] == 'click')
                                                        active
    @endif
                                                        ">{{ $lang['menu_click'] }}</label>
                                            </div>
                                            <div class="checkbox_item">
                                                <input type="radio" name="data[type]"
                                                       class="ui-radio evnet_shop_closed clicktype" id="value_116_1"
                                                       value="view"
                                                       @if(isset($info['type']) && $info['type'] == 'view')
                                                       checked
                                                        @endif
                                                >
                                                <label for="value_116_1" class="ui-radio-label
@if(isset($info['type']) && $info['type'] == 'view')
                                                        active
    @endif
                                                        ">{{ $lang['menu_view'] }}</label>
                                            </div>
                                            <div class="checkbox_item">
                                                <input type="radio" name="data[type]"
                                                       class="ui-radio evnet_shop_closed clicktype" id="value_116_2"
                                                       value="miniprogram"
                                                       @if(isset($info['type']) && $info['type'] == 'miniprogram')
                                                       checked
                                                        @endif
                                                >
                                                <label for="value_116_2" class="ui-radio-label
@if(isset($info['type']) && $info['type'] == 'miniprogram')
                                                        active
    @endif
                                                        ">{{ $lang['menu_miniprogram'] }}</label>
                                            </div>
                                        </div>
                                    </dd>
                                </dl>
                                <dl id="click" class="
@if(isset($info['type']) && $info['type'] != 'click')
                                        hidden
    @endif
                                        ">
                                    <dt>{{ $lang['menu_keyword'] }}：</dt>
                                    <dd>
                                        <input type="text" name="data[key]" class="text"
                                               value="{{ $info['key'] ?? '' }}"/>
                                    </dd>
                                </dl>
                                <dl id="view" class="
@if(isset($info['type']) && $info['type'] == 'click')
                                        hidden
    @endif
                                        ">
                                    <dt>{{ $lang['menu_url'] }}：</dt>
                                    <dd>
                                        <input type="text" name="data[url]" class="text"
                                               value="{{ $info['url'] ?? '' }}"/>
                                    </dd>
                                </dl>
                                <dl class="miniprogram
@if(isset($info['type']) && $info['type'] == 'miniprogram')
                                        hidden
    @endif
                                        ">
                                    <dt>{{ $lang['menu_url'] }}：</dt>
                                    <dd>
                                        <input type="text" name="data[url]" class="text"
                                               value="{{ $info['url'] ?? '' }}"/>
                                    </dd>
                                </dl>
                                <dl class="miniprogram
@if(isset($info['type']) && $info['type'] != 'miniprogram')
                                        hidden
    @endif
                                        ">
                                    <dt>{{ $lang['menu_mini_appid'] }}：</dt>
                                    <dd>
                                        <input type="text" name="data[appid]" class="text"
                                               value="{{ $info['appid'] ?? '' }}"/>
                                    </dd>
                                </dl>
                                <dl class="miniprogram
@if(isset($info['type']) && $info['type'] != 'miniprogram')
                                        hidden
    @endif
                                        ">
                                    <dt>{{ $lang['menu_mini_page'] }}：</dt>
                                    <dd>
                                        <input type="text" name="data[pagepath]" class="text"
                                               value="{{ $info['pagepath'] ?? '' }}"/>
                                    </dd>
                                </dl>
                                <dl>
                                    <dt>{{ $lang['menu_show'] }}：</dt>
                                    <dd>
                                        <div class="checkbox_items">
                                            <div class="checkbox_item">
                                                <input type="radio" name="data[status]" class="ui-radio evnet_show"
                                                       id="value_117_0" value="1"
                                                       @if(isset($info['status']) && $info['status'] == 1)
                                                       checked
                                                        @endif
                                                >
                                                <label for="value_117_0" class="ui-radio-label
@if(isset($info['status']) && $info['status'] == 1)
                                                        active
    @endif
                                                        ">{{ $lang['yes'] }}</label>
                                            </div>
                                            <div class="checkbox_item">
                                                <input type="radio" name="data[status]" class="ui-radio evnet_show"
                                                       id="value_117_1" value="0"
                                                       @if(isset($info['status']) && $info['status'] == 0)
                                                       checked
                                                        @endif
                                                >
                                                <label for="value_117_1" class="ui-radio-label
@if(isset($info['status']) && $info['status'] == 0)
                                                        active
    @endif
                                                        ">{{ $lang['no'] }}</label>
                                            </div>
                                        </div>
                                    </dd>
                                </dl>
                                <dl>
                                    <dt>{{ $lang['sort_order'] }}：</dt>
                                    <dd>
                                        <div class="checkbox_items">
                                            <input type="text" name="data[sort]" class="text"
                                                   value="{{ $info['sort'] ?? '' }}"/>
                                        </div>
                                    </dd>
                                </dl>
                                <dl>
                                    <dt>&nbsp;</dt>
                                    <dd>
                                        <input type="hidden" name="id" value="{{ $info['id'] ?? '' }}"/>
                                        <input type="submit" value="{{ $lang['button_submit'] }}"
                                               class="sc-btn sc-blueBg-btn btn35"/>
                                        <input type="reset" value="{{ $lang['button_reset'] }}"
                                               class="sc-btn sc-blue-btn btn35"/>
                                    </dd>
                                </dl>
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
        $(".clicktype").click(function () {
            // var val = $(this).find("input[type=radio]").val();
            var val = $(this).val();

            if ('click' == val && $("#click").hasClass("hidden")) {
                $("#view").hide().addClass("hidden");
                $(".miniprogram").hide().addClass("hidden");
                $("#click").show().removeClass("hidden");
            } else if ('view' == val && $("#view").hasClass("hidden")) {
                $("#click").hide().addClass("hidden");
                $(".miniprogram").hide().addClass("hidden");
                $("#view").show().removeClass("hidden");
            } else if ('miniprogram' == val && $(".miniprogram").hasClass("hidden")) {
                $("#click").hide().addClass("hidden");
                $("#view").hide().addClass("hidden");
                $(".miniprogram").show().removeClass("hidden");
            }
        });
        $(".form-horizontal").submit(function () {
            var ajax_data = $(this).serialize();
            $.post("{{ route('seller/wechat/menu_edit') }}", ajax_data, function (data) {
                if (data.status > 0) {
                    layer.msg(data.msg);
                    window.parent.location = "{{ route('seller/wechat/menu_list') }}";
                } else {
                    layer.msg(data.msg);
                    return false;
                }
            }, 'json');
        });
    })
</script>

@include('seller.base.seller_pagefooter')
