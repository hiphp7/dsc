<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class MerchantsServer
 */
class MerchantsServer extends Model
{
    protected $table = 'merchants_server';

    protected $primaryKey = 'server_id';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'suppliers_desc',
        'suppliers_percent',
        'commission_model',
        'bill_freeze_day',
        'cycle',
        'day_number',
        'bill_time'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * @return mixed
     */
    public function getSuppliersDesc()
    {
        return $this->suppliers_desc;
    }

    /**
     * @return mixed
     */
    public function getSuppliersPercent()
    {
        return $this->suppliers_percent;
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
    public function getBillFreezeDay()
    {
        return $this->bill_freeze_day;
    }

    /**
     * @return mixed
     */
    public function getCycle()
    {
        return $this->cycle;
    }

    /**
     * @return mixed
     */
    public function getDayNumber()
    {
        return $this->day_number;
    }

    /**
     * @return mixed
     */
    public function getBillTime()
    {
        return $this->bill_time;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setUserId($value)
    {
        $this->user_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSuppliersDesc($value)
    {
        $this->suppliers_desc = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSuppliersPercent($value)
    {
        $this->suppliers_percent = $value;
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
    public function setBillFreezeDay($value)
    {
        $this->bill_freeze_day = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCycle($value)
    {
        $this->cycle = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setDayNumber($value)
    {
        $this->day_number = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setBillTime($value)
    {
        $this->bill_time = $value;
        return $this;
    }
}
