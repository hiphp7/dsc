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
    'pay_status' => '支付狀態',
    'pay_success' => '本次支付已經成功，我們將儘快為您發貨。',
    'pay_fail' => '本次支付失敗，請及時和我們取得聯繫。',
    'pay_disabled' => '您選用的支付方式已經被停用。',
    'pay_invalid' => '您選用了一個無效的支付方式。該支付方式不存在或者已經被停用。請您立即和我們取得聯繫。',

    'receipt_code_order' => '收款碼訂單',
    'seller_automatic_settlement' => '收款碼自動結算商家應結金額',
    'return_online' => '在線原路退款',
    'order_return_prompt' => '訂單退款，退回訂單 ',
    'buy_integral' => ' 購買的積分',
    'order_return_running_number' => '退換貨-流水號',
    'merchant_handle_recharge' => '商家充值，操作員：商家本人線上支付',
    'order_remark' => "【訂單】%s",
    'order_number' => '訂單數量',
    'individual' => '個',
    'order_individual_count' => '訂單個數',
    'sale_money' => '銷售額',
    'money_unit' => '元',
    'not_audited' => '未審核',
    'audited_yes_adopt' => '審核通過',
    'audited_not_adopt' => '審核未通過',
    'goods_salevolume' => '銷量',
    'max_value' => '最大值',
    'min_value' => '最小值',
    'average_value' => '平均值',
    'order_amount' => '訂單金額',
    'payorder_goods_number' => '下單商品數',
    'seller_area_distribution' => '店鋪地區分布',
    'order_confirm_receipt' => '此訂單已經確認收貨',

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
    'ps' => [
        PS_UNPAYED => '未付款',
        PS_PAYING => '付款中',
        PS_PAYED => '已付款',
        PS_PAYED_PART => '部分付款(定金)',
        PS_REFOUND => '已退款',
        PS_REFOUND_PART => '部分退款',
        PS_MAIN_PAYED_PART => '部分已付款',
    ],
    'ss' => [
        SS_UNSHIPPED => '未發貨',
        SS_PREPARING => '配貨中',
        SS_SHIPPED => '已發貨',
        SS_RECEIVED => '收貨確認',
        SS_SHIPPED_PART => '已發貨(部分商品)',
        SS_SHIPPED_ING => '配貨中', // 已分單
        STATUS_DELIVERIED => '已收貨',
        SS_SHIPPED_ING => '發貨中(處理分單)',
        OS_SHIPPED_PART => '已發貨(部分商品)',
    ],

    // 物流跟蹤
    'location_tracking_progress' => '地點和跟蹤進度',
    'time' => '時間',
    'tracking_tips' => [
        '異常提示',
        '抱歉，暫無查詢記錄'
    ],
    'order_action_user' => '計劃任務',
    'order_pay_timeout' => '支付超時',
    'order_failure' => '提交訂單失敗，數據丟失，請重新下單',
    'order_status_not_support' => '訂單狀態不支持此操作',
    'track_shipping_info_one' => '顯示物流信息',
    'track_shipping_info_two' => '收起物流信息',
    'track_shipping_info_three' => '查看更多物流',
];
