<?php

namespace App\Plugins\Shipping\StoExpress;

/**
 * 申通快递费用计算方式:
 * ====================================================================================
 * - 江浙沪地区统一资费： 1公斤以内15元， 每增加1公斤加5-6元, 云南为8元
 * - 其他地区统一资费:    1公斤以内18元， 每增加1公斤加5-6元, 云南为8元
 * - 对于体大质轻的包裹，我们将按照航空运输协会的规定，根据体积和实际重量中较重的一种收费，需将包的长、宽、高、相乘，再除以6000
 * - (具体资费请上此网站查询:http://www.car365.cn/fee.asp 客服电话:021-52238886)
 * -------------------------------------------------------------------------------------
 *
 */
class StoExpress
{
    /*------------------------------------------------------ */
    //-- PUBLIC ATTRIBUTEs
    /*------------------------------------------------------ */

    /**
     * 配置信息参数
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
    public function __construct($cfg = [])
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
     * @param float $goods_number 商品数量
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
     * 查询快递状态
     *
     * @access  public
     * @return  string  查询窗口的链接地址
     */
    public function query($invoice_sn)
    {
        $form_str = '<a href="http://www.kuaidi100.com/query?type=shentong&postid=' . $invoice_sn . '&id=1&valicode=&temp=' . time() . '" target="_blank">' . $invoice_sn . '</a>';
        return $form_str;
    }

    /**
     * 快递100 CODE
     */
    public function get_code_name()
    {
        return 'shentong';
    }
}
