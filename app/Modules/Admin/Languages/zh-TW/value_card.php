<?php

$_LANG['vc_type_list'] = '儲值卡類型列表';
$_LANG['value_card_list'] = '儲值卡列表';
$_LANG['pc_type_list'] = '充值卡類型列表';
$_LANG['pay_card_list'] = '充值卡列表';
$_LANG['vc_type_add'] = '添加儲值卡類型';
$_LANG['vc_type_edit'] = '編輯儲值卡類型';
$_LANG['value_card_send'] = '儲值卡發放';
$_LANG['notice_remove_type_error'] = '此類型下存在已綁定的儲值卡，請逐個刪除！';
$_LANG['notice_remove_vc_error'] = '已綁定用戶使用，無法刪除';
$_LANG['back_list'] = '返回儲值卡類型列表';
$_LANG['batch_drop_ok'] = '批量刪除成功';
$_LANG['select_merchants'] = '選擇指定店鋪';

/* 儲值卡類型 */
$_LANG['send'] = '發放';
$_LANG['send_amount'] = '發放數量';
$_LANG['use_amount'] = '使用數量';
$_LANG['vc_name'] = '儲值卡名稱';
$_LANG['vc_desc'] = '儲值卡描述';
$_LANG['vc_limit'] = '限制綁定張數';
$_LANG['vc_value'] = '儲值卡面值';
$_LANG['vc_prefix'] = '指定儲值卡前綴';
$_LANG['vc_dis'] = '折扣率';
$_LANG['use_condition'] = '使用條件';
$_LANG['all_goods'] = '所有商品';
$_LANG['spec_cat'] = '指定分類';
$_LANG['spec_goods'] = '指定商品';
$_LANG['add_goods'] = '添加商品';
$_LANG['add_cat'] = '添加分類';
$_LANG['begin_time'] = '開始時間';
$_LANG['end_time'] = '結束時間';
$_LANG['indate'] = '有效期';
$_LANG['is_rec'] = '可否充值';
$_LANG['vc_desc_info'] = '儲值卡描述信息';
$_LANG['notice_value'] = '單位：元';
$_LANG['notice_limit'] = '單位：張';
$_LANG['notice_prefix'] = '支持英文或者數字';
$_LANG['notice_dis'] = '%&nbsp&nbsp&nbsp&nbsp 1-100之間的整數';
$_LANG['months'] = '個月';
$_LANG['notice_indate'] = '個自然月';
$_LANG['use_merchants'] = '使用店鋪';
$_LANG['all_merchants'] = '所有店鋪';
$_LANG['self_merchants'] = '自營店鋪';
$_LANG['assign_merchants'] = '指定店鋪';
$_LANG['choice_merchant'] = '選擇店鋪';

/* 儲值卡發放 */
$_LANG['send_num'] = '發放數量';
$_LANG['card_type'] = '生成卡號類型';
$_LANG['password_type'] = '生成密碼類型';
$_LANG['notice_send_num'] = '張';
$_LANG['eight'] = '8位純數字卡號';
$_LANG['twelve'] = '12位純數字卡號';
$_LANG['sixteen'] = '16位純數字卡號';
$_LANG['eight_psd'] = '8位數字字母混合密碼';
$_LANG['twelve_psd'] = '12位數字字母混合密碼';
$_LANG['sixteen_psd'] = '16位數字字母混合密碼';
$_LANG['creat_value_card'] = '生成了';
$_LANG['value_card_num'] = '個儲值卡序列號';

/* 儲值卡列表 */
$_LANG['value_card_sn'] = '序列號';
$_LANG['value_card_password'] = '密碼';
$_LANG['value_card_type'] = '儲值卡類型';
$_LANG['value_card_money'] = '余額';
$_LANG['value_card_value'] = '面值';
$_LANG['bind_user'] = '綁定用戶';
$_LANG['bind_time'] = '綁定時間';
$_LANG['no_use'] = 'N/A';

//導出
$_LANG['export_vc_list'] = "導出儲值卡";

/*js語言項*/
$_LANG['js_languages']['send_num_null'] = '請輸入發放張數';
$_LANG['js_languages']['send_num_number'] = '請輸入正確的數字';
$_LANG['js_languages']['card_name_null'] = '請輸入用戶名';
$_LANG['js_languages']['card_limit_null'] = '請輸入儲值卡限制張數';
$_LANG['js_languages']['card_vaule_number'] = '請輸入正確的數字';
$_LANG['js_languages']['card_vaule_null'] = '請輸入優惠券張數';
$_LANG['js_languages']['card_prefix_null'] = '請輸入儲值卡前綴';
$_LANG['js_languages']['card_prefix_duplicate'] = '儲值卡前綴重復';
$_LANG['js_languages']['card_en_number'] = '僅支持英文字母或數字';
$_LANG['js_languages']['card_goodscat_exits'] = '分類已經存在了';

/* 教程名稱 */
$_LANG['tutorials_bonus_list_one'] = '商城儲值卡功能說明';

/* 頁面頂部操作提示 */
$_LANG['operation_prompt_content']['send'][0] = '發放儲值卡的數量即儲值卡的使用次數。';
$_LANG['operation_prompt_content']['send'][1] = '注意卡號類型和密碼類型的生成位數，越多數量的密碼記憶更加困難。';

$_LANG['operation_prompt_content']['view'][0] = '一種類型下的儲值卡列表。';
$_LANG['operation_prompt_content']['view'][1] = '顯示每個儲值卡的序列號和密碼。';
$_LANG['operation_prompt_content']['view'][2] = '在會員中心，我的儲值卡處可進行綁定儲值卡。';

$_LANG['operation_prompt_content']['info'][0] = '儲值卡類型添加後，需發放儲值卡，填寫發放數量，則該類型的儲值卡將能被多次利用。';

$_LANG['operation_prompt_content']['list'][0] = '如需給會員開放儲值卡功能，需在平台-商店設置-購物流程中開啟使用儲值卡，默認為不開啟。';
$_LANG['operation_prompt_content']['list'][1] = '一種類型的儲值卡可發放多個儲值卡，具體數量需根據目標用戶來定。';

return $_LANG;
