<?php

/* 欄位信息 */
$_LANG['region_id'] = '地區編號';
$_LANG['region_name'] = '地區名稱';
$_LANG['region_type'] = '地區類型';
$_LANG['belonged_to_region'] = '所屬地區';
$_LANG['region_code'] = '地區編碼';
$_LANG['belonged_to_warehouse'] = '所在倉庫';
$_LANG['warehouse_list'] = '倉庫列表';
$_LANG['code'] = '編碼';
$_LANG['region_list'] = '地區列表';
$_LANG['region_manage'] = '區域管理';

$_LANG['05_area_list_01'] = '倉庫管理';
$_LANG['warehouse_freight_template'] = '倉庫運費模板';
$_LANG['data_list'] = '自提時間段';

$_LANG['area'] = '地區';
$_LANG['area_next'] = '以下';
$_LANG['country'] = '一級地區';
$_LANG['province'] = '二級地區';
$_LANG['city'] = '三級地區';
$_LANG['cantonal'] = '四級地區';
$_LANG['back_page'] = '返回上一級';
$_LANG['manage_area'] = '管理';
$_LANG['region_name_empty'] = '區域名稱不能為空！';
$_LANG['add_country'] = '新增一級地區';
$_LANG['add_province'] = '新增二級地區';
$_LANG['add_city'] = '增加三級地區';
$_LANG['add_cantonal'] = '增加四級地區';
$_LANG['class_one'] = '一級';

/* JS語言項 */
$_LANG['js_languages']['region_name_empty'] = '您必須輸入地區的名稱!';
$_LANG['js_languages']['warehouse_name_empty'] = '您必須輸入倉庫的名稱!';
$_LANG['js_languages']['select_region_name_empty'] = '請選擇地區名稱';
$_LANG['js_languages']['option_name_empty'] = '必須輸入調查選項名稱!';
$_LANG['js_languages']['drop_confirm'] = '您確定要刪除這條記錄嗎?';
$_LANG['js_languages']['drop'] = '刪除';
$_LANG['js_languages']['country'] = '一級地區';
$_LANG['js_languages']['province'] = '二級地區';
$_LANG['js_languages']['city'] = '三級地區';
$_LANG['js_languages']['cantonal'] = '四級地區';

/* 提示信息 */
$_LANG['add_area_error'] = '添加新地區失敗!';
$_LANG['region_name_exist'] = '已經有相同的地區名稱存在!';
$_LANG['parent_id_exist'] = '該區域下有其它下級地區存在, 不能刪除!';
$_LANG['form_notic'] = '點擊查看下級地區';
$_LANG['area_drop_confirm'] = '如果訂單或用戶默認配送方式中使用以下地區，這些地區信息將顯示為空。您確認要刪除這條記錄嗎?';
$_LANG['region_code_exist'] = '已經有相同的編碼存在!';

//運費
$_LANG['fee_compute_mode'] = '費用計算方式';
$_LANG['fee_by_weight'] = '按重量計算';
$_LANG['fee_by_number'] = '按商品件數計算';
$_LANG['free_money'] = '免費額度';
$_LANG['pay_fee'] = '貨到付款支付費用';

$_LANG['not_find_plugin'] = '沒有找到指定的配送方式的插件。';

$_LANG['originating_place'] = '始發地';
$_LANG['reach_the_destination'] = '到達目的地';
$_LANG['logistics_distribution'] = '物流配送';
$_LANG['logistics_info'] = '物流信息';
$_LANG['select_logistics_company'] = '已選擇物流公司';
$_LANG['freight'] = '運費';
$_LANG['new_add_warehouse'] = '新增倉庫';
$_LANG['warehouse_new_add_region'] = '倉庫新增地區';
$_LANG['add_region'] = '新增地區';
$_LANG['freight_guanli'] = '運費管理';
$_LANG['distribution_mode'] = '配送方式';
$_LANG['distribution_mode_desc'] = '配送方式描述';
$_LANG['not_distribution_mode'] = '未添加配送方式';
$_LANG['set_distribution_mode'] = '設置倉庫運費模板';

$_LANG['warehouse_confirm'] = '確定要移除選定的運費模板么？';
$_LANG['freight_template_name'] = '運費模板名稱';
$_LANG['originating_warehouse'] = '始發倉庫';
$_LANG['reach_region'] = '抵達地區';
$_LANG['warehouse_confirm_code'] = '確定刪除編號為';
$_LANG['warehouse_confirm_code_2'] = '的模板嗎？';

$_LANG['remove_success'] = '移除成功';
$_LANG['remove_fail'] = '移除失敗';

$_LANG['warehouse_freight_formwork_list'] = '倉庫運費模板列表 - ';
$_LANG['edit_warehouse_freight_formwork'] = '倉庫運費模板編輯 - ';
$_LANG['warehouse_freight_formwork'] = '倉庫運費模板_';
$_LANG['add_freight_formwork'] = '新增運費模板';
$_LANG['back_dispatching_llist'] = '返回配送列表';
$_LANG['back_template_llist'] = '返回模板列表';

$_LANG['info_fillin_complete'] = '請將信息填寫完整';
$_LANG['add_freight_success'] = '運費添加成功';
$_LANG['template_arrive_region'] = '模板抵達地區已存在！';

$_LANG['template_add_success'] = '模板添加成功';
$_LANG['template_edit_success'] = '模板修改成功';

$_LANG['freight_add_success'] = '運費添加成功';
$_LANG['freight_edit_success'] = '運費編輯成功';

$_LANG['select_dispatching_mode'] = '請選擇配送方式';

/* 頁面頂部操作提示 */
$_LANG['operation_prompt_content']['freight'][0] = '可添加多個配送方式。';

$_LANG['operation_prompt_content']['list'][0] = '倉庫下的地區，展示一級倉庫下的所有地區。';
$_LANG['operation_prompt_content']['list'][1] = '倉庫地區最多可添加到四級地區。';

$_LANG['operation_prompt_content']['list_stair'][0] = '可在輸入框輸入倉庫名稱進行添加新倉庫。';
$_LANG['operation_prompt_content']['list_stair'][1] = '一級倉庫管理可新增該倉庫的地區。';
$_LANG['operation_prompt_content']['list_stair'][2] = '倉庫會在添加商品選擇倉庫模式時會使用到，在前台商品詳情頁配送也會用到，請謹慎添加倉庫。';

$_LANG['operation_prompt_content']['shopping_list'][0] = '展示所有倉庫配送方式。';
$_LANG['operation_prompt_content']['shopping_list'][1] = '給倉庫配送方式設置運費模板，如果倉庫單個地區沒有設置倉庫運費時，系統會默認調用倉庫運費模板。';

$_LANG['operation_prompt_content']['tpl_info'][0] = '當目的地不選擇地區添加地區時會添加「<em>全國</em>」';

$_LANG['operation_prompt_content']['tpl_list'][0] = '展示所有倉庫配送方式運費模板列表。」';
$_LANG['operation_prompt_content']['tpl_list'][1] = '可以設置倉庫到不同地區的運費，根據實際需求謹慎設置。';

// 商家後台
$_LANG['select_warehouse'] = '請選擇倉庫';
$_LANG['select_deliver'] = '請選擇物流配送';
$_LANG['delet_tpl_id_1'] = '確定刪除編號為';
$_LANG['delet_tpl_id_2'] = '的模板嗎？';
$_LANG['label_express_deliver'] = '物流配送：';
$_LANG['label_start_send_area'] = '始發地：';
$_LANG['label_terminus_ad_quem'] = '到達目的地：';


return $_LANG;
