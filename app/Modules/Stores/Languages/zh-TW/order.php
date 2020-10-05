<?php

$_LANG['update_shipping'] = "將【%s】配送方式修改為【%s】";
$_LANG['self_motion_goods'] = "自動確認收貨";

$_LANG['order_ship_delivery'] = "發貨單流水號：【%s】";
$_LANG['order_ship_invoice'] = "發貨單號：【%s】";
$_LANG['edit_order_invoice'] = "將發貨單號【%s】修改為：【%s】";

$_LANG['add_order_info'] = "【%s】手工添加訂單";
$_LANG['shipping_refund'] = "編輯訂單導致退款：%s";

$_LANG['01_stores_pick_goods'] = "訂單未付款，無法提貨";
$_LANG['02_stores_pick_goods'] = "操作成功，提貨完成";
$_LANG['03_stores_pick_goods'] = "驗證碼不正確，請重新輸入";
$_LANG['04_stores_pick_goods'] = "請輸入6位提貨碼";

$_LANG['order_label'] = '信息標簽';
$_LANG['amount_label'] = '金額標簽';

$_LANG['bar_code'] = '條形碼';
$_LANG['label_bar_code'] = '條形碼：';

$_LANG['region'] = '配送區域';
$_LANG['order_shipped_sms'] = '您的訂單%s已於%s發貨'; //wang

$_LANG['setorder_nopay'] = '設置為未付款';
$_LANG['setorder_cancel'] = '設置為未付款';

//退換貨 start
$_LANG['rf'][RF_APPLICATION] = '由用戶寄回';
$_LANG['rf'][RF_RECEIVE] = '收到退換貨';
$_LANG['rf'][RF_SWAPPED_OUT_SINGLE] = '換出商品寄出【分單】';
$_LANG['rf'][RF_SWAPPED_OUT] = '換出商品寄出';
$_LANG['rf'][RF_COMPLETE] = '完成';
$_LANG['rf'][REFUSE_APPLY] = '申請被拒';
$_LANG['ff'][FF_NOMAINTENANCE] = '未維修';
$_LANG['ff'][FF_MAINTENANCE] = '已維修';
$_LANG['ff'][FF_REFOUND] = '已退款';
$_LANG['ff'][FF_NOREFOUND] = '未退款';
$_LANG['ff'][FF_NOEXCHANGE] = '未換貨';
$_LANG['ff'][FF_EXCHANGE] = '已換貨';
$_LANG['refund_money'] = '退款金額';
$_LANG['money'] = '金額';
$_LANG['shipping_money'] = '元&nbsp;&nbsp;運費';
$_LANG['is_shipping_money'] = '退運費';
$_LANG['no_shipping_money'] = '不退運費';
$_LANG['return_user_line'] = '線下退款';
$_LANG['return_reason'] = '退換貨原因';
$_LANG['whether_display'] = '是否顯示';
$_LANG['return_baitiao'] = '退回白條余額(如果是余額與白條混合支付,余額部分退回余額)';//bylu;
//退換貨 end

//ecmoban模板堂 --zhuo start
$_LANG['auto_delivery_time'] = '自動確認收貨時間：';
$_LANG['dateType'][0] = '天';
//ecmoban模板堂 --zhuo end

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
$_LANG['cs'][OS_SHIPPED_PART] = '待收貨';

/* 訂單狀態 */
$_LANG['os'][OS_UNCONFIRMED] = '未確認';
$_LANG['os'][OS_CONFIRMED] = '已確認';
$_LANG['os'][OS_CANCELED] = '<font color="red"> 取消</font>';
$_LANG['os'][OS_INVALID] = '<font color="red">無效</font>';
$_LANG['os'][OS_RETURNED] = '<font color="red">退貨</font>';
$_LANG['os'][OS_SPLITED] = '已分單';
$_LANG['os'][OS_SPLITING_PART] = '部分分單';
$_LANG['os'][OS_RETURNED_PART] = '<font color="red">部分已退貨</font>';
$_LANG['os'][OS_ONLY_REFOUND] = '<font color="red">僅退款</font>';

$_LANG['ss'][SS_UNSHIPPED] = '未發貨';
$_LANG['ss'][SS_PREPARING] = '配貨中';
$_LANG['ss'][SS_SHIPPED] = '已發貨';
$_LANG['ss'][SS_RECEIVED] = '收貨確認';
$_LANG['ss'][SS_SHIPPED_PART] = '已發貨(部分商品)';
$_LANG['ss'][SS_SHIPPED_ING] = '發貨中';

$_LANG['ps'][PS_UNPAYED] = '未付款';
$_LANG['ps'][PS_PAYING] = '付款中';
$_LANG['ps'][PS_PAYED] = '已付款';
$_LANG['ps'][PS_PAYED_PART] = '部分付款(定金)';
$_LANG['ps'][PS_REFOUND] = '已退款';
$_LANG['ps'][PS_REFOUND_PART] = '部分退款';

