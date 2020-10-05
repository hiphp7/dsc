<?php

$_LANG['rank_name'] = '會員等級名稱';
$_LANG['integral_min'] = '所需成長值';
$_LANG['integral_min_notice'] = '修改等級所需成長值後，部分客戶會因無法達到該成長值要求而發生會員等級的變化';
$_LANG['integral_max'] = '積分上限';
$_LANG['discount'] = '初始折扣率';
$_LANG['add_user_rank'] = '添加會員等級';
$_LANG['special_rank'] = '特殊會員組';
$_LANG['show_price'] = '在商品詳情頁顯示該會員等級的商品價格';
$_LANG['notice_special'] = '特殊會員組的會員不會隨著成長值的變化而變化。';
$_LANG['add_continue'] = '繼續添加會員等級';
$_LANG['back_list'] = '返回會員等級列表';
$_LANG['show_price_short'] = '顯示價格';
$_LANG['notice_discount'] = '請填寫為0-100的整數,如填入80，表示初始折扣率為8折';

$_LANG['rank_name_exists'] = '會員等級名 %s 已經存在。';
$_LANG['add_rank_success'] = '會員等級已經添加成功。';
$_LANG['integral_min_exists'] = '已經存在一個成長值下限為 %d 的會員等級';
$_LANG['integral_max_exists'] = '已經存在一個成長值上限為 %d 的會員等級';
$_LANG['full_max_exists'] = '超出下一等級的最大積分，請修改後重試';
$_LANG['user_rank_set'] = '成長值有效期';
$_LANG['is_open'] = '是否開啟：';
$_LANG['expiry_date'] = '有效期：';
$_LANG['user_rank_notice'] = '成長值有效期開啟後，會員成長值自獲取之日至有效期內有效，超過有效期的成長值將會自動清零，設置之後需清除商城緩存方可生效。';
$_LANG['month'] = '月';

/* JS 語言 */
$_LANG['js_languages']['remove_confirm'] = '您確定要刪除選定的會員等級嗎？';
$_LANG['js_languages']['rank_name_empty'] = '您沒有輸入會員等級名稱。';
$_LANG['js_languages']['integral_min_invalid'] = '您沒有輸入積分下限或者積分下限不是一個整數。';
$_LANG['js_languages']['integral_max_invalid'] = '您沒有輸入積分上限或者積分上限不是一個整數。';
$_LANG['js_languages']['discount_invalid'] = '您沒有輸入折扣率或者折扣率無效。';
$_LANG['js_languages']['integral_max_small'] = '積分上限必須大於積分下限。';
$_LANG['js_languages']['lang_remove'] = '移除';

/* 頁面頂部操作提示 */
$_LANG['operation_prompt_content']['list'][0] = '會員等級是一種會員成長體系的對外展示形式，通過成長值的變動，幫助商家更好的維護會員關係，促進消費轉化。';
$_LANG['operation_prompt_content']['list'][1] = '根據會員等級和等級所需的成長值，設置會員達到此等級後，可享受的會員權益。';

$_LANG['user_rights'] = '會員權益';
$_LANG['view_users'] = '查看會員';
$_LANG['set_show'] = '顯示設置';
$_LANG['drop_confirm_rank'] = '刪除該會員等級的同時該會員等級下的會員權益也將被刪除，您確定要刪除嗎？';

$new_lang = [
    'user' => '會員',
    'edit_user_rank' => '編輯會員等級',
    'user_rank' => '會員等級',
    'user_rank_has_user' => '當前等級下有會員，不可刪除',
    'user_rank_rights_tips' => [
        '商品分銷/會員卡分銷普通會員等級不可使用，必須配合分銷權益卡使用'
    ],
    'rights_close' => '【該權益已關閉】',
];

return array_merge($_LANG, $new_lang);
