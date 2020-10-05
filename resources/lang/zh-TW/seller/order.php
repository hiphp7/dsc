<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Order Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during Order for various
    | messages. You are free to modify
    | these language lines according to your application's requirements.
    |
    */

    'no_comment' => '尚未評論',

    'update_shipping' => '將【%s】配送方式修改為【%s】',
    'self_motion_goods' => '自動確認收貨',

    'order_ship_delivery' => '發貨單流水號：【%s】',
    'order_ship_invoice' => '發貨單號：【%s】',
    'edit_order_invoice' => '將發貨單號【%s】修改為：【%s】',

    'add_order_info' => '【%s】手工添加訂單',
    'shipping_refund' => '編輯訂單導致退款：%s',

    '01_stores_pick_goods' => '訂單未付款，無法提貨',
    '02_stores_pick_goods' => '操作成功，提貨完成',
    '03_stores_pick_goods' => '驗證碼不正確，請重新輸入',
    '04_stores_pick_goods' => '請輸入6位提貨碼',

    'order_label' => '信息標籤',
    'amount_label' => '金額標籤',

    'bar_code' => '條形碼',
    'label_bar_code' => '條形碼：',

    'region' => '配送區域',
    'order_shipped_sms' => '您的訂單%s已於%s發貨', //wang

    'setorder_nopay' => '設置為未付款',
    'setorder_cancel' => '設置為未付款',

