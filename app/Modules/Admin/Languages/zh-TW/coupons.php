<?php

/* 優惠券類型欄位信息 */
$_LANG['coupons_type'] = '優惠券類型';
$_LANG['coupons_list'] = '優惠券列表';
$_LANG['type_name'] = '類型名稱';
$_LANG['type_money'] = '優惠券金額';
$_LANG['min_goods_amount'] = '最小訂單金額';
$_LANG['notice_min_goods_amount'] = '只有商品總金額達到這個數的訂單才能使用這種優惠券';
$_LANG['min_amount'] = '訂單下限';
$_LANG['max_amount'] = '訂單上限';
$_LANG['send_startdate'] = '開始時間';
$_LANG['send_enddate'] = '結束時間';

$_LANG['use_startdate'] = '使用起始日期';
$_LANG['use_enddate'] = '使用結束日期';
$_LANG['send_count'] = '發放數量';
$_LANG['use_count'] = '使用數量';
$_LANG['send_method'] = '如何發放此類型優惠券';
$_LANG['send_type'] = '發放類型';
$_LANG['param'] = '參數';
$_LANG['no_use'] = '未使用';
$_LANG['no_overdue'] = '未過期';
$_LANG['yuan'] = '元';
$_LANG['user_list'] = '會員列表';
$_LANG['type_name_empty'] = '優惠券類型名稱不能為空！';
$_LANG['type_money_empty'] = '優惠券金額不能為空！';
$_LANG['min_amount_empty'] = '優惠券類型的訂單下限不能為空！';
$_LANG['max_amount_empty'] = '優惠券類型的訂單上限不能為空！';
$_LANG['send_count_empty'] = '優惠券類型的發放數量不能為空！';

$_LANG['send_by'][SEND_BY_USER] = '按用戶發放';
$_LANG['send_by'][SEND_BY_GOODS] = '按商品發放';
$_LANG['send_by'][SEND_BY_ORDER] = '按訂單金額發放';
$_LANG['send_by'][SEND_BY_PRINT] = '線下發放的優惠券';
$_LANG['report_form'] = '報表下載';
$_LANG['send'] = '發放';
$_LANG['coupons_excel_file'] = '線下優惠券信息列表';

$_LANG['goods_cat'] = '選擇商品分類';
$_LANG['goods_brand'] = '商品品牌';
$_LANG['goods_key'] = '商品關鍵字';
$_LANG['all_goods'] = '可選商品';
$_LANG['send_bouns_goods'] = '發放此類型優惠券的商品';
$_LANG['remove_bouns'] = '移除優惠券';
$_LANG['all_remove_bouns'] = '全部移除';
$_LANG['goods_already_bouns'] = '該商品已經發放過其它類型的優惠券了!';
$_LANG['send_user_empty'] = '您沒有選擇需要發放優惠券的會員，請返回!';
$_LANG['batch_drop_success'] = '成功刪除了 %d 個用戶優惠券';
$_LANG['sendcoupons_count'] = '共發送了 %d 個優惠券。';
$_LANG['send_bouns_error'] = '發送會員優惠券出錯, 請返回重試！';
$_LANG['no_select_coupons'] = '您沒有選擇需要刪除的用戶優惠券';
$_LANG['couponstype_edit'] = '編輯優惠券類型';
$_LANG['couponstype_view'] = '查看詳情';
$_LANG['drop_coupons'] = '刪除優惠券';
$_LANG['send_coupons'] = '發放優惠券';
$_LANG['continus_add'] = '添加優惠券';
$_LANG['back_list'] = '返回優惠券類型列表';
$_LANG['continue_add'] = '繼續添加優惠券';
$_LANG['back_coupons_list'] = '返回優惠券列表';
$_LANG['validated_email'] = '只給通過郵件驗證的用戶發放優惠券';

$_LANG['coupons_adopt_status_set_success'] = '優惠券審核狀態設置成功';

/* 優惠券列表 */
$_LANG['coupons_name'] = '優惠券名稱';
//$_LANG['coupons_type'] = '類型';
$_LANG['goods_steps_name'] = '商家名稱';
$_LANG['use_limit'] = '使用門檻';
$_LANG['coupons_value'] = '面值';
$_LANG['give_out_amount'] = '總發行量';
$_LANG['valid_date'] = '有效范圍';
$_LANG['is_overdue'] = '是否過期';

