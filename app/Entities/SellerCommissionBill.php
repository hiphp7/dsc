<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class SellerCommissionBill
 */
class SellerCommissionBill extends Model
{
    protected $table = 'seller_commission_bill';

    public $timestamps = false;

    protected $fillable = [
        'seller_id',
        'bill_sn',
        'order_amount',
        'shipping_amount',
        'return_amount',
        'return_shippingfee',
        'drp_money',
        'proportion',
        'commission_model',
        'gain_commission',
        'should_amount',
        'actual_amount',
        'negative_amount',
        'chargeoff_time',
        'settleaccounts_time',
        'start_time',
        'end_time',
        'chargeoff_status',
        'bill_cycle',
        'bill_apply',
        'apply_note',
        'apply_time',
        'operator',
        'check_status',
        'reject_note',
        'check_time',
        'frozen_money',
        'frozen_data',
        'frozen_time'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getSellerId()
    {
        return $this->seller_id;
    }

    /**
     * @return mixed
     */
    public function getBillSn()
    {
        return $this->bill_sn;
    }

    /**
     * @return mixed
     */
    public function getOrderAmount()
    {
        return $this->order_amount;
    }

    /**
     * @return mixed
     */
    public function getShippingAmount()
    {
        return $this->shipping_amount;
    }

    /**
     * @return mixed
     */
    public function getReturnAmount()
    {
        return $this->return_amount;
    }

    /**
     * @return mixed
     */
    public function getReturnShippingfee()
    {
        return $this->return_shippingfee;
    }

    /**
     * @return mixed
     */
    public function getDrpMoney()
    {
        return $this->drp_money;
    }

    /**
     * @return mixed
     */
    public function getProportion()
    {
        return $this->proportion;
    }

    /**
     * @return mixed
     */
    public function getCommissionModel()
    {
        return $this->commission_model;
    }

    /**
     * @return mixed
     */
    public function getGainCommission()
    {
        return $this->gain_commission;
    }

    /**
     * @return mixed
     */
    public function getShouldAmount()
    {
        return $this->should_amount;
    }

    /**
     * @return mixed
     */
    public function getActualAmount()
    {
        return $this->actual_amount;
    }

    /**
     * @return mixed
     */
    public function getNegativeAmount()
    {
        return $this->negative_amount;
    }

    /**
     * @return mixed
     */
    public function getChargeoffTime()
    {
        return $this->chargeoff_time;
    }

    /**
     * @return mixed
     */
    public function getSettleaccountsTime()
    {
        return $this->settleaccounts_time;
    }

    /**
     * @return mixed
     */
    public function getStartTime()
    {
        return $this->start_time;
    }

    /**
     * @return mixed
     */
    public function getEndTime()
    {
        return $this->end_time;
    }

    /**
     * @return mixed
     */
    public function getChargeoffStatus()
    {
        return $this->chargeoff_status;
    }

    /**
     * @return mixed
     */
    public function getBillCycle()
    {
        return $this->bill_cycle;
    }

    /**
     * @return mixed
     */
    public function getBillApply()
    {
        return $this->bill_apply;
    }

    /**
     * @return mixed
     */
    public function getApplyNote()
    {
        return $this->apply_note;
    }

    /**
     * @return mixed
     */
    public function getApplyTime()
    {
        return $this->apply_time;
    }

    /**
     * @return mixed
     */
    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * @return mixed
     */
    public function getCheckStatus()
    {
        return $this->check_status;
    }

    /**
     * @return mixed
     */
    public function getRejectNote()
    {
        return $this->reject_note;
    }

    /**
     * @return mixed
     */
    public function getCheckTime()
    {
        return $this->check_time;
    }

    /**
     * @return mixed
     */
    public function getFrozenMoney()
    {
        return $this->frozen_money;
    }

    /**
     * @return mixed
     */
    public function getFrozenData()
    {
        return $this->frozen_data;
    }

    /**
     * @return mixed
     */
    public function getFrozenTime()
    {
        return $this->frozen_time;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSellerId($value)
    {
        $this->seller_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setBillSn($value)
    {
        $this->bill_sn = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setOrderAmount($value)
    {
        $this->order_amount = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setShippingAmount($value)
    {
        $this->shipping_amount = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setReturnAmount($value)
    {
        $this->return_amount = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setReturnShippingfee($value)
    {
        $this->return_shippingfee = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setDrpMoney($value)
    {
        $this->drp_money = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setProportion($value)
    {
        $this->proportion = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCommissionModel($value)
    {
        $this->commission_model = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setGainCommission($value)
    {
        $this->gain_commission = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setShouldAmount($value)
    {
        $this->should_amount = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setActualAmount($value)
    {
        $this->actual_amount = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setNegativeAmount($value)
    {
        $this->negative_amount = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setChargeoffTime($value)
    {
        $this->chargeoff_time = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSettleaccountsTime($value)
    {
        $this->settleaccounts_time = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setStartTime($value)
    {
        $this->start_time = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setEndTime($value)
    {
        $this->end_time = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setChargeoffStatus($value)
    {
        $this->chargeoff_status = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setBillCycle($value)
    {
        $this->bill_cycle = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setBillApply($value)
    {
        $this->bill_apply = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setApplyNote($value)
    {
        $this->apply_note = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setApplyTime($value)
    {
        $this->apply_time = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setOperator($value)
    {
        $this->operator = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCheckStatus($value)
    {
        $this->check_status = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRejectNote($value)
    {
        $this->reject_note = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCheckTime($value)
    {
        $this->check_time = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setFrozenMoney($value)
    {
        $this->frozen_money = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setFrozenData($value)
    {
        $this->frozen_data = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setFrozenTime($value)
    {
        $this->frozen_time = $value;
        return $this;
    }
}
