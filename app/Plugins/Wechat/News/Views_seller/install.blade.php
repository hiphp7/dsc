
<div class="wrapper-right of" >
    <div class="tabmenu">
        <ul class="tab ">
            <li><a href="{{ route('seller/wechat/extend_index') }}" class="s-back">{{ $lang['back'] }}</a></li>
            <li role="presentation" class="active"><a href="#home" role="tab" data-toggle="tab">{{ $lang['wechat_extend'] }} - {{ $config['name'] }}</a></li>
        </ul>
    </div>
    <div class="wrapper-list mt20">
        <form action="{{ route('admin/wechat/extend_edit') }}" method="post" class="form-horizontal" role="form">
        <div class="account-setting ecsc-form-goods">
            <dl>
                <dt>{{ $lang['extend_name'] }}：</dt>
                <dd class="txtline">
                    <span><input type="text" name="data[name]" class="text" value="{{ $config['name'] }}" /></span>
                </dd>
            </dl>
            <dl>
                <dt>{{ $lang['extend_command'] }}：</dt>
                <dd class="txtline">
                    <span>{{ $config['command'] }}</span>
                </dd>
            </dl>
            <dl>
                <dt>{{ $lang['extend_keywords'] }}：</dt>
                <dd>
                    <input type="text" name="data[keywords]" class="text" value="{{ $config['keywords'] }}" />
                    <div class="form_prompt"></div>
                    <div class="notic"> {{ $lang['extend_keywords_notice'] }}：</div>
                </dd>
            </dl>
            <dl>
                <dt>&nbsp;</dt>
                <dd>
                    @csrf
                    <input type="hidden" name="data[command]" value="{{ $config['command'] }}" />

                    <input type="hidden" name="data[author]" value="{{ $config['author'] }}">
                    <input type="hidden" name="data[website]" value="{{ $config['website'] }}">
                    <input type="hidden" name="handler" value="{{ $config['handler'] ?? '' }}">
                    <input type="submit" name="submit" class="sc-btn sc-blueBg-btn btn35" value="{{ $lang['button_submit'] }}" />
                    <input type="reset" name="reset" class="sc-btn sc-blue-btn btn35" value="{{ $lang['button_revoke'] }}" />
                </dd>
            </dl>
        </div>
        </form>
    </div>
</div>
