@include('admin.wechat.pageheader')
<div class="panel panel-default" style="margin:0;">
    <div class="panel-heading">{{ $lang['add'].$lang['share_qrcode'] }}</div>
    <div class="content_tips of">
        <div class="flexilist">
            <div class="main-info of">
                <form action="{{ route('admin/wechat/share_edit') }}" method="post" class="form-horizontal" role="form"
                      onsubmit="return false;">
                    <div class="item">
                        <div class="label-t">{{ $lang['share_name'] }}:</div>
                        <div class="label_value">
                            <input type="text" name="data[username]" class="text form-control" placeholder="即网站会员名称"
                                   value="{{ $info['username'] ?? '' }}" autocomplete="off"
                                   @if(isset($info['id']) && $info['id'])
                                   readonly
                                    @endif
                            />
                        </div>
                    </div>
                    <div class="item">
                        <div class="label-t">{{ $lang['share_userid'] }}:</div>
                        <div class="label_value">
                            <input type="number" min="1" max="100000" name="data[scene_id]"
                                   placeholder="{{ $lang['share_userid_desc'] }}" value="{{ $info['scene_id'] ?? '' }}"
                                   class="text form-control"
                                   @if(isset($info['id']) && $info['id'])
                                   readonly
                                    @endif
                            />
                        </div>
                    </div>
                    <div class="item">
                        <div class="label-t">{{ $lang['qrcode_valid_time'] }}:</div>
                        <div class="label_value">
                            <input type="text" name="data[expire_seconds]" class="text form-control" placeholder="单位秒"
                                   value="{{ $info['expire_seconds'] ?? '' }}"
                                   @if(isset($info['id']) && $info['id'])
                                   readonly
                                    @endif
                            />
                            <div class="notic">{{ $lang['share_help'] }}</div>
                        </div>
                    </div>
                    <div class="item">
                        <div class='label-t'>{{ $lang['qrcode_function'] }}:</div>
                        <div class="label_value">
                            <select name="data[function]" class="text form-control">
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
                                                  target="_parent">{{ $lang['goto_reply_keywords'] }}</a>
                            </div>
                        </div>
                    </div>
                    <div class="item">
                        <div class="label-t">{{ $lang['sort_order'] }}:</div>
                        <div class="label_value">
                            <input type="text" name="data[sort]" class="text form-control"/>
                        </div>
                    </div>
                    <div class="item">
                        <div class="label-t">&nbsp;</div>
                        <div class="label_value info_btn">
                            @csrf
                            <input type="hidden" name="id" value="{{ $info['id'] ?? '' }}"/>
                            <input type="submit" value="{{ $lang['button_submit'] }}" class="button btn-danger bg-red"/>
                            <input type="reset" value="{{ $lang['button_reset'] }}" class="button button_reset"/>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
    $(function () {
        // 用户名查询ID
        $("input[name='data[username]']").blur(function () {
            if (this.value != '') {
                $.post("{{ route('admin/wechat/get_user_id') }}", {username: this.value}, function (data) {
                    if (data.error > 0) {
                        layer.msg(data.msg);
                        return false;
                    } else {
                        var user_id = data.user_id;
                        $("input[name='data[scene_id]']").val(user_id);
                    }
                }, 'json');
            }
        });

        $(".form-horizontal").submit(function () {
            var ajax_data = $(this).serialize();
            $.post("{{ route('admin/wechat/share_edit') }}", ajax_data, function (data) {
                layer.msg(data.msg);
                if (data.status > 0) {
                    window.parent.location.reload();
                } else {
                    return false;
                }
            }, 'json');
        });
    })
</script>
@include('admin.wechat.pagefooter')
