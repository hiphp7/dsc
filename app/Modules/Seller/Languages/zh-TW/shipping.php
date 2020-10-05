<?php

$_LANG['shipping_transport'] = '運費管理';

$_LANG['shipping_name'] = '配送方式名稱';
$_LANG['shipping_version'] = '插件版本';
$_LANG['shipping_desc'] = '配送方式描述';
$_LANG['shipping_url'] = '網址 (僅供參考)';
$_LANG['shipping_author'] = '插件作者';
$_LANG['insure'] = '保價費用';
$_LANG['support_kdn_print'] = '支持快遞鳥列印';
$_LANG['support_cod'] = '貨到付款';
$_LANG['shipping_area'] = '設置區域';
$_LANG['shipping_print_edit'] = '編輯列印模板';
$_LANG['shipping_print_template'] = '快遞單模板';
$_LANG['shipping_template_info'] = '訂單模板變數說明:<br/>{$shop_name}表示網店名稱<br/>{$province}表示網店所屬省份<br/>{$city}表示網店所屬城市<br/>{$shop_address}表示網店地址<br/>{$service_phone}表示網店聯系電話<br/>{$order.order_amount}表示訂單金額<br/>{$order.region}表示收件人地區<br/>{$order.tel}表示收件人電話<br/>{$order.mobile}表示收件人手機<br/>{$order.zipcode}表示收件人郵編<br/>{$order.address}表示收件人詳細地址<br/>{$order.consignee}表示收件人名稱<br/>{$order.order_sn}表示訂單號';

/* 表單部分 */
$_LANG['shipping_install'] = '安裝配送方式';
$_LANG['install_succeess'] = '配送方式 %s 安裝成功！';
$_LANG['del_lable'] = '刪除標簽';
$_LANG['upload_shipping_bg'] = '上傳列印單圖片';
$_LANG['del_shipping_bg'] = '刪除列印單圖片';
$_LANG['save_setting'] = '保存設置';
$_LANG['recovery_default'] = '恢復默認';
$_LANG['enabled'] = '已啟用';

/* 快遞單部分 */
$_LANG['lable_select_notice'] = '--選擇插入標簽--';
$_LANG['lable_box']['shop_country'] = '網店-國家';
$_LANG['lable_box']['shop_province'] = '網店-省份';
$_LANG['lable_box']['shop_city'] = '網店-城市';
$_LANG['lable_box']['shop_name'] = '網店-名稱';
$_LANG['lable_box']['shop_district'] = '網店-區/縣';
$_LANG['lable_box']['shop_tel'] = '網店-聯系電話';
$_LANG['lable_box']['shop_address'] = '網店-地址';
$_LANG['lable_box']['customer_country'] = '收件人-國家';
$_LANG['lable_box']['customer_province'] = '收件人-省份';
$_LANG['lable_box']['customer_city'] = '收件人-城市';
$_LANG['lable_box']['customer_district'] = '收件人-區/縣';
$_LANG['lable_box']['customer_tel'] = '收件人-電話';
$_LANG['lable_box']['customer_mobel'] = '收件人-手機';
$_LANG['lable_box']['customer_post'] = '收件人-郵編';
$_LANG['lable_box']['customer_address'] = '收件人-詳細地址';
$_LANG['lable_box']['customer_name'] = '收件人-姓名';
$_LANG['lable_box']['year'] = '年-當日日期';
$_LANG['lable_box']['months'] = '月-當日日期';
$_LANG['lable_box']['day'] = '日-當日日期';
$_LANG['lable_box']['order_no'] = '訂單號-訂單';
$_LANG['lable_box']['order_postscript'] = '備注-訂單';
$_LANG['lable_box']['order_best_time'] = '送貨時間-訂單';
$_LANG['lable_box']['pigeon'] = '√-對號';
//$_LANG['lable_box']['custom_content'] = '自定義內容';

/* 提示信息 */
$_LANG['no_shipping_name'] = '對不起，配送方式名稱不能為空。';
$_LANG['no_shipping_desc'] = '對不起，配送方式描述內容不能為空。';
$_LANG['repeat_shipping_name'] = '對不起，已經存在一個同名的配送方式。';
$_LANG['uninstall_success'] = '配送方式 %s 已經成功卸載。';
$_LANG['add_shipping_area'] = '為該配送方式新建配送區域';
$_LANG['no_shipping_insure'] = '對不起，保價費用不能為空，不想使用請將其設置為0';
$_LANG['not_support_insure'] = '該配送方式不支持保價,保價費用設置失敗';
$_LANG['invalid_insure'] = '配送保價費用不是一個合法價格';
$_LANG['no_shipping_install'] = '您的配送方式尚未安裝，暫不能編輯模板';
$_LANG['edit_template_success'] = '快遞模板已經成功編輯。';

