<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0"/>
    <meta name="apple-mobile-web-app-capable" content="yes"/>
    <meta name="apple-mobile-web-app-status-bar-style" content="black"/>
    <meta name="format-detection" content="telephone=no"/>
    <title>报价单打印格式</title>

    <style>
        @font-face {
            font-family: 'msyh';
            src: url('{{ $url }}fonts/vendor/dompdf/msyh.ttf') format('truetype');
        }

        body {
            font-family: 'msyh';
        }
    </style>
</head>

<body>

<table style="text-align: center; width: 100%" cellspacing="0" cellpadding="0" align="center">
    <tr>
        <td style="font-size: 26px; height: 40px;">
            @if ($shop_info['shop_logo'] != '')
                <img src="{{ $shop_info['shop_logo'] }}" alt="Logo"
                     width="134"/>
            @endif
            {{ $shop_info['shop_name'] }}
        </td>
    </tr>
    <tr>
        <td style="font-size: 12px; height: 20px;">地址：{{ $shop_info['shop_address'] }}</td>
    </tr>
    <tr>
        <td style="font-size: 12px; height: 20px;">
            传真:{{ $shop_info['kf_tel'] }}
            @if ($shop_info['kf_qq'] != '')
                {{ $shop_info['kf_qq'] }}
            @endif
            @if ($shop_info['kf_ww'] != '')
                {{ $shop_info['kf_ww'] }}
            @endif
        </td>
    </tr>
    <tr>
        <td style="font-size: 12px; height: 20px;">网址：{{ $url }}</td>
    </tr>
    <tr>
        <td style="font-size: 12px; height: 20px;"></td>
    </tr>
    <tr>
        <td style="font-size: 26px; height: 40px;">订单</td>
    </tr>
</table>

<table style="margin: auto; width: 980px;" cellspacing="0" cellpadding="0" align="center">
    <tr>
        <td style="width:200px; font-size: 12px;">
            <table style="width: 100%" cellspacing="0" cellpadding="0">
                <tr>
                    <td style="height:20px">收货人姓名： {{ $consignee['consignee'] }}</td>
                    <td style="height:20px">&nbsp;</td>
                </tr>
                <tr>
                    <td style="height:20px">收货地址： {{ $consignee['address'] }}</td>
                    <td style="height:20px">&nbsp;</td>
                </tr>
                <tr>
                    <td style="height:20px">电&nbsp;&nbsp;话：{{ $consignee['tel'] }}</td>
                    <td style="height:20px">&nbsp;</td>
                </tr>
                <tr>
                    <td style="height:20px">传&nbsp;&nbsp;真：{{ $consignee['sign_building'] }}</td>
                    <td style="height:20px">&nbsp;</td>
                </tr>
                <tr>
                    <td style="height:20px">电子邮件：{{ $consignee['email'] }}</td>
                    <td style="height:20px">&nbsp;</td>
                </tr>
                <tr>
                    <td style="height:20px">期望交货期：{{ $consignee['best_time'] }}</td>
                    <td style="height:20px">&nbsp;</td>
                </tr>
                <tr>
                    <td style="height:10px"></td>
                    <td style="height:10px"></td>
                </tr>
            </table>
        </td>
        <td style="width: 600px; "></td>
        <td style="width: 150px; font-size: 12px;">
            <table style="width: 100%" cellspacing="0" cellpadding="0">
                <tr>
                    <td style="height:20px">订单号：</td>
                    <td style="height:20px">{{ $order_info['order_sn'] }}</td>
                </tr>
                <tr>
                    <td style="height:20px">下单时间：</td>
                    <td style="height:20px">{{ $order_info['add_time'] }}</td>
                </tr>
                <tr>
                    <td style="height:20px">手&nbsp;&nbsp;机：</td>
                    <td style="height:20px">{{ $consignee['mobile'] }}</td>
                </tr>
                <tr>
                    <td style="height:20px">币&nbsp;&nbsp;别：</td>
                    <td style="height:20px">RMB</td>
                </tr>
                <tr>
                    <td style="height:20px"></td>
                    <td style="height:20px"></td>
                </tr>

            </table>
        </td>
    </tr>
</table>

<div style="height:10px; overflow:hidden;"></div>
<div style="height:10px; overflow:hidden;"></div>


