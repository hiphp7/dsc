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

    'csv_file' => '上传批量csv文件：',
    'notice_file' => '（CSV文件中一次上传商品数量最好不要超过40，CSV文件大小最好不要超过500K.）',
    'file_charset' => '文件编码：',
    'download_file' => '下载批量CSV文件（%s）',
    
    /* 页面顶部操作提示 */
    'operation_prompt_content' => [
        '0' => '根据使用习惯，下载相应语言的csv文件，例如中国内地用户下载简体中文语言的文件，港台用户下载繁体语言的文件。',
        '1' => '选择所上传商品的分类以及文件编码，上传csv文件。',
    ],
    // 批量上传商品的字段
    'upload_baitiao' => [
        'user_name' => '会员名称',
        'amount' => '金融额度',
        'repay_term' => '信用账期',
        'over_repay_trem' => '信用账期缓期期限',
        'status' => '状态',
    ],

    'status_succeed' => '插入成功',
    'status_failure' => '插入失败',
    'already_show' => '数据已存在',

    'save_products' => '保存白条设置成功',
    '14_batch_add' => '白条批量设置',

];
