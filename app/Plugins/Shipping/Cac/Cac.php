<?php

namespace App\Plugins\Shipping\Cac;

/**
 *  上门取货插件
 */
class Cac
{
    /*------------------------------------------------------ */
    //-- PUBLIC ATTRIBUTEs
    /*------------------------------------------------------ */

    /**
     * 配置信息
     */
    public $configure;

    /**
     * 计算订单的配送费用的函数
     *
     * @param float $goods_weight 商品重量
     * @param float $goods_amount 商品金额
     * @return  decimal
     */
    public function calculate($goods_weight, $goods_amount)
    {
        return 0;
    }

    /**
     * 查询发货状态
     * 该配送方式不支持查询发货状态
     *
     * @access  public
     * @param string $invoice_sn 发货单号
     * @return  string
     */
    public function query($invoice_sn)
    {
        return $invoice_sn;
    }

    /**
     * 快递100 CODE
     */
    public function get_code_name()
    {
        return '';
    }
}