/* JS 語言 */
$_LANG['js_languages']['lang_removeconfirm'] = '您確定要卸載該配送方式嗎？';
$_LANG['js_languages']['shipping_area'] = '設置區域';
$_LANG['js_languages']['upload_falid'] = '錯誤：文件類型不正確。請上傳「%s」類型的文件！';
$_LANG['js_languages']['upload_del_falid'] = '錯誤：刪除失敗！';
$_LANG['js_languages']['upload_del_confirm'] = "提示：您確認刪除列印單圖片嗎？";
$_LANG['js_languages']['no_select_upload'] = "錯誤：您還沒有選擇列印單圖片。請使用「瀏覽...」按鈕選擇！";
$_LANG['js_languages']['no_select_lable'] = "操作終止！您未選擇任何標簽。";
$_LANG['js_languages']['no_add_repeat_lable'] = "操作失敗！不允許添加重復標簽。";
$_LANG['js_languages']['no_select_lable_del'] = "刪除失敗！您沒有選中任何標簽。";
$_LANG['js_languages']['recovery_default_suer'] = "您確認恢復默認嗎？恢復默認後將顯示安裝時的內容。";
$_LANG['js_languages']['enter_start_time'] = "請填寫開始時間";
$_LANG['js_languages']['enter_end_time'] = "請填寫結束時間";
$_LANG['js_languages']['zhanghu_set'] = "帳號設置";
$_LANG['js_languages']['set_success'] = "設置成功";
$_LANG['js_languages']['customer_apply'] = "客戶號申請";
$_LANG['js_languages']['subimt_apply_fail'] = "提交申請失敗";
$_LANG['js_languages']['subimt_apply_success'] = "提交申請成功";
$_LANG['enter_start_time'] = "請填寫開始時間";
$_LANG['enter_end_time'] = "請填寫結束時間";

//大商創1.5版本新增 sunle
$_LANG['shipping_time'] = '配送時間';
$_LANG['optional_start_time'] = '可選開始日期';
$_LANG['optional_start_notice'] = '可選開始日期為今天的n天之後，如今天是2013-1-1，填寫（3），則可選的配送日期為2013-1-4號之後（不包含2013-1-4）';
$_LANG['shop_time_notice'] = '例：09:00-19:00';
$_LANG['time_interval'] = '時段';
$_LANG['select_template_pattern'] = '請選擇模板的模式';
$_LANG['code_pattern'] = '代碼模式';
$_LANG['what_you_see_pattern'] = '所見即所得模式';
$_LANG['edit_template'] = '編輯模板';
$_LANG['select_template_pattern'] = '請選擇模板的模式';
$_LANG['kuaidiniao_dayin'] = '快遞鳥列印';
$_LANG['kuaidiniao_set'] = '快遞鳥帳號設置';
$_LANG['zhanghu_set'] = '帳號設置';
$_LANG['default_mode'] = '默認方式';
$_LANG['set_success'] = '默認方式';
$_LANG['customer_apply'] = '客戶號申請';
$_LANG['subimt_apply_fail'] = '提交申請失敗';
$_LANG['subimt_apply_success'] = '提交申請成功';
$_LANG['not_enabled'] = '未啟用';
$_LANG['please_complete'] = '請您完善';

$_LANG['self_lift_time'] = '自提時間段';
$_LANG['edit_self_lift_time'] = '自提時間段';
$_LANG['add_self_lift_time'] = '添加自提時間段';
$_LANG['self_lift_time_list'] = '自提時間段列表';
$_LANG['back_continue_add'] = '返回繼續添加';
$_LANG['back_again_add'] = '返回重新添加';
$_LANG['back_list_page'] = '返回列表頁';
$_LANG['back_again_edit'] = '返回重新編輯';

/* 教程名稱 */
$_LANG['tutorials_bonus_list_one'] = '配送方式使用說明';

$_LANG['operation_prompt_content']['data_list'][0] = '自提點時間段列表。';
$_LANG['operation_prompt_content']['data_list'][1] = '在前台結算頁面上門自取時間選擇中會使用到。';

$_LANG['operation_prompt_content']['list'][0] = '該頁面展示了平台所有配送方式的信息列表。';
$_LANG['operation_prompt_content']['list'][1] = '安裝配送方式後需設置區域方可使用。';
$_LANG['operation_prompt_content']['list'][2] = '支持快遞鳥列印的快遞，部分還需要申請帳號才能正常使用，請按照如下提示進行設置
                        <p><strong class="red">無需申請帳號：</strong>EMS、郵政快遞包裹、順豐、宅急送</p>
                        <p><strong class="red">線上（快遞鳥後台）申請賬號：</strong>韻達、圓通</p>
                        <p><strong class="red">線下（網點）申請賬號：</strong>百世匯通、全峰、申通、天天、中通</p>';

$_LANG['operation_prompt_content']['temp'][0] = '編輯模板分為代碼模式和自定義模式兩種方式，請根據實際情況選擇編輯列印模板。';
$_LANG['operation_prompt_content']['temp'][1] = '列印快遞單模板會在訂單詳情裡面會使用到。';
$_LANG['operation_prompt_content']['temp'][2] = '選擇「代碼模式」可以切換到以前版本。建議您使用「所見即所得模式」。所有模式選擇後，同樣在列印模板中生效。';

return $_LANG;
