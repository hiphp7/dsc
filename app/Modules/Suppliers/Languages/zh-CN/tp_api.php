<?php

$_LANG['tpApi']['name'] = '第三方服务';

//快递鸟
$lang_kdniao = [];
$lang_kdniao['name'] = '快递鸟';
$lang_kdniao['client_id'] = '用户ID';
$lang_kdniao['appkey'] = 'API key';
$_LANG['tpApi']['kdniao'] = $lang_kdniao;
$_LANG['kdniao_set'] = "快递鸟账号设置";
$_LANG['kdniao_account_use_0'] = "由网站统一设置";
$_LANG['kdniao_account_use_1'] = "商家可自行设置";

//电子面单
$_LANG['set_print'] = "该打印规格还没有设置指定的打印机";
$_LANG['order_print_setting_add'] = "添加打印规格";
$_LANG['order_print_setting_edit'] = "编辑打印规格";
$_LANG['specification_exist'] = "该规格已存在，请重新设置";
$_LANG['no_print_setting'] = "没有设置打印规格";
$_LANG['print_setting_status'] = "A4纸张";
$_LANG['lable_print_size'] = "打印规格：";
$_LANG['print_set_remind'] = "打印规格需店铺管理员在商家后台->店铺->打印设置中进行设置。";
$_LANG['go_set_remind'] = "点击按钮快去设置吧！";
$_LANG['go_set'] = "去设置";

$_LANG['goods_shopping_list'] = "商品购物清单";
$_LANG['wholesale_shopping_list'] = "批发购物清单";
$_LANG['lable_order_sn'] = "订单编号：";
$_LANG['lable_order_time'] = "订购时间：";
$_LANG['lable_consignee'] = "收货人：";
$_LANG['lable_iphone'] = "电话：";
$_LANG['lable_delivery_time'] = "送货时间：";
$_LANG['lable_pay_make'] = "支付方式：";
$_LANG['lable_address'] = "收货地址：";
$_LANG['goods_sn'] = "商品编号";
$_LANG['goods_specifications'] = "商品规格";
$_LANG['number'] = "数量";
$_LANG['unit_price'] = "单价";
$_LANG['subtotal'] = "小计";
$_LANG['commodity_code'] = "商品条形码：";
$_LANG['sum_number'] = "总数量：";
$_LANG['goods_sum_price'] = "商品总金额：";
$_LANG['freight'] = "运费：";
$_LANG['insurance_cost'] = "保价费用：";
$_LANG['invoice_tax'] = "发票税额：";
$_LANG['payment_handling'] = "支付手续费：";
$_LANG['discount'] = "优惠：";
$_LANG['sum_order_amount'] = "订单总金额：";
$_LANG['balance'] = "余额：";
$_LANG['amount_paid'] = "已付款金额：";
$_LANG['stored_card'] = "储值卡：";
$_LANG['integral'] = "积分：";
$_LANG['red_envelope'] = "店铺红包：";
$_LANG['coupon'] = "优惠券：";
$_LANG['amount_to_paid'] = "待付款金额：";
$_LANG['please_input_remarks'] = "请输入备注信息";
$_LANG['click_reedit'] = "点击重新编辑";
$_LANG['piece'] = "件";

//提示
$_LANG['add_success'] = "添加成功";
$_LANG['edit_success'] = "编辑成功";
$_LANG['save_success'] = "保存成功";
$_LANG['save_failed'] = "保存失败";
$_LANG['back_list'] = "返回列表";
$_LANG['back_set'] = "返回设置";

$_LANG['print_s'] = "打印机：";
$_LANG['set_default_s'] = "设为默认：";

$_LANG['preview'] = "预览";
$_LANG['print'] = "打印";
$_LANG['express_way'] = "快递方式";
$_LANG['print_size'] = "打印尺寸";
$_LANG['print_explain'] = "说明：由于不同快递面单尺寸不同，高度在100mm - 180mm不等，我们将单张面单打印尺寸固定为100mm x 180mm，因此我们建议您使用100mm x 180mm尺寸的热敏纸进行打印。";
$_LANG['print_express'] = "打印快递单";
$_LANG['print_title'] = "购物清单";
$_LANG['printer_null'] = "该打印规格还没有设置指定的打印机";

$_LANG['print_spec'] = "打印规格";
$_LANG['spec'] = "规格";
$_LANG['printer'] = "打印机";
$_LANG['width'] = "宽度";
$_LANG['set_default'] = "设为默认";
$_LANG['order'] = "订单";

$_LANG['not_select_order'] = "没有选择订单";
$_LANG['select_express_order_print'] = "请选择快递方式相同的订单进行批量打印";
$_LANG['electron_order_fail'] = "电子面单下单失败";
$_LANG['not_print_template'] = "无打印模板";

$_LANG['printer_name_notic'] = "打印机名称如：ELP-168ES";
$_LANG['print_spec_width_notic'] = "此处设置的width是打印预览时宽度超出纸张尺寸的间距调整，默认是纸张尺寸，根据需求自行设置";
//JS语言项
$_LANG['js_languages']['printer_set_notic'] = '该打印规格还没有设置指定的打印机';
$_LANG['js_languages']['specification_notic'] = '请选择打印规格';
$_LANG['js_languages']['printer_name_notic'] = '请填写打印机名称';

/* 页面顶部操作提示 */
$_LANG['operation_prompt_content']['setting'][0] = '查看电子面单打印规格。';
$_LANG['operation_prompt_content']['setting'][1] = '可根据规格或打印机进行搜索。';

$_LANG['operation_prompt_content']['setting_info'][0] = '从列表中选择规格进行设置。';
$_LANG['operation_prompt_content']['setting_info'][1] = '可以将常用规格设置为默认。';

$_LANG['operation_prompt_content']['kdniao'][0] = '使用快递鸟打印快递单时需要在此次页面填写配置信息';
$_LANG['operation_prompt_content']['kdniao'][1] = '配置快递鸟API信息';

return $_LANG;
