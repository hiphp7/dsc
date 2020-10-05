<?php

$_LANG['sf_express'] = '順豐快遞';
//$_LANG['sf_express_desc']        = '江、浙、滬地區首重15元/KG，續重2元/KG，其餘城市首重20元/KG';
$_LANG['sf_express_desc'] = '<a href="http://www.sf-express.com" target="_blank">http://www.sf-express.com</a>';
$_LANG['item_fee'] = '單件商品費用';
$_LANG['base_fee'] = '首重1KG以內費用';
$_LANG['step_fee'] = '續重每1KG或其零數的費用';
$_LANG['lable_box']['shop_name'] = '發貨人';
$_LANG['lable_box']['shop_address'] = '發貨地址';
$_LANG['lable_box']['shop_tel'] = '聯系電話';
$_LANG['lable_box']['customer_name'] = '收貨人';
$_LANG['lable_box']['customer_address'] = '收貨人地址';

$_LANG['shipping_print'] = '<table style="width:18.8cm; height:3cm;" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td>&nbsp;</td>
  </tr>
</table>
<table style="width:18.8cm;" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td style="width:9.4cm" valign="top">
   <table width="100%" border="0" cellspacing="0" cellpadding="0">
   <tr>
      <td valign="middle" style="width:1.5cm; height:0.8cm;">&nbsp;</td>
      <td width="85%">
     <table width="100%" border="0" cellspacing="0" cellpadding="0">
     <tr>
    <td valign="middle" style="width:5cm; height:0.8cm;">{$shop_name}</td>
      <td valign="middle">&nbsp;</td>
    <td valign="middle" style="width:1.8cm; height:0.8cm;">{$order.order_sn}</td>
    </tr>
   </table>
   </td>
 </tr>
 <tr valign="middle">
 <td>&nbsp;</td>
 <td class="h">
 <table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td style="width:1.3cm; height:0.8cm;">{$province}</td>
    <td>&nbsp;</td>
    <td style="width:1.3cm; height:0.8cm;">{$city}</td>
    <td>&nbsp;</td>
    <td style="width:1.3cm; height:0.8cm;">&nbsp;</td>
    <td>&nbsp;</td>
    <td style="width:1.3cm; height:0.8cm;">&nbsp;</td>
    <td>&nbsp;</td>
  </tr>
</table>
</td>
</tr>
<tr valign="middle">
<td>&nbsp;</td>
<td class="h">{$shop_address}</td>
</tr>
<tr valign="middle">
<td>&nbsp;</td>
<td class="h">&nbsp;</td>
</tr>
<tr valign="middle">
<td>&nbsp;</td>
<td class="h">
<table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td>&nbsp;</td>
    <td style="width:1.5cm; height:0.8cm;">&nbsp;</td>
    <td>&nbsp;</td>
    <td style="width:3.5cm; height:0.8cm;">{$service_phone}</td>
  </tr>
</table>
</td>
</tr>
</table>
  </td>
    <td style="width:9.4cm;" valign="top">
  <table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
<td valign="middle" style="width:1.5cm; height:0.8cm;">&nbsp;</td>
<td width="85%">
<table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
  <td valign="middle" style="width:5cm; height:0.8cm;">{$order.consignee}</td>
  <td valign="middle">&nbsp;</td>
  <td valign="middle" style="width:1.8cm; height:0.8cm;">&nbsp;</td>
  </tr>
</table>
</td>
</tr>
<tr valign="middle">
<td>&nbsp;</td>
<td class="h">{$order.region}</td>
</tr>
<tr valign="middle">
<td>&nbsp;</td>
<td class="h">{$order.address}</td>
</tr>
<tr valign="middle">
<td>&nbsp;</td>
<td class="h">&nbsp;</td>
</tr>
<tr valign="middle">
<td>&nbsp;</td>
<td class="h">
<table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td style="width:1.7cm;">&nbsp;</td>
    <td style="width:1.5cm; height:0.8cm;">&nbsp;</td>
    <td style="width:1.7cm;">&nbsp;</td>
    <td style="width:3.5cm; height:0.8cm;">{$order.tel}</td>
  </tr>
</table>
</td>
</tr>
</table>
</td>
  </tr>
</table>
<table style="width:18.8cm; height:6.5cm;" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td valign="top" style="width:7.4cm;">
  <table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
   <td colspan="2" style="height:0.5cm;"></td>
  </tr>
<tr>
<td rowspan="2" style="width:4.9cm;">&nbsp;</td>
<td style="height:0.8cm;">
<table width="100%" border="0" cellspacing="0" cellpadding="0" style="height:0.8cm;">
  <tr>
    <td style="width:1cm;">&nbsp;</td>
    <td>&nbsp;</td>
  </tr>
</table>
</td>
</tr>
<tr>
<td style="height:1.3cm;">
<table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
  <td style="height:0.7cm;">&nbsp;</td>
  </tr>
  <tr>
  <td>&nbsp;</td>
  </tr>
</table>
</td>
</tr>
</table>
<table width="100%" border="0" cellspacing="0" cellpadding="0" style="height:1.5cm">
<tr>
<td>&nbsp;</td>
</tr>
</table>
</td>
<td valign="top" style="width:11.4cm;">&nbsp;</td>
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
