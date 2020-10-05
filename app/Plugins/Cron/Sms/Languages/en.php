<?php

$_LANG = array(
    'sms' => 'New order notification',
    'sms_desc' => 'After the user orders asynchronous send SMS, email to the business. Avoid calling SMS or email interfaces that cause pages to slow down when placing an order',
    'auto_sms_count' => 'The sum of the maximum number of messages and emails sent each time',
    'auto_sms_count_range' =>
        array(
            5 => '5',
            10 => '10',
            20 => '20',
            50 => '50',
            100 => '100',
        ),
    'order_placed_sms' => 'You have a new order, consignee: %s tel: %s',
    'sms_paid' => 'Payment has been',
);


return $_LANG;
