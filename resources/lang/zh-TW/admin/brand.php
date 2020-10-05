<?php

return [

    /*
    |--------------------------------------------------------------------------
    | brand Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during brand for various
    | messages that we need to display to the user. You are free to modify
    | these language lines according to your application's requirements.
    |
    */

    'brand_separate' => '統一使用平台品牌ID',

    'brand' => '品牌',
    'brand_name' => '品牌名稱',
    'brand_img' => '品牌圖片',
    'site_url' => '品牌網址',
    'brand_desc' => '品牌描述',
    'brand_logo' => '品牌LOGO',
    'index_img' => '品牌專區大圖',
    'brand_bg' => '品牌背景圖',
    'sort_order' => '排序',
    'is_show' => '是否顯示',
    'drop_brand_logo' => '刪除圖標',
    'confirm_drop_logo' => '你確認要刪除該圖標嗎？',
    'drop_brand_logo_success' => '刪除品牌logo成功',
    'lab_intro' => '加入推薦',
    'brand_first_char' => '品牌首字母',
    'generate_brand_first_char' => '生成品牌首字母',

    'brand_edit_lnk' => '重新編輯該品牌',
    'brand_list_lnk' => '返回列表頁面',
    'update_data_success' => '已更新數據成功',
    'update_data_fail' => '已更新數據失敗',

    /*幫助信息*/
    'up_brandlogo' => '請上傳圖片，做為品牌的LOGO！標準尺寸200*88',
    'warn_brandlogo' => '你已經上傳過圖片。再次上傳時將覆蓋原圖片！標準尺寸200*88',
    'brand_first_char_desc' => '用於解決某些生僻字無法正確生成品牌首字母的情況。',
    'index_img_desc' => '標準尺寸278*285',
    'brand_bg_desc' => '使用於品牌詳情頁頭部分類背景，建議尺寸：1920*200',

    /*提示信息*/
    'brand_edit' => '編輯品牌記錄',
    'upload_failure' => '圖片上傳失敗！',
    'brandedit_fail' => '品牌 %s 修改失敗！',
    'brandadd_succed' => '新品牌添加成功！',
    'brandedit_succed' => '品牌 %s 修改成功！',
    'brandname_exist' => '品牌 %s 已經存在！',
    'drop_confirm' => '你確認要刪除選定的商品品牌嗎？',
    'drop_succeed' => '已成功刪除！',
    'drop_fail' => '刪除失敗！',
    'update_type' => '更新狀態',

    'no_brandname' => '您必須輸入品牌名稱！',
    'enter_int' => '請輸入一個整數！',

    'back_list' => '返回品牌列表',
    'continue_add' => '繼續添加新品牌',

    'upfile_type_error' => '只能上傳jpg，gif，png類型的圖片',
    'upfile_error' => '圖片無法上傳，請確保data目錄下所有子目錄的可寫性！',

    'visibility_notes' => '當品牌下還沒有商品的時候，首頁及分類頁的品牌區將不會顯示該品牌。',

    'current_modification_data' => '當前需要修改的數據有',

    'current_update_number' => '當前更新商品數量',

    /*JS 語言項*/
    'js_languages' => [
        'no_brandname' => '您必須輸入品牌名稱！',
        'no_brandletter' => '您必須輸入品牌英文名稱！',
        'require_num' => '排序序號必須是一個數字',
        'title_name_one' => '已完成更新，請關閉該窗口！',
        'title_name_two' => '正在生成品牌首字母中，請勿關閉該窗口！',
        'brand_zh_name_null' => '請輸入中文品牌名稱',
        'brand_logo_null' => '請上傳品牌logo',
    ],

    /* 頁面頂部操作提示 */
    'operation_prompt_content' => [
        'create_brand_letter' => [
            '0' => '生成品牌的首字母，主要用於後台的品牌搜索按首字母查詢',
        ],
        'list' => [
            '0' => '展示了商城自營品牌的相關信息。',
            '1' => '可以通過品牌關鍵字搜索相關品牌信息。',
        ],
        'info' => [
            '0' => '請按提示文案填寫信息，以免出錯。',
        ],
        'separate' => [
            '0' => '操作商家商品品牌統一使用平台品牌ID，請耐心等待數據執行完畢。',
        ]
    ]

];
