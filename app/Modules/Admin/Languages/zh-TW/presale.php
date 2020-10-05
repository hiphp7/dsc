<?php

/* 當前頁面標題及可用鏈接名稱 */
$_LANG['presale_list'] = '預售活動列表';
$_LANG['add_presale'] = '添加預售活動';
$_LANG['edit_presale'] = '編輯預售活動';
$_LANG['add_presale_cat'] = '添加預售分類';
$_LANG['presale_cat_list'] = '預售分類列表';

/* 活動列表頁 */
$_LANG['goods_name'] = '商品名稱';
$_LANG['start_date'] = '開始時間';
$_LANG['end_date'] = '結束時間';
$_LANG['deposit'] = '定金';
$_LANG['restrict_amount'] = '限購';
$_LANG['gift_integral'] = '贈送積分';
$_LANG['valid_order'] = '訂單';
$_LANG['valid_goods'] = '訂購商品';
$_LANG['current_price'] = '當前價格';
$_LANG['current_status'] = '狀態';
$_LANG['view_order'] = '查看訂單';

/* 添加/編輯活動頁 */
$_LANG['goods_cat'] = '商品分類';
$_LANG['all_cat'] = '所有分類';
$_LANG['goods_brand'] = '商品品牌';
$_LANG['all_brand'] = '所有品牌';

$_LANG['label_goods_name'] = '預售商品：';
$_LANG['label_act_name'] = '活動名稱：';
$_LANG['label_presale_cat'] = '活動分類：';
$_LANG['select_option'] = '請選擇……';
$_LANG['notice_goods_name'] = '請先搜索商品,在此生成選項列表...';
$_LANG['label_start_date'] = '活動開始時間：';
$_LANG['label_end_date'] = '活動結束時間：';
$_LANG['label_start_end_date'] = '支付定金時間：';
$_LANG['notice_datetime'] = '（年月日－時）';
$_LANG['label_deposit'] = '定金：';
$_LANG['label_restrict_amount'] = '限購數量：';
$_LANG['notice_restrict_amount'] = '達到此數量，預售活動自動結束。0表示沒有數量限制。';
$_LANG['label_gift_integral'] = '贈送積分數：';
$_LANG['label_price_ladder'] = '價格階梯：';
$_LANG['notice_ladder_amount'] = '數量達到';
$_LANG['notice_ladder_price'] = '享受價格';
$_LANG['label_desc'] = '活動說明：';
$_LANG['label_status'] = '活動當前狀態：';
$_LANG['gbs'][GBS_PRE_START] = '未開始';
$_LANG['gbs'][GBS_UNDER_WAY] = '進行中';
$_LANG['gbs'][GBS_FINISHED] = '結束未處理';
$_LANG['gbs'][GBS_SUCCEED] = '成功結束';
$_LANG['gbs'][GBS_FAIL] = '失敗結束';
$_LANG['label_order_qty'] = '訂單數 / 有效訂單數：';
$_LANG['label_goods_qty'] = '商品數 / 有效商品數：';
$_LANG['label_cur_price'] = '當前價：';
$_LANG['label_end_price'] = '最終價：';
$_LANG['label_handler'] = '操作：';
$_LANG['error_presale'] = '您要操作的預售活動不存在';
$_LANG['error_status'] = '當前狀態不能執行該操作！';
$_LANG['button_finish'] = '結束活動';
$_LANG['notice_finish'] = '（修改活動結束時間為當前時間）';
$_LANG['button_succeed'] = '活動成功';
$_LANG['notice_succeed'] = '（更新訂單價格）';
$_LANG['button_fail'] = '活動失敗';
$_LANG['notice_fail'] = '（取消訂單，保證金退回帳戶余額，失敗原因可以寫到活動說明中）';
$_LANG['cancel_order_reason'] = '預售失敗';
$_LANG['js_languages']['succeed_confirm'] = '此操作不可逆，您確定要設置該預售活動成功嗎？';
$_LANG['js_languages']['fail_confirm'] = '此操作不可逆，您確定要設置該預售活動失敗嗎？';
$_LANG['button_mail'] = '發送郵件';
$_LANG['notice_mail'] = '（通知客戶付清餘款，以便發貨）';
$_LANG['mail_result'] = '該預售活動共有 %s 個有效訂單，成功發送了 %s 封郵件。';
$_LANG['invalid_time'] = '您輸入了一個無效的預售時間。';

