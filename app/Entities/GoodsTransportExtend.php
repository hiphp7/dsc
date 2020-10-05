<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class GoodsTransportExtend
 */
class GoodsTransportExtend extends Model
{
    protected $table = 'goods_transport_extend';

    public $timestamps = false;

    protected $fillable = [
        'tid',
        'ru_id',
        'admin_id',
        'area_id',
        'top_area_id',
        'sprice'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getTid()
    {
        return $this->tid;
    }

    /**
     * @return mixed
     */
    public function getRuId()
    {
        return $this->ru_id;
    }

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
    public function getAreaId()
    {
        return $this->area_id;
    }

    /**
     * @return mixed
     */
    public function getTopAreaId()
    {
        return $this->top_area_id;
    }

    /**
     * @return mixed
     */
    public function getSprice()
    {
        return $this->sprice;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTid($value)
    {
        $this->tid = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRuId($value)
    {
        $this->ru_id = $value;
        return $this;
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
    public function setAreaId($value)
    {
        $this->area_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTopAreaId($value)
    {
        $this->top_area_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSprice($value)
    {
        $this->sprice = $value;
        return $this;
    }
}