$_LANG['ss_admin'][SS_SHIPPED_ING] = '發貨中（前台狀態：未發貨）';
/* 訂單操作 */
$_LANG['label_operable_act'] = '當前可執行操作：';
$_LANG['label_action_note'] = '操作備註：';
$_LANG['label_invoice_note'] = '發貨備註：';
$_LANG['label_invoice_no'] = '發貨單號：';
$_LANG['label_cancel_note'] = '取消原因：';
$_LANG['notice_cancel_note'] = '（會記錄在商家給客戶的留言中）';
$_LANG['op_confirm'] = '確認';
$_LANG['refuse'] = '拒絕';
$_LANG['refuse_apply'] = '拒絕申請';
$_LANG['is_refuse_apply'] = '已拒絕申請';
$_LANG['op_pay'] = '付款';
$_LANG['op_prepare'] = '配貨';
$_LANG['op_ship'] = '發貨';
$_LANG['op_cancel'] = '取消';
$_LANG['op_invalid'] = '無效';
$_LANG['op_return'] = '退貨';
$_LANG['op_unpay'] = '設為未付款';
$_LANG['op_unship'] = '未發貨';
$_LANG['op_cancel_ship'] = '取消發貨';
$_LANG['op_receive'] = '已收貨';
$_LANG['op_assign'] = '指派給';
$_LANG['op_after_service'] = '售後';
$_LANG['act_ok'] = '操作成功';
$_LANG['act_false'] = '操作失敗';
$_LANG['act_ship_num'] = '此單發貨數量不能超出訂單商品數量';
$_LANG['act_good_vacancy'] = '商品已缺貨';
$_LANG['act_good_delivery'] = '貨已發完';
$_LANG['notice_gb_ship'] = '備註：團購活動未處理為成功前，不能發貨';
$_LANG['back_list'] = '返回列表頁';
$_LANG['op_remove'] = '刪除';
$_LANG['op_you_can'] = '您可進行的操作';
$_LANG['op_split'] = '生成發貨單';
$_LANG['op_to_delivery'] = '去發貨';
$_LANG['op_swapped_out_single'] = '換出商品寄出【分單】';
$_LANG['op_swapped_out'] = '換出商品寄出';
$_LANG['op_complete'] = '完成退換貨';
$_LANG['op_refuse_apply'] = '拒絕申請';
$_LANG['baitiao_by_stages'] = '白條分期';
$_LANG['expired'] = '已到期';
$_LANG['unable_to_click'] = '無法點擊';

/* 訂單列表 */
$_LANG['order_amount'] = '應付金額';
$_LANG['total_fee'] = '總金額';
$_LANG['shipping_name'] = '配送方式';
$_LANG['pay_name'] = '支付方式';
$_LANG['address'] = '地址';
$_LANG['order_time'] = '下單時間';
$_LANG['trade_snapshot'] = '交易快照';
$_LANG['detail'] = '查看';
$_LANG['phone'] = '電話';
$_LANG['group_buy'] = '（團購）';
$_LANG['error_get_goods_info'] = '獲取訂單商品信息錯誤';
$_LANG['exchange_goods'] = '（積分兌換）';
$_LANG['auction'] = '（拍賣活動）';
$_LANG['snatch'] = '（奪寶奇兵）';
$_LANG['presale'] = '（預售）';

$_LANG['js_languages']['remove_confirm'] = '刪除訂單將清除該訂單的所有信息。您確定要這么做嗎？';

/* 訂單搜索 */
$_LANG['label_buyer'] = '購貨人：';
$_LANG['label_order_sn'] = '訂單號：';
$_LANG['label_all_status'] = '訂單狀態：';
$_LANG['label_user_name'] = '購貨人：';
$_LANG['label_consignee'] = '收貨人：';
$_LANG['label_email'] = '電子郵件：';
$_LANG['label_address'] = '收貨地址：';
$_LANG['label_zipcode'] = '郵政編碼：';
$_LANG['label_tel'] = '電話號碼：';
$_LANG['label_mobile'] = '手機號碼：';
$_LANG['label_shipping'] = '配送方式：';
$_LANG['label_payment'] = '支付方式：';
$_LANG['label_order_status'] = '訂單狀態：';
$_LANG['label_pay_status'] = '付款狀態：';
$_LANG['label_shipping_status'] = '發貨狀態：';
$_LANG['label_area'] = '所在地區：';
$_LANG['label_time'] = '下單時間：';

/* 訂單詳情 */
$_LANG['prev'] = '前一個訂單';
$_LANG['next'] = '後一個訂單';
$_LANG['print_order'] = '列印訂單';
$_LANG['print_shipping'] = '列印快遞單';
$_LANG['print_order_sn'] = '訂單編號：';
$_LANG['print_buy_name'] = '購 貨 人：';
$_LANG['label_consignee_address'] = '收貨地址：';
$_LANG['no_print_shipping'] = '很抱歉,目前您還沒有設置列印快遞單模板.不能進行列印';
$_LANG['suppliers_no'] = '不指定供貨商本店自行處理';
$_LANG['restaurant'] = '本店';

$_LANG['order_info'] = '訂單信息';
$_LANG['base_info'] = '基本信息';
$_LANG['other_info'] = '其他信息';
$_LANG['consignee_info'] = '收貨人信息';
$_LANG['fee_info'] = '費用信息';
$_LANG['action_info'] = '操作信息';
$_LANG['shipping_info'] = '配送信息';

$_LANG['label_how_oos'] = '缺貨處理：';
$_LANG['label_how_surplus'] = '余額處理：';
$_LANG['label_pack'] = '包裝：';
$_LANG['label_card'] = '賀卡：';
$_LANG['label_card_message'] = '賀卡祝福語：';
$_LANG['label_order_time'] = '下單時間：';
$_LANG['label_pay_time'] = '付款時間：';
$_LANG['label_shipping_time'] = '發貨時間：';
$_LANG['label_sign_building'] = '地址別名：';
$_LANG['label_best_time'] = '送貨時間：';
$_LANG['label_inv_type'] = '發票類型：';
$_LANG['label_inv_payee'] = '發票抬頭：';
$_LANG['label_inv_content'] = '發票內容：';
$_LANG['label_postscript'] = '買家留言：';
$_LANG['label_region'] = '所在地區：';

$_LANG['label_shop_url'] = '網址：';
$_LANG['label_shop_address'] = '地址：';
$_LANG['label_service_phone'] = '電話：';
$_LANG['label_print_time'] = '列印時間：';

