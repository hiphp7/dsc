<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class OrderDelayed
 */
class OrderDelayed extends Model
{
    protected $table = 'order_delayed';

    protected $primaryKey = 'delayed_id';

    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'apply_day',
        'apply_time',
        'review_status',
        'review_time',
        'review_admin'
    ];

    protected $guarded = [];


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
    public function getApplyDay()
    {
        return $this->apply_day;
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
    public function getReviewStatus()
    {
        return $this->review_status;
    }

    /**
     * @return mixed
     */
    public function getReviewTime()
    {
        return $this->review_time;
    }

    /**
     * @return mixed
     */
    public function getReviewAdmin()
    {
        return $this->review_admin;
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
    public function setApplyDay($value)
    {
        $this->apply_day = $value;
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
    public function setReviewStatus($value)
    {
        $this->review_status = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setReviewTime($value)
    {
        $this->review_time = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setReviewAdmin($value)
    {
        $this->review_admin = $value;
        return $this;
    }
}
