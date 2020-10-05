<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class OrderAction
 */
class OrderAction extends Model
{
    protected $table = 'order_action';

    protected $primaryKey = 'action_id';

    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'action_user',
        'order_status',
        'shipping_status',
        'pay_status',
        'action_place',
        'action_note',
        'log_time'
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
    public function getActionUser()
    {
        return $this->action_user;
    }

    /**
     * @return mixed
     */
    public function getOrderStatus()
    {
        return $this->order_status;
    }

    /**
     * @return mixed
     */
    public function getShippingStatus()
    {
        return $this->shipping_status;
    }

    /**
     * @return mixed
     */
    public function getPayStatus()
    {
        return $this->pay_status;
    }

    /**
     * @return mixed
     */
    public function getActionPlace()
    {
        return $this->action_place;
    }

    /**
     * @return mixed
     */
    public function getActionNote()
    {
        return $this->action_note;
    }

    /**
     * @return mixed
     */
    public function getLogTime()
    {
        return $this->log_time;
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
    public function setActionUser($value)
    {
        $this->action_user = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setOrderStatus($value)
    {
        $this->order_status = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setShippingStatus($value)
    {
        $this->shipping_status = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPayStatus($value)
    {
        $this->pay_status = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setActionPlace($value)
    {
        $this->action_place = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setActionNote($value)
    {
        $this->action_note = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setLogTime($value)
    {
        $this->log_time = $value;
        return $this;
    }
}
