<?php

$_LANG['wxpay'] = '微信支付';
$_LANG['wxpay_desc'] = '微信支付，是基於客戶端提供的服務功能。同時向商戶提供銷售經營分析、賬戶和資金管理的功能支持。用戶通過掃描二維碼、微信內打開商品頁面購買等多種方式調起微信支付模塊完成支付。';
$_LANG['wxpay_app_appid'] = '客戶端AppId';
$_LANG['wxpay_appid'] = '微信公眾號AppId';
$_LANG['wxpay_appsecret'] = '微信公眾號AppSecret';
$_LANG['wxpay_key'] = '商戶支付密鑰Key';
$_LANG['wxpay_mchid'] = '微信支付商戶號';
$_LANG['wxpay_mchid_desc'] = '普通商戶版 受理商ID';
$_LANG['wxpay_sub_mch_id'] = '微信支付子商戶號';
$_LANG['wxpay_sub_mch_id_desc'] = '可選填，服務商版需單獨設置支付授權目錄';
$_LANG['is_h5'] = '是否開通微信h5支付';
$_LANG['is_h5_range'][0] = '未開通';
$_LANG['is_h5_range'][1] = '已開通';
$_LANG['is_h5_desc'] = '用於非微信瀏覽器下支付場景';
$_LANG['sslcert'] = '支付證書cert';
$_LANG['sslkey'] = '支付證書key';
$_LANG['sslcert_desc'] = '證書可選填，僅用於退款、發送現金紅包等功能，不影響正常購物支付。請在微信商戶後台下載支付證書，用記事本打開apiclient_cert.pem，並復制裡面的內容粘貼到這里';
$_LANG['sslkey_desc'] = '證書可選填，僅用於退款、發送現金紅包等功能，不影響正常購物支付。請在微信商戶後台下載支付證書，用記事本打開apiclient_key.pem，並復制裡面的內容粘貼到這里';
$_LANG['wxpay_signtype'] = '簽名方式';
$_LANG['wxpay_button'] = '立即用微信支付';

return $_LANG;