/* 提示信息 */
$_LANG['attradd_succed'] = '操作成功!';
$_LANG['js_languages']['type_name_empty'] = '請輸入優惠券類型名稱!';
$_LANG['js_languages']['type_money_empty'] = '請輸入優惠券類型價格!';
$_LANG['js_languages']['order_money_empty'] = '請輸入訂單金額!';
$_LANG['js_languages']['type_money_isnumber'] = '類型金額必須為數字格式!';
$_LANG['js_languages']['order_money_isnumber'] = '訂單金額必須為數字格式!';
$_LANG['js_languages']['coupons_sn_empty'] = '請輸入優惠券的序列號!';
$_LANG['js_languages']['coupons_sn_number'] = '優惠券的序列號必須是數字!';
$_LANG['send_count_error'] = '優惠券的發放數量必須是一個整數!';
$_LANG['js_languages']['coupons_sum_empty'] = '請輸入您要發放的優惠券數量!';
$_LANG['js_languages']['coupons_sum_number'] = '優惠券的發放數量必須是一個整數!';
$_LANG['js_languages']['coupons_type_empty'] = '請選擇優惠券的類型金額!';
$_LANG['js_languages']['user_rank_empty'] = '您沒有指定會員等級!';
$_LANG['js_languages']['user_name_empty'] = '您至少需要選擇一個會員!';
$_LANG['js_languages']['invalid_min_amount'] = '請輸入訂單下限（大於0的數字）';
$_LANG['js_languages']['send_start_lt_end'] = '優惠券發放開始日期不能大於結束日期';
$_LANG['js_languages']['use_start_lt_end'] = '優惠券使用開始日期不能大於結束日期';
$_LANG['js_languages']['range_exists'] = '該選項已存在';
$_LANG['js_languages']['allow_user_rank'] = '允許參與的會員等級,一個不選表示沒有任何會員能參與';
$_LANG['js_languages']['coupons_title_empty'] = '請輸入優惠券標題';
$_LANG['js_languages']['coupons_zhang_empty'] = '請輸入優惠券張數';
$_LANG['js_languages']['coupons_face_empty'] = '請輸入優惠券面值';
$_LANG['js_languages']['coupons_total_isnumber'] = '金額必須是數字格式';
$_LANG['js_languages']['coupons_total_min'] = '金額必須大於0';
$_LANG['js_languages']['coupons_threshold_empty'] = '請輸入優惠券使用門檻';
$_LANG['js_languages']['coupons_data_empty'] = '有效開始時間不能為空';
$_LANG['js_languages']['coupons_data_invalid_gt'] = '有效結束時間必須大於有效開始時間';
$_LANG['js_languages']['coupons_cat_exist'] = '分類已經存在了';
$_LANG['js_languages']['coupons_set_goods'] = '請設置商品';
$_LANG['js_languages']['continus_add'] = '添加優惠券';

$_LANG['order_money_notic'] = '只要訂單金額達到該數值，就會發放優惠券給用戶';
$_LANG['type_money_notic'] = '此類型的優惠券可以抵銷的金額';
$_LANG['send_startdate_notic'] = '只有當前時間介於起始日期和截止日期之間時，此類型的優惠券才可以發放';
$_LANG['use_startdate_notic'] = '只有當前時間介於起始日期和截止日期之間時，此類型的優惠券才可以使用';
$_LANG['type_name_exist'] = '此類型的名稱已經存在!';
$_LANG['type_money_beyond'] = '優惠券金額不得大於最小訂單金額!';
$_LANG['type_money_error'] = '金額必須是數字並且不能小於 0 !';
$_LANG['coupons_sn_notic'] = '提示：優惠券序列號由六位序列號種子加上四位隨機數字組成';
$_LANG['creat_coupons'] = '生成了 ';
$_LANG['creat_coupons_num'] = ' 個優惠券序列號';
$_LANG['coupons_sn_error'] = '優惠券序列號必須是數字!';
$_LANG['send_user_notice'] = '給指定的用戶發放優惠券時,請在此輸入用戶名, 多個用戶之間請用逗號(,)分隔開<br />如:liry, wjz, zwj';
$_LANG['allow_level_no_select_no_join'] = '允許參與的會員等級,一個不選表示沒有任何會員能參與';

