<?php

return [
    /*
    |--------------------------------------------------------------------------
    | affiliate_ck Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during affiliate_ck for various
    | messages. You are free to modify
    | these language lines according to your application's requirements.
    |
    */

    'separate_name' => '推薦分成',
    'order_id' => '訂單號',
    'affiliate_separate' => '分成',
    'affiliate_cancel' => '取消',
    'affiliate_rollback' => '撤銷',
    'log_info' => '操作信息',
    'edit_ok' => '操作成功',
    'edit_fail' => '操作失敗',
    'separate_info' => '訂單號 %s, 分成:金錢 %s 積分 %s',
    'separate_info2' => '用戶ID %s ( %s ), 分成:金錢 %s 積分 %s',
    'sch_order' => '搜索訂單號/用戶名/昵稱',

    'sch_stats' => [
        'name' => '操作狀態',
        'info' => '按操作狀態查找:',
        'all' => '全部',
        '0' => '等待處理',
        '1' => '已分成',
        '2' => '取消分成',
        '3' => '已撤銷',
    ],

    'order_stats' => [
        'name' => '訂單狀態',
        '0' => '未確認',
        '1' => '已確認',
        '2' => '已取消',
        '3' => '無效',
        '4' => '退貨',
    ],
    'js_languages' => [
        'cancel_confirm' => '您確定要取消分成嗎？此操作不能撤銷。',
        'rollback_confirm' => '您確定要撤銷此次分成嗎？',
        'separate_confirm' => '您確定要分成嗎？',
    ],
    'loginfo' => [
        '0' => '用戶id:',
        '1' => '現金:',
        '2' => '積分:',
        'cancel' => '分成被管理員取消！',
    ],
    'separate_type' => '分成類型',
    'batch_separate' => '批量分成',
    'separate_by' => [
        '0' => '推薦註冊分成',
        '1' => '推薦訂單分成',
        '-1' => '推薦註冊分成',
        '-2' => '推薦訂單分成',
    ],

    'show_affiliate_orders' => '此列表顯示此用戶推薦的訂單信息。',
    'back_note' => '返回會員編輯頁面',

    /* 頁面頂部操作提示 */
    'operation_prompt_content' => [
        '0' => '分成訂單信息管理。',
        '1' => '可搜索分成訂單號查詢分成訂單。',
    ],


];