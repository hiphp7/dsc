<?php

$_LANG['separate_name'] = '推荐分成';
$_LANG['order_id'] = '订单号';
$_LANG['affiliate_separate'] = '分成';
$_LANG['affiliate_cancel'] = '取消';
$_LANG['affiliate_rollback'] = '撤销';
$_LANG['log_info'] = '操作信息';
$_LANG['edit_ok'] = '操作成功';
$_LANG['edit_fail'] = '操作失败';
$_LANG['separate_info'] = '订单号 %s, 分成:金钱 %s 积分 %s';
$_LANG['separate_info2'] = '用户ID %s ( %s ), 分成:金钱 %s 积分 %s';
$_LANG['sch_order'] = '搜索订单号/用户名/昵称';

$_LANG['sch_stats']['name'] = '操作状态';
$_LANG['sch_stats']['info'] = '按操作状态查找:';
$_LANG['sch_stats']['all'] = '全部';
$_LANG['sch_stats'][0] = '等待处理';
$_LANG['sch_stats'][1] = '已分成';
$_LANG['sch_stats'][2] = '取消分成';
$_LANG['sch_stats'][3] = '已撤销';
$_LANG['order_stats']['name'] = '订单状态';
$_LANG['order_stats'][OS_UNCONFIRMED] = '未确认';
$_LANG['order_stats'][OS_CONFIRMED] = '已确认';
$_LANG['order_stats'][OS_CANCELED] = '已取消';
$_LANG['order_stats'][OS_INVALID] = '无效';
$_LANG['order_stats'][OS_RETURNED] = '退货';
$_LANG['order_stats'][OS_SPLITED] = '已分单';
$_LANG['order_stats'][OS_SPLITING_PART] = '部分分单';
$_LANG['order_stats'][OS_RETURNED_PART] = '部分已退货';
$_LANG['order_stats'][OS_ONLY_REFOUND] = '仅退款';
$_LANG['shipping_status']['name'] = '配送状态';
$_LANG['shipping_status'][SS_UNSHIPPED] = '未收货';
$_LANG['shipping_status'][SS_SHIPPED] = '已发货';
$_LANG['shipping_status'][SS_RECEIVED] = '已收货';
$_LANG['shipping_status'][SS_PREPARING] = '备货中';
$_LANG['shipping_status'][SS_SHIPPED_PART] = '已发货(部分商品)';
$_LANG['shipping_status'][SS_SHIPPED_ING] = '发货中(处理分单)';
$_LANG['shipping_status'][OS_SHIPPED_PART] = '已发货(部分商品)';
$_LANG['pay_status']['name'] = '支付状态';
$_LANG['pay_status'][PS_UNPAYED] = '未付款';
$_LANG['pay_status'][PS_PAYING] = '付款中';
$_LANG['pay_status'][PS_PAYED] = '已付款';
$_LANG['pay_status'][PS_PAYED_PART] = '部分付款--预售定金';
$_LANG['pay_status'][PS_REFOUND] = '已退款';
$_LANG['pay_status'][PS_REFOUND_PART] = '部分退款';

$_LANG['js_languages']['cancel_confirm'] = '您确定要取消分成吗？此操作不能撤销。';
$_LANG['js_languages']['rollback_confirm'] = '您确定要撤销此次分成吗？';
$_LANG['js_languages']['separate_confirm'] = '您确定要分成吗？';
$_LANG['loginfo'][0] = '用户id:';
$_LANG['loginfo'][1] = '现金:';
$_LANG['loginfo'][2] = '积分:';
$_LANG['loginfo']['cancel'] = '分成被管理员取消！';

$_LANG['separate_type'] = '分成类型';
$_LANG['batch_separate'] = '批量分成';
$_LANG['separate_by'][0] = '推荐注册分成';
$_LANG['separate_by'][1] = '推荐订单分成';
$_LANG['separate_by'][-1] = '推荐注册分成';
$_LANG['separate_by'][-2] = '推荐订单分成';

$_LANG['show_affiliate_orders'] = '此列表显示此用户推荐的订单信息。';
$_LANG['back_note'] = '返回会员编辑页面';

/* 页面顶部操作提示 */
$_LANG['operation_prompt_content'][0] = '分成订单信息管理。';
$_LANG['operation_prompt_content'][1] = '可搜索分成订单号查询分成订单。';

return $_LANG;
