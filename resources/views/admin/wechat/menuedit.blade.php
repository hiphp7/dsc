@include('admin.wechat.pageheader')
<div class="fancy">
    <div class="title">{{ $lang['menu_edit'] }}</div>
    <div class="flexilist of">
        <div class="common-content">
            <div class="main-info">
                <form action="{{ route('admin/wechat/menu_edit') }}" method="post" class="form-horizontal" role="form"
                      onsubmit="return false;">
                    @csrf
                    <div class="switch_info">
                        <div class="item">
                            <div class="label-t">{{ $lang['menu_parent'] }}:</div>
                            <div class="label_value col-md-4">
                                <div style="width:299px;">
                                    <select name="data[pid]" class="input-sm">
                                        <option value="0">{{ $lang['menu_select'] }}</option>

                                        @foreach($top_menu as $m)

                                            <option value="{{ $m['id'] }}"
                                                    @if($info['pid'] == $m['id'])
                                                    selected
                                                    @endif
                                            >{{ $m['name'] }}</option>

                                        @endforeach

                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="item">
                            <div class="label-t">{{ $lang['menu_name'] }}:</div>
                            <div class="label_value col-md-4">
                                <input type="text" name="data[name]" class="text" value="{{ $info['name'] ?? '' }}"/>
                            </div>
                        </div>
                        <div class="item">
                            <div class="label-t">{{ $lang['menu_type'] }}:</div>
                            <div class="label_value col-md-10">
                                <div class="checkbox_items">
                                    <div class="checkbox_item">
                                        <input type="radio" name="data[type]"
                                               class="ui-radio evnet_shop_closed clicktype" id="value_116_0"
                                               value="click"
                                               @if($info['type'] == 'click')
                                               checked
                                                @endif
                                        >
                                        <label for="value_116_0" class="ui-radio-label
@if($info['type'] == 'click')
                                                active
    @endif
                                                ">{{ $lang['menu_click'] }}</label>
                                    </div>
                                    <div class="checkbox_item">
                                        <input type="radio" name="data[type]"
                                               class="ui-radio evnet_shop_closed clicktype" id="value_116_1"
                                               value="view"
                                               @if($info['type'] == 'view')
                                               checked
                                                @endif
                                        >
                                        <label for="value_116_1" class="ui-radio-label
@if($info['type'] == 'view')
                                                active
    @endif
                                                ">{{ $lang['menu_view'] }}</label>
                                    </div>
                                    <div class="checkbox_item">
                                        <input type="radio" name="data[type]"
                                               class="ui-radio evnet_shop_closed clicktype" id="value_116_2"
                                               value="miniprogram"
                                               @if($info['type'] == 'miniprogram')
                                               checked
                                                @endif
                                        >
                                        <label for="value_116_2" class="ui-radio-label
@if($info['type'] == 'miniprogram')
                                                active
    @endif
                                                ">{{ $lang['menu_miniprogram'] }}</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="click" class="item
@if($info['type'] != 'click')
                                hidden
    @endif
                                ">
                            <div class="label-t">{{ $lang['menu_keyword'] }}:</div>
                            <div class="label_value col-md-4">
                                <input type="text" name="data[key]" class="text" value="{{ $info['key'] ?? '' }}"/>
                            </div>
                        </div>
                        <div id="view" class="item
@if($info['type'] == 'click')
                                hidden
    @endif
                                ">
                            <div class="label-t">{{ $lang['menu_url'] }}:</div>
                            <div class="label_value col-md-10">
                                <input type="text" name="data[url]" class="text" value="{{ $info['url'] ?? '' }}"/>
                            </div>
                        </div>
                        <div id="miniprogram" class="
@if($info['type'] != 'miniprogram')
                                hidden
    @endif
                                ">
                            <div class="item">
                                <div class="label-t">{{ $lang['menu_mini_appid'] }}:</div>
                                <div class="label_value col-md-10">
                                    <input type="text" name="data[appid]" class="text"
                                           value="{{ $info['appid'] ?? '' }}"/>
                                </div>
                            </div>
                            <div class="item">
                                <div class="label-t">{{ $lang['menu_mini_page'] }}:</div>
                                <div class="label_value col-md-10">
                                    <input type="text" name="data[pagepath]" class="text"
                                           value="{{ $info['pagepath'] ?? '' }}"/>
                                </div>
                            </div>
                        </div>
                        <div class="item">
                            <div class="label-t">{{ $lang['menu_show'] }}:</div>
                            <div class="label_value col-md-10">
                                <div class="checkbox_items">
                                    <div class="checkbox_item">
                                        <input type="radio" name="data[status]" class="ui-radio evnet_show"
                                               id="value_117_0" value="1"
                                               @if($info['status'] == 1)
                                               checked
                                                @endif
                                        >
                                        <label for="value_117_0" class="ui-radio-label
@if($info['status'] == 1)
                                                active
    @endif
                                                ">{{ $lang['yes'] }}</label>
                                    </div>
                                    <div class="checkbox_item">
                                        <input type="radio" name="data[status]" class="ui-radio evnet_show"
                                               id="value_117_1" value="0"
                                               @if($info['status'] == 0)
                                               checked
                                                @endif
                                        >
                                        <label for="value_117_1" class="ui-radio-label
@if($info['status'] == 0)
                                                active
    @endif
                                                ">{{ $lang['no'] }}</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="item">
                            <div class="label-t">{{ $lang['sort_order'] }}:</div>
                            <div class="label_value col-md-2">
                                <input type="text" name="data[sort]" class="text" value="{{ $info['sort'] ?? '' }}"/>
                            </div>
                        </div>
                        <div class="item">
                            <div class="label-t">&nbsp;</div>
                            <div class="label_value col-md-4">
                                <div class="info_btn">
                                    <input type="hidden" name="id" value="{{ $info['id'] ?? '' }}"/>
                                    <input type="submit" value="{{ $lang['button_submit'] }}"
                                           class="button btn-danger bg-red fn"/>
                                    <input type="reset" value="{{ $lang['button_reset'] }}"
                                           class="button button_reset fn"/>
                                </div>
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
        $(".clicktype").click(function () {
            // var val = $(this).find("input[type=radio]").val();
            var val = $(this).val();

            if ('click' == val) {
                $("#click").show().removeClass("hidden");
                $("#view").hide().addClass("hidden");
                $("#miniprogram").hide().addClass("hidden");
            } else if ('view' == val) {
                $("#click").hide().addClass("hidden");
                $("#view").show().removeClass("hidden");
                $("#miniprogram").hide().addClass("hidden");
            } else if ('miniprogram' == val) {
                $("#click").hide().addClass("hidden");
                $("#view").show().removeClass("hidden");
                $("#miniprogram").show().removeClass("hidden");
            }
        });
        $(".form-horizontal").submit(function () {
            var ajax_data = $(this).serialize();
            $.post("{{ route('admin/wechat/menu_edit') }}", ajax_data, function (data) {
                layer.msg(data.msg);
                if (data.status > 0) {
                    window.parent.location = "{{ route('admin/wechat/menu_list') }}";
                } else {
                    return false;
                }
            }, 'json');
        });
    })
</script>
@include('admin.wechat.pagefooter')