$_LANG['label_suppliers'] = '選擇供貨商：';
$_LANG['label_agency'] = '辦事處：';
$_LANG['suppliers_name'] = '供貨商';

$_LANG['product_sn'] = '貨品號';
$_LANG['goods_info'] = '商品信息';
$_LANG['goods_name'] = '商品名稱';
$_LANG['goods_name_brand'] = '商品名稱 [ 品牌 ]';
$_LANG['goods_sn'] = '貨號';
$_LANG['goods_price'] = '價格';
$_LANG['give_integral'] = '商品贈送積分';
$_LANG['goods_number'] = '數量';
$_LANG['goods_attr'] = '屬性';
$_LANG['goods_delivery'] = '已發貨數量';
$_LANG['goods_delivery_curr'] = '此單發貨數量';
$_LANG['storage'] = '庫存';
$_LANG['subtotal'] = '小計';
$_LANG['amount_return'] = '商品退款';
$_LANG['label_total'] = '合計：';
$_LANG['label_total_weight'] = '商品總重量：';
$_LANG['label_total_cost'] = '合計商品成本：';
$_LANG['measure_unit'] = '單位';
$_LANG['contain_content'] = '包含內容';
$_LANG['application_refund'] = '申請退款';

$_LANG['return_discount'] = '享受折扣';

$_LANG['label_goods_amount'] = '商品總金額：';
$_LANG['label_discount'] = '折扣：';
$_LANG['label_tax'] = '發票稅額：';
$_LANG['label_shipping_fee'] = '配送費用：';
$_LANG['label_insure_fee'] = '保價費用：';
$_LANG['label_insure_yn'] = '是否保價：';
$_LANG['label_pay_fee'] = '支付費用：';
$_LANG['label_pack_fee'] = '包裝費用：';
$_LANG['label_card_fee'] = '賀卡費用：';
$_LANG['label_money_paid'] = '已付款金額：';
$_LANG['label_surplus'] = '使用余額：';
$_LANG['label_integral'] = '使用積分：';
$_LANG['label_bonus'] = '使用紅包：';
$_LANG['label_value_card'] = '使用儲值卡：';
$_LANG['label_coupons'] = '使用優惠券：';
$_LANG['label_order_amount'] = '訂單總金額：';
$_LANG['label_money_dues'] = '應付款金額：';
$_LANG['label_money_refund'] = '應退款金額：';
$_LANG['label_to_buyer'] = '商家給客戶的留言：';
$_LANG['save_order'] = '保存訂單';
$_LANG['notice_gb_order_amount'] = '（備註：團購如果有保證金，第一次只需支付保證金和相應的支付費用）';
$_LANG['formated_order_amount'] = '訂單總金額';
$_LANG['stores_info'] = '門店信息';

$_LANG['action_user'] = '操作者';
$_LANG['action_time'] = '操作時間';
$_LANG['return_status'] = '操作';
$_LANG['refound_status'] = '狀態';
$_LANG['order_status'] = '訂單狀態';
$_LANG['pay_status'] = '付款狀態';
$_LANG['shipping_status'] = '發貨狀態';
$_LANG['action_note'] = '備注';
$_LANG['pay_note'] = '支付備註：';
$_LANG['action_jilu'] = '操作記錄';
$_LANG['not_action_jilu'] = '當前還沒有操作記錄';

$_LANG['sms_time_format'] = 'm月j日G時';
$_LANG['order_splited_sms'] = '您的訂單%s,%s正在%s [%s]';
$_LANG['order_removed'] = '訂單刪除成功。';
$_LANG['return_list'] = '返回訂單列表';
$_LANG['order_remove_failure'] = '%s 存在單商品退換貨訂單，訂單刪除失敗。';

/* 訂單處理提示 */
$_LANG['surplus_not_enough'] = '該訂單使用 %s 余額支付，現在用戶余額不足';
$_LANG['integral_not_enough'] = '該訂單使用 %s 積分支付，現在用戶積分不足';
$_LANG['bonus_not_available'] = '該訂單使用紅包支付，現在紅包不可用';

/* 購貨人信息 */
$_LANG['display_buyer'] = '顯示購貨人信息';
$_LANG['buyer_info'] = '購貨人信息';
$_LANG['pay_points'] = '消費積分';
$_LANG['rank_points'] = '等級積分';
$_LANG['user_money'] = '賬戶余額';
$_LANG['email'] = '電子郵件';
$_LANG['rank_name'] = '會員等級';
$_LANG['bonus_count'] = '紅包數量';
$_LANG['zipcode'] = '郵編';
$_LANG['tel'] = '電話';
$_LANG['mobile'] = '手機號碼';
$_LANG['leaving_message'] = '留言';

/*增值稅發票信息*/
$_LANG['vat_info'] = '增值稅發票信息';
$_LANG['vat_name'] = '單位名稱';
$_LANG['vat_taxid'] = '納稅人識別碼';
$_LANG['vat_company_address'] = '注冊地址';
$_LANG['vat_company_telephone'] = '注冊電話';
$_LANG['vat_bank_of_deposit'] = '開戶銀行';
$_LANG['vat_bank_account'] = '銀行賬戶';

/* 合並訂單 */
$_LANG['seller_order_sn_same'] = '要合並的兩個訂單所屬商家必須相同';
$_LANG['merge_order_main_count'] = '要合並的訂單不能為主訂單';
$_LANG['order_sn_not_null'] = '請填寫要合並的訂單號';
$_LANG['two_order_sn_same'] = '要合並的兩個訂單號不能相同';
$_LANG['order_not_exist'] = '定單 %s 不存在';
$_LANG['os_not_unconfirmed_or_confirmed'] = '%s 的訂單狀態不是「未確認」或「已確認」';
$_LANG['ps_not_unpayed'] = '訂單 %s 的付款狀態不是「未付款」';
$_LANG['ss_not_unshipped'] = '訂單 %s 的發貨狀態不是「未發貨」';
$_LANG['order_user_not_same'] = '要合並的兩個訂單不是同一個用戶下的';
$_LANG['merge_invalid_order'] = '對不起，您選擇合並的訂單不允許進行合並的操作。';

