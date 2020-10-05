<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class SnatchLog
 */
class SnatchLog extends Model
{
    protected $table = 'snatch_log';

    protected $primaryKey = 'log_id';

    public $timestamps = false;

    protected $fillable = [
        'snatch_id',
        'user_id',
        'bid_price',
        'bid_time'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getSnatchId()
    {
        return $this->snatch_id;
    }

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
    public function getBidPrice()
    {
        return $this->bid_price;
    }

    /**
     * @return mixed
     */
    public function getBidTime()
    {
        return $this->bid_time;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSnatchId($value)
    {
        $this->snatch_id = $value;
        return $this;
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
    public function setBidPrice($value)
    {
        $this->bid_price = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setBidTime($value)
    {
        $this->bid_time = $value;
        return $this;
    }
}
