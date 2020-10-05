<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Stages
 */
class Stages extends Model
{
    protected $table = 'stages';

    protected $primaryKey = 'stages_id';

    public $timestamps = false;

    protected $fillable = [
        'order_sn',
        'stages_total',
        'stages_one_price',
        'yes_num',
        'create_date',
        'repay_date',
        'stages_rate'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getOrderSn()
    {
        return $this->order_sn;
    }

    /**
     * @return mixed
     */
    public function getStagesTotal()
    {
        return $this->stages_total;
    }

    /**
     * @return mixed
     */
    public function getStagesOnePrice()
    {
        return $this->stages_one_price;
    }

    /**
     * @return mixed
     */
    public function getYesNum()
    {
        return $this->yes_num;
    }

    /**
     * @return mixed
     */
    public function getCreateDate()
    {
        return $this->create_date;
    }

    /**
     * @return mixed
     */
    public function getRepayDate()
    {
        return $this->repay_date;
    }

    /**
     * @return mixed
     */
    public function getStagesRate()
    {
        return $this->stages_rate;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setOrderSn($value)
    {
        $this->order_sn = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setStagesTotal($value)
    {
        $this->stages_total = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setStagesOnePrice($value)
    {
        $this->stages_one_price = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setYesNum($value)
    {
        $this->yes_num = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCreateDate($value)
    {
        $this->create_date = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRepayDate($value)
    {
        $this->repay_date = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setStagesRate($value)
    {
        $this->stages_rate = $value;
        return $this;
    }
}
