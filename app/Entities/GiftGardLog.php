<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class GiftGardLog
 */
class GiftGardLog extends Model
{
    protected $table = 'gift_gard_log';

    public $timestamps = false;

    protected $fillable = [
        'admin_id',
        'gift_gard_id',
        'delivery_status',
        'addtime',
        'handle_type'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getAdminId()
    {
        return $this->admin_id;
    }

    /**
     * @return mixed
     */
    public function getGiftGardId()
    {
        return $this->gift_gard_id;
    }

    /**
     * @return mixed
     */
    public function getDeliveryStatus()
    {
        return $this->delivery_status;
    }

    /**
     * @return mixed
     */
    public function getAddtime()
    {
        return $this->addtime;
    }

    /**
     * @return mixed
     */
    public function getHandleType()
    {
        return $this->handle_type;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAdminId($value)
    {
        $this->admin_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setGiftGardId($value)
    {
        $this->gift_gard_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setDeliveryStatus($value)
    {
        $this->delivery_status = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAddtime($value)
    {
        $this->addtime = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setHandleType($value)
    {
        $this->handle_type = $value;
        return $this;
    }
}
