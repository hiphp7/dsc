<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class OrderCloud
 */
class OrderCloud extends Model
{
    protected $table = 'order_cloud';

    public $timestamps = false;

    protected $fillable = [
        'apiordersn',
        'goods_id',
        'user_id',
        'totalprice',
        'rec_id',
        'parentordersn',
        'cloud_orderid',
        'cloud_detailed_id'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getApiordersn()
    {
        return $this->apiordersn;
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
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * @return mixed
     */
    public function getTotalprice()
    {
        return $this->totalprice;
    }

    /**
     * @return mixed
     */
    public function getRecId()
    {
        return $this->rec_id;
    }

    /**
     * @return mixed
     */
    public function getParentordersn()
    {
        return $this->parentordersn;
    }

    /**
     * @return mixed
     */
    public function getCloudOrderid()
    {
        return $this->cloud_orderid;
    }

    /**
     * @return mixed
     */
    public function getCloudDetailedId()
    {
        return $this->cloud_detailed_id;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setApiordersn($value)
    {
        $this->apiordersn = $value;
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
    public function setUserId($value)
    {
        $this->user_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTotalprice($value)
    {
        $this->totalprice = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRecId($value)
    {
        $this->rec_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setParentordersn($value)
    {
        $this->parentordersn = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCloudOrderid($value)
    {
        $this->cloud_orderid = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCloudDetailedId($value)
    {
        $this->cloud_detailed_id = $value;
        return $this;
    }
}
