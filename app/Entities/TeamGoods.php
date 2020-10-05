<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class TeamGoods
 */
class TeamGoods extends Model
{
    protected $table = 'team_goods';

    public $timestamps = false;

    protected $fillable = [
        'goods_id',
        'team_price',
        'team_num',
        'validity_time',
        'limit_num',
        'astrict_num',
        'tc_id',
        'is_audit',
        'is_team',
        'sort_order',
        'team_desc',
        'isnot_aduit_reason'
    ];

    protected $guarded = [];


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
    public function getTeamPrice()
    {
        return $this->team_price;
    }

    /**
     * @return mixed
     */
    public function getTeamNum()
    {
        return $this->team_num;
    }

    /**
     * @return mixed
     */
    public function getValidityTime()
    {
        return $this->validity_time;
    }

    /**
     * @return mixed
     */
    public function getLimitNum()
    {
        return $this->limit_num;
    }

    /**
     * @return mixed
     */
    public function getAstrictNum()
    {
        return $this->astrict_num;
    }

    /**
     * @return mixed
     */
    public function getTcId()
    {
        return $this->tc_id;
    }

    /**
     * @return mixed
     */
    public function getIsAudit()
    {
        return $this->is_audit;
    }

    /**
     * @return mixed
     */
    public function getIsTeam()
    {
        return $this->is_team;
    }

    /**
     * @return mixed
     */
    public function getSortOrder()
    {
        return $this->sort_order;
    }

    /**
     * @return mixed
     */
    public function getTeamDesc()
    {
        return $this->team_desc;
    }

    /**
     * @return mixed
     */
    public function getIsnotAduitReason()
    {
        return $this->isnot_aduit_reason;
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
    public function setTeamPrice($value)
    {
        $this->team_price = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTeamNum($value)
    {
        $this->team_num = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setValidityTime($value)
    {
        $this->validity_time = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setLimitNum($value)
    {
        $this->limit_num = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAstrictNum($value)
    {
        $this->astrict_num = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTcId($value)
    {
        $this->tc_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsAudit($value)
    {
        $this->is_audit = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsTeam($value)
    {
        $this->is_team = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSortOrder($value)
    {
        $this->sort_order = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTeamDesc($value)
    {
        $this->team_desc = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsnotAduitReason($value)
    {
        $this->isnot_aduit_reason = $value;
        return $this;
    }
}