$_LANG['merge_order'] = '合並訂單';
$_LANG['from_order_sn'] = '從訂單：';
$_LANG['to_order_sn'] = '主訂單：';
$_LANG['merge'] = '合並';
$_LANG['notice_order_sn'] = '當兩個訂單不一致時，合並後的訂單信息（如：支付方式、配送方式、包裝、賀卡、紅包等）以主訂單為准。';
$_LANG['js_languages']['confirm_merge'] = '您確實要合並這兩個訂單嗎？';

/* 批處理 */
$_LANG['pls_select_order'] = '請選擇您要操作的訂單';
$_LANG['no_fulfilled_order'] = '沒有滿足操作條件的訂單。';
$_LANG['updated_order'] = '更新的訂單：';
$_LANG['order'] = '訂單：';
$_LANG['confirm_order'] = '以下訂單無法設置為確認狀態';
$_LANG['invalid_order'] = '以下訂單無法設置為無效';
$_LANG['cancel_order'] = '以下訂單無法取消';
$_LANG['remove_order'] = '以下訂單無法被移除';

/* 編輯訂單列印模板 */
$_LANG['edit_order_templates'] = '編輯訂單列印模板';
$_LANG['template_resetore'] = '還原模板';
$_LANG['edit_template_success'] = '編輯訂單列印模板操作成功!';
$_LANG['remark_fittings'] = '（配件）';
$_LANG['remark_gift'] = '（贈品）';
$_LANG['remark_favourable'] = '（特惠品）';
$_LANG['remark_package'] = '（禮包）';
$_LANG['remark_package_goods'] = '（禮包內產品）';

/* 訂單來源統計 */
$_LANG['from_order'] = '訂單來源';
$_LANG['referer'] = '來源';
$_LANG['from_ad_js'] = '廣告：';
$_LANG['from_goods_js'] = '商品站外JS投放';
$_LANG['from_self_site'] = '來自本站';
$_LANG['from'] = '來自站點：';

/* 添加、編輯訂單 */
$_LANG['add_order'] = '添加訂單';
$_LANG['edit_order'] = '編輯訂單';
$_LANG['step']['user'] = '請選擇您要為哪個會員下訂單';
$_LANG['step']['goods'] = '選擇商品';
$_LANG['step']['consignee'] = '設置收貨人信息';
$_LANG['step']['shipping'] = '選擇配送方式';
$_LANG['step']['payment'] = '選擇支付方式';
$_LANG['step']['other'] = '設置其他信息';
$_LANG['step']['money'] = '設置費用';
$_LANG['anonymous'] = '匿名用戶';
$_LANG['by_useridname'] = '按會員編號或會員名搜索';
$_LANG['button_prev'] = '上一步';
$_LANG['button_next'] = '下一步';
$_LANG['button_finish'] = '完成';
$_LANG['button_cancel'] = '取消';
$_LANG['name'] = '名稱';
$_LANG['desc'] = '描述';
$_LANG['shipping_fee'] = '配送費';
$_LANG['free_money'] = '免費額度';
$_LANG['insure'] = '保價費';
$_LANG['pay_fee'] = '手續費';
$_LANG['pack_fee'] = '包裝費';
$_LANG['card_fee'] = '賀卡費';
$_LANG['no_pack'] = '不要包裝';
$_LANG['no_card'] = '不要賀卡';
$_LANG['add_to_order'] = '加入訂單';
$_LANG['calc_order_amount'] = '計算訂單金額';
$_LANG['available_surplus'] = '可用余額：';
$_LANG['available_integral'] = '可用積分：';
$_LANG['available_bonus'] = '可用紅包：';
$_LANG['admin'] = '管理員添加';
$_LANG['search_goods'] = '按商品編號或商品名稱或商品貨號搜索';
$_LANG['category'] = '分類';
$_LANG['order_category'] = '訂單分類';
$_LANG['brand'] = '品牌';
$_LANG['user_money_not_enough'] = '用戶余額不足';
$_LANG['pay_points_not_enough'] = '用戶積分不足';
$_LANG['money_paid_enough'] = '已付款金額比商品總金額和各種費用之和還多，請先退款';
$_LANG['price_note'] = '備註：商品價格中已包含屬性加價';
$_LANG['select_pack'] = '選擇包裝';
$_LANG['select_card'] = '選擇賀卡';
$_LANG['select_shipping'] = '請先選擇配送方式';
$_LANG['want_insure'] = '我要保價';
$_LANG['update_goods'] = '更新商品';
$_LANG['notice_user'] = '<strong>注意：</strong>搜索結果只顯示前20條記錄，如果沒有找到相' .
    '應會員，請更精確地查找。另外，如果該會員是從論壇注冊的且沒有在商城登錄過，' .
    '也無法找到，需要先在商城登錄。';
