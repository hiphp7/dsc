<?php

$_LANG = array(
    'wxpay' => 'WeChat pay',
    'wxpay_desc' => 'WeChat payment is based on the service function provided by the client. At the same time, it provides functional support of sales operation analysis, account and fund management to merchants. The user completes the payment by scanning the qr code, opening the product page and purchasing in WeChat, etc.',
    'wxpay_app_appid' => 'The client AppId',
    'wxpay_appid' => 'WeChat public account AppId',
    'wxpay_appsecret' => 'WeChat public account AppSecret',
    'wxpay_key' => 'Merchant payment Key',
    'wxpay_mchid' => 'WeChat merchant number',
    'wxpay_mchid_desc' => 'General merchant version acceptance business ID',
    'wxpay_sub_mch_id' => 'WeChat payment sub-merchant number',
    'wxpay_sub_mch_id_desc' => 'Optional, service providers need to set up separate payment authorization directory',
    'is_h5' => 'Whether to open WeChat h5 payment',
    'is_h5_range' =>
        array(
            0 => 'Did not open',
            1 => 'Has been opened',
        ),
    'is_h5_desc' => 'For non-WeChat payment scenarios',
    'sslcert' => 'Payment certificate cert',
    'sslkey' => 'Payment certificate key',
    'sslcert_desc' => 'The certificate can be filled in optionally. It is only used for refund, cash red envelope and other functions. It does not affect the normal shopping payment. Please download the payment certificate in the WeChat merchant background, open apiclient_cert. Pem with notepad, and copy and paste the contents into here',
    'sslkey_desc' => 'The certificate can be filled in optionally. It is only used for refund, cash red envelope and other functions. It does not affect the normal shopping payment. Please download the payment certificate in WeChat merchant background, use notepad to open apicli 64 clearing |==|**t_key.pem, and copy and paste the contents into here',
    'wxpay_signtype' => 'Signature way',
    'wxpay_button' => 'Pay immediately with WeChat',
);


return $_LANG;