$_LANG['add_success'] = '添加預售活動成功。';
$_LANG['edit_success'] = '編輯預售活動成功。';
$_LANG['back_list'] = '返回預售活動列表。';
$_LANG['continue_add'] = '繼續添加預售活動。';
$_LANG['PS'] = '預售商品需先下架';
$_LANG['pay_start_end'] = '支付尾款時間：';
$_LANG['shop_price'] = '本店價：';

$_LANG['ess_info'] = '基本信息';
$_LANG['act_help'] = '活動說明';
$_LANG['order'] = '訂單';


$_LANG['select_search_presale_goods'] = '請選擇搜索預售商品';
$_LANG['integral_goods_adopt_status_success'] = '積分商品審核狀態設置成功';

/* 添加/編輯活動提交 */
$_LANG['error_goods_null'] = '您沒有選擇預售商品！';
$_LANG['error_goods_exist'] = '您選擇的商品目前有一個預售活動正在進行！';
$_LANG['error_price_ladder'] = '您沒有輸入有效的價格階梯！';
$_LANG['error_restrict_amount'] = '限購數量不能小於價格階梯中的最大數量';

$_LANG['js_languages']['error_goods_null'] = '您沒有選擇預售商品！';
$_LANG['js_languages']['error_deposit'] = '您輸入的保證金不是數字！';
$_LANG['js_languages']['error_restrict_amount'] = '您輸入的限購數量不是整數！';
$_LANG['js_languages']['error_gift_integral'] = '您輸入的贈送積分數不是整數！';
$_LANG['js_languages']['search_is_null'] = '沒有搜索到任何商品，請重新搜索';
$_LANG['js_languages']['select_cat_null'] = '請選擇活動分類';
$_LANG['js_languages']['pay_start_time_null'] = '支付尾款開始時間不能為空';
$_LANG['js_languages']['pay_start_time_cw'] = '支付尾款開始時間必須大於支付定金結束時間';
$_LANG['js_languages']['pay_end_time_null'] = '支付尾款結束時間不能為空';
$_LANG['js_languages']['pay_end_time_cw'] = '支付尾款結束時間必須大於支付尾款開始時間';

/* 刪除預售活動 */
$_LANG['js_languages']['batch_drop_confirm'] = '您確定要刪除選定的預售活動嗎？';
$_LANG['error_exist_order'] = '該預售活動已經有訂單，不能刪除！';
$_LANG['batch_drop_success'] = '成功刪除了 %s 條預售活動記錄（已經有訂單的預售活動不能刪除）。';
$_LANG['no_select_presale'] = '您現在沒有預售活動記錄！';

/* 教程名稱 */
$_LANG['tutorials_bonus_list_one'] = '第三方登錄插件使用說明';

/* 操作日誌 */
$_LANG['log_action']['group_buy'] = '預售商品';
$_LANG['notice_act_name'] = '如果留空，取拍賣商品的名稱（該名稱僅用於後台，前台不會顯示）';

/* 頁面頂部操作提示 */
$_LANG['operation_prompt_content']['info'][0] = '注意預售商品需要先下架。';
$_LANG['operation_prompt_content']['info'][1] = '注意支付尾款的時間要在支付定金的時間之後。';
$_LANG['operation_prompt_content']['info'][2] = '前台在預售頻道可查看添加的預售活動信息。';
$_LANG['operation_prompt_content']['info'][3] = '注意如果沒有填寫預售」活動說明「，默認調取商品詳情描述。';

$_LANG['operation_prompt_content']['list'][0] = '展示了預售活動的相關信息列表。';
$_LANG['operation_prompt_content']['list'][1] = '可通過搜索關鍵字商品名稱，篩選店鋪名稱搜索出具體預售活動信息。';
$_LANG['operation_prompt_content']['list'][2] = '可進行增加、編輯、修改、刪除等操作，查看預售活動訂單。';

return $_LANG;
