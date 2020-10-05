<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class WarehouseFreightTpl
 */
class WarehouseFreightTpl extends Model
{
    protected $table = 'warehouse_freight_tpl';

    public $timestamps = false;

    protected $fillable = [
        'tpl_name',
        'user_id',
        'warehouse_id',
        'shipping_id',
        'region_id',
        'configure'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getTplName()
    {
        return $this->tpl_name;
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
    public function getWarehouseId()
    {
        return $this->warehouse_id;
    }

    /**
     * @return mixed
     */
    public function getShippingId()
    {
        return $this->shipping_id;
    }

    /**
     * @return mixed
     */
    public function getRegionId()
    {
        return $this->region_id;
    }

    /**
     * @return mixed
     */
    public function getConfigure()
    {
        return $this->configure;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTplName($value)
    {
        $this->tpl_name = $value;
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
    public function setWarehouseId($value)
    {
        $this->warehouse_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setShippingId($value)
    {
        $this->shipping_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRegionId($value)
    {
        $this->region_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setConfigure($value)
    {
        $this->configure = $value;
        return $this;
    }
}