$_LANG['amount_increase'] = '由於您修改了訂單，導致訂單總金額增加，需要再次付款';
$_LANG['amount_decrease'] = '由於您修改了訂單，導致訂單總金額減少，需要退款';
$_LANG['continue_shipping'] = '由於您修改了收貨人所在地區，導致原來的配送方式不再可用，請重新選擇配送方式';
$_LANG['continue_payment'] = '由於您修改了配送方式，導致原來的支付方式不再可用，請重新選擇配送方式';
$_LANG['refund'] = '退款';
$_LANG['cannot_edit_order_shipped'] = '您不能修改已發貨的訂單';
$_LANG['cannot_edit_order_payed'] = '您不能修改已付款的訂單';
$_LANG['address_list'] = '從已有收貨地址中選擇：';
$_LANG['order_amount_change'] = '訂單總金額由 %s 變為 %s';
$_LANG['shipping_note'] = '說明：因為訂單已發貨，修改配送方式將不會改變配送費和保價費。';
$_LANG['change_use_surplus'] = '編輯訂單 %s ，改變使用預付款支付的金額';
$_LANG['change_use_integral'] = '編輯訂單 %s ，改變使用積分支付的數量';
$_LANG['return_order_surplus'] = '由於取消、無效或退貨操作，退回支付訂單 %s 時使用的預付款';
$_LANG['return_order_integral'] = '由於取消、無效或退貨操作，退回支付訂單 %s 時使用的積分';
$_LANG['order_gift_integral'] = '訂單 %s 贈送的積分';
$_LANG['return_order_gift_integral'] = '由於退貨或未發貨操作，退回訂單 %s 贈送的積分';
$_LANG['invoice_no_mall'] = '多個發貨單號，請用英文逗號（「,」）隔開。';

$_LANG['js_languages']['input_price'] = '自定義價格';
$_LANG['js_languages']['pls_search_user'] = '請搜索並選擇會員';
$_LANG['js_languages']['confirm_drop'] = '確認要刪除該商品嗎？';
$_LANG['js_languages']['invalid_goods_number'] = '商品數量不正確';
$_LANG['js_languages']['pls_search_goods'] = '請搜索並選擇商品';
$_LANG['js_languages']['pls_select_area'] = '請完整選擇所在地區';
$_LANG['js_languages']['pls_select_shipping'] = '請選擇配送方式';
$_LANG['js_languages']['pls_select_payment'] = '請選擇支付方式';
$_LANG['js_languages']['pls_select_pack'] = '請選擇包裝';
$_LANG['js_languages']['pls_select_card'] = '請選擇賀卡';
$_LANG['js_languages']['pls_input_note'] = '請您填寫備注！';
$_LANG['js_languages']['pls_input_cancel'] = '請您填寫取消原因！';
$_LANG['js_languages']['pls_select_refund'] = '請選擇退款方式！';
$_LANG['js_languages']['pls_select_agency'] = '請選擇辦事處！';
$_LANG['js_languages']['pls_select_other_agency'] = '該訂單現在就屬於這個辦事處，請選擇其他辦事處！';
$_LANG['js_languages']['loading'] = '載入中...';

/* 訂單操作 */
$_LANG['order_operate'] = '訂單操作：';
$_LANG['label_refund_amount'] = '退款金額：';
$_LANG['label_handle_refund'] = '退款方式：';
$_LANG['label_refund_note'] = '退款說明：';
$_LANG['return_user_money'] = '退回用戶余額';
$_LANG['create_user_account'] = '生成退款申請';
$_LANG['not_handle'] = '不處理，誤操作時選擇此項';

$_LANG['order_refund'] = '訂單退款：%s';
$_LANG['order_pay'] = '訂單支付：%s';

$_LANG['send_mail_fail'] = '發送郵件失敗';

$_LANG['send_message'] = '發送/查看留言';

/* 發貨單操作 */
$_LANG['delivery_operate'] = '發貨單操作：';
$_LANG['delivery_sn_number'] = '流水號：';
$_LANG['invoice_no_sms'] = '請填寫發貨單號！';

/* 發貨單搜索 */
$_LANG['delivery_sn'] = '發貨單';

/* 發貨單狀態 */
$_LANG['delivery_status_dt'] = '發貨單狀態';
$_LANG['delivery_status'][0] = '已發貨';
$_LANG['delivery_status'][1] = '退貨';
$_LANG['delivery_status'][2] = '正常';

/* 發貨單標簽 */
$_LANG['label_delivery_status'] = '狀態';
$_LANG['label_suppliers_name'] = '供貨商';
$_LANG['label_delivery_time'] = '生成時間';
$_LANG['label_delivery_sn'] = '發貨單流水號';
$_LANG['label_add_time'] = '下單時間';
$_LANG['label_update_time'] = '發貨時間';
$_LANG['label_send_number'] = '發貨數量';
$_LANG['batch_delivery'] = '批量發貨';

/* 發貨單提示 */
$_LANG['tips_delivery_del'] = '發貨單刪除成功！';

/* 退貨單操作 */
$_LANG['back_operate'] = '退貨單操作：';

/* 退貨單標簽 */
$_LANG['return_time'] = '退貨時間：';
$_LANG['label_return_time'] = '退貨時間';
$_LANG['label_apply_time'] = '退換貨申請時間';
$_LANG['label_back_shipping'] = '退換貨配送方式';
$_LANG['label_back_invoice_no'] = '退換貨發貨單號';
$_LANG['back_order_info'] = '退貨單信息';

/* 退貨單提示 */
$_LANG['tips_back_del'] = '退貨單刪除成功！';

$_LANG['goods_num_err'] = '庫存不足，請重新選擇！';

/*大商創1.5後台新增*/
/*退換貨列表*/
$_LANG['problem_desc'] = '問題描述';
$_LANG['product_repair'] = '商品維修';
$_LANG['product_return'] = '商品退貨';
$_LANG['product_change'] = '商品換貨';
$_LANG['product_price'] = '商品價格';

