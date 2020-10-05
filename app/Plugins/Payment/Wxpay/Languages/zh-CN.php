<?php

$_LANG['wxpay'] = '微信支付';
$_LANG['wxpay_desc'] = '微信支付，是基于客户端提供的服务功能。同时向商户提供销售经营分析、账户和资金管理的功能支持。用户通过扫描二维码、微信内打开商品页面购买等多种方式调起微信支付模块完成支付。';
$_LANG['wxpay_app_appid'] = '客户端AppId';
$_LANG['wxpay_appid'] = '微信公众号AppId';
$_LANG['wxpay_appsecret'] = '微信公众号AppSecret';
$_LANG['wxpay_key'] = '商户支付密钥Key';
$_LANG['wxpay_mchid'] = '微信支付商户号';
$_LANG['wxpay_mchid_desc'] = '普通商户版 受理商ID';
$_LANG['wxpay_sub_mch_id'] = '微信支付子商户号';
$_LANG['wxpay_sub_mch_id_desc'] = '可选填，服务商版需单独设置支付授权目录';
$_LANG['is_h5'] = '是否开通微信h5支付';
$_LANG['is_h5_range'][0] = '未开通';
$_LANG['is_h5_range'][1] = '已开通';
$_LANG['is_h5_desc'] = '用于非微信浏览器下支付场景';
$_LANG['sslcert'] = '支付证书cert';
$_LANG['sslkey'] = '支付证书key';
$_LANG['sslcert_desc'] = '证书可选填，仅用于退款、发送现金红包等功能，不影响正常购物支付。请在微信商户后台下载支付证书，用记事本打开apiclient_cert.pem，并复制里面的内容粘贴到这里';
$_LANG['sslkey_desc'] = '证书可选填，仅用于退款、发送现金红包等功能，不影响正常购物支付。请在微信商户后台下载支付证书，用记事本打开apiclient_key.pem，并复制里面的内容粘贴到这里';
$_LANG['wxpay_signtype'] = '签名方式';
$_LANG['wxpay_button'] = '立即用微信支付';

return $_LANG;
