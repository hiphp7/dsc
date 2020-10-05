<?php

$_LANG = array(
    'ems' => 'Domestic post express',
    'ems_express_desc' => '<a href="http://www.ems.com.cn" target="_blank">http://www.ems.com.cn</a>',
    'item_fee' => 'Cost per item',
    'base_fee' => 'No more than 500 grams',
    'step_fee' => 'Cost per 500 grams or zero of the renewed weight',
    'lable_box' =>
        array(
            'shop_name' => 'Online store - name',
            'shop_address' => 'Online shop - address',
            'shop_tel' => 'Shop - contact number',
            'customer_name' => 'Addressee - name',
            'customer_address' => 'Addressee - full address',
            'shop_country' => 'Online shop - country',
            'shop_province' => 'Online shop - province',
            'shop_city' => 'Online shop - city',
            'shop_district' => 'Online store - district/county',
            'customer_country' => 'Recipient - country',
            'customer_province' => 'Addressee - province',
            'customer_city' => 'Addressee - city',
            'customer_district' => 'Recipient - district/county',
            'customer_tel' => 'Addressee - telephone',
            'customer_mobel' => 'Recipient - mobile phone',
            'customer_post' => 'Addressee - zip code',
            'year' => 'Year - date of the date',
            'months' => 'Month - date of the day',
            'day' => 'Date - date of the day',
            'order_no' => 'Order number - order',
            'order_postscript' => 'Remarks - order',
            'order_best_time' => 'Delivery time - order',
            'pigeon' => 'Tick - check the number',
        ),
    'lable_select_notice' => 'Select insert tag',
    'shipping_print' => '<table style="width:18.8cm" border="0" cellspacing="0" cellpadding="0">
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
</table>',
);


return $_LANG;
