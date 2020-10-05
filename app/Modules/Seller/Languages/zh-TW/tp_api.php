<?php

$_LANG['tpApi']['name'] = '第三方服務';

//快遞鳥
$lang_kdniao = [];
$lang_kdniao['name'] = '快遞鳥';
$lang_kdniao['client_id'] = '用戶ID';
$lang_kdniao['appkey'] = 'API key';
$_LANG['tpApi']['kdniao'] = $lang_kdniao;
$_LANG['kdniao_set'] = "快遞鳥賬號設置";
$_LANG['kdniao_account_use_0'] = "由網站統一設置";
$_LANG['kdniao_account_use_1'] = "商家可自行設置";

//電子面單
$_LANG['printing_type'] = "A4紙張";
$_LANG['order_print_setting_add'] = "添加列印規格";
$_LANG['order_print_setting_edit'] = "編輯列印規格";
$_LANG['specification_exist'] = "該規格已存在，請重新設置";
$_LANG['no_print_setting'] = "沒有設置列印規格";

//提示
$_LANG['add_success'] = "添加成功";
$_LANG['edit_success'] = "編輯成功";
$_LANG['save_success'] = "保存成功";
$_LANG['save_failed'] = "保存失敗";
$_LANG['back_list'] = "返回列表";
$_LANG['back_set'] = "返回設置";

$_LANG['preview'] = "預覽";
$_LANG['print'] = "列印";
$_LANG['express_way'] = "快遞方式";
$_LANG['print_size'] = "列印尺寸";
$_LANG['print_explain'] = "說明：由於不同快遞面單尺寸不同，高度在100mm - 180mm不等，我們將單張面單列印尺寸固定為100mm x 180mm，因此我們建議您使用100mm x 180mm尺寸的熱敏紙進行列印。";
$_LANG['print_express'] = "列印快遞單";
$_LANG['print_title'] = "購物清單";
$_LANG['printer_null'] = "該列印規格還沒有設置指定的列印機";

$_LANG['print_spec'] = "列印規格";
$_LANG['spec'] = "規格";
$_LANG['printer'] = "列印機";
$_LANG['width'] = "寬度";
$_LANG['set_default'] = "設為默認";
$_LANG['order'] = "訂單";

$_LANG['not_select_order'] = "沒有選擇訂單";
$_LANG['select_express_order_print'] = "請選擇快遞方式相同的訂單進行批量列印";
$_LANG['electron_order_fail'] = "電子面單下單失敗";
$_LANG['not_print_template'] = "無列印模板";

$_LANG['printer_name_notic'] = "列印機名稱如：ELP-168ES";
$_LANG['print_spec_width_notic'] = "此處設置的width是列印預覽時寬度超出紙張尺寸的間距調整，默認是紙張尺寸，根據需求自行設置";
//JS語言項
$_LANG['js_languages']['printer_set_notic'] = '該列印規格還沒有設置指定的列印機';
$_LANG['js_languages']['specification_notic'] = '請選擇列印規格';
$_LANG['js_languages']['printer_name_notic'] = '請填寫列印機名稱';

/* 頁面頂部操作提示 */
$_LANG['operation_prompt_content']['setting'][0] = '查看電子面單列印規格。';
$_LANG['operation_prompt_content']['setting'][1] = '可根據規格或列印機進行搜索。';

$_LANG['operation_prompt_content']['setting_info'][0] = '從列表中選擇規格進行設置。';
$_LANG['operation_prompt_content']['setting_info'][1] = '可以將常用規格設置為默認。';

$_LANG['operation_prompt_content']['kdniao'][0] = '使用快遞鳥列印快遞單時需要在此次頁面填寫配置信息';
$_LANG['operation_prompt_content']['kdniao'][1] = '配置快遞鳥API信息';

return $_LANG;