/* 優惠券信息欄位 */
$_LANG['coupons_id'] = '編號';
$_LANG['coupons_type_id'] = '類型金額';
$_LANG['send_coupons_count'] = '優惠券數量';
$_LANG['start_coupons_sn'] = '起始序列號';
$_LANG['coupons_sn'] = '優惠券編號';
$_LANG['user_name'] = '所屬會員';
$_LANG['user_id'] = '使用會員';
$_LANG['used_time'] = '使用時間';
$_LANG['order_id'] = '訂單號';
$_LANG['send_mail'] = '發郵件';
$_LANG['emailed'] = '郵件通知';
$_LANG['mail_status'][BONUS_NOT_MAIL] = '未發';
$_LANG['mail_status'][BONUS_MAIL_FAIL] = '已發失敗';
$_LANG['mail_status'][BONUS_MAIL_SUCCEED] = '已發成功';

$_LANG['sendtouser'] = '給指定用戶發放優惠券';
$_LANG['senduserrank'] = '按用戶等級發放優惠券';
$_LANG['userrank'] = '用戶等級';
$_LANG['select_rank'] = '選擇會員等級...';
$_LANG['keywords'] = '關鍵字：';
$_LANG['userlist'] = '會員列表：';
$_LANG['send_to_user'] = '給下列用戶發放優惠券';
$_LANG['search_users'] = '搜索會員';
$_LANG['confirm_send_coupons'] = '確定發送優惠券';
$_LANG['coupons_not_exist'] = '該優惠券不存在';
$_LANG['success_send_mail'] = '%d 封郵件已被加入郵件列表';
$_LANG['send_continue'] = '繼續發放優惠券';
$_LANG['please_input_coupons_name'] = '請輸入優惠券名稱';
$_LANG['allow_user_rank'] = '允許參與的會員等級,一個不選表示沒有任何會員能參與';
$_LANG['use_threshold_money'] = '使用門檻(元)';
$_LANG['face_value_money'] = '面值(元)';
$_LANG['total_send_num'] = '總發行數(張)';
$_LANG['expiry_date'] = '有效期';
$_LANG['expired'] = '已過期';

/*大商創1.5新增 sunle*/
$_LANG['start_enddate'] = '發放起止日期';
$_LANG['use_start_enddate'] = '有效時間';
$_LANG['send_continue'] = '繼續發放優惠券';
$_LANG['bind_password'] = '綁定密碼';

$_LANG['coupons_default_title'] = '選擇創建一張優惠券';
$_LANG['coupons_default_tit'] = '通過發放優惠券提高用戶參與度';
$_LANG['coupons_add_title'] = "請選擇需要添加的優惠券";
$_LANG['coupons_type_01'] = '注冊贈券';
$_LANG['coupons_type_02'] = '購物贈券';
$_LANG['coupons_type_03'] = '全場贈券';
$_LANG['coupons_type_04'] = '會員贈券';
$_LANG['coupons_type_05'] = '免郵券';
$_LANG['coupons_type_06'] = '領取關注店鋪券';
$_LANG['not_free_shipping'] = '不包郵地區';
$_LANG['set_free_shipping'] = '設置不包郵地區';
$_LANG['cou_man_desc'] = '，無門檻請設為0';

$_LANG['coupons_desc_title_01'] = '新注冊用戶可獲得指定優惠券';
$_LANG['coupons_desc_span_01'] = '通過注冊贈券提高用戶注冊積極性';
$_LANG['coupons_desc_title_02'] = '購物滿金額可獲得指定優惠券';
$_LANG['coupons_desc_span_02'] = '通過購物贈券鼓勵用戶提高訂單金額';
$_LANG['coupons_desc_title_03'] = '可在商品詳情頁領取優惠券';
$_LANG['coupons_desc_span_03'] = '通過全場贈券，吸引更多會員在店鋪下單';
$_LANG['coupons_desc_title_04'] = '指定會員可獲得優惠券';
$_LANG['coupons_desc_span_04'] = '通過指定會員贈券為不同等級會員提供個性促銷';

