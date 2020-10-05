<?php

return [

    /*
    |--------------------------------------------------------------------------
    | baitiao_batch Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during baitiao_batch for various
    | messages that we need to display to the user. You are free to modify
    | these language lines according to your application's requirements.
    |
    */

    'csv_file' => '上傳批量csv文件：',
    'notice_file' => '（CSV文件中一次上傳商品數量最好不要超過40，CSV文件大小最好不要超過500K.）',
    'file_charset' => '文件編碼：',
    'download_file' => '下載批量CSV文件（%s）',
    
    /* 頁面頂部操作提示 */
    'operation_prompt_content' => [
        '0' => '根據使用習慣，下載相應語言的csv文件，例如中國內地用戶下載簡體中文語言的文件，港台用戶下載繁體語言的文件。',
        '1' => '選擇所上傳商品的分類以及文件編碼，上傳csv文件。',
    ],
    // 批量上傳商品的欄位
    'upload_baitiao' => [
        'user_name' => '會員名稱',
        'amount' => '金融額度',
        'repay_term' => '信用賬期',
        'over_repay_trem' => '信用賬期緩期期限',
        'status' => '狀態',
    ],

    'status_succeed' => '插入成功',
    'status_failure' => '插入失敗',
    'already_show' => '數據已存在',

    'save_products' => '保存白條設置成功',
    '14_batch_add' => '白條批量設置',

];
