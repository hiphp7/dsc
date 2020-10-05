<?php

$_LANG['ems'] = 'EMS 國內郵政特快專遞';
//$_LANG['ems_express_desc']      = 'EMS 國內郵政特快專遞描述內容';
$_LANG['ems_express_desc'] = '<a href="http://www.ems.com.cn" target="_blank">http://www.ems.com.cn</a>';
//$_LANG['fee_compute_mode'] = '費用計算方式';
$_LANG['item_fee'] = '單件商品費用';
$_LANG['base_fee'] = '500克以內費用';
$_LANG['step_fee'] = '續重每500克或其零數的費用';

$_LANG['lable_box']['shop_name'] = '發貨人';
$_LANG['lable_box']['shop_address'] = '發貨地址';
$_LANG['lable_box']['shop_tel'] = '聯系電話';
$_LANG['lable_box']['customer_name'] = '收貨人';
$_LANG['lable_box']['customer_address'] = '收貨人地址';

/* 快遞單部分 */
$_LANG['lable_select_notice'] = '--選擇插入標簽--';
$_LANG['lable_box']['shop_country'] = '網店-國家';
$_LANG['lable_box']['shop_province'] = '網店-省份';
$_LANG['lable_box']['shop_city'] = '網店-城市';
$_LANG['lable_box']['shop_name'] = '網店-名稱';
$_LANG['lable_box']['shop_district'] = '網店-區/縣';
$_LANG['lable_box']['shop_tel'] = '網店-聯系電話';
$_LANG['lable_box']['shop_address'] = '網店-地址';
$_LANG['lable_box']['customer_country'] = '收件人-國家';
$_LANG['lable_box']['customer_province'] = '收件人-省份';
$_LANG['lable_box']['customer_city'] = '收件人-城市';
$_LANG['lable_box']['customer_district'] = '收件人-區/縣';
$_LANG['lable_box']['customer_tel'] = '收件人-電話';
$_LANG['lable_box']['customer_mobel'] = '收件人-手機';
$_LANG['lable_box']['customer_post'] = '收件人-郵編';
$_LANG['lable_box']['customer_address'] = '收件人-詳細地址';
$_LANG['lable_box']['customer_name'] = '收件人-姓名';
$_LANG['lable_box']['year'] = '年-當日日期';
$_LANG['lable_box']['months'] = '月-當日日期';
$_LANG['lable_box']['day'] = '日-當日日期';
$_LANG['lable_box']['order_no'] = '訂單號-訂單';
$_LANG['lable_box']['order_postscript'] = '備注-訂單';
$_LANG['lable_box']['order_best_time'] = '送貨時間-訂單';
$_LANG['lable_box']['pigeon'] = '√-對號';

$_LANG['shipping_print'] = '<table style="width:18.8cm" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td style="height:3.2cm;">&nbsp;</td>
  </tr>
</table>
<table style="width:18.8cm;" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td style="width:8.9cm;">
    <table width="100%" border="0" cellspacing="0" cellpadding="0">
    <tr>
    <td style="width:1.6cm; height:0.7cm;">&nbsp;</td>
    <td style="width:2.3cm;">{$shop_name}</td>
    <td style="width:2cm;">&nbsp;</td>
    <td>{$service_phone}</td>
    </tr>
    <tr>
    <td colspan="4" style="height:0.7cm; padding-left:1.6cm;">{$shop_name}</td>
    </tr>
    <tr>
    <td>&nbsp;</td>
    <td colspan="3" style="height:1.7cm;">{$shop_address}</td>
    </tr>
    <tr>
    <td colspan="3" style="width:6.3cm; height:0.5cm;"></td>
    <td>&nbsp;</td>
    </tr>
    </table>
    </td>
    <td style="width:0.4cm;"></td>
    <td style="width:9.5cm;">
    <table width="100%" border="0" cellspacing="0" cellpadding="0">
    <tr>
    <td style="width:1.6cm; height:0.7cm;">&nbsp;</td>
    <td style="width:2.3cm;">{$order.consignee}</td>
    <td style="width:2cm;">&nbsp;</td>
    <td>{$order.mobile}</td>
    </tr>
    <tr>
    <td colspan="4" style="height:0.7cm;">&nbsp;</td>
    </tr>
    <tr>
    <td>&nbsp;</td>
    <td colspan="3" style="height:1.7cm;">{$order.address}</td>
    </tr>
    <tr>
    <td colspan="3" style="width:6.3cm; height:0.5cm;"></td>
    <td>{$order.zipcode}</td>
    </tr>
    </table>
    </td>
  </tr>
</table>
<table style="width:18.8cm" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td style="height:5.1cm;">&nbsp;</td>
  </tr>
</table>';

return $_LANG;
