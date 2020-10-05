<?php

$_LANG['Import_data_remind'] = "請選擇需要導入模塊數據！";

$_LANG['set_up_config'] = "請配置源站點資料庫信息";
$_LANG['select_method'] = '選擇商品的方式：';
$_LANG['by_cat'] = '根據商品分類、品牌';
$_LANG['by_sn'] = '根據商品貨號';
$_LANG['select_cat'] = '選擇商品分類：';
$_LANG['select_brand'] = '選擇商品品牌：';
$_LANG['goods_list'] = '商品列表：';
$_LANG['src_list'] = '待選列表：';
$_LANG['dest_list'] = '選定列表：';
$_LANG['input_sn'] = '輸入商品貨號：<br />（每行一個）';
$_LANG['edit_method'] = '編輯方式：';
$_LANG['edit_each'] = '逐個編輯';
$_LANG['edit_all'] = '統一編輯';
$_LANG['go_edit'] = '進入編輯';
$_LANG['error_transfer'] = '處理 %s 表時，出現一下錯誤：%s 欄位';

$_LANG['notice_edit'] = '會員價格為-1表示會員價格將根據會員等級折扣比例計算';

$_LANG['goods_class'] = '商品類別';
$_LANG['g_class'][G_REAL] = '實體商品';
$_LANG['g_class'][G_CARD] = '虛擬卡';

$_LANG['goods_sn'] = '貨號';
$_LANG['goods_name'] = '商品名稱';
$_LANG['market_price'] = '市場價格';
$_LANG['shop_price'] = '本店價格';
$_LANG['integral'] = '積分購買';
$_LANG['give_integral'] = '贈送積分';
$_LANG['goods_number'] = '庫存';
$_LANG['brand'] = '品牌';
$_LANG['transfer_manage'] = '源站點信息設置';
$_LANG['select_file_template'] = '請選擇導入模塊';
$_LANG['category_manage'] = '分類管理';
$_LANG['user_manage'] = '會員管理';
$_LANG['seller_list_info'] = '商家列表信息';
$_LANG['order_list_info'] = '訂單列表信息';
$_LANG['order_goods_list'] = '訂單商品列表';
$_LANG['attr_list'] = '屬性列表';
$_LANG['exec'] = '執行程序';
$_LANG['silent_1'] = '出錯時忽略錯誤,繼續執行程序';
$_LANG['silent_0'] = '出錯時立即提示，並中止程序';

$_LANG['batch_edit_ok'] = '批量修改成功';
$_LANG['manage_date_import'] = '管理數據導入';
$_LANG['import_success'] = '導入成功';

$_LANG['nav_list'] = '商品列表|goods.php?act=list,訂單列表|order.php?act=list,用戶評論|comment_manage.php?act=list,會員列表|users.php?act=list,商店設置|shop_config.php?act=list_edit';

$_LANG['export_format'] = '數據格式';
$_LANG['export_dscmall'] = 'dscmall支持數據格式';
$_LANG['export_taobao'] = '淘寶助理支持數據格式';
$_LANG['export_taobao46'] = '淘寶助理4.6支持數據格式';
$_LANG['export_paipai'] = '拍拍助理支持數據格式';
$_LANG['export_paipai3'] = '拍拍助理3.0支持數據格式';
$_LANG['goods_cat'] = '所屬分類：';
$_LANG['csv_file'] = '上傳批量csv文件：';
$_LANG['notice_file'] = '（CSV文件中一次上傳商品數量最好不要超過1000，CSV文件大小最好不要超過500K.）';
$_LANG['file_charset'] = '文件編碼：';
$_LANG['download_file'] = '下載批量CSV文件（%s）';
$_LANG['use_help'] = '使用說明：' .
    '<ol>' .
    '<li>遷移數據應注意，是否需要保留本站數據信息；選擇不保留時，導入這部分數據時將會覆蓋相應表數據；<br />' .
    '如保留本站數據時，導入的數據將是最新添加；序號及其他關聯表信息不能對應上。<br />' .
    '</ol>';

$_LANG['js_languages']['please_select_goods'] = '請您選擇商品';
$_LANG['js_languages']['please_input_sn'] = '請您輸入商品貨號';
$_LANG['js_languages']['goods_cat_not_leaf'] = '請選擇底級分類';
$_LANG['js_languages']['please_select_cat'] = '請您選擇所屬分類';
$_LANG['js_languages']['please_upload_file'] = '請您上傳批量csv文件';

