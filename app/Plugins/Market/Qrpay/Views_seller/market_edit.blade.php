
<div class="wrapper-right of" >
    <div class="tabmenu">
        <ul class="tab">
            <li><a href="{{ route('seller/wechat/market_list', array('type' => $config['keywords'])) }}" class="s-back">返回</a></li>
            <li class="active"><a href="#">{{ $config['name'] }} -
@if(isset($info['id']) && $info['id'])
编辑
@else
添加
@endif
</a></li>
        </ul>
    </div>

    <div class="explanation" id="explanation">
        <div class="ex_tit"><i class="sc_icon"></i><h4>操作提示</h4></div>
        <ul>
            <li>1、收款码类型一经创建后不可修改。</li>
        </ul>
    </div>

    <div class="wrapper-list mt20" >

        <form action="{!!  route('seller/wechat/market_edit', array('type' => $config['keywords']))  !!}" method="post" class="form-horizontal" role="form" enctype="multipart/form-data" onsubmit="return false;">
        <div class="account-setting ecsc-form-goods">
            <dl>
                <dt>收款码名称：</dt>
                <dd>
                    <div class="col-sm-3">
                        <input type="text" name="data[qrpay_name]" class="form-control" value="{{ $info['qrpay_name'] ?? '' }}" />
                    </div>
                    <div class="form_prompt"></div>
                    <div class="notic" style="width:50%"> * 必填 收款码名称建议不超过32个字符</div>
                </dd>
            </dl>
            <dl>
                <dt>收款码类型：</dt>
                <dd>
                    <div class="col-sm-4">
                        <div class="checkbox_items">
                            <div class="checkbox_item">
                                <input type="radio" name="data[type]" class="ui-radio evnet_shop_closed clicktype" id="value_116_0" value="0"
@if(isset($info['id']) && $info['id'])
disabled="disabled"
@endif

@if(isset($info['type']) && $info['type'] == '0')
checked
@endif
 >
                                <label for="value_116_0" class="ui-radio-label
@if(isset($info['id']) && $info['id'])
disabled
@endif

@if(isset($info['type']) && $info['type'] == '0')
active
@endif
">自助收款码</label>
                            </div>
                            <div class="checkbox_item">
                                <input type="radio" name="data[type]" class="ui-radio evnet_shop_closed clicktype" id="value_116_1" value="1"
@if(isset($info['id']) && $info['id'])
disabled="disabled"
@endif

@if(isset($info['type']) && $info['type'] == '1')
checked
@endif
>
                                <label for="value_116_1" class="ui-radio-label
@if(isset($info['id']) && $info['id'])
disabled
@endif

@if(isset($info['type']) && $info['type'] == '1')
active
@endif
">指定金额收款码</label>
                            </div>
                        </div>
                    </div>
                    <div class="form_prompt"></div>
                    <div class="notic" style="width:50%;color:red">* 收款码类型 创建后不可修改</div>
                </dd>
            </dl>
            <dl class="
@if(isset($info['type']) && $info['type'] == '0')
hidden
@endif
" id="click">
                <dt>收款码金额：</dt>
                <dd>
                    <div class="col-sm-3">
                        <div class="input-group">
                            <input type="number" min="0" step="0.01" name="data[amount]" class="form-control" value="{{ $info['amount'] ?? '' }}" placeholder="输入收款金额" />
                            <span class="input-group-addon">元</span>
                        </div>
                    </div>
                    <div class="form_prompt"></div>
                    <div class="notic" style="width:50%">商家设置固定金额创建收款码，消费者扫码后直接支付</div>
                </dd>
            </dl>
            <dl class="
@if(isset($info['type']) && $info['type'] == '1')
hidden
@endif
" id="view">
                <dt>&nbsp;</dt>
                <dd>
                    <div class="form_prompt"></div>
                    <div class="notic  pl20" style="width:50%">扫描二维码，消费者输入付款金额，支付成功后收入到账</div>
                </dd>
            </dl>
            <dl>
                <dt>选择标签：</dt>
                <dd>
                    <div class="col-sm-3">
                        <div class="input-group">
                        <select name="data[tag_id]" class="form-control">
                            <option value='0' >无</option>
@if (isset($tag_list))
@foreach($tag_list as $tag)

                            <option value="{{ $tag['id'] ?? '' }}"
@if(isset($info['tag_id']) && $info['tag_id'] == $tag['id'])
 selected
@endif
 >{{ $tag['tag_name'] ?? '' }}</option>

@endforeach
@endif
                        </select>
                        </div>
                    </div>
                    <div class="notic" style="width:50%"> <a class="sc-btn sc-blue-btn" href="{{ route('seller/wechat/data_list', array('type' => $config['keywords'], 'function' => 'qrpay_tag_list')) }}" >管理标签</a></div>
                </dd>
            </dl>
            <dl>
                <dt>选择优惠：</dt>
                <dd>
                    <div class="col-sm-3">
                        <div class="input-group">
                        <select name="data[discount_id]" class="form-control">
                            <option value='0' >无</option>

@foreach($discounts_list as $dis)

                            <option value="{{ $dis['id'] ?? '' }}"
@if(isset($info['discount_id']) && $info['discount_id'] == $dis['id'])
 selected
@endif
 >{{ $dis['dis_name'] ?? '' }}</option>

@endforeach

                        </select>
                        </div>
                    </div>
                </dd>
            </dl>
            <dl>
                <dt>&nbsp;</dt>
                <dd class="button_info">
                    <input type="hidden" name="id" value="{{ $info['id'] ?? '' }}">
                    <input type="hidden" name="data[ru_id]" value="{{ $info['ru_id'] ?? '' }}">

                    <input type="submit" name="submit" class="sc-btn sc-blueBg-btn btn35" value="{{ $lang['button_submit'] }}" />
                    <input type="reset" name="reset" class="sc-btn sc-blue-btn btn35" value="{{ $lang['button_revoke'] }}" />
                </dd>
            </dl>
        </div>
        </form>

    </div>

</div>
<script type="text/javascript">
$(function(){
    $(".clicktype").click(function(){
        // var val = $(this).find("input[type=radio]").val();
        var val = $(this).val();

        if('0' == val && !$("#click").hasClass("hidden")){
            $("#click").hide().addClass("hidden");
            $("#view").show().removeClass("hidden");
        }
        if('1' == val && $("#click").hasClass("hidden")){
            $("#click").show().removeClass("hidden");
            $("#view").hide().addClass("hidden");
        }
    });

    $(".form-horizontal").submit(function(){
        var ajax_data = $(this).serialize();
        $.post("{!! route('seller/wechat/market_edit', array('type' => $config['keywords'])) !!}", ajax_data, function(data){
            layer.msg(data.msg);
            if (data.error == 0) {
                if (data.url) {
                    window.location.href = data.url;
                } else {
                    window.location.reload();
                }
            } else {
                return false;
            }
        }, 'json');
    });
})
</script>
