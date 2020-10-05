<?php

$_LANG = array(
    'separate_name' => 'Recommended into',
    'order_id' => 'The order number',
    'affiliate_separate' => 'Divided into',
    'affiliate_cancel' => 'cancel',
    'affiliate_rollback' => 'undo',
    'log_info' => 'Operational information',
    'edit_ok' => 'Operation is successful',
    'edit_fail' => 'The operation failure',
    'separate_info' => 'Order number %s, divided into: money %s points %s',
    'separate_info2' => 'User ID %s (%s), divided into: money %s credits %s',
    'sch_order' => 'Search for order number/username/nickname',
    'sch_stats' =>
        array(
            'name' => 'Operating state',
            'info' => 'Search by operation state:',
            'all' => 'all',
            0 => 'Waiting to be processed',
            1 => 'Has been divided into',
            2 => 'Cancel into',
            3 => 'Had withdrawn',
        ),
    'order_stats' =>
        array(
            'name' => 'The order status',
            0 => 'unconfirmed',
            1 => 'Have been confirmed',
            2 => 'Has been cancelled',
            3 => 'invalid',
            4 => 'Return goods',
            5 => 'Has been single',
            6 => 'Part of single',
            7 => 'Partially returned',
            8 => 'Only a refund',
        ),
    'shipping_status' =>
        array(
            'name' => 'Shipment status',
            0 => 'Not receiving',
            1 => 'Has been shipped',
            2 => 'Have the goods',
            3 => 'In the stock',
            4 => 'Shipped (some goods)',
            5 => 'Delivery in progress (handling sub-orders)',
            6 => 'Shipped (some goods)',
        ),
    'pay_status' =>
        array(
            'name' => 'Payment status',
            0 => 'Not paying',
            1 => 'In the payment',
            2 => 'Payment has been',
            3 => 'Partial payment - advance payment',
            4 => 'Have a refund',
            5 => 'Part of the refund',
        ),
    'js_languages' =>
        array(
            'cancel_confirm' => 'Are you sure you want to cancel the cut? This operation cannot be undone.',
            'rollback_confirm' => 'Are you sure you want to revoke the share?',
            'separate_confirm' => 'Are you sure about the cut?',
        ),
    'loginfo' =>
        array(
            0 => 'User id:',
            1 => 'Cash:',
            2 => 'Integral:',
            'cancel' => 'Divided by administrator cancel!',
        ),
    'separate_type' => 'Divided into types',
    'batch_separate' => 'The batch is divided into',
    'separate_by' =>
        array(
            0 => 'Recommended registration share',
            1 => 'Recommended order sharing',
            -1 => 'Recommended registration share',
            -2 => 'Recommended order sharing',
        ),
    'show_affiliate_orders' => 'This list displays the order information recommended by this user.',
    'back_note' => 'Return to the member edit page',
    'operation_prompt_content' =>
        array(
            0 => 'Split order information management.',
            1 => 'Search into order number query into order.',
        ),
);


return $_LANG;
