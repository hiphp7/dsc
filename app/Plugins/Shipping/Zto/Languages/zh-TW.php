<?php

$_LANG['zto'] = '中通速遞';
//$_LANG['zto_desc']     = '中通快遞的相關說明。保價費按照申報價值的2％交納，但是，保價費不低於100元，保價金額不得高於10000元，保價金額超過10000元的，超過的部分無效';
$_LANG['zto_desc'] = '<a href="http://www.zto.cn" target="_blank">http://www.zto.cn</a>';
$_LANG['item_fee'] = '單件商品費用';
$_LANG['base_fee'] = '首重1KG以內費用';
$_LANG['step_fee'] = '續重每1KG或其零數的費用';
$_LANG['shipping_print'] = '<table style="width:18.2cm" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td style="height:2.2cm;">&nbsp;</td>
  </tr>
</table>
<table style="width:18.2cm" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td style="height:4.4cm; width:9.1cm;">
    <table width="100%" border="0" cellspacing="0" cellpadding="0">
    <tr>
    <td style="width:2cm; height:0.8cm;">&nbsp;</td>
    <td style="width:2.7cm;">{$shop_name}</td>
    <td style="width:1.2cm;">&nbsp;</td>
    <td>{$province}</td>
    </tr>
    <tr>
    <td>&nbsp;</td>
    <td colspan="3" style="height:1.6cm;">{$shop_address}</td>
    </tr>
    <tr>
    <td>&nbsp;</td>
    <td colspan="3" style="height:0.8cm;">{$shop_name}</td>
    </tr>
    <tr>
    <td>&nbsp;</td>
    <td style="height:1.2cm;">{$service_phone}</td>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
    </tr>
    </table>
    </td>
    <td style="height:4.4cm; width:9.1cm;">
    <table width="100%" border="0" cellspacing="0" cellpadding="0">
    <tr>
    <td style="width:2cm; height:0.8cm;">&nbsp;</td>
    <td style="width:2.7cm;">{$order.consignee}</td>
    <td style="width:1.2cm;">&nbsp;</td>
    <td>{$order.region}</td>
    </tr>
    <tr>
    <td>&nbsp;</td>
    <td colspan="3" style="height:1.6cm;">{$order.address}</td>
    </tr>
    <tr>
    <td>&nbsp;</td>
    <td colspan="3" style="height:0.8cm;"></td>
    </tr>
    <tr>
    <td>&nbsp;</td>
    <td style="height:1.2cm;">{$order.mobile}</td>
    <td>&nbsp;</td>
    <td>{$order.zipcode}</td>
    </tr>
    </table>
    </td>
  </tr>
</table>
<table style="width:18.2cm" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td style="height:4.2cm;">&nbsp;</td>
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
