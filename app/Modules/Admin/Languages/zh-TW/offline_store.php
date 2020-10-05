<?php

$_LANG['add_stores'] = '添加門店';
$_LANG['stores_user'] = '登陸名';
$_LANG['stores_pwd'] = '登陸密碼';
$_LANG['confirm_pwd'] = '確認密碼';
$_LANG['stores_name'] = '門店名稱';
$_LANG['stores_name_dsc'] = '請認真填寫您的門店名稱，以確保用戶（購買者）線下到店自提時查找。';
$_LANG['area_info'] = '所在地區';
$_LANG['area_info_dsc'] = '所在地區將直接影響購買者在選擇線下自提時的地區篩選，因此請如實認真選擇全部地區級。';
$_LANG['stores_address'] = '詳細地址';
$_LANG['stores_address_dsc'] = '請認真填寫詳細地址，以確保用戶（購物者）線下到店自提時能最准確的到達您的門店。';
$_LANG['stores_tel'] = '聯系方式';
$_LANG['stores_tel_dsc'] = '請認真填寫門店聯系電話，方便用戶（購物者）通過該電話與您直接取得聯系。電話格式：4000-0000000';
$_LANG['stores_email'] = 'email';
$_LANG['stores_email_dsc'] = '請認真填寫門店email，方便密碼找回。';
$_LANG['stores_opening_hours'] = '營業時間';
$_LANG['stores_opening_hours_dsc'] = '如實填寫您的線下門店營業時間，以免用戶（購物者）在營業時間外到店產生誤會。';
$_LANG['stores_traffic_line'] = '交通線路';
$_LANG['stores_traffic_line_dsc'] = '如您的門店周圍有公交、地鐵線路到達，請填寫該選項，多條線路請以「|」進行分隔。';
$_LANG['stores_img'] = '實景圖片';
$_LANG['stores_img_dsc'] = '將您的實體店面沿街圖上傳，方便用戶（購物者）線下到店自提時能最准確直觀的找到您的門店。';
$_LANG['is_confirm'] = '狀態';
$_LANG['title_exist'] = '門店名稱已存在';
$_LANG['invalid_file'] = '上傳文件格式不正確';
$_LANG['only_stores_name'] = '登陸名已存在';
$_LANG['is_different'] = '兩次密碼不一致';
$_LANG['GO_add'] = '繼續添加';
$_LANG['bank_list'] = '返回列表';
$_LANG['add_succeed'] = '添加成功';
$_LANG['delete_succeed'] = '刪除成功';
$_LANG['delete_fail'] = '請選擇刪除項';
$_LANG['file_url'] = '或輸入文件地址';
$_LANG['newpass'] = '新登錄密碼';
$_LANG['edit_succeed'] = '編輯成功';
$_LANG['open_batch'] = '批量開啟';
$_LANG['off_batch'] = '批量關閉';
$_LANG['drop_batch'] = '批量刪除';
$_LANG['handle_succeed'] = '操作成功';
$_LANG['back_list'] = '返回列表';
$_LANG['shop_name'] = '商家名稱';
$_LANG['overall_sum'] = '有效訂單金額';
$_LANG['query'] = '查詢';
$_LANG['sale_stat'] = '銷量統計';
$_LANG['start_end_date'] = '起止時間';
$_LANG['sale_total'] = '銷售總額';
$_LANG['sale_order_numbher'] = '訂單數量';
$_LANG['sale_goods_price'] = '商品單價';
$_LANG['sale_goods_total'] = '商品總銷量';
$_LANG['last_sale_time'] = '最新售出日期';
$_LANG['select_date'] = '選擇日期';
$_LANG['order_type'] = '訂單類型';
$_LANG['order_state'] = '訂單狀態';
$_LANG['deliver_state'] = '發貨狀態';
$_LANG['stores'] = '門店';
$_LANG['sku'] = '貨號';
$_LANG['number'] = '數量';
$_LANG['price'] = '單價';
$_LANG['sellout_date'] = '售出日期';
$_LANG['order_all'] = '全部訂單';
$_LANG['robbing_order'] = '搶單訂單';
$_LANG['stores_order'] = '門店訂單';
$_LANG['total'] = '合計';

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

/*js語言項*/
$_LANG['js_languages']['stores_user_null'] = '登陸名不能為空';
$_LANG['js_languages']['stores_pwd_null'] = '登陸密碼不能為空';
$_LANG['js_languages']['confirm_pwd_null'] = '確認密碼不能為空';
$_LANG['js_languages']['stores_name_null'] = '門店名稱不能為空';
$_LANG['js_languages']['country_null'] = '請選擇國家';
$_LANG['js_languages']['province_null'] = '請選擇省/直轄市';
$_LANG['js_languages']['city_null'] = '請選擇市區';
$_LANG['js_languages']['district_null'] = '請選擇區/縣';
$_LANG['js_languages']['stores_address_null'] = '詳細地址不能為空';
$_LANG['js_languages']['stores_tel_null'] = '聯系方式不能為空';
$_LANG['js_languages']['stores_tel_error'] = '聯系方式格式錯誤';
$_LANG['js_languages']['stores_email_null'] = '郵箱不能為空';
$_LANG['js_languages']['stores_email_error'] = '郵箱格式錯誤';
$_LANG['js_languages']['stores_opening_hours_null'] = '營業時間不能為空';
$_LANG['js_languages']['stores_img_null'] = '請上傳實景圖片';
$_LANG['js_languages']['email_null'] = 'email不能為空';
$_LANG['js_languages']['pwd_not_six'] = '密碼長度不能小於六位';
$_LANG['js_languages']['confirm_pwd_equal'] = '兩次輸入的密碼不一致';

/* 教程名稱 */
$_LANG['tutorials_bonus_list_one'] = '線下門店使用說明';

/* 頁面頂部操作提示 */
$_LANG['operation_prompt_content']['info'][0] = '請准確無誤的填寫門店信息。';
$_LANG['operation_prompt_content']['info'][1] = '請上傳高清門店實景圖片，方便用戶准確直觀的找到該門店。';

$_LANG['operation_prompt_content']['list'][0] = '商城所有門店相關信息管理。';
$_LANG['operation_prompt_content']['list'][1] = '可對門店進行開啟或關閉狀態。';
$_LANG['operation_prompt_content']['list'][2] = '可對門店名稱關鍵字進行搜索，側邊欄進行高級搜索。';

$_LANG['operation_prompt_content']['stat'][0] = '搜索某個時間的商城用戶充值總額。';
$_LANG['operation_prompt_content']['stat'][1] = '搜索出來的信息只能查看不能修改。';

$_LANG['operation_prompt_content']['stats'][0] = '門店訂單相關信息管理。';
$_LANG['operation_prompt_content']['stats'][1] = '可對門店訂單進行確認、列印訂單等操作。';
$_LANG['operation_prompt_content']['stats'][2] = '門店訂單可進行訂單查詢和合並訂單操作。';

return $_LANG;
