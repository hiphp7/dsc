<?php

$_LANG['bt_ur_here'] = '設置會員白條額度';
$_LANG['bt_list'] = '白條列表';
$_LANG['bt_details'] = '白條詳情';
$_LANG['user_name'] = "會員名稱";
$_LANG['financial_credit'] = "金融額度";
$_LANG['Credit_payment_days'] = '信用賬期';
$_LANG['Suspended_term'] = "信用賬期緩期期限";
$_LANG['user_baitiao_credit'] = "會員白條額度";
$_LANG['yes_delete_biaotiao'] = "你確定要刪除該會員的會員白條嗎？";
$_LANG['yes_delete_record'] = "你確定要刪除該消費記錄嗎？";

$_LANG['total_amount'] = "總額度";
$_LANG['residual_amount'] = "剩餘額度";
$_LANG['repayments_amount'] = "已還款總額";
$_LANG['pending_repayment_amount'] = "待還款總額";
$_LANG['baitiao_number'] = "白條數";

$_LANG['consumption_money'] = "消費記錄";
$_LANG['billing_day'] = "消費記賬日";
$_LANG['repayment_data'] = "客戶還款日";
$_LANG['repayment_cycle'] = "還款周期";
$_LANG['order_amount'] = '應付金額';
$_LANG['conf_pay'] = "支付狀態";

$_LANG['baitiao_by_stage'] = "白條分期";
$_LANG['order_refund'] = "已失效,訂單已退款";
$_LANG['yuan_stage'] = "元/期";
$_LANG['stage'] = "期";
$_LANG['is_pay'] = '已付款';
$_LANG['dai_pay'] = '待付款';

$_LANG['users_note'] = "基本信息";
$_LANG['address_list'] = '收貨地址';
$_LANG['view_order'] = '查看訂單';
$_LANG['set_baitiao'] = "設置白條";
$_LANG['account_log'] = '賬目明細';

$_LANG['baitiao_batch_set'] = '白條批量設置';
$_LANG['baitiao_update_success'] = '會員白條更新成功！';
$_LANG['baitiao_set_success'] = '會員白條設置成功！';
$_LANG['baitiao_remove_success'] = '刪除白條成功';
$_LANG['no_del_num_notic'] = '條待付款記錄不可刪除';
$_LANG['remove_consume_success'] = '刪除消費記錄成功';
$_LANG['biao_remove_consume_success'] = '刪除白條記錄成功';
$_LANG['pay_not_consume_remove'] = '待付款的記錄不可刪除';

/*提示*/
$_LANG['notice_financial_credit'] = '元 如:3000元　(用戶可支配的信用款額度)';
$_LANG['notice_Credit_payment_days'] = '天 如:30天　(會根據此值自動生成還款日期,到期未還款,用戶將不能夠用白條方式支付)';
$_LANG['notice_Suspended_term'] = "天 如:10天　(超過信用期限+該文本設置的天數總和後，用戶將不能夠下單)";

/* js 驗證提示 */
$_LANG['confirm_bath'] = '你確定要刪除所選的會員白條嗎？';
$_LANG['js_languages']['amount_not_null'] = '金融額度不能為空';
$_LANG['js_languages']['repay_term_not_null'] = '信用賬期不能為空';
$_LANG['js_languages']['over_repay_trem_not_null'] = '信用賬期緩期期限不能為空';

/* 頁面頂部操作提示 */
$_LANG['operation_prompt_content']['baitiao_list'][0] = '該頁面展示了會員白條相關信息。';
$_LANG['operation_prompt_content']['baitiao_list'][1] = '可查看白條消費的訂單信息，可設置白條額度等操作。';
$_LANG['operation_prompt_content']['baitiao_list'][2] = '可以輸入會員名稱關鍵字進行搜索。';

$_LANG['operation_prompt_content']['baitiao_log_list'][0] = '該頁面展示白條消費訂單信息。';
$_LANG['operation_prompt_content']['baitiao_log_list'][1] = '請謹慎操作白條信息。';

return $_LANG;