// 批量上傳商品的欄位
$_LANG['upload_goods']['goods_id'] = '商品ID';
$_LANG['upload_goods']['cat_id'] = '分類ID';
$_LANG['upload_goods']['user_id'] = '會員ID';
$_LANG['upload_goods']['goods_sn'] = '商品貨號';
$_LANG['upload_goods']['goods_name'] = '商品名稱';
$_LANG['upload_goods']['goods_name_style'] = '商品名稱顯示的樣式';
$_LANG['upload_goods']['click_count'] = '商品點擊數';
$_LANG['upload_goods']['brand_id'] = '商品品牌';
$_LANG['upload_goods']['provider_name'] = '供貨人的名稱';
$_LANG['upload_goods']['goods_number'] = '庫存數量';
$_LANG['upload_goods']['goods_weight'] = '商品重量（kg）';
$_LANG['upload_goods']['market_price'] = '市場售價';
$_LANG['upload_goods']['shop_price'] = '本店售價';
$_LANG['upload_goods']['promote_price'] = '促銷價格';
$_LANG['upload_goods']['promote_start_date'] = '促銷價格開始日期';
$_LANG['upload_goods']['promote_end_date'] = '促銷結束日期';
$_LANG['upload_goods']['warn_number'] = '商品報警數量';
$_LANG['upload_goods']['keywords'] = '商品關鍵字';
$_LANG['upload_goods']['goods_brief'] = '商品的簡短描述';
$_LANG['upload_goods']['goods_desc'] = '商品的詳細描述';
$_LANG['upload_goods']['goods_thumb'] = '商品微縮圖片';
$_LANG['upload_goods']['goods_img'] = '商品的實際大小圖片';
$_LANG['upload_goods']['original_img'] = '商品的原始圖片';
$_LANG['upload_goods']['is_real'] = '是否是實物';
$_LANG['upload_goods']['extension_code'] = '商品的擴展屬性';
$_LANG['upload_goods']['is_on_sale'] = '商品是否開放銷售';
$_LANG['upload_goods']['is_alone_sale'] = '是否能單獨銷售';
$_LANG['upload_goods']['is_shipping'] = '是否免運費';
$_LANG['upload_goods']['integral'] = '積分購買額度';
$_LANG['upload_goods']['add_time'] = '添加時間';
$_LANG['upload_goods']['sort_order'] = '顯示順序';
$_LANG['upload_goods']['is_delete'] = '商品是否已經刪除';
$_LANG['upload_goods']['is_best'] = '是否是精品';
$_LANG['upload_goods']['is_new'] = '是否是新品';
$_LANG['upload_goods']['is_hot'] = '是否熱銷';
$_LANG['upload_goods']['is_promote'] = '是否特價促銷';
$_LANG['upload_goods']['bonus_type_id'] = '購買該商品所能領到的紅包類型';
$_LANG['upload_goods']['last_update'] = '最近一次更新商品配置的時間';
$_LANG['upload_goods']['goods_type'] = '商品所屬類型id';
$_LANG['upload_goods']['seller_note'] = '商品的商家備注';
$_LANG['upload_goods']['give_integral'] = '購買該商品時每筆成功交易贈送的積分數量';
$_LANG['upload_goods']['rank_integral'] = '等級積分';
$_LANG['upload_goods']['suppliers_id'] = '供貨商ID';
$_LANG['upload_goods']['is_check'] = '是否審核';

//批量上傳分類
$_LANG['upload_category']['cat_id'] = '分類ID';
$_LANG['upload_category']['cat_name'] = '分類名稱';
$_LANG['upload_category']['keywords'] = '分類關鍵詞';
$_LANG['upload_category']['cat_desc'] = '分類描述';
$_LANG['upload_category']['parent_id'] = '分類父id';
$_LANG['upload_category']['sort_order'] = '顯示排序';
$_LANG['upload_category']['template_file'] = '該分類的單獨模板文件的名字';
$_LANG['upload_category']['measure_unit'] = '該分類的計量單位';
$_LANG['upload_category']['show_in_nav'] = '是否顯示在導航欄';
$_LANG['upload_category']['style'] = '該分類的單獨的樣式表的包括文件名部分的文件路徑';
$_LANG['upload_category']['is_show'] = '是否在前台頁面顯示';
$_LANG['upload_category']['grade'] = '該分類的最高和最低價之間的價格分級';
$_LANG['upload_category']['filter_attr'] = '屬性篩選';

