<?php

return [

    /*
    |--------------------------------------------------------------------------
    | drpcard Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used by the drpcard
    |
    */

    // 分銷權益卡
    'drp_card' => '分銷權益卡',
    'drp_card_tips' => [
        '設置分銷權益卡，當用戶申請或購買後，即可獲得分銷會員資格。不同的會員卡可設置不同的權益，為這些會員提供尊貴會員級別的超值優惠，刺激會員在商城下單消費；',
        '分銷員可幫助商城推廣商品，推廣越多返佣越多，通過分潤返佣加速裂變傳播，拉新促客，以客推客，從而獲得更多會員以及提高商城銷量；',
        '會員卡下如果沒有會員的情況下，可以在編輯頁刪除，如果有會員的情況下，只能編輯會員卡信息，不可刪除會員卡。',
        '已停發的卡，用戶端將不可出售，但已購買/領取的權益卡，還可以繼續使用並享受相關的權益'
    ],
    'add_drp_card' => '添加分銷權益卡',
    'edit_drp_card' => '編輯分銷權益卡',
    'sync' => '同步歷史數據',
    'sync_ok' => '同步成功',
    'drp_card_image' => '權益卡圖片',
    'background_img_notice' => '建議尺寸：1029*480像素，小於1M，支持jpg、png、jpeg格式',
    'drp_card_name' => '權益卡名稱',
    'drp_card_name_not_null' => '權益卡名稱不能為空',
    'drp_card_name_repeat' => '權益卡名稱已存在',
    'drp_card_limit' => '權益卡數量超出限制',
    'drp_card_condition' => '領取條件',
    'drp_card_expiry' => '有效期',
    'expiry_type_forever' => '永久有效',
    'expiry_type_days' => '領卡時起',
    'expiry_type_days_part' => '天內有效',
    'expiry_date_start' => '開始時間',
    'to' => '至',
    'expiry_date_end' => '結束時間',
    'receive_config' => '領取設置',
    'receive_type_free' => '免費領取',
    'receive_type_buy' => '付費購買',
    'placeholder_for_buy' => '請輸入付費金額',
    'receive_type_goods' => '購買指定商品',
    'select_goods_menu' => '選擇商品',
    'select_goods_length_limit' => '最多可選擇10件商品',
    'receive_type_order' => '消費金額累積',
    'placeholder_for_order' => '請輸入指定金額',
    'receive_type_integral' => '消費積分兌換',
    'placeholder_for_integral' => '請輸入積分兌換值',
    'receive_type_order_buy' => '訂單購買成爲分銷商',
    'drp_card_desc' => '權益卡描述',
    'card_bg_set' => '背景設置',
    'member_view' => '查看成員',
    'bind_rights_list' => '綁定的會員權益',
    'confirm_unbind_rights' => '您確定要刪除此會員權益嗎？',
    'confirm_drop_card' => '您確定要刪除此分銷權益卡嗎？',
    'cannot_drop_card' => '此分銷權益卡下有關聯會員禁止刪除！',
    'please_input_value' => '請輸入大於0的值',
    'disabled_card' => '停止發放',
    'card_enable_1' => '發放中',
    'card_enable_0' => '已停發',
    'confirm_disabled_card' => '您確定停止發放【:card_name】會員權益卡？停發後，在用戶端，會員不可以領取此權益卡，已領取權益卡的會員，還可繼續使用。',
    'bind_rights' => '選擇會員權益',
    'bind_rights_tips' => [
        '打勾表示選擇，灰色的勾表示已選擇過',
        '沒有想要選擇的會員權益，請在【會員】-【會員權益】下，<a href="../user_rights">點擊安裝</a> >>'
    ],
    'rights_type' => '權益類型',
    'rights_choice' => '選擇',
    'rights_desc' => '權益描述',
    'edit_rights' => '編輯會員權益',
    'edit_rights_tips' => [
        '修改分銷會員卡權益的優惠方式；'
    ],

    'membership_card_id_empty' => '權益卡id不能為空',
    'id_empty' => '權益id不能為空',
    'all' => '全部',

    'goods_info' => '商品信息',
    'price' => '價格',

];
