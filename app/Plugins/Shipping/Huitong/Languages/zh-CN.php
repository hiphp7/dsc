<?php

$_LANG['huitong'] = '百世汇通快递';
//$_LANG['huitong_desc']     = '首重为5元/KG，续重为5元/KG。';
$_LANG['huitong_desc'] = '<a href="http://www.htky365.com" target="_blank">http://www.htky365.com</a>';
$_LANG['item_fee'] = '单件商品费用：';
$_LANG['base_fee'] = '首重1KG以内费用';
$_LANG['step_fee'] = '续重每1KG或其零数的费用';

$_LANG['lable_box']['shop_name'] = '发货人';
$_LANG['lable_box']['shop_address'] = '发货地址';
$_LANG['lable_box']['shop_tel'] = '联系电话';
$_LANG['lable_box']['customer_name'] = '收货人';
$_LANG['lable_box']['customer_address'] = '收货人地址';

/* 快递单部分 */
$_LANG['lable_select_notice'] = '--选择插入标签--';
$_LANG['lable_box']['shop_country'] = '网店-国家';
$_LANG['lable_box']['shop_province'] = '网店-省份';
$_LANG['lable_box']['shop_city'] = '网店-城市';
$_LANG['lable_box']['shop_name'] = '网店-名称';
$_LANG['lable_box']['shop_district'] = '网店-区/县';
$_LANG['lable_box']['shop_tel'] = '网店-联系电话';
$_LANG['lable_box']['shop_address'] = '网店-地址';
$_LANG['lable_box']['customer_country'] = '收件人-国家';
$_LANG['lable_box']['customer_province'] = '收件人-省份';
$_LANG['lable_box']['customer_city'] = '收件人-城市';
$_LANG['lable_box']['customer_district'] = '收件人-区/县';
$_LANG['lable_box']['customer_tel'] = '收件人-电话';
$_LANG['lable_box']['customer_mobel'] = '收件人-手机';
$_LANG['lable_box']['customer_post'] = '收件人-邮编';
$_LANG['lable_box']['customer_address'] = '收件人-详细地址';
$_LANG['lable_box']['customer_name'] = '收件人-姓名';
$_LANG['lable_box']['year'] = '年-当日日期';
$_LANG['lable_box']['months'] = '月-当日日期';
$_LANG['lable_box']['day'] = '日-当日日期';
$_LANG['lable_box']['order_no'] = '订单号-订单';
$_LANG['lable_box']['order_postscript'] = '备注-订单';
$_LANG['lable_box']['order_best_time'] = '送货时间-订单';
$_LANG['lable_box']['pigeon'] = '√-对号';

$_LANG['shipping_print'] = '<table border="0" cellspacing="0" cellpadding="0" style="width:18.6cm; height:11.3cm;">
  <tr>
    <td valign="top" style="width:7.2cm;">
      <table width="100%" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td style="height:1.5cm;">&nbsp;</td>
      </tr>
    </table>
    <table width="100%" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td colspan="4" style="height:0.4cm;"></td>
        </tr>
      <tr>
        <td style="width:1cm; height:1cm;">&nbsp;</td>
        <td style="width:2.4cm;">{$shop_name}</td>
        <td style="width:1cm; height:1cm;">&nbsp;</td>
        <td>{$city}</td>
      </tr>
    </table>
    <table width="100%" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td colspan="6" style="height:0.4cm;">&nbsp;</td>
        </tr>
      <tr>
        <td style="width:1.6cm;">{$province}</td>
        <td style="width:0.8cm; height:0.6cm;"></td>
        <td style="width:1.6cm;">{$city}</td>
        <td style="width:0.8cm;"></td>
        <td style="width:1.6cm;">&nbsp;</td>
        <td style="width:0.8cm;"></td>
      </tr>
      <tr>
        <td colspan="6" style="height:1cm;">{$shop_address}</td>
        </tr>
    </table>
    <table width="100%" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td style="height:0.4cm;"></td>
      </tr>
      <tr>
        <td style="height:1cm;">{$shop_name}</td>
      </tr>
    </table>
     <table width="100%" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td style="height:0.8cm; width:0.8cm;">&nbsp;</td>
        <td style="width:2.8cm;">{$service_phone}</td>
        <td style="height:0.8cm; width:0.8cm;">&nbsp;</td>
        <td style="width:2.8cm;">&nbsp;</td>
      </tr>
    </table>
    </td>
    <td valign="top" style="width:7.2cm;">
    <table width="100%" border="0" cellspacing="0" cellpadding="0">
    <tr>
      <td style="height:2.9cm;">&nbsp;</td>
    </tr>
  </table>
  <table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td style="height:1cm; width:1.2cm;">&nbsp;</td>
    <td style="width:2.4cm;">{$order.consignee}</td>
    <td style="height:1cm; width:1.2cm;">&nbsp;</td>
    <td style="width:2.4cm;">{$order.region}</td>
  </tr>
</table>
<table width="100%" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td colspan="6" style="height:0.4cm;">&nbsp;</td>
        </tr>
      <tr>
        <td style="width:1.6cm;">{$province}</td>
        <td style="width:0.8cm; height:0.6cm;"></td>
        <td style="width:1.6cm;">{$city}</td>
        <td style="width:0.8cm;"></td>
        <td style="width:1.6cm;"></td>
        <td style="width:0.8cm;"></td>
      </tr>
      <tr>
        <td colspan="6" style="height:1cm;">{$order.address}</td>
        </tr>
    </table>
    <table width="100%" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td style="height:0.4cm;"></td>
      </tr>
      <tr>
        <td style="height:1cm;">{$order.consignee}</td>
      </tr>
    </table>
    <table width="100%" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td style="height:0.8cm; width:0.8cm;">&nbsp;</td>
        <td style="width:2.8cm;"></td>
        <td style="height:0.8cm; width:0.8cm;">&nbsp;</td>
        <td style="width:2.8cm;">{$order.mobile}</td>
      </tr>
    </table>

    </td>
    <td valign="top" style="width:4.2cm;">&nbsp;

    </td>
  </tr>
</table>';

return $_LANG;
