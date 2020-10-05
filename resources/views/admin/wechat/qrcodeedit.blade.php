@include('admin.wechat.pageheader')
<div class="panel panel-default" style="margin:0;">
    <div class="panel-heading">{{ $lang['qrcode_edit'] }}</div>
    <div class="content_tips of">
        <div class="flexilist">
            <div class="main-info of">
                <form action="{{ route('admin/wechat/qrcode_edit') }}" method="post" class="form-horizontal" role="form"
                      onsubmit="return false;">
                    <div class="switch_info of">
                        <div class="item">
                            <div class="label-t">{{ $lang['qrcode_type'] }}:</div>
                            <div class="label_value">
                                <div class="select_w320">
                                    <select class="input-sm select_type" name="data[type]">
                                        <option value="0">{{ $lang['qrcode_short'] }}</option>
                                        <option value="1">{{ $lang['qrcode_forever'] }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="item">
                            <div class="label-t">{{ $lang['qrcode_valid_time'] }}:</div>
                            <div class="label_value">
                                <div class="input-group input-group-lg">
                                    <input type="number" min="0" name="data[expire_seconds]" class="text"
                                           id="valid_time" placeholder="{{ $lang['qrcode_valid_time_placeholder'] }}"/>
                                    <select name="unit" id="valid_time_select" class="">
                                        <option value="0">{{ $lang['minute'] }}</option>
                                        <option value="1">{{ $lang['hour'] }}</option>
                                        <option value="2">{{ $lang['day'] }}</option>
                                    </select>
                                </div>
                                <div class="notic">{{ $lang['qrcode_help1'] }}</div>
                            </div>
                        </div>
                        <div class="item">
                            <div class="label-t">{{ $lang['qrcode_function'] }}:</div>
                            <div class="label_value">
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
                                <div class="notic"><a href="{{ route('admin/wechat/reply_keywords') }}"
                                                      target="_parent">{{ $lang['goto_reply_keywords'] }}</a></div>
                            </div>

                        </div>
                        <div class="item">
                            <div class="label-t">{{ $lang['qrcode_scene_value'] }}:</div>
                            <div class="label_value">
                                <input type="number" min="100001" max="4294967295" id="scene_id" name="data[scene_id]"
                                       class="text" placeholder=""/>
                                <div class="notic">{{ $lang['qrcode_help2'] }}</div>
                            </div>
                        </div>
                        <div class="item">
                            <div class="label-t">{{ $lang['wechat_status'] }}:</div>
                            <div class="label_value">
                                <div class="checkbox_items">
                                    <div class="checkbox_item">
                                        <input type="radio" name="data[status]" class="ui-radio event_zhuangtai"
                                               id="value_118_0" value="1" checked="true">
                                        <label for="value_118_0"
                                               class="ui-radio-label active">{{ $lang['enabled'] }}</label>
                                    </div>
                                    <div class="checkbox_item">
                                        <input type="radio" name="data[status]" class="ui-radio event_zhuangtai"
                                               id="value_118_1" value="0">
                                        <label for="value_118_1"
                                               class="ui-radio-label active">{{ $lang['disabled'] }}</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="item">
                            <div class="label-t">{{ $lang['sort_order'] }}:</div>
                            <div class="label_value">
                                <input type="text" name="data[sort]" class="text"/>
                            </div>
                        </div>
                        <div class="item">
                            <div class="label-t">&nbsp;</div>
                            <div class="label_value info_btn">
                                @csrf
                                <input type="submit" value="{{ $lang['button_submit'] }}"
                                       class="button btn-danger bg-red"/>
                                <input type="reset" value="{{ $lang['button_reset'] }}" class="button button_reset"/>
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
        $(".form-horizontal").submit(function () {
            var ajax_data = $(this).serialize();
            $.post("{{ route('admin/wechat/qrcode_edit') }}", ajax_data, function (data) {
                layer.msg(data.msg);
                if (data.status > 0) {
                    window.parent.location.reload();
                } else {
                    return false;
                }
            }, 'json');
        });

        // 切换二维码类型
        $('.select_type').change(function () {
            var op = $(this).children('option:selected').val(); //是selected的值

            if (op == 0) {
                $('input[name="data[scene_id]"]').attr("min", "100001");
                $('input[name="data[scene_id]"]').attr("max", "4294967295");
            }
            if (op == 1) {
                $('input[name="data[scene_id]"]').attr("min", "1");
                $('input[name="data[scene_id]"]').attr("max", "100000");
            }
        });

    })
</script>
@include('admin.wechat.pagefooter')