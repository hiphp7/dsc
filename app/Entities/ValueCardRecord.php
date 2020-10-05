<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ValueCardRecord
 */
class ValueCardRecord extends Model
{
    protected $table = 'value_card_record';

    protected $primaryKey = 'rid';

    public $timestamps = false;

    protected $fillable = [
        'vc_id',
        'order_id',
        'use_val',
        'vc_dis',
        'add_val',
        'record_time'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getVcId()
    {
        return $this->vc_id;
    }

    /**
     * @return mixed
     */
    public function getOrderId()
    {
        return $this->order_id;
    }

    /**
     * @return mixed
     */
    public function getUseVal()
    {
        return $this->use_val;
    }

    /**
     * @return mixed
     */
    public function getVcDis()
    {
        return $this->vc_dis;
    }

    /**
     * @return mixed
     */
    public function getAddVal()
    {
        return $this->add_val;
    }

    /**
     * @return mixed
     */
    public function getRecordTime()
    {
        return $this->record_time;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setVcId($value)
    {
        $this->vc_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setOrderId($value)
    {
        $this->order_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setUseVal($value)
    {
        $this->use_val = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setVcDis($value)
    {
        $this->vc_dis = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAddVal($value)
    {
        $this->add_val = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRecordTime($value)
    {
        $this->record_time = $value;
        return $this;
    }
}
