<?php
return [

    'cat_name' => '文章分類名稱',
    'type' => '分類類型',
    'type_name' => [
        COMMON_CAT => '普通分類',
        SYSTEM_CAT => '系統分類',
        INFO_CAT => '網店信息',
        UPHELP_CAT => '幫助分類',
        HELP_CAT => '網店幫助',
    ],


    'cat_keywords' => '關鍵字',
    'cat_desc' => '描述',
    'parent_cat' => '上級分類',
    'cat_top' => '頂級分類',
    'not_allow_add' => '你所選分類不允許添加子分類',
    'not_allow_remove' => '系統保留分類，不允許刪除',
    'is_fullcat' => '該分類下還有子分類，請先刪除其子分類',
    'show_in_nav' => '顯示在導航欄',

    'isopen' => '顯示',
    'isclose' => '不顯示',
    'add_article' => '添加文章',

    'articlecat_edit' => '文章分類編輯',
    'cat_grade' => [
        '1' => '一級分類',
        '2' => '二級分類',
        '3' => '三級分類',
        '4' => '四級分類',
        '5' => '五級分類',
    ],

    /* 提示信息 */
    'catname_exist' => '分類名 %s 已經存在',
    'parent_id_err' => '分類名 %s 的父分類不能設置成本身或本身的子分類',
    'back_list' => '返回分類列表',
    'continue_add' => '繼續添加新分類',
    'catadd_succed' => '已成功添加',
    'catedit_succed' => '分類 %s 編輯成功',
    'back_list' => '返回分類列表',
    'continue_add' => '繼續添加分類',
    'no_catname' => '請填入分類名',
    'edit_fail' => '編輯失敗',
    'enter_int' => '請輸入一個整數',
    'not_emptycat' => '分類下還有文章，不允許刪除非空分類',
    'has_article_on_this_cat' => '該分類下存在文章，無法添加子分類',

    /*幫助信息*/
    'notice_keywords' => '關鍵字為選填項，其目的在於方便外部搜索引擎搜索',
    'notice_isopen' => '該文章分類是否顯示在前台的主導航欄中。',

    /*JS 語言項*/
    'js_languages' => [
        'no_catname' => '沒有輸入分類的名稱',
        'sys_hold' => '系統保留分類，不允許添加子分類',
        'remove_confirm' => '您確定要刪除選定的分類嗎？',
    ],


    /* 頁面頂部操作提示 */
    'operation_prompt_content' => [
        '0' => '該頁面展示所有文章分類。',
        '1' => '可添加子分類以及編輯修改、刪除分類。',
        '2' => '請注意選擇上級分類。',
    ],


];