@include('admin.wxapp.pageheader')

<div class="wrapper shop_special">
    <div class="title">{{ $lang['wx_menu'] }} - {{ $lang['wx_config'] }}</div>
    <div class="content_tips">
        <div class="explanation" id="explanation">
            <div class="ex_tit"><i class="sc_icon"></i><h4>{{ $lang['operating_hints'] }}</h4><span id="explanationZoom"
                                                                                                    title="{{ $lang['fold_tips'] }}"></span>
            </div>
            <ul>
                @if(isset($lang['wx_config_tips']) && !empty($lang['wx_config_tips']))

                    @foreach($lang['wx_config_tips'] as $v)
                        <li>{!! $v !!}</li>
                    @endforeach

                @endif
            </ul>
        </div>
        <div class="flexilist">
            <div class="main-info">
            <!--<div class="panel-heading">{{ $lang['edit_wechat'] }}</div>-->
                <form method="post" action="{{ route('admin/wxapp/index') }}" class="form-horizontal" role="form">
                    <div class="switch_info">
                        <div class="item">
                            <div class="label-t">{{ $lang['wx_appname'] }}</div>
                            <div class="label_value">
                                <input type="text" name="data[wx_appname]" class="text"
                                       value="{{ $data['wx_appname'] ?? '' }}"/>
                                <div class="notic"> {{ $lang['wx_appname'] }}</div>
                            </div>
                        </div>
                        <div class="item">
                            <div class="label-t">{{ $lang['wx_appid'] }}</div>
                            <div class="label_value">
                                <input type="text" name="data[wx_appid]" class="text"
                                       value="{{ $data['wx_appid'] ?? '' }}">
                                <div class="notic">* {{ $lang['wxapp_help1'] }}</div>
                            </div>
                        </div>
                        <div class="item">
                            <div class="label-t">{{ $lang['wx_appsecret'] }}</div>
                            <div class="label_value">
                                <input type="text" name="data[wx_appsecret]" class="text"
                                       value="{{ $data['wx_appsecret'] ?? '' }}">
                                <div class="notic">* {{ $lang['wxapp_help2'] }}</div>
                            </div>
                        </div>

                        <div class="item">
                            <div class="label-t">{{ $lang['token_secret'] }}</div>
                            <div class="label_value">
                                <input type="text" name="data[token_secret]" class="text"
                                       value="{{ $data['token_secret'] ?? '' }}">
                                <div class="notic">
                                    <span class="btn btn-success makeToken">{{ $lang['make_token'] }}</span>
                                    <span class="btn btn-info btn-xs copyToken">{{ $lang['copy_token'] }}</span>
                                    * {{ $lang['wxapp_help5'] }}
                                </div>
                            </div>
                        </div>

                        {{--小程序微信支付--}}
                        <div class="item">
                            <div class="label-t">{{ $lang['wx_mch_id'] }}</div>
                            <div class="label_value">
                                <input type="text" name="data[wx_mch_id]" class="text"
                                       value="{{ $data['wx_mch_id'] ?? '' }}">
                                <div class="notic">* {{ $lang['wxapp_help3'] }}</div>
                            </div>
                        </div>
                        <div class="item">
                            <div class="label-t">{{ $lang['wx_mch_key'] }}</div>
                            <div class="label_value">
                                <input type="text" name="data[wx_mch_key]" class="text"
                                       value="{{ $data['wx_mch_key'] ?? '' }}">
                                <div class="notic">* {{ $lang['wxapp_help4'] }}</div>
                            </div>
                        </div>


                        <div class="item">
                            <div class="label-t">{{ $lang['status'] }}</div>
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
@if(isset($data['status']) && $data['status']==1)
                                                active
                                                @endif
                                                ">{{ $lang['open'] }}</label>
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
                                                ">{{ $lang['close'] }}</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="item">
                            <div class="label-t">&nbsp;</div>
                            <div class="label_value info_btn">
                                @csrf
                                <input type="hidden" name="id" value="{{ $data['id'] ?? '' }}"/>
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

    var copyToken = document.querySelector('.copyToken');
    if (copyToken) {
        copyToken.onclick = function () {
            var newtoken = $("input[name='data[token_secret]']").val();
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
        $("input[name='data[token_secret]']").val(token);
    });

</script>

@include('admin.wxapp.pagefooter')