$_LANG['return_sn'] = '流水號';
$_LANG['return_change_sn'] = '流水號';
$_LANG['repair'] = '維修';
$_LANG['return_goods'] = '退貨';
$_LANG['change'] = '換貨';
$_LANG['only_return_money'] = '僅退款';
$_LANG['already_repair'] = '已維修';
$_LANG['refunded'] = '已退款';
$_LANG['already_change'] = '已換貨';
$_LANG['return_change_type'] = '類型';
$_LANG['apply_time'] = '申請時間';
$_LANG['y_amount'] = '應退金額';
$_LANG['s_amount'] = '實退金額';
$_LANG['actual_return'] = '合計已退款';
$_LANG['return_change_num'] = '退換數量';
$_LANG['receipt_time'] = '簽收時間';
$_LANG['applicant'] = '申請人';
$_LANG['to_order_sn2'] = '主訂單';
$_LANG['to_order_sn3'] = '主訂單';
$_LANG['sub_order_sn'] = '子訂單';
$_LANG['sub_order_sn2'] = '子訂單';

$_LANG['return_reason'] = '退換原因';
$_LANG['reason_cate'] = '原因分類';
$_LANG['top_cate'] = '頂級分類';

$_LANG['since_some_info'] = '自提點信息';
$_LANG['since_some_name'] = '自提點名稱';
$_LANG['contacts'] = '聯系人';
$_LANG['tpnd_time'] = '提貨時間';
$_LANG['warehouse_name'] = '倉庫名稱';
$_LANG['ciscount'] = '優惠';
$_LANG['notice_delete_order'] = '(用戶會員中心：已刪除該訂單)';
$_LANG['notice_trash_order'] = '用戶已處理進入回收站';
$_LANG['order_not_operable'] = '（訂單不可操作）';

$_LANG['region'] = '地區';
$_LANG['seller_mail'] = '賣家寄出';
$_LANG['courier_sz'] = '快遞單號';
$_LANG['select_courier'] = '請選擇快遞公司';
$_LANG['fillin_courier_number'] = '請填寫快遞單號';
$_LANG['edit_return_reason'] = '編輯退換貨原因';
$_LANG['buyers_return_reason'] = '買家退換貨原因';
$_LANG['user_file_image'] = '用戶上傳圖片憑證';
$_LANG['operation_notes'] = '操作備注';
$_LANG['agree_apply'] = '同意申請';
$_LANG['receive_goods'] = '收到退回商品';
$_LANG['current_executable_operation'] = '當前可執行操作';
$_LANG['refound'] = '去退款';
$_LANG['swapped_out_single'] = '換出商品寄出【分單】';
$_LANG['swapped_out'] = '換出商品寄出';
$_LANG['complete'] = '完成退換貨';
$_LANG['after_service'] = '售後';
$_LANG['complete'] = '完成退換貨';
$_LANG['wu'] = '無';
$_LANG['not_filled'] = '未填寫';
$_LANG['seller_message'] = '賣家留言';
$_LANG['buyer_message'] = '買家留言';
$_LANG['total_stage'] = '總分期數';
$_LANG['stage'] = '期';
$_LANG['by_stage'] = '分期金額';
$_LANG['yuan_stage'] = '元/期';
$_LANG['submit_order'] = '提交訂單';
$_LANG['payment_order'] = '支付訂單';
$_LANG['seller_shipping'] = '商家發貨';
$_LANG['confirm_shipping'] = '確認收貨';
$_LANG['evaluate'] = '評價';
$_LANG['logistics_tracking'] = '物流跟蹤';
$_LANG['cashdesk'] = '收銀台';
$_LANG['info'] = '信息';
$_LANG['general_invoice'] = '普通發票';
$_LANG['personal_general_invoice'] = '個人普通發票';
$_LANG['enterprise_general_invoice'] = '企業普通發票';
$_LANG['VAT_invoice'] = '增值稅發票';
$_LANG['id_code'] = '識別碼';
$_LANG['has_been_issued'] = '已發';
$_LANG['invoice_generated'] = '發貨單已生成';
$_LANG['has_benn_refund'] = '已退款';
$_LANG['net_profit'] = '凈利潤約';
$_LANG['one_key_delivery'] = '一鍵發貨';
$_LANG['goods_delivery'] = '商品發貨';
$_LANG['search_logistics_info'] = '正在查詢物流信息，請稍後...';
$_LANG['consignee_address'] = '收貨地址';
$_LANG['view_order'] = '查看訂單';
$_LANG['set_baitiao'] = '設置白條';
$_LANG['account_details'] = '賬目明細';
$_LANG['goods_sku'] = '商品編號';
$_LANG['all_order'] = '全部訂單';
$_LANG['order_status_01'] = '待確認';
$_LANG['order_status_02'] = '待付款';
$_LANG['order_status_03'] = '待發貨';
$_LANG['order_status_04'] = '已完成';
$_LANG['order_status_05'] = '待收貨';
$_LANG['order_status_06'] = '付款中';
$_LANG['search_keywords_placeholder'] = '訂單編號/商品編號/商品關鍵字';
$_LANG['search_keywords_placeholder2'] = '商品編號/商品關鍵字';
$_LANG['is_reality'] = '正品保證';
$_LANG['is_return'] = '包退服務';
$_LANG['is_fast'] = '閃速配送';

$_LANG['order_category'] = '訂單分類';
$_LANG['baitiao_order'] = '白條訂單';
$_LANG['zc_order'] = '眾籌訂單';
$_LANG['so_order'] = '門店訂單';
$_LANG['fx_order'] = '分銷訂單';
$_LANG['team_order'] = '拼團';
$_LANG['bargain_order'] = '砍價';
$_LANG['wholesale_order'] = '批發';
$_LANG['package_order'] = '超值禮包';
$_LANG['xn_order'] = '虛擬商品訂單';
$_LANG['pt_order'] = '普通訂單';
$_LANG['return_order'] = '退換貨訂單';
$_LANG['other_order'] = '促銷訂單';
$_LANG['db_order'] = '奪寶訂單';
$_LANG['ms_order'] = '秒殺訂單';
$_LANG['tg_order'] = '團購訂單';
$_LANG['pm_order'] = '拍賣訂單';
$_LANG['jf_order'] = '積分訂單';
$_LANG['ys_order'] = '預售訂單';

