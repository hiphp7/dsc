<?php

$_LANG = array(
    'sto_express' => 'Shentong express delivery',
    'sto_express_desc' => '<a href="http://www.sto.cn" target="_blank">http://www.sto.cn</a>',
    'item_fee' => 'Cost per item',
    'base_fee' => 'Cost within 1KG of first weight',
    'step_fee' => 'Cost per 1KG or zero of renewed weight',
    'shipping_print' => '<table border="0" cellspacing="0" cellpadding="0" style="width:18.9cm;">
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
</table>',
    'lable_select_notice' => 'Select insert tag',
    'lable_box' =>
        array(
            'shop_country' => 'Online shop - country',
            'shop_province' => 'Online shop - province',
            'shop_city' => 'Online shop - city',
            'shop_name' => 'Online store - name',
            'shop_district' => 'Online store - district/county',
            'shop_tel' => 'Shop - contact number',
            'shop_address' => 'Online shop - address',
            'customer_country' => 'Recipient - country',
            'customer_province' => 'Addressee - province',
            'customer_city' => 'Addressee - city',
            'customer_district' => 'Recipient - district/county',
            'customer_tel' => 'Addressee - telephone',
            'customer_mobel' => 'Recipient - mobile phone',
            'customer_post' => 'Addressee - zip code',
            'customer_address' => 'Addressee - full address',
            'customer_name' => 'Addressee - name',
            'year' => 'Year - date of the date',
            'months' => 'Month - date of the day',
            'day' => 'Date - date of the day',
            'order_no' => 'Order number - order',
            'order_postscript' => 'Remarks - order',
            'order_best_time' => 'Delivery time - order',
            'pigeon' => 'Tick - check the number',
        ),
);


return $_LANG;