<table style="border: 1px solid #000000; text-align: center; background: #FFFFFF; width: 980px; margin: auto"
       cellspacing="0" cellpadding="0">
    <tr>
        <td style="height:30px; width: 39px; font-size: 12px; text-align: center; border-bottom: 1px solid #000000;border-right: 1px solid #000000;">
            序号
        </td>
        <td style="height:30px; width: 149px; font-size: 12px; text-align: center; border-bottom: 1px solid #000000;border-right: 1px solid #000000;">
            订货号
        </td>
        <td style="height:30px; width: 359px; font-size: 12px; text-align: center; border-bottom: 1px solid #000000;border-right: 1px solid #000000;">
            产品名称
        </td>
        <td style="height:30px; width: 169px; font-size: 12px; text-align: center; border-bottom: 1px solid #000000;border-right: 1px solid #000000;">
            产品属性
        </td>
        <td style="height:30px; width: 79px; font-size: 12px; text-align: center; border-bottom: 1px solid #000000;border-right: 1px solid #000000;">
            数量
        </td>
        <td style="height:30px; width: 79px; font-size: 12px; text-align: center; border-bottom: 1px solid #000000;border-right: 1px solid #000000;">
            单价
        </td>
        <td style="height:30px; width: 100px; font-size: 12px; text-align: center; border-bottom-style: solid; border-bottom-color: #000000; border-bottom-width: 1px;">
            金额
        </td>
    </tr>
    @foreach ($order_goods as $k => $v)
        <tr>
            <td style="height:30px; width: 39px; font-size: 12px; text-align: center; border-bottom: 1px solid #000000;border-right: 1px solid #000000;">{{ $k + 1 }}</td>
            <td style="height:30px; width: 149px; font-size: 12px; text-align: center; border-bottom: 1px solid #000000;border-right: 1px solid #000000;">{{ $v['goods_sn'] }}</td>
            <td style="height:30px; width: 359px; font-size: 12px; text-align: center; border-bottom: 1px solid #000000;border-right: 1px solid #000000;">{{ $v['goods_name'] }}</td>
            <td style="height:30px; width: 169px; font-size: 12px; text-align: center; border-bottom: 1px solid #000000;border-right: 1px solid #000000;">{{ $v['goods_attr'] }}</td>
            <td style="height:30px; width: 79px; font-size: 12px; text-align: center; border-bottom: 1px solid #000000;border-right: 1px solid #000000;">{{ $v['goods_number'] }}</td>
            <td style="height:30px; width: 79px; font-size: 12px; text-align: center; border-bottom: 1px solid #000000;border-right: 1px solid #000000;">{{ $v['formated_goods_price'] }}</td>
            <td style="height:30px; width: 100px; font-size: 12px; text-align: center; border-bottom-style: solid; border-bottom-color: #000000; border-bottom-width: 1px;">
                {{ $v['formated_subtotal'] }}
                <br/>
                @if($v['dis_amount'] > 0)
                    <font style='color:#F00'>（优惠：" . {{ $v['discount_amount'] }} . "）</font>;
                @endif
            </td>
        </tr>
    @endforeach
    <tr>
        <td style="border-bottom: 1px solid #000000; height:30px; font-size: 12px; text-align: left; " colspan="7">
            &nbsp;留言：
        </td>
    </tr>
</table>


<table style="border: 1px solid #000000; text-align: left; background: #FFFFFF; width: 980px; margin: auto; font-size: 12px;"
       cellspacing="0" cellpadding="0">
    <tr>
        <td style="width:70%">

            <table style="width: 100%" cellspacing="0" cellpadding="0">
                <tr>
                    <td align="center" style="height:25px; font-size: 12px;" colspan="3">条款说明</td>
                </tr>
                <tr>
                    <td style="height:20px; width: 100px; text-align:right">付款方式：</td>
                    <td style="height:20px" colspan="2">{{ $order_info['pay_name'] }}</td>
                </tr>
                <tr>
                    <td style="height:20px; width: 100px; text-align:right">配送方式：</td>
                    <td style="height:20px" colspan="2">{{ $order_info['shipping_name'] }}</td>
                </tr>
                <tr>
                    <td colspan="3">&nbsp;</td>
                </tr>
            </table>

        </td>
        <td style="width:30%; border-left: 1px solid #000000">
            <table style="width: 100%" cellspacing="0" cellpadding="0">
                <tr>
                    <td style="height:20px">&nbsp;</td>
                </tr>
                <tr>
                    <td style="height:20px">&nbsp;</td>
                </tr>
                <tr>
                    <td style="height:20px">&nbsp;总金额: {{ $order_info['order_amount'] }}（RMB）</td>
                </tr>
                <tr>
                    <td style="color:#F00">&nbsp;包含：</td>
                </tr>
                <tr>
                    <td style="color:#F00">&nbsp;运费: {{ $order_info['formated_shipping_fee'] }}（RMB）</td>
                </tr>
                <tr>
                    <td style="height:20px">&nbsp;</td>
                </tr>
            </table>


        </td>
    </tr>
</table>


<table style="border: 1px solid #000000; text-align: left; background: #FFFFFF; ; margin: auto; font-size: 12px; width:980px;"
       cellspacing="0" cellpadding="0">
    <tr>
        <td style="width: 30px">
        </td>

        <td style="border-right: 1px solid #000000; width: 460px">
            <table style="width: 470px" cellspacing="0" cellpadding="0">
                <tr>
                    <td style="height:20px">&nbsp;</td>
                </tr>
                <tr>
                    <td style="height:20px">&nbsp;客户确认：</td>
                </tr>
                <tr>
                    <td style="height:20px">&nbsp;{{ $consignee['consignee'] }}</td>
                </tr>
                <tr>
                    <td style="height:20px">&nbsp;</td>
                </tr>
            </table>
        </td>

        <td style="width: 490px">
            <table style="width: 500px" cellspacing="0" cellpadding="0">
                <tr>
                    <td style="height:20px">&nbsp;</td>
                </tr>
                <tr>
                    <td style="height:20px">&nbsp;公司确认：</td>
                </tr>
                <tr>
                    <td style="height:20px; font-size: 26px;">&nbsp;{{ $shop_info['shop_name'] }}</td>
                </tr>
                <tr>
                    <td style="height:20px">&nbsp;</td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>

</html>
