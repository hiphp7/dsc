<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class AuctionLog
 */
class AuctionLog extends Model
{
    protected $table = 'auction_log';

    protected $primaryKey = 'log_id';

    public $timestamps = false;

    protected $fillable = [
        'act_id',
        'bid_user',
        'bid_price',
        'bid_time'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getActId()
    {
        return $this->act_id;
    }

    /**
     * @return mixed
     */
    public function getBidUser()
    {
        return $this->bid_user;
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
    public function setActId($value)
    {
        $this->act_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setBidUser($value)
    {
        $this->bid_user = $value;
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
