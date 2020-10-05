<?php

return [

    /*
    |--------------------------------------------------------------------------
    | auction Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during auction for various
    | messages that we need to display to the user. You are free to modify
    | these language lines according to your application's requirements.
    |
    */

    'is_hot' => '熱銷',
    'ishot' => '熱銷',
    'isnothot' => '非熱銷',

    /* menu */
    'auction_list' => '拍賣活動列表',
    'add_auction' => '添加拍賣活動',
    'edit_auction' => '編輯拍賣活動',
    'auction_log' => '拍賣活動出價記錄',
    'continue_add_auction' => '繼續添加拍賣活動',
    'back_auction_list' => '返回拍賣活動列表',
    'add_auction_ok' => '添加拍賣活動成功',
    'edit_auction_ok' => '編輯拍賣活動成功',
    'settle_deposit_ok' => '處理凍結的保證金成功',
    'auction_audited_set_ok' => '拍賣活動審核狀態設置成功',

    /* list */
    'act_is_going' => '僅顯示進行中的活動',
    'act_name' => '拍賣活動名稱',
    'goods_name' => '商品名稱',
    'start_time' => '開始時間',
    'end_time' => '結束時間',
    'deposit' => '保證金',
    'start_price' => '起拍價',
    'end_price' => '一口價',
    'amplitude' => '加價幅度',
    'auction_not_exist' => '您要操作的拍賣活動不存在',
    'auction_cannot_remove' => '該拍賣活動已經有人出價，不能刪除',
    'batch_drop_ok' => '操作完成（已經有人出價的拍賣活動不能刪除）',
    'no_record_selected' => '沒有選擇記錄',

    /* info */
    'label_act_name' => '拍賣活動名稱：',
    'notice_act_name' => '如果留空，取拍賣商品的名稱（該名稱僅用於後台，前台不會顯示）',
    'label_act_desc' => '拍賣活動描述：',
    'label_search_goods' => '根據商品編號、名稱或貨號搜索商品',
    'label_goods_name' => '拍賣商品名稱：',
    'label_start_time' => '拍賣開始時間：',
    'label_end_time' => '拍賣結束時間：',
    'label_status' => '當前狀態：',
    'label_start_price' => '起拍價：',
    'label_end_price' => '一口價：',
    'label_no_top' => '無封頂',
    'label_amplitude' => '加價幅度：',
    'label_deposit' => '保證金：',
    'bid_user_count' => '已有 %s 個買家出價',
    'settle_frozen_money' => '怎樣處理買家的凍結資金？',
    'unfreeze' => '解凍保證金',
    'deduct' => '扣除保證金',
    'invalid_status' => '當前狀態不正確',
    'no_deposit' => '沒有保證金需要處理',
    'unfreeze_auction_deposit' => '解凍拍賣活動的保證金：%s',
    'deduct_auction_deposit' => '扣除拍賣活動的保證金：%s',

    'auction_status' => [
        PRE_START => '未開始',
        UNDER_WAY => '進行中',
        FINISHED => '已結束',
        SETTLED => '已結束',
    ],

    'pls_search_goods' => '請先搜索商品',
    'search_goods' => '搜索商品',
    'search_result_empty' => '沒有找到商品，請重新搜索',

    'pls_select_goods' => '請選擇拍賣商品',
    'goods_not_exist' => '您要拍賣的商品不存在',

    'js_languages' => [
        'start_price_not_number' => '起拍價格式不正確（數字）',
        'end_price_not_number' => '一口價格式不正確（數字）',
        'end_gt_start' => '一口價應該大於起拍價',
        'amplitude_not_number' => '加價幅度格式不正確（數字）',
        'deposit_not_number' => '保證金格式不正確（數字）',
        'start_lt_end' => '拍賣開始時間不能大於結束時間',
        'search_is_null' => '沒有搜索到任何商品，請重新搜索',
        'good_name_not_null' => '請選擇商品',
        'start_price_not_null' => '請輸入起拍價',
        'amplitude_not_null' => '請輸入加價幅度',
        'batch_drop_confirm' => '您確實要刪除選中的拍賣活動嗎？',
    ],

    /* log */
    'bid_user' => '買家',
    'bid_price' => '出價',
    'bid_time' => '時間',
    'status' => '狀態',

    'label_start_end_time' => '拍賣起止時間：',
    'basic_info' => '基本信息',
    'auction_desc' => '拍賣介紹',
    'promise' => '服務保障',
    'ensure' => '競拍攻略',

    'auction_explain' => '商城拍賣活動說明',

    /* 頁面頂部操作提示 */
    'operation_prompt_content' => [
        '0' => '列表頁可根據條件，如商品名稱、店鋪名稱等搜索出具體參加拍賣活動的商品活動信息。',
        '1' => '列表頁展示了所有的拍賣活動名稱、商家名稱、商品名稱、起始時間等信息列表。',
        '2' => '可添加、查看、編輯、刪除或批量刪除拍賣活動操作。',

        '3' => '該頁面展示了拍賣活動的出價記錄。',
        '4' => '主要展示了買家、出價、時間、狀態的信息列表。',
    ],

    'operating_hints' => '操作提示',
    'warn_submit_will_recheck' => '溫馨提示：提交操作將會重新審核，請慎重提交確定。',
    'whether_hot' => '是否熱銷',

];