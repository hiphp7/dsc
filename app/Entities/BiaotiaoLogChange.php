<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class BiaotiaoLogChange
 */
class BiaotiaoLogChange extends Model
{
    protected $table = 'biaotiao_log_change';

    public $timestamps = false;

    protected $fillable = [
        'log_id',
        'original_price',
        'chang_price',
        'add_time'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getLogId()
    {
        return $this->log_id;
    }

    /**
     * @return mixed
     */
    public function getOriginalPrice()
    {
        return $this->original_price;
    }

    /**
     * @return mixed
     */
    public function getChangPrice()
    {
        return $this->chang_price;
    }

    /**
     * @return mixed
     */
    public function getAddTime()
    {
        return $this->add_time;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setLogId($value)
    {
        $this->log_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setOriginalPrice($value)
    {
        $this->original_price = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setChangPrice($value)
    {
        $this->chang_price = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAddTime($value)
    {
        $this->add_time = $value;
        return $this;
    }
}
