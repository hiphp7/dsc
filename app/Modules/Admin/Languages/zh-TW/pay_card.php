<?php

$_LANG['vc_type_list'] = '儲值卡類型列表';
$_LANG['value_card_list'] = '儲值卡列表';
$_LANG['pc_type_list'] = '充值卡類型列表';
$_LANG['pay_card_list'] = '充值卡列表';
$_LANG['pc_type_add'] = '添加充值卡類型';
$_LANG['pc_type_edit'] = '編輯充值卡類型';
$_LANG['pay_card_send'] = '充值卡發放';
$_LANG['notice_remove_type_error'] = '此類型下存在已使用的充值卡，請逐個刪除！';

/* 充值卡類型欄位信息 */
$_LANG['bonus_type'] = '充值卡類型';
$_LANG['bonus_list'] = '充值卡列表';
$_LANG['type_name'] = '類型名稱';
$_LANG['type_money'] = '充值卡金額';
$_LANG['type_prefix'] = '生成充值卡前綴';
$_LANG['send_count'] = '發放數量';
$_LANG['use_count'] = '使用數量';
$_LANG['handler'] = '操作';
$_LANG['send'] = '發放';
$_LANG['type_money_notic'] = '此類型充值卡可以充值的金額，例如：100';
$_LANG['use_enddate'] = '有效期';
$_LANG['use_enddate'] = '有效期';
$_LANG['back_list'] = '返回充值卡管理列表';
$_LANG['continus_add'] = '繼續添加充值卡管理';
$_LANG['send_bonus'] = '生成充值卡';
$_LANG['creat_bonus'] = '生成了 ';
$_LANG['creat_bonus_num'] = ' 個充值卡';
$_LANG['back_bonus_list'] = '返回充值卡列表';
$_LANG['bonus_sn'] = '充值卡卡號';
$_LANG['bonus_psd'] = '充值卡密碼';
$_LANG['batch_drop_success'] = '成功刪除了 %d 個充值卡';
$_LANG['back_bonus_list'] = '返回充值卡列表';
$_LANG['no_select_bonus'] = '您沒有選擇需要刪除的充值卡';
$_LANG['attradd_succed'] = '操作成功!';
$_LANG['used_time'] = '使用時間';
$_LANG['user_id'] = '使用會員';
$_LANG['bonus_excel_file'] = '充值卡信息列表';
$_LANG['type_name_exist'] = '充值卡類型名稱已存在';
$_LANG['notice_prefix'] = '支持英文或者數字';

/* 充值卡發放 */
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
$_LANG['pay_card_num'] = '個充值卡序列號';

//導出
$_LANG['export_pc_list'] = "導出充值卡";
$_LANG['no_use'] = 'N/A';

/* JS提示信息 */
$_LANG['js_languages']['send_num_null'] = '請輸入發放張數';
$_LANG['js_languages']['send_num_number'] = '請輸入正確的數字';

$_LANG['js_languages']['type_name_null'] = '請輸入充值卡類型名稱';
$_LANG['js_languages']['type_money_null'] = '請輸入充值卡面值';
$_LANG['js_languages']['type_money_number'] = '請輸入正確的數字';
$_LANG['js_languages']['type_prefix_null'] = '請輸入生成充值卡前綴';
$_LANG['js_languages']['type_prefix_gs'] = '輸入格式不正確';
$_LANG['js_languages']['use_end_date_null'] = '請選擇有效期';

/* 頁面頂部操作提示 */
$_LANG['operation_prompt_content']['pc_type_list'][0] = '充值卡可給儲值卡充值。';
$_LANG['operation_prompt_content']['pc_type_list'][1] = '儲值卡充值後可循環使用。';
$_LANG['operation_prompt_content']['pc_type_list'][2] = '充值卡也可以循環使用。';

$_LANG['operation_prompt_content']['pc_type_info'][0] = '充值卡類型類似於儲值卡。';

$_LANG['operation_prompt_content']['info'][0] = '生成充值卡的數量。';
$_LANG['operation_prompt_content']['info'][1] = '具體生成數量根據需求設定。';
$_LANG['operation_prompt_content']['info'][2] = '注意卡號類型和密碼類型的生成位數，越多數量的密碼記憶更加困難。';

$_LANG['operation_prompt_content']['view'][0] = '一種類型下的充值卡列表。';
$_LANG['operation_prompt_content']['view'][1] = '顯示每個充值卡的卡號和密碼。';

return $_LANG;