$_LANG['have_commission_bill'] = '已出傭金賬單';
$_LANG['knot_commission_bill'] = '已結傭金賬單';
$_LANG['view_commission_bill'] = '查看賬單';

$_LANG['order_export_dialog'] = '訂單導出彈窗';
$_LANG['operation_error'] = '操作錯誤';

$_LANG['confirmation_receipt_time'] = '確認收貨時間';

$_LANG['fill_user'] = '填寫用戶';
$_LANG['batch_add_order'] = '批量添加訂單';
$_LANG['search_user_placeholder'] = '會員名稱/編號';
$_LANG['username'] = '會員名稱';
$_LANG['search_user_name_not'] = '請先搜索會員名稱';
$_LANG['search_user_name_notic'] = '<strong>注意：</strong>搜索結果只顯示前20條記錄，如果沒有找到相應會員，請更精確地查找。<br>另外，如果該會員是從論壇注冊的且沒有在商城登錄過，也無法找到，需要先在商城登錄。';

$_LANG['search_number_placeholder'] = '編號/名稱/貨號';
$_LANG['select_warehouse'] = '請先選擇倉庫';
$_LANG['receipt_info'] = '請填寫收貨信息';
$_LANG['add_distribution_mode'] = '添加配送方式';
$_LANG['select_payment_method'] = '選擇支付方式';
$_LANG['join_order'] = '加入訂單';
$_LANG['add_invoice'] = '添加發票';
$_LANG['search_goods_first'] = '請先搜索商品';

$_LANG['order_step_notic_01'] = '請查看地區信息是否輸入正確';
/*大商創1.5後台新增end*/

/*眾籌相關 by wu*/
$_LANG['zc_goods_info'] = '眾籌方案信息';
$_LANG['zc_project_name'] = '眾籌項目名稱';
$_LANG['zc_project_raise_money'] = '眾籌金額';
$_LANG['zc_goods_price'] = '方案價格';
$_LANG['zc_shipping_fee'] = '配送費用';
$_LANG['zc_return_time'] = '預計回報時間';
$_LANG['zc_return_content'] = '回報內容';
$_LANG['zc_return_detail'] = '項目成功結束後%s天內';

$_LANG['set_grab_order'] = '設為搶單';
$_LANG['set_success'] = '設置完成';

//by wu
$_LANG['cannot_delete'] = '存在子退換原因，無法刪除';
$_LANG['invoice_no_null'] = '快遞單號不能為空';

$_LANG['seckill'] = '秒殺訂單';

//分單操作
$_LANG['split_action_note'] = "【商品貨號：%s，發貨：%s件】";

/* 退換貨原因 */
$_LANG['add_return_cause'] = "添加退換貨原因";
$_LANG['return_cause_list'] = "退換貨原因列表";
$_LANG['back_return_cause_list'] = "返回退換貨原因列表";
$_LANG['return_order_info'] = "返回訂單信息";
$_LANG['back_return_delivery_handle'] = "返回退貨單操作";

$_LANG['detection_list_notic'] = "只能操作-訂單應收貨時間-小於當前系統時間的訂單";

$_LANG['refund_type_notic_one'] = "退款失敗，退款金額大於實際退款金額";
$_LANG['refund_type_notic_two'] = "退款失敗，您的賬戶資金負金額大於信用額度，請您進行賬戶資金充值或者聯系客服";
$_LANG['refund_type_notic_three'] = "該訂單存在退換貨商品，不能退貨";

$_LANG['batch_delivery_success'] = "批量發貨成功";
$_LANG['inspect_order_type'] = "請檢查訂單狀態";

$_LANG['order_return_prompt'] = "訂單退款，退回訂單";
$_LANG['buy_integral'] = "購買的積分";

/* js 驗證提示 */
$_LANG['js_languages']['not_back_cause'] = '請輸出退換貨原因';
$_LANG['js_languages']['no_confirmation_delivery_info'] = '暫無確認發貨信息';

/* order_step js*/
$_LANG['order_step_js_notic_01'] = '請填寫收貨人名稱';
$_LANG['order_step_js_notic_02'] = '請選擇國家';
$_LANG['order_step_js_notic_03'] = '請選擇省/直轄市';
$_LANG['order_step_js_notic_04'] = '請選擇城市';
$_LANG['order_step_js_notic_05'] = '請選擇區/縣';
$_LANG['order_step_js_notic_06'] = '請填寫收貨地址';
$_LANG['order_step_js_notic_07'] = '請填寫手機號碼';
$_LANG['order_step_js_notic_08'] = '手機號碼不正確';
$_LANG['order_step_js_notic_09'] = '商品庫存不足';
$_LANG['order_step_js_notic_10'] = '請先添加商品';
$_LANG['order_step_js_notic_11'] = '您修改的費用信息折扣不能大於實際訂單總金額';
$_LANG['order_step_js_notic_12'] = '，會產生退款，一旦退款則將扣除您賬戶裡面的資金';
$_LANG['order_step_js_notic_13'] = '您修改的費用信息產生了負數（';
$_LANG['order_step_js_notic_14'] = '，會是否繼續？';

$_LANG['lab_bar_shop_price'] = '本店售價';
$_LANG['lab_bar_market_price'] = '市場價';

$_LANG['refund_way'] = '退款方式';
$_LANG['return_balance'] = '退回到余額';

