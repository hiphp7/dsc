<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class groupbuy_activity
 */
class GroupbuyActivity extends Model
{
    protected $table = 'groupbuy_activity';

    protected $primaryKey = 'act_id';

    public $timestamps = false;

    protected $fillable = [
        'leader_id',
        'goods_id',
        'status',
        'is_best',
        'add_time'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getLeaderId()
    {
        return $this->leader_id;
    }

    /**
     * @return mixed
     */
    public function getGoodsId()
    {
        return $this->goods_id;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return mixed
     */
    public function getIsBest()
    {
        return $this->is_best;
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
    public function setLeaderId($value)
    {
        $this->leader_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setGoodsId($value)
    {
        $this->goods_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setStatus($value)
    {
        $this->status = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsBest($value)
    {
        $this->is_best = $value;
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
