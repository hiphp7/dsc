<?php

$_LANG['create_region_initial'] = '生成地區首字母';

/* 欄位信息 */
$_LANG['region_id'] = '地區編號';
$_LANG['region_name'] = '地區名稱';
$_LANG['region_type'] = '地區類型';
$_LANG['region_hierarchy'] = '所在層級';
$_LANG['region_belonged'] = '所屬地區';

$_LANG['city_region_id'] = '市級地區ID';
$_LANG['city_region_name'] = '市級地區名稱';
$_LANG['city_region_initial'] = '首字母';

$_LANG['area'] = '地區';
$_LANG['area_next'] = '以下';
$_LANG['country'] = '一級地區';
$_LANG['province'] = '二級地區';
$_LANG['city'] = '三級地區';
$_LANG['cantonal'] = '四級地區';
$_LANG['street'] = '五級地區';
$_LANG['back_page'] = '返回上一級';
$_LANG['manage_area'] = '管理';
$_LANG['region_name_empty'] = '區域名稱不能為空！';
$_LANG['add_country'] = '新增一級地區';
$_LANG['add_province'] = '新增二級地區';
$_LANG['add_city'] = '增加三級地區';
$_LANG['add_cantonal'] = '增加四級地區';
$_LANG['restore_default_set'] = '恢復默認設置';
$_LANG['region_name_placeholder'] = '請輸入地區名稱';
$_LANG['add_region'] = '新增地區';
$_LANG['confirm_set'] = '你確定要恢復默認設置嗎？';

/* JS語言項 */
$_LANG['js_languages']['region_name_empty'] = '您必須輸入地區的名稱!';
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

/* 恢復默認地區 */
$_LANG['restore_region'] = '恢復默認地區';
$_LANG['restore_success'] = '恢復默認地區成功';
$_LANG['restore_failure'] = '恢復默認地區失敗';

/* 頁面頂部操作提示 */
$_LANG['operation_prompt_content']['initial'][0] = '地區首字母是所有二級市區生成的字母。';
$_LANG['operation_prompt_content']['initial'][1] = '把每個城市按首字母歸類，便於前台查找;注意生成地區首字母是城市不會出現縣級。';

$_LANG['operation_prompt_content']['list'][0] = '在新增一級地區點擊管理進入下一級地區，可進行刪除和編輯。';
$_LANG['operation_prompt_content']['list'][1] = '地區用於商城定位，請根據商城實際情況謹慎設置。';
$_LANG['operation_prompt_content']['list'][2] = '生成地區首字母是方便根據地區首字母搜索相對應的地區。';
$_LANG['operation_prompt_content']['list'][3] = '地區層級關系必須為中國→省/直轄市→市→縣，地區暫只支持到四級地區其後不顯示，暫不支持國外。';

return $_LANG;
