@include('admin.wxapp.pageheader')

<div class="wrapper">
    <div class="title">{{ $lang['wx_menu'] }} - {{ $lang['templates'] }}</div>
    <div class="content_tips">
        <div class="explanation" id="explanation">
            <div class="ex_tit"><i class="sc_icon"></i><h4>{{ $lang['operating_hints'] }}</h4><span id="explanationZoom"
                                                                                                    title="{{ $lang['fold_tips'] }}"></span>
            </div>
            <ul>
                @if(isset($lang['template_tips']) && !empty($lang['template_tips']))

                    @foreach($lang['template_tips'] as $v)
                        <li>{!! $v !!}</li>
                    @endforeach

                @endif
            </ul>
        </div>
        <div class="flexilist">

            <div class="common-content">
                <div class="list-div">
                    <table cellspacing="0" cellpadding="0" border="0">
                        <tr>
                            <th class="text-center">{{ $lang['template_title'] }}</th>
                            <th class="text-center">{{ $lang['template_code'] }}</th>
                            <th class="text-center">{{ $lang['add_time'] }}</th>
                            <th class="text-center">{{ $lang['handler'] }}</th>
                        </tr>

                        @foreach($list as $key=>$val)

                            <tr>
                                <td class="text-center">{{ $val['wx_title'] }}</td>
                                <td class="text-center">{{ $val['wx_code'] }}</td>
                                <td class="text-center">{{ $val['add_time'] }}</td>
                                <td class="handle text-center">
                                    <div class="tDiv a3">

                                        @if($val['status'] == 1)

                                            <a href="{{ route('admin/wxapp/switch_template', array('id'=>$val['id'], 'status'=>0)) }}"
                                               class="btn_trash" title="{{ $lang['to_disabled'] }}"><i
                                                        class="fa fa-toggle-on"></i>{{ $lang['already_enabled'] }}</a>

                                        @else

                                            <a href="{{ route('admin/wxapp/switch_template', array('id'=>$val['id'], 'status'=>1)) }}"
                                               class="btn_trash" title="{{ $lang['to_enabled'] }}"><i
                                                        class="fa fa-toggle-off"></i>{{ $lang['already_disabled'] }}</a>

                                        @endif


                                        <a href="{{ route('admin/wxapp/edit_template', array('id' => $val['id'])) }}"
                                           class="btn_edit fancybox fancybox.iframe" title="{{ $lang['edit'] }}"><i
                                                    class="fa fa-edit"></i>{{ $lang['editor'] }}</a>

                                        <a class="btn_trash reset-template" href="javascript:;"
                                           data-href="{{ route('admin/wxapp/reset_template', array('id' => $val['id'])) }}"
                                           title="{{ $lang['button_reset'] }}"><i
                                                    class="fa fa-repeat"></i>{{ $lang['button_reset'] }}</a>
                                    </div>

                                </td>
                            </tr>

                        @endforeach

                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(function () {
        // 重置模板消息
        $(".reset-template").click(function () {
            var url = $(this).attr("data-href");
            //询问框
            layer.confirm('{{ $lang['confirm_reset_template'] }}', {
                btn: ['{{ $lang['ok'] }}', '{{ $lang['cancel'] }}'] //按钮
            }, function () {
                $.get(url, '', function (data) {
                    layer.msg(data.msg);
                    if (data.error == 0) {
                        if (data.url) {
                            window.location.href = data.url;
                        } else {
                            window.location.reload();
                        }
                    }
                    return false;
                }, 'json');
            });
        });
    })
</script>

@include('admin.wxapp.pagefooter')
