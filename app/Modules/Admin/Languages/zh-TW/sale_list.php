<?php

/* 訂單搜索 */
$_LANG['order_sn'] = '訂單號';
$_LANG['consignee'] = '收貨人';
$_LANG['all_status'] = '訂單狀態';

$_LANG['cs'][OS_UNCONFIRMED] = '待確認';
$_LANG['cs'][CS_AWAIT_PAY] = '待付款';
$_LANG['cs'][CS_AWAIT_SHIP] = '待發貨';
$_LANG['cs'][CS_FINISHED] = '已完成';
$_LANG['cs'][PS_PAYING] = '付款中';
$_LANG['cs'][OS_CANCELED] = '取消';
$_LANG['cs'][OS_INVALID] = '無效';
$_LANG['cs'][OS_RETURNED] = '退貨';
$_LANG['cs'][OS_SHIPPED_PART] = '部分發貨';

/* 訂單狀態 */
$_LANG['os'][OS_UNCONFIRMED] = '未確認';
$_LANG['os'][OS_CONFIRMED] = '已確認';
$_LANG['os'][OS_CANCELED] = '<font color="red"> 取消</font>';
$_LANG['os'][OS_INVALID] = '<font color="red">無效</font>';
$_LANG['os'][OS_RETURNED] = '<font color="red">退貨</font>';
$_LANG['os'][OS_SPLITED] = '已分單';
$_LANG['os'][OS_SPLITING_PART] = '部分分單';
$_LANG['os'][OS_RETURNED_PART] = '部分已退貨';
$_LANG['os'][OS_ONLY_REFOUND] = '僅退款';

$_LANG['ss'][SS_UNSHIPPED] = '未發貨';
$_LANG['ss'][SS_PREPARING] = '配貨中';
$_LANG['ss'][SS_SHIPPED] = '已發貨';
$_LANG['ss'][SS_RECEIVED] = '收貨確認';
$_LANG['ss'][SS_SHIPPED_PART] = '已發貨(部分商品)';
$_LANG['ss'][SS_SHIPPED_ING] = '發貨中';

$_LANG['ps'][PS_UNPAYED] = '未付款';
$_LANG['ps'][PS_PAYING] = '付款中';
$_LANG['ps'][PS_PAYED] = '已付款';

$_LANG['sale_statistics'] = '銷售統計';
$_LANG['start_end_date'] = '起止時間';
$_LANG['goods_sn'] = '貨號';
$_LANG['order_date_type'] = '訂單時間類型';
$_LANG['label_update_time'] = '發貨時間';
$_LANG['label_add_time'] = '下單時間';
$_LANG['order_status'] = '訂單狀態';
$_LANG['shipping_status'] = '發貨狀態';
$_LANG['cat_name'] = '分類名稱';
$_LANG['query'] = '查詢';
$_LANG['export_list'] = '導出列表';
$_LANG['export_data'] = '導出數據';
$_LANG['goods_number'] = '數量';
$_LANG['sell_price'] = '售價';
$_LANG['total_fee'] = '總金額';
$_LANG['from_order'] = '訂單來源';
$_LANG['cashdesk'] = '收銀台';
$_LANG['sell_date'] = '售出日期';

/* 頁面頂部操作提示 */
$_LANG['operation_prompt_content']['list'][0] = '統計所有銷售統計信息。';
$_LANG['operation_prompt_content']['list'][1] = '根據訂單時間、訂單類型、發貨狀態等篩選出某個時間段的訂單信息。';

$_LANG['label_time_type'] = '時間類型：';
$_LANG['label_order_source'] = '訂單來源：';
$_LANG['label_order_status'] = '訂單狀態：';
$_LANG['label_deliver_status'] = '發貨狀態：';
$_LANG['label_goods_sn'] = '商品貨號：';
$_LANG['accord_deliver_time'] = '按發貨時間';
$_LANG['accord_order_time'] = '按下單時間';

return $_LANG;