//批量上傳會員
$_LANG['upload_users']['user_id'] = '用戶ID';
$_LANG['upload_users']['email'] = '會員郵箱';
$_LANG['upload_users']['user_name'] = '用戶名';
$_LANG['upload_users']['password'] = '用戶密碼';
$_LANG['upload_users']['question'] = '安全問題答案';
$_LANG['upload_users']['answer'] = '安全問題';
$_LANG['upload_users']['sex'] = '性別';
$_LANG['upload_users']['birthday'] = '生日';
$_LANG['upload_users']['user_money'] = '用戶現有資金';
$_LANG['upload_users']['frozen_money'] = '用戶凍結資金';
$_LANG['upload_users']['pay_points'] = '消費積分';
$_LANG['upload_users']['rank_points'] = '會員等級積分';
$_LANG['upload_users']['address_id'] = '收貨信息id';
$_LANG['upload_users']['reg_time'] = '注冊時間';
$_LANG['upload_users']['last_login'] = '最後一次登錄時間';
$_LANG['upload_users']['last_time'] = '應該是最後一次修改信息時間';
$_LANG['upload_users']['last_ip'] = '最後一次登錄ip';
$_LANG['upload_users']['visit_count'] = '登錄次數';
$_LANG['upload_users']['user_rank'] = '會員等級id';
$_LANG['upload_users']['is_special'] = '特殊用戶';
$_LANG['upload_users']['salt'] = '登錄標識';
$_LANG['upload_users']['parent_id'] = '推薦人會員id';
$_LANG['upload_users']['flag'] = '標記';
$_LANG['upload_users']['alias'] = '昵稱';
$_LANG['upload_users']['msn'] = 'msn';
$_LANG['upload_users']['qq'] = 'qq';
$_LANG['upload_users']['office_phone'] = '辦公電話';
$_LANG['upload_users']['home_phone'] = '家庭電話';
$_LANG['upload_users']['mobile_phone'] = '手機';
$_LANG['upload_users']['is_validated'] = '郵箱是否驗證';
$_LANG['upload_users']['credit_line'] = '信用額度';
$_LANG['upload_users']['passwd_question'] = '密碼問題';
$_LANG['upload_users']['passwd_answer'] = '密碼答案';

//批量上傳訂單列表
$_LANG['upload_order_info']['order_id'] = '';
$_LANG['upload_order_info']['main_order_id'] = '';
$_LANG['upload_order_info']['order_sn'] = '';
$_LANG['upload_order_info']['user_id'] = '';
$_LANG['upload_order_info']['order_status'] = '';
$_LANG['upload_order_info']['shipping_status'] = '';
$_LANG['upload_order_info']['pay_status'] = '';
$_LANG['upload_order_info']['consignee'] = '';
$_LANG['upload_order_info']['country'] = '';
$_LANG['upload_order_info']['province'] = '';
$_LANG['upload_order_info']['city'] = '';
$_LANG['upload_order_info']['district'] = '';
$_LANG['upload_order_info']['address'] = '';
$_LANG['upload_order_info']['zipcode'] = '';
$_LANG['upload_order_info']['tel'] = '';
$_LANG['upload_order_info']['mobile'] = '';
$_LANG['upload_order_info']['email'] = '';
$_LANG['upload_order_info']['best_time'] = '';
$_LANG['upload_order_info']['sign_building'] = '';
$_LANG['upload_order_info']['postscript'] = '';
$_LANG['upload_order_info']['shipping_id'] = '';
$_LANG['upload_order_info']['shipping_name'] = '';
$_LANG['upload_order_info']['pay_id'] = '';
$_LANG['upload_order_info']['pay_name'] = '';
$_LANG['upload_order_info']['how_oos'] = '';
$_LANG['upload_order_info']['how_surplus'] = '';
$_LANG['upload_order_info']['pack_name'] = '';
$_LANG['upload_order_info']['card_name'] = '';
$_LANG['upload_order_info']['card_message'] = '';
$_LANG['upload_order_info']['inv_payee'] = '';
$_LANG['upload_order_info']['inv_content'] = '';
$_LANG['upload_order_info']['goods_amount'] = '';
$_LANG['upload_order_info']['shipping_fee'] = '';
$_LANG['upload_order_info']['insure_fee'] = '';
$_LANG['upload_order_info']['pay_fee'] = '';
$_LANG['upload_order_info']['pack_fee'] = '';
$_LANG['upload_order_info']['card_fee'] = '';
$_LANG['upload_order_info']['money_paid'] = '';
$_LANG['upload_order_info']['surplus'] = '';
$_LANG['upload_order_info']['integral'] = '';
$_LANG['upload_order_info']['integral_money'] = '';
$_LANG['upload_order_info']['bonus'] = '';
$_LANG['upload_order_info']['order_amount'] = '';
$_LANG['upload_order_info']['from_ad'] = '';
$_LANG['upload_order_info']['referer'] = '';
$_LANG['upload_order_info']['add_time'] = '';
$_LANG['upload_order_info']['confirm_time'] = '';
$_LANG['upload_order_info']['pay_time'] = '';
$_LANG['upload_order_info']['shipping_time'] = '';
$_LANG['upload_order_info']['pack_id'] = '';
$_LANG['upload_order_info']['card_id'] = '';
$_LANG['upload_order_info']['bonus_id'] = '';
$_LANG['upload_order_info']['invoice_no'] = '';
$_LANG['upload_order_info']['extension_code'] = '';
$_LANG['upload_order_info']['extension_id'] = '';
$_LANG['upload_order_info']['to_buyer'] = '';
$_LANG['upload_order_info']['pay_note'] = '';
$_LANG['upload_order_info']['agency_id'] = '';
$_LANG['upload_order_info']['inv_type'] = '';
$_LANG['upload_order_info']['tax'] = '';
$_LANG['upload_order_info']['is_separate'] = '';
$_LANG['upload_order_info']['parent_id'] = '';
$_LANG['upload_order_info']['discount'] = '';

