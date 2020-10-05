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
                            <li class="active"><a href="javascript:;">{{ $postion['ur_here'] ?? '' }}</a></li>
                        </ul>
                    </div>
                    <div class="explanation" id="explanation">
                        <div class="ex_tit"><i class="sc_icon"></i><h4>{{ $lang['operating_hints'] }}</h4></div>
                        <ul>
                            @if(isset($lang['edit_wechat_tips']) && !empty($lang['edit_wechat_tips']))

                                @foreach($lang['edit_wechat_tips'] as $v)
                                    <li>{!! $v !!}</li>
                                @endforeach

                            @endif
                        </ul>
                    </div>
                    <form method="post" action="{{ route('seller/wechat/modify') }}" class="form-horizontal"
                          role="form">
                        <div class="wrapper-list border1 mt20 ecsc-form-goods">
                            <dl>
                                <dt>{{ $lang['wechat_name'] }}：</dt>
                                <dd>
                                    <input type="text" value="{{ $data['name'] ?? '' }}" name="data[name]" class="text">
                                    <div class="form_prompt"></div>
                                    <div class="notic">* {{ $lang['wechat_help1'] }}</div>
                                </dd>
                            </dl>
                            <dl>
                                <dt>{{ $lang['wechat_id'] }}：</dt>
                                <dd>
                                    <input type="text" value="{{ $data['orgid'] ?? '' }}" name="data[orgid]"
                                           class="text">
                                    <div class="form_prompt"></div>
                                    <div class="notic">* {{ $lang['wechat_help2'] }}</div>
                                </dd>
                            </dl>
                            <dl>
                                <dt>{{ $lang['appid'] }}：</dt>
                                <dd>
                                    <input type="text" value="{{ $data['appid'] ?? '' }}" name="data[appid]"
                                           class="text">
                                    <div class="form_prompt"></div>
                                    <div class="notic">* {{ $lang['wechat_help4'] }}</div>
                                </dd>
                            </dl>
                            <dl>
                                <dt>{{ $lang['appsecret'] }}：</dt>
                                <dd>
                                    <input type="text" value="{{ $data['appsecret'] ?? '' }}" name="data[appsecret]"
                                           class="text">
                                    <div class="form_prompt"></div>
                                    <div class="notic">* {{ $lang['wechat_help5'] }}</div>
                                </dd>
                            </dl>
                            <dl>
                                <dt>{{ $lang['token'] }}：</dt>
                                <dd>
                                    <input type="text" value="{{ $data['token'] ?? '' }}" name="data[token]"
                                           class="text">
                                    <div class="form_prompt"></div>
                                    <div class="notic">* {{ $lang['wechat_help3'] }}</div>
                                </dd>
                            </dl>
                            <dl>
                                <dt>{{ $lang['aeskey'] }}：</dt>
                                <dd>
                                    <input type="text" value="{{ $data['encodingaeskey'] ?? '' }}"
                                           name="data[encodingaeskey]" class="text">
                                    <div class="form_prompt"></div>
                                    <div class="notic">({{ $lang['selection'] }}) {{ $lang['wechat_help7'] }}</div>
                                </dd>
                            </dl>

                            <dl>
                                <dt class="step_label">{{ $lang['wechat_status'] }}：</dt>
                                <dd class="step_value">
                                    <div class="checkbox_items">
                                        <div class="checkbox_item">
                                            <input type="radio" name="data[status]" class="ui-radio event_zhuangtai"
                                                   id="value_118_0" value="1"
                                                   @if(isset($data['status']) && $data['status'] == 1)
                                                   checked
                                                    @endif
                                            >
                                            <label for="value_118_0"
                                                   class="ui-radio-label active">{{ $lang['wechat_open'] }}</label>
                                        </div>

                                        <div class="checkbox_item mr15">
                                            <input type="radio" name="data[status]" class="ui-radio event_zhuangtai"
                                                   id="value_118_1" value="0"
                                                   @if(isset($data['status']) && $data['status'] == 0)
                                                   checked
                                                    @endif
                                            >
                                            <label for="value_118_1"
                                                   class="ui-radio-label ">{{ $lang['wechat_close'] }}</label>
                                        </div>
                                    </div>
                                </dd>
                            </dl>

                            @if(isset($data['orgid']) && $data['orgid'])

                                <dl>
                                    <dt>{{ $lang['wechat_api_url'] }}：</dt>
                                    <dd class="step_value">
                                        <span class="text weixin_url">{{ $data['url'] ?? '' }}</span>
                                        <span class="sc-btn sc-blue-btn copy" style="margin-left:10px;">{{ $lang['copy_url'] }}</span>
                                    </dd>
                                </dl>

                            @endif

                            <dl>
                                <dt>&nbsp;</dt>
                                <dd class="button_info">
                                    @csrf
                                    <input type="hidden" name="id" value="{{ $data['id'] ?? '' }}"/>
                                    <input type="hidden" name="data[type]" value="2">
                                    <input type="submit" name="submit" value="{{ $lang['button_save'] }}" class="sc-btn btn35 sc-blueBg-btn">
                                </dd>
                            </dl>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
</div>
<script>
    // H5 复制粘贴 - execCommand http://www.jianshu.com/p/37322bb86a48  兼容IE8+，Chrome 45+, Firefox 43+
    var copyUrl = document.querySelector('.copy');
    // 点击的时候调用 copyTextToClipboard() 方法就好了.
    copyUrl.onclick = function () {
        copyTextToClipboard('{{ $data['url'] }}');
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


    // // 生成token md5
    // $(".makeToken").click(function(){
    //     var mydate = new Date();
    //     var mytime = mydate.toLocaleTimeString(); //获取当前时间
    //     var token = $.md5(mytime);
    //     $("input[name='data[token]']").val(token);
    // });

</script>

@include('seller.base.seller_pagefooter')
