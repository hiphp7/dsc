<?php

return [

    /*
    |--------------------------------------------------------------------------
    | bonus Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during bonus for various
    | messages that we need to display to the user. You are free to modify
    | these language lines according to your application's requirements.
    |
    */
    /* 紅包類型欄位信息 */
    'bonus_type' => '紅包類型',
    'bonus_list' => '紅包列表',
    'type_name' => '類型名稱',
    'type_money' => '紅包金額',
    'min_goods_amount' => '最小訂單金額',
    'notice_min_goods_amount' => '只有商品總金額達到這個數的訂單才能使用這種紅包',
    'min_amount' => '訂單下限',
    'max_amount' => '訂單上限',
    'send_startdate' => '發放起始日期',
    'send_enddate' => '發放結束日期',
    'add_bonus_type' => '添加紅包類型',

    'use_startdate' => '使用起始日期',
    'use_enddate' => '使用結束日期',
    'send_count' => '發放量',
    'use_count' => '使用量',
    'send_method' => '如何發放此類型紅包',
    'send_type' => '發放類型',
    'param' => '參數',
    'no_use' => '未使用',
    'yuan' => '元',
    'user_list' => '會員列表',
    'type_name_empty' => '紅包類型名稱不能為空！',
    'type_money_empty' => '紅包金額不能為空！',
    'min_amount_empty' => '紅包類型的訂單下限不能為空！',
    'max_amount_empty' => '紅包類型的訂單上限不能為空！',
    'send_count_empty' => '紅包類型的發放數量不能為空！',
    'not_select_any_data' => '沒有選擇任何數據',
    'bonus_audited_type_set_ok' => '紅包類型審核狀態設置成功',

    'send_by' => [
        SEND_BY_USER => '按用戶發放',
        SEND_BY_GOODS => '按商品發放',
        SEND_BY_ORDER => '按訂單金額發放',
        SEND_BY_PRINT => '線下發放的紅包',
        SEND_BY_GET => '自行領取',
    ],
    'report_form' => '報表下載',
    'send' => '發放',
    'bonus_excel_file' => '線下紅包信息列表',
    'batch_drop_ok' => '批量刪除成功',

    'goods_cat' => '選擇商品分類',
    'goods_brand' => '商品品牌',
    'goods_key' => '商品關鍵字',
    'all_goods' => '可選商品',
    'send_bouns_goods' => '發放此類型紅包的商品',
    'remove_bouns' => '移除紅包',
    'all_remove_bouns' => '全部移除',
    'goods_already_bouns' => '該商品已經發放過其它類型的紅包了!',
    'send_user_empty' => '您沒有選擇需要發放紅包的會員，請返回!',
    'batch_drop_success' => '成功刪除了 %d 個用戶紅包',
    'sendbonus_count' => '共發送了 %d 個紅包。',
    'send_bouns_error' => '發送會員紅包出錯, 請返回重試！',
    'no_select_bonus' => '您沒有選擇需要刪除的用戶紅包',
    'bonustype_edit' => '編輯紅包類型',
    'bonustype_view' => '查看詳情',
    'drop_bonus' => '刪除紅包',
    'send_bonus' => '發放紅包',
    'continus_add' => '繼續添加紅包類型',
    'back_list' => '返回紅包類型列表',
    'continue_add' => '繼續添加紅包',
    'back_bonus_list' => '返回紅包列表',
    'validated_email' => '只給通過郵件驗證的用戶發放紅包',
    'attradd_succed' => '操作成功!',

    /* js提示信息 */
    'js_languages' => [
        'type_name_empty' => '請輸入紅包類型名稱',
        'type_money_empty' => '請輸入紅包類型價格',
        'order_money_empty' => '請輸入訂單金額',
        'type_money_isnumber' => '類型金額必須為數字格式',
        'order_money_isnumber' => '訂單金額必須為數字格式',
        'bonus_sn_empty' => '請輸入紅包的序列號',
        'bonus_sn_number' => '紅包的序列號必須是數字',
        'bonus_sum_empty' => '請輸入您要發放的紅包數量',
        'bonus_sum_number' => '紅包的發放數量必須是一個整數',
        'bonus_type_empty' => '請選擇紅包的類型金額',
        'user_rank_empty' => '您沒有指定會員等級',
        'user_name_empty' => '您至少需要選擇一個會員',
        'invalid_min_amount' => '請輸入訂單下限（大於0的數字）',
        'send_start_lt_end' => '紅包發放開始日期不能大於結束日期',
        'use_start_lt_end' => '紅包使用開始日期不能大於結束日期',
        'min_order_total' => '請輸入最小訂單金額',
    ],

    'send_count_error' => '紅包的發放數量必須是一個整數',

    'order_min_money_notic' => '只要訂單金額達到該數值，就會發放紅包給用戶',
    'order_max_money_notic' => '0表示沒有上限',
    'type_money_notic' => '此類型的紅包可以抵銷的金額',
    'send_startdate_notic' => '只有當前時間介於起始日期和截止日期之間時，此類型的紅包才可以發放',
    'use_startdate_notic' => '只有當前時間介於起始日期和截止日期之間時，此類型的紅包才可以使用',
    'type_name_exist' => '此類型的名稱已經存在!',
    'type_money_beyond' => '紅包金額不得大於最小訂單金額!',
    'type_money_error' => '金額必須是數字並且不能小於 0 !',
    'bonus_sn_notic' => '提示：紅包序列號由六位序列號種子加上四位隨機數字組成',
    'creat_bonus' => '生成了 ',
    'creat_bonus_num' => ' 個紅包序列號',
    'bonus_sn_error' => '紅包序列號必須是數字!',
    'send_user_notice' => '給指定的用戶發放紅包時,請在此輸入用戶名, 多個用戶之間請用逗號(,)分隔開<br />如:liry, wjz, zwj',

    /* 紅包信息欄位 */
    'bonus_id' => '編號',
    'bonus_type_id' => '類型金額',
    'send_bonus_count' => '紅包數量',
    'start_bonus_sn' => '起始序列號',
    'bonus_sn' => '紅包序列號',
    'user_id' => '使用會員',
    'used_time' => '使用時間',
    'order_id' => '訂單號',
    'send_mail' => '發郵件',
    'emailed' => '郵件通知',

    'mail_status' => [
        BONUS_NOT_MAIL => '未發',
        BONUS_MAIL_FAIL => '已發失敗',
        BONUS_MAIL_SUCCEED => '已發成功',
    ],

    'sendtouser' => '給指定用戶發放紅包',
    'senduserrank' => '按用戶等級發放紅包',
    'select_user_grade' => '請選擇會員等級',
    'userrank' => '用戶等級',
    'select_rank' => '選擇會員等級...',
    'keywords' => '關鍵字：',
    'userlist' => '會員列表',
    'send_to_user' => '給下列用戶發放紅包',
    'search_users' => '搜索會員',
    'confirm_send_bonus' => '確定發送紅包',
    'bonus_not_exist' => '該紅包不存在',
    'success_send_mail' => '%d 封郵件已被加入郵件列表',
    'send_continue' => '繼續發放紅包',

    'start_enddate' => '發放起止日期',
    'use_start_enddate' => '使用起止日期',
    'send_continue' => '繼續發放紅包',
    'bind_password' => '綁定密碼',
    'copy_url' => '複製鏈接',
    'user_name_send' => '根據會員名稱發放紅包',
    'user_grade_send' => '根據會員等級發放紅包',
    'no_offline_send_bonus' => '（非線下發放紅包）',

    /* 教程名稱 */
    'tutorials_bonus_list_one' => '商城紅包使用說明',

    /* 頁面頂部操作提示 */
    'operation_prompt_content' => [
        'send' => [
            '0' => '根據會員等級發放紅包，也可搜索具體會員發放紅包。',
            '1' => '請合理髮放紅包。',
            '2' => '被發紅包的會員可在個人主頁中的賬戶中心查看紅包信息，如果紅包類型是線下發放的紅包需要輸入卡號和密碼。',
        ],
        'info' => [
            '0' => '點擊添加/編輯紅包類型進入紅包信息頁面，填寫信息有：類型名稱、紅包金額、最小訂單金額等。',
            '1' => '點擊確定完成添加/編輯紅包，即可生成/編輯一條紅包列表。',
        ],
        'list' => [
            '0' => '展示所有紅包類型相關信息。',
            '1' => '可進行使用類型、活動名稱搜索相關信息。',
            '2' => '只有自行領取的紅包類型，才可以複製鏈接。',
        ],
        'view' => [
            '0' => '如果紅包類型是線下發放的紅包，則查看的是發放紅包的個數列表，展示生成所有紅包的序列號和密碼。',
            '1' => '除此紅包類型查看的是紅包發放的情況，如發放的會員的使用情況，可進行發送郵件或刪除等操作。',
        ]
    ]

];