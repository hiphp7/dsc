@include('admin.drp.pageheader')

<style>
    .main-info .item .label_value {
        width: 25%;
        float: left;
    }
</style>

<div class="wrapper">
    {{--<div class="title">{{ $lang['edit_credit_condition'] }}</div>--}}
    <div class="title">{{ $lang['drp_manage'] }} - {{ $lang['drp_credit'] }}</div>
    <div class="content_tips">
        <div class="tabs_info">
            <ul>
                <li><a href="{{ route('admin/drp/shop') }}">{{ $lang['drp_shop_list'] }}</a></li>
                <li class="curr"><a href="{{ route('admin/drp/drp_user_credit') }}">{{ $lang['drp_credit'] }}</a></li>
            </ul>
        </div>
        <div class="explanation" id="explanation">
            <div class="ex_tit"><i class="sc_icon"></i><h4>{{ $lang['operating_hints'] }}</h4><span id="explanationZoom" title="{{ $lang['fold_tips'] }}"></span>
            </div>
            <ul>
                <li>{{ $lang['drp_credit_tips']['0'] }}</li>
                <li>{{ $lang['drp_credit_tips']['1'] }}</li>
            </ul>
        </div>

    <div class="flexilist of">
        <div class="main-info">
            <form action="{{ route('admin/drp/drp_user_credit_edit') }}" method="post" class="form-horizontal"
                  role="form" onsubmit="return false;">
                <div class="switch_info">

                    <div class="item">
                        <div class="label-t">{{ $lang['all_order_money'] }}：</div>
                        <div class="label_value col-md-4">
                            <input type="number" name="data[all_order_money]" class="text w200" value="{{ $new_condition['all_order_money']['value'] ?? ''}}"/>
                            <input type="hidden" name="data[all_order_money_status]" value="{{ $new_condition['all_order_money']['type'] ?? 0 }}" id="all_order_money_status">
                        </div>

                        <div class="label-t">{{ $lang['award_num'] }}：</div>
                        <div class="label_value col-md-4">
                            <input type="number" name="data[all_order_money_num]" class="text w200" value="{{ $new_condition['all_order_money']['award_num'] ?? '' }}"/>

                        </div>

                        <input type="radio" @if(isset($new_condition['all_order_money']['type']) && $new_condition['all_order_money']['type'] == 0) checked="checked" @endif name="all_order_money_status" class="ui-radio" id="all_order_money_status_zero" value="0" onclick="click_all_order_money_status()"/>
                        <label for="all_order_money_status_zero" class="ui-radio-label">{{$lang['integral']}}</label>

                        <input type="radio" @if(isset($new_condition['all_order_money']['type']) && $new_condition['all_order_money']['type'] == 1) checked="checked" @endif name="all_order_money_status" class="ui-radio" id="all_order_money_status_one" value="1" onclick="click_all_order_money_status()"/>
                        <label for="all_order_money_status_one" class="ui-radio-label">{{$lang['balance']}}</label>

                    </div>

                    <div class="item">
                        <div class="label-t">{{ $lang['all_direct_order_money'] }}：</div>
                        <div class="label_value col-md-4">
                            <input type="number" name="data[all_direct_order_money]" class="text w200" value="{{ $new_condition['all_direct_order_money']['value'] ?? '' }}"/>
                            <input type="hidden" name="data[all_direct_order_money_status]" value="{{ $new_condition['all_direct_order_money']['type'] ?? 0 }}" id="all_direct_order_money_status">
                        </div>

                        <div class="label-t">{{ $lang['award_num'] }}：</div>
                        <div class="label_value col-md-4">
                            <input type="number" name="data[all_direct_order_money_num]" class="text w200" value="{{ $new_condition['all_direct_order_money']['award_num'] ?? '' }}"/>
                        </div>

                        <input type="radio" @if(isset($new_condition['all_direct_order_money']['type']) && $new_condition['all_direct_order_money']['type'] == 0) checked="checked" @endif name="all_direct_order_money_status" class="ui-radio" id="all_direct_order_money_status_zero" value="0" onclick="click_all_direct_order_money_status()"/>
                        <label for="all_direct_order_money_status_zero" class="ui-radio-label">{{$lang['integral']}}</label>

                        <input type="radio" @if(isset($new_condition['all_direct_order_money']['type']) && $new_condition['all_direct_order_money']['type'] == 1) checked="checked" @endif name="all_direct_order_money_status" class="ui-radio" id="all_direct_order_money_status_one" value="1" onclick="click_all_direct_order_money_status()"/>
                        <label for="all_direct_order_money_status_one" class="ui-radio-label">{{$lang['balance']}}</label>
                    </div>

                    <div class="item">
                        <div class="label-t">{{ $lang['all_order_num'] }}：</div>
                        <div class="label_value col-md-4">
                            <input type="number" name="data[all_order_num]" class="text w200" value="{{ $new_condition['all_order_num']['value'] ?? '' }}"/>
                            <input type="hidden" name="data[all_order_num_status]" value="{{ $new_condition['all_order_num']['type'] ?? 0 }}" id="all_order_num_status">
                        </div>

                        <div class="label-t">{{ $lang['award_num'] }}：</div>
                        <div class="label_value col-md-4">
                            <input type="number" name="data[all_order_num_num]" class="text w200" value="{{ $new_condition['all_order_num']['award_num'] ?? '' }}"/>
                        </div>

                        <input type="radio" @if(isset($new_condition['all_order_num']['type']) && $new_condition['all_order_num']['type'] == 0) checked="checked" @endif name="all_order_num_status" class="ui-radio" id="all_order_num_status_zero" value="0" onclick="click_all_order_num_status()"/>
                        <label for="all_order_num_status_zero" class="ui-radio-label">{{$lang['integral']}}</label>

                        <input type="radio" @if(isset($new_condition['all_order_num']['type']) && $new_condition['all_order_num']['type'] == 1) checked="checked" @endif name="all_order_num_status" class="ui-radio" id="all_order_num_status_one" value="1" onclick="click_all_order_num_status()"/>
                        <label for="all_order_num_status_one" class="ui-radio-label">{{$lang['balance']}}</label>
                    </div>

                    <div class="item">
                        <div class="label-t">{{ $lang['all_direct_order_num'] }}：</div>
                        <div class="label_value col-md-4">
                            <input type="number" name="data[all_direct_order_num]" class="text w200" value="{{ $new_condition['all_direct_order_num']['value'] ?? '' }}"/>
                            <input type="hidden" name="data[all_direct_order_num_status]" value="{{ $new_condition['all_direct_order_num']['type'] ?? 0 }}" id="all_direct_order_num_status">
                        </div>

                        <div class="label-t">{{ $lang['award_num'] }}：</div>
                        <div class="label_value col-md-4">
                            <input type="number" name="data[all_direct_order_num_num]" class="text w200" value="{{ $new_condition['all_direct_order_num']['award_num'] ?? '' }}"/>
                        </div>

                        <input type="radio" @if(isset($new_condition['all_direct_order_num']['type']) && $new_condition['all_direct_order_num']['type'] == 0) checked="checked" @endif name="all_direct_order_num_status" class="ui-radio" id="all_direct_order_num_status_zero" value="0" onclick="click_all_direct_order_num_status()"/>
                        <label for="all_direct_order_num_status_zero" class="ui-radio-label">{{$lang['integral']}}</label>

                        <input type="radio" @if(isset($new_condition['all_direct_order_num']['type']) && $new_condition['all_direct_order_num']['type'] == 1) checked="checked" @endif name="all_direct_order_num_status" class="ui-radio" id="all_direct_order_num_status_one" value="1" onclick="click_all_direct_order_num_status()"/>
                        <label for="all_direct_order_num_status_one" class="ui-radio-label">{{$lang['balance']}}</label>
                    </div>

                    <div class="item">
                        <div class="label-t">{{ $lang['all_self_order_money'] }}：</div>
                        <div class="label_value col-md-4">
                            <input type="number" name="data[all_self_order_money]" class="text w200" value="{{ $new_condition['all_self_order_money']['value'] ?? '' }}"/>
                            <input type="hidden" name="data[all_self_order_money_status]" value="{{ $new_condition['all_self_order_money']['type'] ?? 0 }}" id="all_self_order_money_status">
                        </div>

                        <div class="label-t">{{ $lang['award_num'] }}：</div>
                        <div class="label_value col-md-4">
                            <input type="number" name="data[all_self_order_money_num]" class="text w200" value="{{ $new_condition['all_self_order_money']['award_num'] ?? '' }}"/>
                        </div>

                        <input type="radio" @if(isset($new_condition['all_self_order_money']['type']) && $new_condition['all_self_order_money']['type'] == 0) checked="checked" @endif name="all_self_order_money_status" class="ui-radio" id="all_self_order_money_status_zero" value="0" onclick="click_all_self_order_money_status()"/>
                        <label for="all_self_order_money_status_zero" class="ui-radio-label">{{$lang['integral']}}</label>

                        <input type="radio" @if(isset($new_condition['all_self_order_money']['type']) && $new_condition['all_self_order_money']['type'] == 1) checked="checked" @endif name="all_self_order_money_status" class="ui-radio" id="all_self_order_money_status_one" value="1" onclick="click_all_self_order_money_status()"/>
                        <label for="all_self_order_money_status_one" class="ui-radio-label">{{$lang['balance']}}</label>
                    </div>

                    <div class="item">
                        <div class="label-t">{{ $lang['all_self_order_num'] }}：</div>
                        <div class="label_value col-md-4">
                            <input type="number" name="data[all_self_order_num]" class="text w200" value="{{ $new_condition['all_self_order_num']['value'] ?? '' }}"/>
                            <input type="hidden" name="data[all_self_order_num_status]" value="{{ $new_condition['all_self_order_num']['type'] ?? 0 }}" id="all_self_order_num_status">
                        </div>

                        <div class="label-t">{{ $lang['award_num'] }}：</div>
                        <div class="label_value col-md-4">
                            <input type="number" name="data[all_self_order_num_num]" class="text w200" value="{{ $new_condition['all_self_order_num']['award_num'] ?? '' }}"/>
                        </div>

                        <input type="radio" @if(isset($new_condition['all_self_order_num']['type']) && $new_condition['all_self_order_num']['type'] == 0) checked="checked" @endif name="all_self_order_num_status" class="ui-radio" id="all_self_order_num_status_zero" value="0" onclick="click_all_self_order_num_status()"/>
                        <label for="all_self_order_num_status_zero" class="ui-radio-label">{{$lang['integral']}}</label>

                        <input type="radio" @if(isset($new_condition['all_self_order_num']['type']) && $new_condition['all_self_order_num']['type'] == 1) checked="checked" @endif name="all_self_order_num_status" class="ui-radio" id="all_self_order_num_status_one" value="1" onclick="click_all_self_order_num_status()"/>
                        <label for="all_self_order_num_status_one" class="ui-radio-label">{{$lang['balance']}}</label>
                    </div>

                    <div class="item">
                        <div class="label-t">{{ $lang['all_direct_user_num'] }}：</div>
                        <div class="label_value col-md-4">
                            <input type="number" name="data[all_direct_user_num]" class="text w200" value="{{ $new_condition['all_direct_user_num']['value'] ?? '' }}"/>
                            <input type="hidden" name="data[all_direct_user_num_status]" value="{{ $new_condition['all_direct_user_num']['type'] ?? 0 }}" id="all_direct_user_num_status">
                        </div>

                        <div class="label-t">{{ $lang['award_num'] }}：</div>
                        <div class="label_value col-md-4">
                            <input type="number" name="data[all_direct_user_num_num]" class="text w200" value="{{ $new_condition['all_direct_user_num']['award_num'] ?? '' }}"/>
                        </div>

                        <input type="radio" @if(isset($new_condition['all_direct_user_num']['type']) && $new_condition['all_direct_user_num']['type'] == 0) checked="checked" @endif name="all_direct_user_num_status" class="ui-radio" id="all_direct_user_num_status_zero" value="0" onclick="click_all_direct_user_num_status()"/>
                        <label for="all_direct_user_num_status_zero" class="ui-radio-label">{{$lang['integral']}}</label>

                        <input type="radio" @if(isset($new_condition['all_direct_user_num']['type']) && $new_condition['all_direct_user_num']['type'] == 1) checked="checked" @endif name="all_direct_user_num_status" class="ui-radio" id="all_direct_user_num_status_one" value="1" onclick="click_all_direct_user_num_status()"/>
                        <label for="all_direct_user_num_status_one" class="ui-radio-label">{{$lang['balance']}}</label>
                    </div>

                    <div class="item">
                        <div class="label-t">{{ $lang['all_direct_drp_num'] }}：</div>
                        <div class="label_value col-md-4">
                            <input type="number" name="data[all_direct_drp_num]" class="text w200" value="{{ $new_condition['all_direct_drp_num']['value'] ?? '' }}"/>
                            <input type="hidden" name="data[all_direct_drp_num_status]" value="{{ $new_condition['all_direct_drp_num']['type'] ?? 0 }}" id="all_direct_drp_num_status">
                        </div>

                        <div class="label-t">{{ $lang['award_num'] }}：</div>
                        <div class="label_value col-md-4">
                            <input type="number" name="data[all_direct_drp_num_num]" class="text w200" value="{{ $new_condition['all_direct_drp_num']['award_num'] ?? '' }}"/>
                        </div>

                        <input type="radio" @if(isset($new_condition['all_direct_drp_num']['type']) && $new_condition['all_direct_drp_num']['type'] == 0) checked="checked" @endif name="all_direct_drp_num_status" class="ui-radio" id="all_direct_drp_num_status_zero" value="0" onclick="click_all_direct_drp_num_status()"/>
                        <label for="all_direct_drp_num_status_zero" class="ui-radio-label">{{$lang['integral']}}</label>

                        <input type="radio" @if(isset($new_condition['all_direct_drp_num']['type']) && $new_condition['all_direct_drp_num']['type'] == 1) checked="checked" @endif name="all_direct_drp_num_status" class="ui-radio" id="all_direct_drp_num_status_one" value="1" onclick="click_all_direct_drp_num_status()"/>
                        <label for="all_direct_drp_num_status_one" class="ui-radio-label">{{$lang['balance']}}</label>
                    </div>

                    <div class="item">
                        <div class="label-t">{{ $lang['all_indirect_drp_num'] }}：</div>
                        <div class="label_value col-md-4">
                            <input type="number" name="data[all_indirect_drp_num]" class="text w200" value="{{ $new_condition['all_indirect_drp_num']['value'] ?? '' }}"/>
                            <input type="hidden" name="data[all_indirect_drp_num_status]" value="{{ $new_condition['all_indirect_drp_num']['type'] ?? 0 }}" id="all_indirect_drp_num_status">
                        </div>

                        <div class="label-t">{{ $lang['award_num'] }}：</div>
                        <div class="label_value col-md-4">
                            <input type="number" name="data[all_indirect_drp_num_num]" class="text w200" value="{{ $new_condition['all_indirect_drp_num']['award_num'] ?? '' }}"/>
                        </div>

                        <input type="radio" @if(isset($new_condition['all_indirect_drp_num']['type']) && $new_condition['all_indirect_drp_num']['type'] == 0) checked="checked" @endif name="all_indirect_drp_num_status" class="ui-radio" id="all_indirect_drp_num_status_zero" value="0" onclick="click_all_indirect_drp_num_status()"/>
                        <label for="all_indirect_drp_num_status_zero" class="ui-radio-label">{{$lang['integral']}}</label>

                        <input type="radio" @if(isset($new_condition['all_indirect_drp_num']['type']) && $new_condition['all_indirect_drp_num']['type'] == 1) checked="checked" @endif name="all_indirect_drp_num_status" class="ui-radio" id="all_indirect_drp_num_status_one" value="1" onclick="click_all_indirect_drp_num_status()"/>
                        <label for="all_indirect_drp_num_status_one" class="ui-radio-label">{{$lang['balance']}}</label>
                    </div>

                    <div class="item">
                        <div class="label-t">{{ $lang['all_develop_drp_num'] }}：</div>
                        <div class="label_value col-md-4">
                            <input type="number" name="data[all_develop_drp_num]" class="text w200" value="{{ $new_condition['all_develop_drp_num']['value'] ?? '' }}"/>
                            <input type="hidden" name="data[all_develop_drp_num_status]" value="{{ $new_condition['all_develop_drp_num']['type'] ?? 0 }}" id="all_develop_drp_num_status">
                        </div>

                        <div class="label-t">{{ $lang['award_num'] }}：</div>
                        <div class="label_value col-md-4">
                            <input type="number" name="data[all_develop_drp_num_num]" class="text w200" value="{{ $new_condition['all_develop_drp_num']['award_num'] ?? '' }}"/>
                        </div>

                        <input type="radio" @if(isset($new_condition['all_develop_drp_num']['type']) && $new_condition['all_develop_drp_num']['type'] == 0) checked="checked" @endif name="all_develop_drp_num_status" class="ui-radio" id="all_develop_drp_num_status_zero" value="0" onclick="click_all_develop_drp_num_status()"/>
                        <label for="all_develop_drp_num_status_zero" class="ui-radio-label">{{$lang['integral']}}</label>

                        <input type="radio" @if(isset($new_condition['all_develop_drp_num']['type']) && $new_condition['all_develop_drp_num']['type'] == 1) checked="checked" @endif name="all_develop_drp_num_status" class="ui-radio" id="all_develop_drp_num_status_one" value="1" onclick="click_all_develop_drp_num_status()"/>
                        <label for="all_develop_drp_num_status_one" class="ui-radio-label">{{$lang['balance']}}</label>
                    </div>

                    <div class="item">
                        <div class="label-t">{{ $lang['goods_id'] }}：</div>
                        <div class="label_value col-md-4">
                            {{--<input type="button" class="btn btn30 blue_btn fl mr10 valid" value="{{$lang['set_goods']}}" ectype="setupGroupGoods" data-diffeseller="1" data-pbmode="setpackagegoods" data-pbtype="package" aria-invalid="false">--}}
                            <input type="hidden" name="data[goods_id_status]" value="{{ $new_condition['goods_id']['type'] ?? 0 }}" id="goods_id_status">
                            <input type="text" class="text w200" name="data[buy_goods]" value="{{ $new_condition['goods_id']['value'] ?? 0 }}">
                            <p class="notic pr5"><a href="{{ route('distribute.admin.select_goods', ['type' => 'upgrade']) }}" class="fancybox fancybox.iframe"><span class="btn btn-info btn-xs ">{{ $lang['select_goods_menu'] }}</span></a></p>
                        </div>

                        <div class="label-t">{{ $lang['award_num'] }}：</div>
                        <div class="label_value col-md-4">
                            <input type="number" name="data[goods_id_num]" class="text w200" value="{{ $new_condition['goods_id']['award_num'] ?? '' }}"/>
                        </div>

                        <input type="radio" @if(isset($new_condition['goods_id']['type']) && $new_condition['goods_id']['type'] == 0) checked="checked" @endif name="goods_id_status" class="ui-radio" id="goods_id_status_zero" value="0" onclick="click_goods_id_status()"/>
                        <label for="goods_id_status_zero" class="ui-radio-label">{{$lang['integral']}}</label>

                        <input type="radio" @if(isset($new_condition['goods_id']['type']) && $new_condition['goods_id']['type'] == 1) checked="checked" @endif name="goods_id_status" class="ui-radio" id="goods_id_status_one" value="1" onclick="click_goods_id_status()"/>
                        <label for="goods_id_status_one" class="ui-radio-label">{{$lang['balance']}}</label>
                    </div>

                    <div class="item">
                        <div class="label-t">{{ $lang['withdraw_all_money'] }}：</div>
                        <div class="label_value col-md-4">
                            <input type="number" name="data[withdraw_all_money]" class="text w200" value="{{ $new_condition['withdraw_all_money']['value'] ?? '' }}"/>
                            <input type="hidden" name="data[withdraw_all_money_status]" value="{{ $new_condition['withdraw_all_money']['type'] ?? 0 }}" id="withdraw_all_money_status">
                        </div>

                        <div class="label-t">{{ $lang['award_num'] }}：</div>
                        <div class="label_value col-md-4">
                            <input type="number" name="data[withdraw_all_money_num]" class="text w200" value="{{ $new_condition['withdraw_all_money']['award_num'] ?? '' }}"/>
                        </div>

                        <input type="radio" @if(isset($new_condition['withdraw_all_money']['type']) && isset($new_condition['withdraw_all_money']['type']) && $new_condition['withdraw_all_money']['type'] == 0) checked="checked" @endif name="withdraw_all_money_status" class="ui-radio" id="withdraw_all_money_status_zero" value="0" onclick="click_withdraw_all_money_status()"/>
                        <label for="withdraw_all_money_status_zero" class="ui-radio-label">{{$lang['integral']}}</label>

                        <input type="radio" @if(isset($new_condition['withdraw_all_money']['type']) && $new_condition['withdraw_all_money']['type'] == 1) checked="checked" @endif name="withdraw_all_money_status" class="ui-radio" id="withdraw_all_money_status_one" value="1" onclick="click_withdraw_all_money_status()"/>
                        <label for="withdraw_all_money_status_one" class="ui-radio-label">{{$lang['balance']}}</label>
                    </div>

                    <div class="item">
                        <div class="label-t">&nbsp;</div>
                        <div class="label_value col-md-4">
                            <div class="info_btn">
                                <input type="hidden" name="id" value="{{ $info['id'] }}"/>
                                <input type="submit" value="{{ $lang['button_submit'] }}" class="button btn-danger bg-red fn"/>
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
    //分销订单总金额
    function click_all_order_money_status() {
        var dis_commission_type = $('input[name=all_order_money_status]:checked').val();
        $('#all_order_money_status').val(dis_commission_type);
    }
    //多选商品ID
    function click_goods_id_status() {
        var dis_commission_type = $('input[name=goods_id_status]:checked').val();
        $('#goods_id_status').val(dis_commission_type);
    }
    //一级分销订单总额
    function click_all_direct_order_money_status() {
        var dis_commission_type = $('input[name=all_direct_order_money_status]:checked').val();
        $('#all_direct_order_money_status').val(dis_commission_type);
    }
    //分销订单总笔数
    function click_all_order_num_status() {
        var dis_commission_type = $('input[name=all_order_num_status]:checked').val();
        $('#all_order_num_status').val(dis_commission_type);
    }
    //一级分销订单总数
    function click_all_direct_order_num_status() {
        var dis_commission_type = $('input[name=all_direct_order_num_status]:checked').val();
        $('#all_direct_order_num_status').val(dis_commission_type);
    }
    //自购订单金额
    function click_all_self_order_money_status() {
        var dis_commission_type = $('input[name=all_self_order_money_status]:checked').val();
        $('#all_self_order_money_status').val(dis_commission_type);
    }
    //自购订单数量
    function click_all_self_order_num_status() {
        var dis_commission_type = $('input[name=all_self_order_num_status]:checked').val();
        $('#all_self_order_num_status').val(dis_commission_type);
    }
    //下级总数量
    function click_all_direct_user_num_status() {
        var dis_commission_type = $('input[name=all_direct_user_num_status]:checked').val();
        $('#all_direct_user_num_status').val(dis_commission_type);
    }
    //直属下级总数量
    function click_all_direct_drp_num_status() {
        var dis_commission_type = $('input[name=all_direct_drp_num_status]:checked').val();
        $('#all_direct_drp_num_status').val(dis_commission_type);
    }
    //下级分销商总人数
    function click_all_indirect_drp_num_status() {
        var dis_commission_type = $('input[name=all_indirect_drp_num_status]:checked').val();
        $('#all_indirect_drp_num_status').val(dis_commission_type);
    }
    //一级分销商人数
    function click_all_develop_drp_num_status() {
        var dis_commission_type = $('input[name=all_develop_drp_num_status]:checked').val();
        $('#all_develop_drp_num_status').val(dis_commission_type);
    }
    //已提现佣金总额
    function click_withdraw_all_money_status() {
        var dis_commission_type = $('input[name=withdraw_all_money_status]:checked').val();
        $('#withdraw_all_money_status').val(dis_commission_type);
    }

    $(function () {
        $(".form-horizontal").submit(function () {

            var minval = parseInt($("input[name='data[min_money]']").val());
            var maxval = parseInt($("input[name='data[max_money]']").val());
            if (minval > maxval) {
                layer.msg('{{ $lang['price_small'] }}');
                return false;
            }

            var ajax_data = $(this).serialize();
            $.post("{{ route('admin/drp/drp_user_credit_condition') }}", ajax_data, function (data) {
                layer.msg(data.msg);
                if (data.error == 0) {
                    window.location = "{{ route('admin/drp/drp_user_credit') }}";
                } else {
                    return false;
                }
            }, 'json');
        });
    })
</script>


@include('admin.drp.pagefooter')