//批量上傳訂單商品表
$_LANG['upload_order_goods']['rec_id'] = '';
$_LANG['upload_order_goods']['order_id'] = '';
$_LANG['upload_order_goods']['goods_id'] = '';
$_LANG['upload_order_goods']['goods_name'] = '';
$_LANG['upload_order_goods']['goods_sn'] = '';
$_LANG['upload_order_goods']['goods_number'] = '';
$_LANG['upload_order_goods']['market_price'] = '';
$_LANG['upload_order_goods']['goods_price'] = '';
$_LANG['upload_order_goods']['goods_attr'] = '';
$_LANG['upload_order_goods']['send_number'] = '';
$_LANG['upload_order_goods']['is_real'] = '';
$_LANG['upload_order_goods']['extension_code'] = '';
$_LANG['upload_order_goods']['parent_id'] = '';
$_LANG['upload_order_goods']['is_gift'] = '';
$_LANG['upload_order_goods']['goods_attr_id'] = '';
$_LANG['upload_order_goods']['ru_id'] = '';

//批量上傳商品類型
$_LANG['upload_goods_type']['cat_id'] = '';
$_LANG['upload_goods_type']['cat_name'] = '';
$_LANG['upload_goods_type']['enabled'] = '';
$_LANG['upload_goods_type']['attr_group'] = '';

//批量上傳屬性列表
$_LANG['upload_attribute']['attr_id'] = '';
$_LANG['upload_attribute']['cat_id'] = '';
$_LANG['upload_attribute']['attr_name'] = '';
$_LANG['upload_attribute']['attr_input_type'] = '';
$_LANG['upload_attribute']['attr_type'] = '';
$_LANG['upload_attribute']['attr_values'] = '';
$_LANG['upload_attribute']['attr_index'] = '';
$_LANG['upload_attribute']['sort_order'] = '';
$_LANG['upload_attribute']['is_linked'] = '';
$_LANG['upload_attribute']['attr_group'] = '';

//批量上傳文章列表
$_LANG['upload_article']['article_id'] = '文章ID';
$_LANG['upload_article']['cat_id'] = '文章的分類ID';
$_LANG['upload_article']['title'] = '標題';
$_LANG['upload_article']['content'] = '內容';
$_LANG['upload_article']['author'] = '作者';
$_LANG['upload_article']['author_email'] = '作者郵箱';
$_LANG['upload_article']['keywords'] = '文章的關鍵字';
$_LANG['upload_article']['article_type'] = '文章類型';
$_LANG['upload_article']['is_open'] = '是否顯示';
$_LANG['upload_article']['add_time'] = '文章添加時間';
$_LANG['upload_article']['file_url'] = '上傳文件或者外部文件的url';
$_LANG['upload_article']['open_type'] = '連接地址等於file_url的值';
$_LANG['upload_article']['link'] = '文章標題所引用的連接';
$_LANG['upload_article']['description'] = '描述';

