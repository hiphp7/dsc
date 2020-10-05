<?php

$_LANG = array(
    'zto' => 'Zhongtong express',
    'zto_desc' => '<a href="http://www.zto.cn" target="_blank">http://www.zto.cn</a>',
    'item_fee' => 'Cost per item',
    'base_fee' => 'Cost within 1KG of first weight',
    'step_fee' => 'Cost per 1KG or zero of renewed weight',
    'shipping_print' => '<table style="width:18.2cm" border="0" cellspacing="0" cellpadding="0">
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
