<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class PackageGoods
 */
class PackageGoods extends Model
{
    protected $table = 'package_goods';

    public $timestamps = false;

    protected $fillable = [
        'package_id',
        'goods_id',
        'product_id',
        'goods_number',
        'admin_id'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getPackageId()
    {
        return $this->package_id;
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
    public function getProductId()
    {
        return $this->product_id;
    }

    /**
     * @return mixed
     */
    public function getGoodsNumber()
    {
        return $this->goods_number;
    }

    /**
     * @return mixed
     */
    public function getAdminId()
    {
        return $this->admin_id;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPackageId($value)
    {
        $this->package_id = $value;
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
    public function setProductId($value)
    {
        $this->product_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setGoodsNumber($value)
    {
        $this->goods_number = $value;
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
}