/* 頁面頂部操作提示 */
$_LANG['operation_prompt_content']['return_cause_list'][0] = '商城退貨原因信息列表管理。';
$_LANG['operation_prompt_content']['return_cause_list'][1] = '可進行刪除或修改退換貨原因。';

$_LANG['operation_prompt_content']['return_cause_info'][0] = '可選擇已有的頂級原因分類。';

$_LANG['operation_prompt_content']['back_list'][0] = '商城所有退貨訂單列表管理。';
$_LANG['operation_prompt_content']['back_list'][1] = '可通過訂單號進行查詢，側邊欄進行高級搜索。';

$_LANG['operation_prompt_content']['delivery_list'][0] = '商城和平台所有已發貨的訂單列表管理。';
$_LANG['operation_prompt_content']['delivery_list'][1] = '可通過訂單號進行查詢，側邊欄進行高級搜索。';
$_LANG['operation_prompt_content']['delivery_list'][2] = '可進入查看取消發貨。';

$_LANG['operation_prompt_content']['detection_list'][0] = '檢測商城所有已發貨訂單。';
$_LANG['operation_prompt_content']['detection_list'][1] = '可通過訂單號進行查詢，側邊欄進行高級搜索。';
$_LANG['operation_prompt_content']['detection_list'][2] = '一鍵確認收貨功能說明：使用一鍵確認收貨功能，只能針對當前頁面的訂單條數進行操作處理，如需一次操作更多的訂單條數，可修改每頁顯示的條數進行操作處理。';

$_LANG['operation_prompt_content']['list'][0] = '商城所有的訂單列表，包括平台自營和入駐商家的訂單。';
$_LANG['operation_prompt_content']['list'][1] = '點擊訂單號即可進入詳情頁面對訂單進行操作。';
$_LANG['operation_prompt_content']['list'][2] = 'Tab切換不同狀態下的訂單，便於分類訂單。';

$_LANG['operation_prompt_content']['search'][0] = '訂單查詢提供多個條件進行精準查詢。';

$_LANG['operation_prompt_content']['step'][0] = '添加訂單流程為：選擇商城已有會員-選擇商品加入訂單-確認訂單金額-填寫收貨信息-添加配送方式-選擇支付方式-添加發票-查看費用信息-完成。';

$_LANG['operation_prompt_content']['return_list'][0] = '買家提交的退換貨申請信息管理。';
$_LANG['operation_prompt_content']['return_list'][1] = '可通過訂單號進行查詢，側邊欄進行高級搜索。';

$_LANG['operation_prompt_content']['templates'][0] = '下單成功後，可列印訂單詳情。';
$_LANG['operation_prompt_content']['templates'][1] = '請不要輕易修改模板變數，否則會導致數據無法正常顯示。';
$_LANG['operation_prompt_content']['templates'][2] = '變數說明：
                    <p>$lang.***：語言項，修改路徑：根目錄/languages/zh_cn/admin/order.php</p>
                    <p>$order.***：訂單數據，請參考dsc_order數據表</p>
                    <p>$goods.***：商品數據，請參考dsc_goods數據表</p>';

$_LANG['stores_name'] = '門店名稱：';
$_LANG['stores_address'] = '門店地址：';
$_LANG['stores_tel'] = '聯系方式：';
$_LANG['stores_opening_hours'] = '營業時間：';
$_LANG['stores_traffic_line'] = '交通線路：';
$_LANG['stores_img'] = '實景圖片：';
$_LANG['pick_code'] = '提貨碼：';


// 商家後台
$_LANG['label_sure_collect_goods_time'] = '確認收貨時間：';
$_LANG['label_order_cate'] = '訂單分類：';
$_LANG['label_id_code'] = '識別碼：';
$_LANG['label_get_post_address'] = '收票地址：';

$_LANG['js_languages']['jl_merge_order'] = '合並訂單';
$_LANG['js_languages']['jl_merge'] = '合並';
$_LANG['js_languages']['jl_sure_merge_order'] = '您確定合並這兩個訂單么？';
$_LANG['js_languages']['jl_order_step_js_notic_11'] = '您修改的費用信息折扣不能大於實際訂單總金額';
$_LANG['js_languages']['jl_order_step_js_notic_12'] = '，會產生退款，一旦退款則將扣除您賬戶裡面的資金';
$_LANG['js_languages']['jl_order_step_js_notic_13'] = '您修改的費用信息產生了負數（';
$_LANG['js_languages']['jl_order_step_js_notic_14'] = '，是否繼續？';
$_LANG['js_languages']['jl_order_export_dialog'] = '訂單導出彈窗';
$_LANG['js_languages']['jl_set_rob_order'] = '設置搶單';
$_LANG['js_languages']['jl_vat_info'] = '增值稅發票信息';
$_LANG['js_languages']['jl_search_logistics_info'] = '正在查詢物流信息，請稍後...';
$_LANG['js_languages']['jl_goods_delivery'] = '商品發貨';

$_LANG['add_order_step'][0] = '選擇下單用戶';
$_LANG['add_order_step'][1] = '選擇訂單商品';
$_LANG['add_order_step'][2] = '填寫收貨地址';
$_LANG['add_order_step'][3] = '選擇配送方式';
$_LANG['add_order_step'][4] = '選擇支付方式';
$_LANG['add_order_step'][5] = '填寫發票信息';
$_LANG['add_order_step'][6] = '填寫費用信息';

$_LANG['print_shipping_form'] = '列印發貨單';
$_LANG['current'] = '當前';
$_LANG['store_info'] = '門店信息';

$_LANG['this_order_return_no_continue'] = '此訂單已確認為退換貨訂單，無法繼續訂單操作！';
$_LANG['no_info_fill_express_number'] = '暫無信息,請填寫快遞單號';

return $_LANG;
