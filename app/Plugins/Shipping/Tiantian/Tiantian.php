<?php

namespace App\Plugins\Shipping\Tiantian;

/**
 * 天天快递
 * Class Tiantian
 * @package App\Plugins\Shipping\Tiantian
 */
class Tiantian
{
    /*------------------------------------------------------ */
    //-- PUBLIC ATTRIBUTEs
    /*------------------------------------------------------ */

    /**
     * 配置信息
     */
    public $configure;

    /*------------------------------------------------------ */
    //-- PUBLIC METHODs
    /*------------------------------------------------------ */

    /**
     * 构造函数
     *
     * @param: $configure[array]    配送方式的参数的数组
     *
     * @return null
     */
    public function __construct($cfg = array())
    {
        if ($cfg) {
            foreach ($cfg as $key => $val) {
                $this->configure[$val['name']] = $val['value'];
            }
        }
    }

    /**
     * 计算订单的配送费用的函数
     *
     * @param float $goods_weight 商品重量
     * @param float $goods_amount 商品金额
     * @param float $goods_number 商品件数
     * @return  decimal
     */
    public function calculate($goods_weight, $goods_amount, $goods_number)
    {
        if ($this->configure['free_money'] > 0 && $goods_amount >= $this->configure['free_money']) {
            return 0;
        } else {
            @$fee = $this->configure['base_fee'];
            $this->configure['fee_compute_mode'] = !empty($this->configure['fee_compute_mode']) ? $this->configure['fee_compute_mode'] : 'by_weight';

            if ($this->configure['fee_compute_mode'] == 'by_number') {
                $fee = $goods_number * $this->configure['item_fee'];
            } else {
                if ($goods_weight > 1) {
                    $fee += (ceil(($goods_weight - 1))) * $this->configure['step_fee'];
                }
            }

            return $fee;
        }
    }


    /**
     * 查询发货状态
     *
     * @access  public
     * @param string $invoice_sn 发货单号
     * @return  string
     */
    public function query($invoice_sn)
    {
        $str = '<form style="margin:0px" methods="post" ' .
            'action="http://www.ttkdex.com/ttkdweb/query.jsp" name="queryForm_' . $invoice_sn . '" target="_blank">' .
            '<input type="hidden" name="wen_no" value="' . $invoice_sn . '" />' .
            '<a href="javascript:document.forms[\'queryForm_' . $invoice_sn . '\'].submit();">' . $invoice_sn . '</a>' .
            '</form>';

        return $str;
    }

    /**
     * 快递100 CODE
     */
    public function get_code_name()
    {
        return 'tiantian';
    }
}
