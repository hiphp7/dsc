<?php

$_LANG['sto_express'] = '申通快遞';
//$_LANG['sto_express_desc']       = '江、浙、滬地區首重為15元/KG，其他地區18元/KG， 續重均為5-6元/KG， 雲南地區為8元';
$_LANG['sto_express_desc'] = '<a href="http://www.sto.cn" target="_blank">http://www.sto.cn</a>';
$_LANG['item_fee'] = '單件商品費用';
$_LANG['base_fee'] = '首重1KG以內費用';
$_LANG['step_fee'] = '續重每1KG或其零數的費用';
$_LANG['shipping_print'] = '<table border="0" cellspacing="0" cellpadding="0" style="width:18.9cm;">
  <tr>
    <td colspan="3" style="height:2cm;">&nbsp;</td>
  </tr>
  <tr>
    <td style="width:8cm; height:4cm; padding-top:0.3cm;" align="center" valign="middle">
     <table border="0" cellspacing="0" cellpadding="0" style="width:7.5cm;" align="center">
  <tr>
    <td style="width:2.3cm;">&nbsp;</td>
    <td style="height:1.5cm;">{$shop_address}</td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td style="height:0.9cm;">{$shop_name}</td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td style="height:0.9cm;">{$shop_name}</td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td style="height:0.9cm;">{$service_phone}</td>
  </tr>
</table>

    </td>
    <td style="width:8cm; height:4cm; padding-top:0.3cm;" align="center" valign="middle">
    <table border="0" cellspacing="0" cellpadding="0" style="width:7.5cm;" align="center">
  <tr>
    <td style="width:2.3cm;">&nbsp;</td>
    <td style="height:1.5cm;">{$order.address}</td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td style="height:0.9cm;"></td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td style="height:0.9cm;">{$order.consignee}</td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td style="height:0.9cm;">{$order.tel}</td>
  </tr>
</table>
    </td>
    <td rowspan="2" style="width:3cm;">&nbsp;</td>
  </tr>
  <tr>
    <td colspan="2" style="height:3.5cm;">&nbsp;</td>
  </tr>
</table>';

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

return $_LANG;
