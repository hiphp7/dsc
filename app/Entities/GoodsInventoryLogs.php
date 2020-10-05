<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class GoodsInventoryLogs
 */
class GoodsInventoryLogs extends Model
{
    protected $table = 'goods_inventory_logs';

    public $timestamps = false;

    protected $fillable = [
        'goods_id',
        'order_id',
        'use_storage',
        'admin_id',
        'number',
        'model_inventory',
        'model_attr',
        'product_id',
        'warehouse_id',
        'area_id',
        'suppliers_id',
        'add_time',
        'batch_number',
        'remark',
        'city_id'
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
    public function getOrderId()
    {
        return $this->order_id;
    }

    /**
     * @return mixed
     */
    public function getUseStorage()
    {
        return $this->use_storage;
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
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * @return mixed
     */
    public function getModelInventory()
    {
        return $this->model_inventory;
    }

    /**
     * @return mixed
     */
    public function getModelAttr()
    {
        return $this->model_attr;
    }

    /**
     * @return mixed
     */
    public function getProductId()
    {
        return $this->product_id;
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
    public function getAreaId()
    {
        return $this->area_id;
    }

    /**
     * @return mixed
     */
    public function getSuppliersId()
    {
        return $this->suppliers_id;
    }

    /**
     * @return mixed
     */
    public function getAddTime()
    {
        return $this->add_time;
    }

    /**
     * @return mixed
     */
    public function getBatchNumber()
    {
        return $this->batch_number;
    }

    /**
     * @return mixed
     */
    public function getRemark()
    {
        return $this->remark;
    }

    /**
     * @return mixed
     */
    public function getCityId()
    {
        return $this->city_id;
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
    public function setOrderId($value)
    {
        $this->order_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setUseStorage($value)
    {
        $this->use_storage = $value;
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
    public function setNumber($value)
    {
        $this->number = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setModelInventory($value)
    {
        $this->model_inventory = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setModelAttr($value)
    {
        $this->model_attr = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setProductId($value)
    {
        $this->product_id = $value;
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
    public function setAreaId($value)
    {
        $this->area_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSuppliersId($value)
    {
        $this->suppliers_id = $value;
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

    /**
     * @param $value
     * @return $this
     */
    public function setBatchNumber($value)
    {
        $this->batch_number = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRemark($value)
    {
        $this->remark = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCityId($value)
    {
        $this->city_id = $value;
        return $this;
    }
}
