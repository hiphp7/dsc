@include('admin.wechat.pageheader')

<div class="wrapper shop_special">
    <div class="title">{{ $lang['wechat_menu'] }} - {{ $lang['edit_wechat'] }}</div>
    <div class="content_tips">
        <div class="explanation" id="explanation">
            <div class="ex_tit"><i class="sc_icon"></i><h4>{{ $lang['operating_hints'] }}</h4><span id="explanationZoom"
                                                                                                    title="{{ $lang['fold_tips'] }}"></span>
            </div>
            <ul>
                @if(isset($lang['edit_wechat_tips']) && !empty($lang['edit_wechat_tips']))

                    @foreach($lang['edit_wechat_tips'] as $v)
                        <li>{!! $v !!}</li>
                    @endforeach

                @endif
            </ul>
        </div>
        <div class="flexilist">
            <div class="main-info">

                <ul class="list-group">
                    @foreach($system_res as $v)
                        @if ($v['support'] == 'off')
                            <li class="list-group-item list-group-item-danger">{{ $v['name'] }} {{ $lang['support_off'] }}</li>
                        @endif
                    @endforeach
                </ul>

                {{--<div class="panel-heading">{{ $lang['edit_wechat'] }}</div>--}}
                <form method="post" action="{{ route('admin/wechat/modify') }}" class="form-horizontal" role="form">
                    <div class="switch_info">
                        <div class="item">
                            <div class="label-t">{{ $lang['wechat_name'] }}</div>
                            <div class="label_value">
                                <input type="text" name="data[name]" class="text" value="{{ $data['name'] ?? '' }}"/>
                                <div class="notic">* {{ $lang['wechat_help1'] }}</div>
                            </div>
                        </div>
                        <div class="item">
                            <div class="label-t">{{ $lang['wechat_id'] }}</div>
                            <div class="label_value">
                                <input type="text" name="data[orgid]" class="text" value="{{ $data['orgid'] ?? '' }}">
                                <div class="notic">* {{ $lang['wechat_help2'] }}</div>
                            </div>
                        </div>
                        <div class="item">
                            <div class="label-t">{{ $lang['appid'] }}</div>
                            <div class="label_value">
                                <input type="text" name="data[appid]" class="text" value="{{ $data['appid'] ?? '' }}">
                                <div class="notic">* {{ $lang['wechat_help4'] }}</div>
                            </div>
                        </div>
                        <div class="item">
                            <div class="label-t">{{ $lang['appsecret'] }}</div>
                            <div class="label_value">
                                <input type="text" name="data[appsecret]" class="text"
                                       value="{{ $data['appsecret'] ?? '' }}">
                                <div class="notic">* {{ $lang['wechat_help5'] }}</div>
                            </div>
                        </div>
                        <div class="item">
                            <div class="label-t">{{ $lang['token'] }}</div>
                            <div class="label_value">
                                <input type="text" name="data[token]" class="text" value="{{ $data['token']  ?? '' }}">
                                <span class="btn btn-success makeToken">{{ $lang['make_token'] }}</span>
                                <span class="btn btn-info btn-xs copyToken">{{ $lang['copy_token'] }}</span>
                                <div class="notic">* {{ $lang['wechat_help3'] }}</div>
                            </div>
                        </div>
                        <div class="item">
                            <div class="label-t">{{ $lang['aeskey'] }}</div>
                            <div class="label_value">
                                <input type="text" name="data[encodingaeskey]" class="text"
                                       value="{{ $data['encodingaeskey'] ?? '' }}">
                                <div class="notic">({{ $lang['selection'] }}) {{ $lang['wechat_help7'] }}</div>
                            </div>
                        </div>
                        <div class="item">
                            <div class="label-t">{{ $lang['wechat_status'] }}</div>
                            <div class="label_value">
                                <div class="checkbox_items">
                                    <div class="checkbox_item">
                                        <input type="radio" name="data[status]" class="ui-radio event_zhuangtai"
                                               id="value_118_0" value="1"
                                               @if(isset($data['status']) && $data['status'] == 1)
                                               checked
                                                @endif
                                        >
                                        <label for="value_118_0" class="ui-radio-label
                                            @if(isset($data['status']) && $data['status'] == 1)
                                                active
                                                @endif
                                                ">{{ $lang['wechat_open'] }}</label>
                                    </div>
                                    <div class="checkbox_item">
                                        <input type="radio" name="data[status]" class="ui-radio event_zhuangtai"
                                               id="value_118_1" value="0"

                                               @if(isset($data['status']) && $data['status'] == 0)

                                               checked

                                                @endif
                                        >
                                        <label for="value_118_1" class="ui-radio-label
                                            @if(isset($data['status']) && $data['status'] == 0)
                                                active
                                                @endif
                                                ">{{ $lang['wechat_close'] }}</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if(isset($data['orgid']) && $data['orgid'])

                            <div class="item">
                                <div class="label-t">{{ $lang['wechat_api_url'] }}</div>
                                <div class="label_value">
                                    <span class="text weixin_url">{{ $data['url'] ?? '' }}</span>
                                    <span class="btn btn-info btn-xs copy">{{ $lang['copy_url'] }}</span>
                                </div>
                            </div>

                        @endif

                        <div class="item">
                            <div class="label-t">&nbsp;</div>
                            <div class="label_value info_btn">
                                @csrf
                                <input type="hidden" name="id" value="{{ $data['id'] ?? '' }}"/>
                                <input type="hidden" name="data[type]" value="2">
                                <input type="submit" name="submit" value="{{ $lang['button_save'] }}"
                                       class="button btn-danger bg-red"/>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>
<script>
    // H5 复制粘贴 - execCommand http://www.jianshu.com/p/37322bb86a48  兼容IE8+，Chrome 45+, Firefox 43+
    var copyUrl = document.querySelector('.copy');
    if (copyUrl) {
        // 点击的时候调用 copyTextToClipboard() 方法就好了.
        copyUrl.onclick = function () {
            copyTextToClipboard("{{ $data['url'] }}");
        }
    }

    var copyToken = document.querySelector('.copyToken');
    if (copyToken) {
        copyToken.onclick = function () {
            var newtoken = $("input[name='data[token]']").val();
            copyTextToClipboard(newtoken);
        }
    }

    function copyTextToClipboard(text) {
        var textArea = document.createElement("textarea");
        textArea.style.position = 'fixed';
        textArea.style.top = 0;
        textArea.style.left = 0;
        textArea.style.width = '2em';
        textArea.style.height = '2em';
        textArea.style.padding = 0;
        textArea.style.border = 'none';
        textArea.style.outline = 'none';
        textArea.style.boxShadow = 'none';
        textArea.style.background = 'transparent';
        textArea.value = text;
        document.body.appendChild(textArea);

        textArea.select();
        try {
            var msg = document.execCommand('copy') ? '成功' : '失败';
            console.log('复制内容 ' + msg);
            layer.msg('复制内容 ' + msg);
        } catch (err) {
            console.log('浏览器不支持此复制方法');
            layer.msg('浏览器不支持此复制方法');
        }
        document.body.removeChild(textArea);
    }


    // 生成token md5
    $(".makeToken").click(function () {
        var mydate = new Date();
        var mytime = mydate.toLocaleTimeString(); //获取当前时间
        var token = $.md5(mytime);
        $("input[name='data[token]']").val(token);
    });

</script>

@include('admin.wechat.pagefooter')