//批量上傳文章分類
$_LANG['upload_article_cat']['cat_id'] = '分類ID';
$_LANG['upload_article_cat']['cat_name'] = '分類名稱';
$_LANG['upload_article_cat']['cat_type'] = '分類類型';
$_LANG['upload_article_cat']['keywords'] = '關鍵詞';
$_LANG['upload_article_cat']['cat_desc'] = '分類說明';
$_LANG['upload_article_cat']['sort_order'] = '排序';
$_LANG['upload_article_cat']['show_in_nav'] = '是否在導航欄顯示';
$_LANG['upload_article_cat']['parent_id'] = '父節點id，取值於該表cat_id欄位';

$_LANG['batch_upload_ok'] = '批量上傳成功';
$_LANG['goods_upload_confirm'] = '批量上傳確認';

$_LANG['notes'] = "圖片批量處理允許您重新生成商品的縮略圖以及重新添加水印。<br />該處理過程可能會比較慢，請您耐心等候。";
$_LANG['change_link'] = '為處理後圖片生成新鏈接';
$_LANG['yes_change'] = '新生成圖片使用新名稱，並刪除舊圖片';
$_LANG['do_album'] = '處理商品相冊';
$_LANG['do_icon'] = '處理商品圖片';
$_LANG['all_goods'] = '所有商品';
$_LANG['action_notice'] = '請選上「處理商品相冊」或「處理商品圖片」';
$_LANG['no_change'] = '新生成圖片覆蓋舊圖片';
$_LANG['thumb'] = '重新生成縮略圖';
$_LANG['watermark'] = '重新生成商品詳情圖';
$_LANG['page'] = '頁數';
$_LANG['total'] = '總頁數';
$_LANG['time'] = '處理時間';
$_LANG['wait'] = '正在處理.....';
$_LANG['page_format'] = '第 %d 頁';
$_LANG['total_format'] = '共 %d 頁';
$_LANG['time_format'] = '耗時 %s 秒';
$_LANG['goods_format'] = '商品圖片共 %d 張，每頁處理 %d 張';
$_LANG['gallery_format'] = '商品相冊圖片共 %d 張，每頁處理 %d 張';

$_LANG['done'] = '圖片批量處理成功';
$_LANG['error_pos'] = '在處理商品ID為 %s 的商品圖片時發生以下錯誤：';
$_LANG['error_rename'] = '無法將文件 %s 重命名為 %s';

$_LANG['js_languages']['no_action'] = '你沒選擇任何操作';

$_LANG['silent'] = '出錯時忽略錯誤,繼續執行程序';
$_LANG['no_silent'] = '出錯時立即提示，並中止程序';

$_LANG['data_migration'] = '數據遷移';
$_LANG['data_host'] = '資料庫主機';
$_LANG['data_host_notic'] = '示例：127.0.0.1';
$_LANG['port'] = '埠';
$_LANG['port_notic'] = '示例：3306';
$_LANG['user_notic'] = '示例：root';
$_LANG['pwd'] = '密碼';
$_LANG['pwd_notic'] = '示例：123456';
$_LANG['database_name'] = '資料庫名';
$_LANG['database_name_notic'] = '示例：ecmoban_dsc';
$_LANG['s_db_prefix'] = '表前綴';
$_LANG['s_db_prefix_notic'] = '示例：dsc_  (說明：比如表dsc_users)';
$_LANG['s_db_retain'] = '本站數據表數據是否保留';
$_LANG['get_sql_basic'] = '檢測資料庫連接';
$_LANG['ws_zhandian_set'] = '請完善源站點配置信息！';

$_LANG['databases_message_one'] = '連接 資料庫失敗，請檢查您輸入的 資料庫帳號 是否正確。';
$_LANG['databases_message_two'] = '連接 資料庫失敗，請檢查您輸入的 資料庫名稱 是否存在。';
$_LANG['databases_message_three'] = '連接資料庫成功！';

$_LANG['seller_apply_info'] = '商家入駐信息';

$_LANG['operator'] = '操作員：【';
$_LANG['operator_two'] = '】，訂單退款【';

/* 頁面頂部操作提示 */
$_LANG['operation_prompt_content']['info'][0] = '獲取轉移的資料庫表內容。';

$_LANG['operation_prompt_content']['config'][0] = '設置導入來源網站資料庫配置信息';

return $_LANG;