//退換貨 start
    'rf' => [
        RF_APPLICATION => '由用戶寄回',
        RF_RECEIVE => '收到退換貨',
        RF_SWAPPED_OUT_SINGLE => '換出商品寄出【分單】',
        RF_SWAPPED_OUT => '換出商品寄出',
        RF_COMPLETE => '完成',
        RF_AGREE_APPLY => '同意申請',
        REFUSE_APPLY => '申請被拒',
        FF_NOMAINTENANCE => '未維修',
        FF_MAINTENANCE => '已維修',
        FF_REFOUND => '已退款',
        FF_NOREFOUND => '未退款',
        FF_NOEXCHANGE => '未換貨',
        FF_EXCHANGE => '已換貨',
    ],

    'refund_money' => '退款金額',
    'money' => '金額',
    'shipping_money' => '元&nbsp;&nbsp;運費',
    'is_shipping_money' => '退運費',
    'no_shipping_money' => '不退運費',
    'return_user_line' => '線下退款',
    'return_reason' => '退換貨原因',
    'whether_display' => '是否顯示',
    'return_baitiao' => '退回白條餘額(如果是餘額與白條混合支付,餘額部分退回餘額)',
    'return_online' => '在線原路退款',
    //退換貨 end

    //ecmoban模板堂 --zhuo start
    'auto_delivery_time' => '自動確認收貨時間：',
    'dateType' => [
        '0' => '天',
    ],
    //ecmoban模板堂 --zhuo end

    /* 訂單搜索 */
    'order_sn' => '訂單號',
    'consignee' => '收貨人',
    'all_status' => '訂單狀態',

    'cs' => [
        OS_UNCONFIRMED => '待確認',
        CS_AWAIT_PAY => '待付款',
        CS_AWAIT_SHIP => '待發貨',
        CS_FINISHED => '已完成',
        PS_PAYING => '付款中',
        OS_CANCELED => '取消',
        OS_INVALID => '無效',
        OS_RETURNED => '退貨',
        OS_SHIPPED_PART => '待收貨',
    ],


    /* 訂單狀態 */
    'os' => [
        OS_UNCONFIRMED => '未確認',
        OS_CONFIRMED => '已確認',
        OS_CANCELED => '取消',
        OS_INVALID => '無效',
        OS_RETURNED => '退貨',
        OS_SPLITED => '已分單',
        OS_SPLITING_PART => '部分分單',
        OS_RETURNED_PART => '部分已退貨',
        OS_ONLY_REFOUND => '僅退款',
    ],

    'ss' => [
        SS_UNSHIPPED => '未發貨',
        SS_PREPARING => '配貨中',
        SS_SHIPPED => '已發貨',
        SS_RECEIVED => '收貨確認',
        SS_SHIPPED_PART => '已發貨(部分商品)',
        SS_SHIPPED_ING => '發貨中',
    ],

    'ps' => [
        PS_UNPAYED => '未付款',
        PS_PAYING => '付款中',
        PS_PAYED => '已付款',
        PS_PAYED_PART => '部分付款(定金)',
        PS_REFOUND => '已退款',
        PS_REFOUND_PART => '部分退款',
    ],

    'ss_admin' => [
        SS_SHIPPED_ING => '發貨中（前台狀態：未發貨）',
    ],

    /* 訂單操作 */
    'label_operable_act' => '當前可執行操作：',
    'label_action_note' => '操作備註：',
    'label_invoice_note' => '發貨備註：',
    'label_invoice_no' => '發貨單號：',
    'label_cancel_note' => '取消原因：',
    'notice_cancel_note' => '（會記錄在商家給客戶的留言中）',
    'op_confirm' => '確認',
    'refuse' => '拒絕',
    'refuse_apply' => '拒絕申請',
    'is_refuse_apply' => '已拒絕申請',
    'op_pay' => '付款',
    'op_prepare' => '配貨',
    'op_ship' => '發貨',
    'op_cancel' => '取消',
    'op_invalid' => '無效',
    'op_return' => '退貨',
    'op_unpay' => '設為未付款',
    'op_unship' => '未發貨',
    'op_cancel_ship' => '取消發貨',
    'op_receive' => '已收貨',
    'op_assign' => '指派給',
    'op_after_service' => '售後',
    'act_ok' => '操作成功',
    'act_false' => '操作失敗',
    'act_ship_num' => '此單發貨數量不能超出訂單商品數量',
    'act_good_vacancy' => '商品已缺貨',
    'act_good_delivery' => '貨已發完',
    'notice_gb_ship' => '備註：團購活動未處理為成功前，不能發貨',
    'back_list' => '返回列表頁',
    'op_remove' => '刪除',
    'op_you_can' => '您可進行的操作',
    'op_split' => '生成發貨單',
    'op_to_delivery' => '去發貨',
    'op_swapped_out_single' => '換出商品寄出【分單】',
    'op_swapped_out' => '換出商品寄出',
    'op_complete' => '完成退換貨',
    'op_refuse_apply' => '拒絕申請',
    'baitiao_by_stages' => '白條分期',
    'expired' => '已到期',
    'unable_to_click' => '無法點擊',

    /* 訂單列表 */
    'order_amount' => '應付金額',
    'total_fee' => '總金額',
    'shipping_name' => '配送方式',
    'pay_name' => '支付方式',
    'address' => '地址',
    'order_time' => '下單時間',
    'trade_snapshot' => '交易快照',
    'detail' => '查看',
    'phone' => '電話',
    'group_buy' => '（團購）',
    'error_get_goods_info' => '獲取訂單商品信息錯誤',
    'exchange_goods' => '（積分兌換）',
    'auction' => '（拍賣活動）',
    'snatch' => '（奪寶奇兵）',
    'presale' => '（預售）',


    /* 訂單搜索 */
    'label_buyer' => '購貨人：',
    'label_order_sn' => '訂單號：',
    'label_all_status' => '訂單狀態：',
    'label_user_name' => '購貨人：',
    'label_consignee' => '收貨人：',
    'label_email' => '電子郵件：',
    'label_address' => '收貨地址：',
    'label_zipcode' => '郵政編碼：',
    'label_tel' => '電話號碼：',
    'label_mobile' => '手機號碼：',
    'label_shipping' => '配送方式：',
    'label_payment' => '支付方式：',
    'label_order_status' => '訂單狀態：',
    'label_pay_status' => '付款狀態：',
    'label_shipping_status' => '發貨狀態：',
    'label_area' => '所在地區：',
    'label_time' => '下單時間：',

    /* 訂單詳情 */
    'prev' => '前一個訂單',
    'next' => '後一個訂單',
    'print_order' => '列印訂單',
    'print_shipping' => '列印快遞單',
    'print_order_sn' => '訂單編號：',
    'print_buy_name' => '購 貨 人：',
    'label_consignee_address' => '收貨地址：',
    'no_print_shipping' => '很抱歉,目前您還沒有設置列印快遞單模板.不能進行列印',
    'suppliers_no' => '不指定供貨商本店自行處理',
    'restaurant' => '本店',

    'order_info' => '訂單信息',
    'base_info' => '基本信息',
    'other_info' => '其他信息',
    'consignee_info' => '收貨人信息',
    'fee_info' => '費用信息',
    'action_info' => '操作信息',
    'shipping_info' => '配送信息',

    'label_how_oos' => '缺貨處理：',
    'label_how_surplus' => '餘額處理：',
    'label_pack' => '包裝：',
    'label_card' => '賀卡：',
    'label_card_message' => '賀卡祝福語：',
    'label_order_time' => '下單時間：',
    'label_pay_time' => '付款時間：',
    'label_shipping_time' => '發貨時間：',
    'label_sign_building' => '地址別名：',
    'label_best_time' => '送貨時間：',
    'label_inv_type' => '發票類型：',
    'label_inv_payee' => '發票抬頭：',
    'label_inv_content' => '發票內容：',
    'label_postscript' => '買家留言：',
    'label_region' => '所在地區：',

    'label_shop_url' => '網址：',
    'label_shop_address' => '地址：',
    'label_service_phone' => '電話：',
    'label_print_time' => '列印時間：',

    'label_suppliers' => '選擇供貨商：',
    'label_agency' => '辦事處：',
    'suppliers_name' => '供貨商',

    'product_sn' => '貨品號',
    'goods_info' => '商品信息',
    'goods_name' => '商品名稱',
    'goods_name_brand' => '商品名稱 [ 品牌 ]',
    'goods_sn' => '貨號',
    'goods_price' => '價格',
    'give_integral' => '商品贈送積分',
    'goods_number' => '數量',
    'goods_attr' => '屬性',
    'goods_delivery' => '已發貨數量',
    'goods_delivery_curr' => '此單發貨數量',
    'storage' => '庫存',
    'subtotal' => '小計',
    'amount_return' => '商品退款',
    'label_total' => '合計：',
    'label_total_weight' => '商品總重量：',
    'label_total_cost' => '合計商品成本：',
    'measure_unit' => '單位',
    'contain_content' => '包含內容',
    'application_refund' => '申請退款',

    'return_discount' => '享受折扣',

    'label_goods_amount' => '商品總金額：',
    'label_discount' => '折扣：',
    'label_tax' => '發票稅額：',
    'label_shipping_fee' => '配送費用：',
    'label_insure_fee' => '保價費用：',
    'label_insure_yn' => '是否保價：',
    'label_pay_fee' => '支付費用：',
    'label_pack_fee' => '包裝費用：',
    'label_card_fee' => '賀卡費用：',
    'label_money_paid' => '已付款金額：',
    'label_surplus' => '使用餘額：',
    'label_integral' => '使用積分：',
    'label_bonus' => '使用紅包：',
    'label_value_card' => '使用儲值卡：',
    'label_coupons' => '使用優惠券：',
    'label_order_amount' => '訂單總金額：',
    'label_money_dues' => '應付款金額：',
    'label_money_refund' => '應退款金額：',
    'label_to_buyer' => '商家給客戶的留言：',
    'save_order' => '保存訂單',
    'notice_gb_order_amount' => '（備註：團購如果有保證金，第一次只需支付保證金和相應的支付費用）',
    'formated_order_amount' => '訂單總金額',
    'stores_info' => '門店信息',

    'action_user' => '操作者',
    'action_time' => '操作時間',
    'return_status' => '操作',
    'refound_status' => '狀態',
    'order_status' => '訂單狀態',
    'pay_status' => '付款狀態',
    'shipping_status' => '發貨狀態',
    'action_note' => '備註',
    'pay_note' => '支付備註：',
    'action_jilu' => '操作記錄',
    'not_action_jilu' => '當前還沒有操作記錄',

    'sms_time_format' => 'm月j日G時',
    'order_splited_sms' => '您的訂單%s,%s正在%s [%s]',
    'order_removed' => '訂單刪除成功。',
    'return_list' => '返回訂單列表',
    'order_remove_failure' => '%s 存在單商品退換貨訂單，訂單刪除失敗。',

    /* 訂單處理提示 */
    'surplus_not_enough' => '該訂單使用 %s 餘額支付，現在用戶餘額不足',
    'integral_not_enough' => '該訂單使用 %s 積分支付，現在用戶積分不足',
    'bonus_not_available' => '該訂單使用紅包支付，現在紅包不可用',

    /* 購貨人信息 */
    'display_buyer' => '顯示購貨人信息',
    'buyer_info' => '購貨人信息',
    'pay_points' => '消費積分',
    'rank_points' => '等級積分',
    'user_money' => '賬戶餘額',
    'email' => '電子郵件',
    'rank_name' => '會員等級',
    'bonus_count' => '紅包數量',
    'zipcode' => '郵編',
    'tel' => '電話',
    'mobile' => '手機號碼',
    'leaving_message' => '留言',

    /*增值稅發票信息*/
    'vat_info' => '增值稅發票信息',
    'vat_name' => '單位名稱',
    'vat_taxid' => '納稅人識別碼',
    'vat_company_address' => '註冊地址',
    'vat_company_telephone' => '註冊電話',
    'vat_bank_of_deposit' => '開戶銀行',
    'vat_bank_account' => '銀行賬戶',

    /* 合并訂單 */
    'seller_order_sn_same' => '要合并的兩個訂單所屬商家必須相同',
    'merge_order_main_count' => '要合并的訂單不能為主訂單',
    'order_sn_not_null' => '請填寫要合并的訂單號',
    'two_order_sn_same' => '要合并的兩個訂單號不能相同',
    'order_not_exist' => '定單 %s 不存在',
    'os_not_unconfirmed_or_confirmed' => '%s 的訂單狀態不是「未確認」或「已確認」',
    'ps_not_unpayed' => '訂單 %s 的付款狀態不是「未付款」',
    'ss_not_unshipped' => '訂單 %s 的發貨狀態不是「未發貨」',
    'order_user_not_same' => '要合并的兩個訂單不是同一個用戶下的',
    'merge_invalid_order' => '對不起，您選擇合并的訂單不允許進行合并的操作。',

    'merge_order' => '合并訂單',
    'from_order_sn' => '從訂單：',
    'to_order_sn' => '主訂單：',
    'merge' => '合并',
    'notice_order_sn' => '當兩個訂單不一致時，合并後的訂單信息（如：支付方式、配送方式、包裝、賀卡、紅包等）以主訂單為準。',


    /* 批處理 */
    'pls_select_order' => '請選擇您要操作的訂單',
    'no_fulfilled_order' => '沒有滿足操作條件的訂單。',
    'updated_order' => '更新的訂單：',
    'order' => '訂單：',
    'confirm_order' => '以下訂單無法設置為確認狀態',
    'invalid_order' => '以下訂單無法設置為無效',
    'cancel_order' => '以下訂單無法取消',
    'remove_order' => '以下訂單無法被移除',

    /* 編輯訂單列印模板 */
    'edit_order_templates' => '編輯訂單列印模板',
    'template_resetore' => '還原模板',
    'edit_template_success' => '編輯訂單列印模板操作成功!',
    'remark_fittings' => '（配件）',
    'remark_gift' => '（贈品）',
    'remark_favourable' => '（特惠品）',
    'remark_package' => '（禮包）',
    'remark_package_goods' => '（禮包內產品）',

    /* 訂單來源統計 */
    'from_order' => '訂單來源',
    'referer' => '來源',
    'from_ad_js' => '廣告：',
    'from_goods_js' => '商品站外JS投放',
    'from_self_site' => '來自本站',
    'from' => '來自站點：',

    /* 添加、編輯訂單 */
    'add_order' => '添加訂單',
    'edit_order' => '編輯訂單',
    'step' => [
        'user' => '請選擇您要為哪個會員下訂單',
        'goods' => '選擇商品',
        'consignee' => '設置收貨人信息',
        'shipping' => '選擇配送方式',
        'payment' => '選擇支付方式',
        'other' => '設置其他信息',
        'money' => '設置費用',
    ],

    'anonymous' => '匿名用戶',
    'by_useridname' => '按會員編號或會員名搜索',
    'button_prev' => '上一步',
    'button_next' => '下一步',
    'button_finish' => '完成',
    'button_cancel' => '取消',
    'name' => '名稱',
    'desc' => '描述',
    'shipping_fee' => '配送費',
    'free_money' => '免費額度',
    'insure' => '保價費',
    'pay_fee' => '手續費',
    'pack_fee' => '包裝費',
    'card_fee' => '賀卡費',
    'no_pack' => '不要包裝',
    'no_card' => '不要賀卡',
    'add_to_order' => '加入訂單',
    'calc_order_amount' => '計算訂單金額',
    'available_surplus' => '可用餘額：',
    'available_integral' => '可用積分：',
    'available_bonus' => '可用紅包：',
    'admin' => '管理員添加',
    'search_goods' => '按商品編號或商品名稱或商品貨號搜索',
    'category' => '分類',
    'order_category' => '訂單分類',
    'brand' => '品牌',
    'user_money_not_enough' => '用戶餘額不足',
    'pay_points_not_enough' => '用戶積分不足',
    'money_paid_enough' => '已付款金額比商品總金額和各種費用之和還多，請先退款',
    'price_note' => '備註：商品價格中已包含屬性加價',
    'select_pack' => '選擇包裝',
    'select_card' => '選擇賀卡',
    'select_shipping' => '請先選擇配送方式',
    'want_insure' => '我要保價',
    'update_goods' => '更新商品',
    'notice_user' => '<strong>注意：</strong>搜索結果只顯示前20條記錄，如果沒有找到相' .
        '應會員，請更精確地查找。另外，如果該會員是從論壇註冊的且沒有在商城登錄過，' .
        '也無法找到，需要先在商城登錄。',
    'amount_increase' => '由於您修改了訂單，導致訂單總金額增加，需要再次付款',
    'amount_decrease' => '由於您修改了訂單，導致訂單總金額減少，需要退款',
    'continue_shipping' => '由於您修改了收貨人所在地區，導致原來的配送方式不再可用，請重新選擇配送方式',
    'continue_payment' => '由於您修改了配送方式，導致原來的支付方式不再可用，請重新選擇配送方式',
    'refund' => '退款',
    'cannot_edit_order_shipped' => '您不能修改已發貨的訂單',
    'cannot_edit_order_payed' => '您不能修改已付款的訂單',
    'address_list' => '從已有收貨地址中選擇：',
    'order_amount_change' => '訂單總金額由 %s 變為 %s',
    'shipping_note' => '說明：因為訂單已發貨，修改配送方式將不會改變配送費和保價費。',
    'change_use_surplus' => '編輯訂單 %s ，改變使用預付款支付的金額',
    'change_use_integral' => '編輯訂單 %s ，改變使用積分支付的數量',
    'return_order_surplus' => '由於取消、無效或退貨操作，退回支付訂單 %s 時使用的預付款',
    'return_order_integral' => '由於取消、無效或退貨操作，退回支付訂單 %s 時使用的積分',
    'order_gift_integral' => '訂單 %s 贈送的積分',
    'return_order_gift_integral' => '由於退貨或未發貨操作，退回訂單 %s 贈送的積分',
    'invoice_no_mall' => '多個發貨單號，請用英文逗號（「,」）隔開。',

    /* js 驗證提示 */
    'js_languages' => [
        'confirm_merge' => '您確實要合并這兩個訂單嗎？',
        'remove_confirm' => '刪除訂單將清除該訂單的所有信息。您確定要這麼做嗎？',

        'input_price' => '自定義價格',
        'pls_search_user' => '請搜索並選擇會員',
        'confirm_drop' => '確認要刪除該商品嗎？',
        'invalid_goods_number' => '商品數量不正確',
        'pls_search_goods' => '請搜索並選擇商品',
        'pls_select_area' => '請完整選擇所在地區',
        'pls_select_shipping' => '請選擇配送方式',
        'pls_select_payment' => '請選擇支付方式',
        'pls_select_pack' => '請選擇包裝',
        'pls_select_card' => '請選擇賀卡',
        'pls_input_note' => '請您填寫備註！',
        'pls_input_cancel' => '請您填寫取消原因！',
        'pls_select_refund' => '請選擇退款方式！',
        'pls_select_agency' => '請選擇辦事處！',
        'pls_select_other_agency' => '該訂單現在就屬於這個辦事處，請選擇其他辦事處！',
        'loading' => '載入中...',

        'not_back_cause' => '請輸出退換貨原因',
        'no_confirmation_delivery_info' => '暫無確認發貨信息',

        'jl_merge_order' => '合并訂單',
        'jl_merge' => '合并',
        'jl_sure_merge_order' => '您確定合并這兩個訂單么？',
        'jl_order_step_js_notic_11' => '您修改的費用信息折扣不能大於實際訂單總金額',
        'jl_order_step_js_notic_12' => '，會產生退款，一旦退款則將扣除您賬戶裡面的資金',
        'jl_order_step_js_notic_13' => '您修改的費用信息產生了負數（',
        'jl_order_step_js_notic_14' => '，是否繼續？',
        'jl_order_export_dialog' => '訂單導出彈窗',
        'jl_set_rob_order' => '設置搶單',
        'jl_vat_info' => '增值稅發票信息',
        'jl_search_logistics_info' => '正在查詢物流信息，請稍後...',
        'jl_goods_delivery' => '商品發貨',
    ],


    /* 訂單操作 */
    'order_operate' => '訂單操作：',
    'label_refund_amount' => '退款金額：',
    'label_handle_refund' => '退款方式：',
    'label_refund_note' => '退款說明：',
    'return_user_money' => '退回用戶餘額',
    'create_user_account' => '生成退款申請',
    'not_handle' => '不處理，誤操作時選擇此項',

    'order_refund' => '訂單退款：%s',
    'order_pay' => '訂單支付：%s',

    'send_mail_fail' => '發送郵件失敗',

    'send_message' => '發送/查看留言',

    /* 發貨單操作 */
    'delivery_operate' => '發貨單操作：',
    'delivery_sn_number' => '流水號：',
    'invoice_no_sms' => '請填寫發貨單號！',

    /* 發貨單搜索 */
    'delivery_sn' => '發貨單',

    /* 發貨單狀態 */
    'delivery_status_dt' => '發貨單狀態',
    'delivery_status' => [
        '0' => '已發貨',
        '1' => '退貨',
        '2' => '正常',
    ],


    /* 發貨單標籤 */
    'label_delivery_status' => '狀態',
    'label_suppliers_name' => '供貨商',
    'label_delivery_time' => '生成時間',
    'label_delivery_sn' => '發貨單流水號',
    'label_add_time' => '下單時間',
    'label_update_time' => '發貨時間',
    'label_send_number' => '發貨數量',
    'batch_delivery' => '批量發貨',

    /* 發貨單提示 */
    'tips_delivery_del' => '發貨單刪除成功！',

    /* 退貨單操作 */
    'back_operate' => '退貨單操作：',

    /* 退貨單標籤 */
    'return_time' => '退貨時間：',
    'label_return_time' => '退貨時間',
    'label_apply_time' => '退換貨申請時間',
    'label_back_shipping' => '退換貨配送方式',
    'label_back_invoice_no' => '退換貨發貨單號',
    'back_order_info' => '退貨單信息',

    /* 退貨單提示 */
    'tips_back_del' => '退貨單刪除成功！',

    'goods_num_err' => '庫存不足，請重新選擇！',

    /*大商創1.5後台新增*/
    /*退換貨列表*/
    'problem_desc' => '問題描述',
    'product_repair' => '商品維修',
    'product_return' => '商品退貨',
    'product_change' => '商品換貨',
    'product_price' => '商品價格',

    'return_sn' => '流水號',
    'return_change_sn' => '流水號',
    'repair' => '維修',
    'return_goods' => '退貨',
    'change' => '換貨',
    'only_return_money' => '僅退款',
    'already_repair' => '已維修',
    'refunded' => '已退款',
    'already_change' => '已換貨',
    'return_change_type' => '類型',
    'apply_time' => '申請時間',
    'y_amount' => '應退金額',
    's_amount' => '實退金額',
    'actual_return' => '合計已退款',
    'return_change_num' => '退換數量',
    'receipt_time' => '簽收時間',
    'applicant' => '申請人',
    'to_order_sn2' => '主訂單',
    'to_order_sn3' => '主訂單',
    'sub_order_sn' => '子訂單',
    'sub_order_sn2' => '子訂單',

    'return_reason' => '退換原因',
    'reason_cate' => '原因分類',
    'top_cate' => '頂級分類',

    'since_some_info' => '自提點信息',
    'since_some_name' => '自提點名稱',
    'contacts' => '聯繫人',
    'tpnd_time' => '提貨時間',
    'warehouse_name' => '倉庫名稱',
    'ciscount' => '優惠',
    'notice_delete_order' => '(用戶會員中心：已刪除該訂單)',
    'notice_trash_order' => '用戶已處理進入回收站',
    'order_not_operable' => '（訂單不可操作）',

    'region' => '地區',
    'seller_mail' => '賣家寄出',
    'courier_sz' => '快遞單號',
    'select_courier' => '請選擇快遞公司',
    'fillin_courier_number' => '請填寫快遞單號',
    'edit_return_reason' => '編輯退換貨原因',
    'buyers_return_reason' => '買家退換貨原因',
    'user_file_image' => '用戶上傳圖片憑證',
    'operation_notes' => '操作備註',
    'agree_apply' => '同意申請',
    'receive_goods' => '收到退回商品',
    'current_executable_operation' => '當前可執行操作',
    'refound' => '去退款',
    'swapped_out_single' => '換出商品寄出【分單】',
    'swapped_out' => '換出商品寄出',
    'complete' => '完成退換貨',
    'after_service' => '售後',
    'complete' => '完成退換貨',
    'wu' => '無',
    'not_filled' => '未填寫',
    'seller_message' => '賣家留言',
    'buyer_message' => '買家留言',
    'total_stage' => '總分期數',
    'stage' => '期',
    'by_stage' => '分期金額',
    'yuan_stage' => '元/期',
    'submit_order' => '提交訂單',
    'payment_order' => '支付訂單',
    'seller_shipping' => '商家發貨',
    'confirm_shipping' => '確認收貨',
    'evaluate' => '評價',
    'logistics_tracking' => '物流跟蹤',
    'cashdesk' => '收銀台',
    'wxapp' => '小程序',
    'info' => '信息',
    'general_invoice' => '普通發票',
    'personal_general_invoice' => '個人普通發票',
    'enterprise_general_invoice' => '企業普通發票',
    'VAT_invoice' => '增值稅發票',
    'id_code' => '識別碼',
    'has_been_issued' => '已發',
    'invoice_generated' => '發貨單已生成',
    'has_benn_refund' => '已退款',
    'net_profit' => '凈利潤約',
    'one_key_delivery' => '一鍵發貨',
    'goods_delivery' => '商品發貨',
    'search_logistics_info' => '正在查詢物流信息，請稍後...',
    'consignee_address' => '收貨地址',
    'view_order' => '查看訂單',
    'set_baitiao' => '設置白條',
    'account_details' => '賬目明細',
    'goods_sku' => '商品編號',
    'all_order' => '全部訂單',
    'order_status_01' => '待確認',
    'order_status_02' => '待付款',
    'order_status_03' => '待發貨',
    'order_status_04' => '已完成',
    'order_status_05' => '待收貨',
    'order_status_06' => '付款中',
    'search_keywords_placeholder' => '訂單編號/商品編號/商品關鍵字',
    'search_keywords_placeholder2' => '商品編號/商品關鍵字',
    'is_reality' => '正品保證',
    'is_return' => '包退服務',
    'is_fast' => '閃速配送',

    'order_category' => '訂單分類',
    'baitiao_order' => '白條訂單',
    'zc_order' => '眾籌訂單',
    'so_order' => '門店訂單',
    'fx_order' => '分銷訂單',
    'team_order' => '拼團',
    'bargain_order' => '砍價',
    'wholesale_order' => '批發',
    'package_order' => '超值禮包',
    'xn_order' => '虛擬商品訂單',
    'pt_order' => '普通訂單',
    'return_order' => '退換貨訂單',
    'other_order' => '促銷訂單',
    'db_order' => '奪寶訂單',
    'ms_order' => '秒殺訂單',
    'tg_order' => '團購訂單',
    'pm_order' => '拍賣訂單',
    'jf_order' => '積分訂單',
    'ys_order' => '預售訂單',

    'have_commission_bill' => '已出傭金賬單',
    'knot_commission_bill' => '已結傭金賬單',
    'view_commission_bill' => '查看賬單',

    'order_export_dialog' => '訂單導出彈窗',
    'operation_error' => '操作錯誤',

    'confirmation_receipt_time' => '確認收貨時間',

    'fill_user' => '填寫用戶',
    'batch_add_order' => '批量添加訂單',
    'search_user_placeholder' => '會員名稱/編號',
    'username' => '會員名稱',
    'search_user_name_not' => '請先搜索會員名稱',
    'search_user_name_notic' => '<strong>注意：</strong>搜索結果只顯示前20條記錄，如果沒有找到相應會員，請更精確地查找。<br>另外，如果該會員是從論壇註冊的且沒有在商城登錄過，也無法找到，需要先在商城登錄。',

    'search_number_placeholder' => '編號/名稱/貨號',
    'select_warehouse' => '請先選擇倉庫',
    'receipt_info' => '請填寫收貨信息',
    'add_distribution_mode' => '添加配送方式',
    'select_payment_method' => '選擇支付方式',
    'join_order' => '加入訂單',
    'add_invoice' => '添加發票',
    'search_goods_first' => '請先搜索商品',

    'order_step_notic_01' => '請查看地區信息是否輸入正確',
    /*大商創1.5後台新增end*/

    /*眾籌相關 by wu*/
    'zc_goods_info' => '眾籌方案信息',
    'zc_project_name' => '眾籌項目名稱',
    'zc_project_raise_money' => '眾籌金額',
    'zc_goods_price' => '方案價格',
    'zc_shipping_fee' => '配送費用',
    'zc_return_time' => '預計回報時間',
    'zc_return_content' => '回報內容',
    'zc_return_detail' => '項目成功結束後%s天內',

    'set_grab_order' => '設為搶單',
    'set_success' => '設置完成',

    //by wu
    'cannot_delete' => '存在子退換原因，無法刪除',
    'invoice_no_null' => '快遞單號不能為空',

    'seckill' => '秒殺訂單',

    //分單操作
    'split_action_note' => '【商品貨號：%s，發貨：%s件】',

    /* 退換貨原因 */
    'add_return_cause' => '添加退換貨原因',
    'return_cause_list' => '退換貨原因列表',
    'back_return_cause_list' => '返回退換貨原因列表',
    'return_order_info' => '返回訂單信息',
    'back_return_delivery_handle' => '返回退貨單操作',

    'detection_list_notic' => '只能操作-訂單應收貨時間-小於當前系統時間的訂單',

    'refund_type_notic_one' => '退款失敗，退款金額大於實際退款金額',
    'refund_type_notic_two' => '退款失敗，您的賬戶資金負金額大於信用額度，請您進行賬戶資金充值或者聯繫客服',
    'refund_type_notic_three' => '該訂單存在退換貨商品，不能退貨',
    'refund_type_notic_six' => '在線退款申請失敗，請檢查支付方式是否配置，或在線賬戶資金充足或者聯繫客服',

    'batch_delivery_success' => '批量發貨成功',
    'batch_delivery_failed' => '批量發貨失敗，發貨單號不能為空',
    'inspect_order_type' => '請檢查訂單狀態',

    'order_return_prompt' => '訂單退款，退回訂單',
    'buy_integral' => '購買的積分',


    /* order_step js*/
    'order_step_js_notic_01' => '請填寫收貨人名稱',
    'order_step_js_notic_02' => '請選擇國家',
    'order_step_js_notic_03' => '請選擇省/直轄市',
    'order_step_js_notic_04' => '請選擇城市',
    'order_step_js_notic_05' => '請選擇區/縣',
    'order_step_js_notic_06' => '請填寫收貨地址',
    'order_step_js_notic_07' => '請填寫手機號碼',
    'order_step_js_notic_08' => '手機號碼不正確',
    'order_step_js_notic_09' => '商品庫存不足',
    'order_step_js_notic_10' => '請先添加商品',
    'order_step_js_notic_11' => '您修改的費用信息折扣不能大於實際訂單總金額',
    'order_step_js_notic_12' => '，會產生退款，一旦退款則將扣除您賬戶裡面的資金',
    'order_step_js_notic_13' => '您修改的費用信息產生了負數（',
    'order_step_js_notic_14' => '，會是否繼續？',

    'lab_bar_shop_price' => '本店售價',
    'lab_bar_market_price' => '市場價',

    'refund_way' => '退款方式',
    'return_balance' => '退回到餘額',

    /* 頁面頂部操作提示 */
    'operation_prompt_content' => [
        'return_cause_list' => [
            '0' => '商城退貨原因信息列表管理。',
            '1' => '可進行刪除或修改退換貨原因。',
        ],
        'return_cause_info' => [
            '0' => '可選擇已有的頂級原因分類。',
        ],
        'back_list' => [
            '0' => '商城所有退貨訂單列表管理。',
            '1' => '可通過訂單號進行查詢，側邊欄進行高級搜索。',
        ],
        'delivery_list' => [
            '0' => '商城和平台所有已發貨的訂單列表管理。',
            '1' => '可通過訂單號進行查詢，側邊欄進行高級搜索。',
            '2' => '可進入查看取消發貨。',
        ],
        'detection_list' => [
            '0' => '檢測商城所有已發貨訂單。',
            '1' => '可通過訂單號進行查詢，側邊欄進行高級搜索。',
            '2' => '一鍵確認收貨功能說明：使用一鍵確認收貨功能，只能針對當前頁面的訂單條數進行操作處理，如需一次操作更多的訂單條數，可修改每頁顯示的條數進行操作處理。',
        ],
        'list' => [
            '0' => '商城所有的訂單列表，包括平台自營和入駐商家的訂單。',
            '1' => '點擊訂單號即可進入詳情頁面對訂單進行操作。',
            '2' => 'Tab切換不同狀態下的訂單，便於分類訂單。',
        ],
        'search' => [
            '0' => '訂單查詢提供多個條件進行精準查詢。',
        ],
        'step' => [
            '0' => '添加訂單流程為：選擇商城已有會員-選擇商品加入訂單-確認訂單金額-填寫收貨信息-添加配送方式-選擇支付方式-添加發票-查看費用信息-完成。',
        ],
        'return_list' => [
            '0' => '買家提交的退換貨申請信息管理。',
            '1' => '可通過訂單號進行查詢，側邊欄進行高級搜索。',
        ],
        'templates' => [
            '0' => '下單成功後，可列印訂單詳情。',
            '1' => '請不要輕易修改模板變數，否則會導致數據無法正常顯示。',
            '2' => '變數說明：
                            <p>$lang.***：語言項，修改路徑：根目錄/languages/zh_cn/admin/order.php</p>
                            <p>$order.***：訂單數據，請參考dsc_order數據表</p>
                            <p>$goods.***：商品數據，請參考dsc_goods數據表</p>',
        ],
    ],

    'stores_name' => '門店名稱：',
    'stores_address' => '門店地址：',
    'stores_tel' => '聯繫方式：',
    'stores_opening_hours' => '營業時間：',
    'stores_traffic_line' => '交通線路：',
    'stores_img' => '實景圖片：',
    'pick_code' => '提貨碼：',


    // 商家後台
    'label_sure_collect_goods_time' => '確認收貨時間：',
    'label_order_cate' => '訂單分類：',
    'label_id_code' => '識別碼：',
    'label_get_post_address' => '收票地址：',


    'add_order_step' => [
        '0' => '選擇下單用戶',
        '1' => '選擇訂單商品',
        '2' => '填寫收貨地址',
        '3' => '選擇配送方式',
        '4' => '選擇支付方式',
        '5' => '填寫發票信息',
        '6' => '填寫費用信息',
    ],


    'print_shipping_form' => '列印發貨單',
    'current' => '當前',
    'store_info' => '門店信息',

    'this_order_return_no_continue' => '此訂單已確認為退換貨訂單，無法繼續訂單操作！',
    'no_info_fill_express_number' => '暫無信息,請填寫快遞單號',

];