$_LANG['coupons_name'] = "優惠劵名稱";
$_LANG['activity_rule'] = "活動規則";
$_LANG['worth'] = "價值(元)";
$_LANG['receive_limit'] = "領取限制";
$_LANG['activity_state'] = "活動狀態";
$_LANG['already_receive'] = "已領取";
$_LANG['already_used'] = "已使用";
$_LANG['handle'] = "操作";
$_LANG['coupons_number'] = "優惠劵總張數";
$_LANG['coupons_man'] = "使用門檻";//bylu
$_LANG['coupons_money'] = "面值";//bylu
$_LANG['coupons_intro'] = "備注";//bylu
$_LANG['cou_get_man'] = "獲取門檻";//bylu
$_LANG['cou_list'] = "優惠券列表";//bylu
$_LANG['cou_edit'] = "優惠劵編輯";//bylu
$_LANG['full_shopping'] = "購物滿";
$_LANG['yuan'] = "元";
$_LANG['deductible'] = "可抵扣";
$_LANG['each_purchase'] = "每人限領";
$_LANG['use_goods'] = "可使用商品";
$_LANG['buy_goods_deduction'] = "購買以下商品可使用優惠券抵扣金額";
$_LANG['goods_all'] = "全部商品";
$_LANG['goods_appoint'] = "指定商品";
$_LANG['spec_cat'] = '指定分類';
$_LANG['label_search_and_add'] = "搜索並加入優惠范圍";
$_LANG['full_delivery'] = "滿送金額";
$_LANG['desc_yuan'] = "元可獲得優惠券";
$_LANG['give_quan_goods'] = "可贈券商品";
$_LANG['buy_has_coupon'] = "購買以下商品並滿一定金額可獲得優惠券";
$_LANG['come_user'] = "參加會員";
$_LANG['all_checkbox'] = "全選";

$_LANG['label_coupons_title'] = "優惠券標題：";
$_LANG['coupons_title_example'] = "如:僅可購買手機類指定商品或全品類通用";
$_LANG['coupons_title_tip'] = '需與"可使用商品"設置的商品相對應,做到名副其實';
$_LANG['coupons_total_send_num'] = '優惠券總發行數量';

/* 補充優惠券語言包 */
$_LANG['name_notice'] = "優惠券的名稱";
$_LANG['coupons_title'] = "優惠券標題";
$_LANG['title_notice'] = "需與'可使用商品'設置的商品相對應,做到名副其實";
$_LANG['total_notice'] = "優惠券總發行數量 單位：張";
$_LANG['money_notice'] = "單位：元";
$_LANG['one'] = "1 張";
$_LANG['add_goods'] = "添加商品";
$_LANG['add_cat'] = '添加分類';

$_LANG['title_exist'] = '優惠券 %s 已經存在';
$_LANG['coupon_store_exist'] = '關注店鋪優惠券 %s 已經存在';
$_LANG['copy_url'] = '復制鏈接';

/* 教程名稱 */
$_LANG['tutorials_bonus_list_one'] = '商城優惠券功能說明';

/* 頁面頂部操作提示 */
$_LANG['operation_prompt_content']['view'][0] = '查看優惠券發放或領取記錄。';
$_LANG['operation_prompt_content']['view'][1] = '可進行刪除操作。';

$_LANG['operation_prompt_content']['list'][0] = '展示了優惠券的相關信息列表。';
$_LANG['operation_prompt_content']['list'][1] = '通過優惠券名稱關鍵字、篩選使用類型搜索出具體優惠券信息。';

$_LANG['operation_prompt_content']['info'][0] = '注冊贈券:新用戶注冊成功即發放到用戶賬戶中；';
$_LANG['operation_prompt_content']['info'][1] = '購物贈券:購物滿一定金額即發放到用戶賬戶中(後台發貨後)；';
$_LANG['operation_prompt_content']['info'][2] = '全場贈券:用戶在優惠券首頁或列表頁或商品詳情頁或側邊欄點擊領取；';
$_LANG['operation_prompt_content']['info'][3] = '會員贈券:同全場贈券(可限定領取的會員等級)。';

return $_LANG;
