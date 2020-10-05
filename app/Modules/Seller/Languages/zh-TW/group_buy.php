<?php

$_LANG['lab_market_price'] = '市場售價：';

/* 當前頁面標題及可用鏈接名稱 */
$_LANG['group_buy_list'] = '團購活動列表';
$_LANG['add_group_buy'] = '添加團購活動';
$_LANG['edit_group_buy'] = '編輯團購活動';

/* 活動列表頁 */
$_LANG['goods_name'] = '商品名稱';
$_LANG['start_date'] = '開始時間';
$_LANG['end_date'] = '結束時間';
$_LANG['deposit'] = '保證金';
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

$_LANG['new'] = '新品';
$_LANG['hot'] = '熱銷';

$_LANG['label_goods_name'] = '團購商品：';
$_LANG['notice_goods_name'] = '請先搜索商品,在此生成選項列表...';
$_LANG['label_start_date'] = '活動開始時間：';
$_LANG['label_end_date'] = '活動結束時間：';
$_LANG['label_start_end_date'] = '活動起止時間：';
$_LANG['notice_datetime'] = '（年月日－時）';
$_LANG['label_deposit'] = '保證金：';
$_LANG['label_restrict_amount'] = '限購數量：';
$_LANG['notice_restrict_amount'] = '達到此數量，團購活動自動結束。0表示沒有數量限制。';
$_LANG['label_gift_integral'] = '贈送積分數：';
$_LANG['notice_start_time'] = '團購活動已開始則無法修改開始時間。';
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
$_LANG['error_group_buy'] = '您要操作的團購活動不存在';
$_LANG['error_status'] = '當前狀態不能執行該操作！';
$_LANG['button_finish'] = '結束活動';
$_LANG['notice_finish'] = '（修改活動結束時間為當前時間）';
$_LANG['button_succeed'] = '活動成功';
$_LANG['notice_succeed'] = '（更新訂單價格）';
$_LANG['button_fail'] = '活動失敗';
$_LANG['notice_fail'] = '（取消訂單，保證金退回帳戶余額，失敗原因可以寫到活動說明中）';
$_LANG['cancel_order_reason'] = '團購失敗';
$_LANG['js_languages']['succeed_confirm'] = '此操作不可逆，您確定要設置該團購活動成功嗎？';
$_LANG['js_languages']['fail_confirm'] = '此操作不可逆，您確定要設置該團購活動失敗嗎？';
$_LANG['button_mail'] = '發送郵件';
$_LANG['notice_mail'] = '（通知客戶付清餘款，以便發貨）';
$_LANG['mail_result'] = '該團購活動共有 %s 個有效訂單，成功發送了 %s 封郵件。';
$_LANG['invalid_time'] = '您輸入了一個無效的團購時間。';

$_LANG['add_success'] = '添加團購活動成功。';
$_LANG['edit_success'] = '編輯團購活動成功。';
$_LANG['back_list'] = '返回團購活動列表。';
$_LANG['continue_add'] = '繼續添加團購活動。';

/* 添加/編輯活動提交 */
$_LANG['error_goods_null'] = '您沒有選擇團購商品！';
$_LANG['error_goods_exist'] = '您選擇的商品目前有一個團購活動正在進行！';
$_LANG['error_price_ladder'] = '您沒有輸入有效的價格階梯！';
$_LANG['error_restrict_amount'] = '限購數量不能小於價格階梯中的最大數量';

$_LANG['js_languages']['error_goods_null'] = '您沒有選擇團購商品！';
$_LANG['js_languages']['error_deposit'] = '您輸入的保證金不是數字！';
$_LANG['js_languages']['error_restrict_amount'] = '您輸入的限購數量不是整數！';
$_LANG['js_languages']['error_gift_integral'] = '您輸入的贈送積分數不是整數！';
$_LANG['js_languages']['search_is_null'] = '沒有搜索到任何商品，請重新搜索';
$_LANG['js_languages']['error_goods_price'] = '您沒有輸入有效的價格階梯價格';
$_LANG['js_languages']['error_goods_nunber'] = '您沒有輸入有效的價格階梯數量';
$_LANG['js_languages']['ladder_price_min_notice'] = '階梯價格不能小於保證金金額！';

/* 刪除團購活動 */
$_LANG['js_languages']['batch_drop_confirm'] = '您確定要刪除選定的團購活動嗎？';
$_LANG['error_exist_order'] = '該團購活動已經有訂單，不能刪除！';
$_LANG['batch_drop_success'] = '成功刪除了 %s 條團購活動記錄。';
$_LANG['no_select_group_buy'] = '您現在沒有團購活動記錄！';

/* 操作日誌 */
$_LANG['log_action']['group_buy'] = '團購商品';

/* 教程名稱 */
$_LANG['tutorials_bonus_list_one'] = '商城團購活動說明';

/* 頁面頂部操作提示 */
$_LANG['operation_prompt_content']['list'][0] = '團購活動列表展示商品的團購相關信息。';
$_LANG['operation_prompt_content']['list'][1] = '可根據條件，如商品名稱、店鋪名稱等搜索團購商品。';
$_LANG['operation_prompt_content']['list'][2] = '可查看團購商品的訂單列表（可進行訂單相關操作）。';
$_LANG['operation_prompt_content']['list'][3] = '可添加、編輯、刪除或批量刪除團購活動。';

$_LANG['operation_prompt_content']['info'][0] = '活動規則：團購活動時間內，根據後台設置的價格階梯，買家先支付保證金，根據商家評判標准決定活動是否成功，如果成功，買家根據階梯價格支付尾款；如果失敗，已支付保證金退回買家賬戶。';
$_LANG['operation_prompt_content']['info'][1] = '前台可在團購頻道頁看到該參團活動的商品。';
$_LANG['operation_prompt_content']['info'][2] = '保證金：保證金為0時，根據階梯價格支付全款購買。當保證金大於0時，階梯價格的最小值不得小於保證金金額，活動開始後保證金金額無法修改。';

return $_LANG;
