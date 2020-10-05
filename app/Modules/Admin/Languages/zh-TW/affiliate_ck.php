<?php

$_LANG['separate_name'] = '推薦分成';
$_LANG['order_id'] = '訂單號';
$_LANG['affiliate_separate'] = '分成';
$_LANG['affiliate_cancel'] = '取消';
$_LANG['affiliate_rollback'] = '撤銷';
$_LANG['log_info'] = '操作信息';
$_LANG['edit_ok'] = '操作成功';
$_LANG['edit_fail'] = '操作失敗';
$_LANG['separate_info'] = '訂單號 %s, 分成:金錢 %s 積分 %s';
$_LANG['separate_info2'] = '用戶ID %s ( %s ), 分成:金錢 %s 積分 %s';
$_LANG['sch_order'] = '搜索訂單號/用戶名/昵稱';

$_LANG['sch_stats']['name'] = '操作狀態';
$_LANG['sch_stats']['info'] = '按操作狀態查找:';
$_LANG['sch_stats']['all'] = '全部';
$_LANG['sch_stats'][0] = '等待處理';
$_LANG['sch_stats'][1] = '已分成';
$_LANG['sch_stats'][2] = '取消分成';
$_LANG['sch_stats'][3] = '已撤銷';
$_LANG['order_stats']['name'] = '訂單狀態';
$_LANG['order_stats'][OS_UNCONFIRMED] = '未確認';
$_LANG['order_stats'][OS_CONFIRMED] = '已確認';
$_LANG['order_stats'][OS_CANCELED] = '已取消';
$_LANG['order_stats'][OS_INVALID] = '無效';
$_LANG['order_stats'][OS_RETURNED] = '退貨';
$_LANG['order_stats'][OS_SPLITED] = '已分單';
$_LANG['order_stats'][OS_SPLITING_PART] = '部分分單';
$_LANG['order_stats'][OS_RETURNED_PART] = '部分已退貨';
$_LANG['order_stats'][OS_ONLY_REFOUND] = '僅退款';
$_LANG['shipping_status']['name'] = '配送狀態';
$_LANG['shipping_status'][SS_UNSHIPPED] = '未收貨';
$_LANG['shipping_status'][SS_SHIPPED] = '已發貨';
$_LANG['shipping_status'][SS_RECEIVED] = '已收貨';
$_LANG['shipping_status'][SS_PREPARING] = '備貨中';
$_LANG['shipping_status'][SS_SHIPPED_PART] = '已發貨(部分商品)';
$_LANG['shipping_status'][SS_SHIPPED_ING] = '發貨中(處理分單)';
$_LANG['shipping_status'][OS_SHIPPED_PART] = '已發貨(部分商品)';
$_LANG['pay_status']['name'] = '支付狀態';
$_LANG['pay_status'][PS_UNPAYED] = '未付款';
$_LANG['pay_status'][PS_PAYING] = '付款中';
$_LANG['pay_status'][PS_PAYED] = '已付款';
$_LANG['pay_status'][PS_PAYED_PART] = '部分付款--預售定金';
$_LANG['pay_status'][PS_REFOUND] = '已退款';
$_LANG['pay_status'][PS_REFOUND_PART] = '部分退款';

$_LANG['js_languages']['cancel_confirm'] = '您確定要取消分成嗎？此操作不能撤銷。';
$_LANG['js_languages']['rollback_confirm'] = '您確定要撤銷此次分成嗎？';
$_LANG['js_languages']['separate_confirm'] = '您確定要分成嗎？';
$_LANG['loginfo'][0] = '用戶id:';
$_LANG['loginfo'][1] = '現金:';
$_LANG['loginfo'][2] = '積分:';
$_LANG['loginfo']['cancel'] = '分成被管理員取消！';

$_LANG['separate_type'] = '分成類型';
$_LANG['batch_separate'] = '批量分成';
$_LANG['separate_by'][0] = '推薦注冊分成';
$_LANG['separate_by'][1] = '推薦訂單分成';
$_LANG['separate_by'][-1] = '推薦注冊分成';
$_LANG['separate_by'][-2] = '推薦訂單分成';

$_LANG['show_affiliate_orders'] = '此列表顯示此用戶推薦的訂單信息。';
$_LANG['back_note'] = '返回會員編輯頁面';

/* 頁面頂部操作提示 */
$_LANG['operation_prompt_content'][0] = '分成訂單信息管理。';
$_LANG['operation_prompt_content'][1] = '可搜索分成訂單號查詢分成訂單。';

return $_LANG;
