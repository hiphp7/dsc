<?php

//供应商结算列表
$_LANG['suppliers_name'] = '供应商名称';
$_LANG['company_name'] = '公司名称';
$_LANG['company_address'] = '公司地址';
$_LANG['order_valid_total'] = '有效金额';
$_LANG['order_refund_total'] = '退款金额';
$_LANG['is_settlement_amount'] = '已结算';
$_LANG['no_settlement_amount'] = '未结算';
$_LANG['suppliers_percent'] = "结算比例";
$_LANG['suppliers_order_list'] = "供货商订单结算列表";

$_LANG['shop_list_administration'] = "商城所有供应商结算订单相关信息管理。";
$_LANG['look_order_edit'] = "可查看供应商所有的结算订单，并进行相关操作。";
$_LANG['where_order_look'] = "订单结算只针对已付款，已完成的订单";
$_LANG['set_settlement'] = "结算比例在编辑供货商处设置";


//供应商结算订单列表
$_LANG['order_sn'] = '订单号';
$_LANG['add_time'] = '添加时间';
$_LANG['consignee'] = '收货人';
$_LANG['order_amount'] = '总金额';
$_LANG['effective_amount_into'] = '有效结算';
$_LANG['brokerage_amount'] = '应结金额';
$_LANG['all_status'] = '订单状态';
$_LANG['settlement_state'] = '结算状态';
$_LANG['percent_value'] = '结算百分比';
$_LANG['is_brokerage_amount'] = '已结';
$_LANG['no_brokerage_amount'] = '未结';
$_LANG['is_settlement'] = '已结算';
$_LANG['no_settlement'] = '未结算';
$_LANG['all_brokerage_amount'] = '应结总金额';

$_LANG['not_settlement'] = '您已结算，钱已进入供应商账户，不允许二次操作';
$_LANG['01_admin_settlement'] = "【%s】平台操作供应商订单【%s】应结金额";
$_LANG['all_order'] = '所有订单';
$_LANG['export_merchant_commission'] = '导出商家结算表格'; //liu

/* 佣金订单导出 */
$_LANG['down']['order_sn'] = '订单编号';
$_LANG['down']['short_order_time'] = '下单时间';
$_LANG['down']['consignee_address'] = '收货人';
$_LANG['down']['total_fee'] = '总金额';
$_LANG['down']['shipping_fee'] = '运费';
$_LANG['down']['discount'] = '折扣';
$_LANG['down']['coupons'] = '优惠券';
$_LANG['down']['integral_money'] = '积分';
$_LANG['down']['bonus'] = '红包';
$_LANG['down']['return_amount_price'] = '退款金额';
$_LANG['down']['brokerage_amount_price'] = '有效分成金额';
$_LANG['down']['effective_amount_price'] = '订单状态';
$_LANG['down']['settlement_status'] = '应结金额';
$_LANG['down']['ordersTatus'] = '结算状态';
$_LANG['export_all_suppliers'] = '导出表格';

$_LANG['suppliers_settlement_order_list'] = "供应商结算的所有订单列表。";
$_LANG['look_log'] = "可进行查看操作日志。";
$_LANG['set_settlement_remind'] = "结算百分比数值越大，商家获取的结算金额就越大。";
$_LANG['settlement_formula'] = "总金额 - 退款金额 = 有效结算";
$_LANG['amount_formula'] = "有效结算 * 结算百分比 = 应结金额";
$_LANG['order_settlement_remind'] = "订单结算只针对已付款，已完成的订单或者部分退款订单";

$_LANG['settlement_status'] = "结算状态";
$_LANG['log'] = "操作日志";
$_LANG['order_settlement_window'] = "订单结算导出弹窗";

return $_LANG;
