<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class GroupbuyGoods
 */
class GroupbuyGoods extends Model
{
    protected $table = 'groupbuy_goods';

    protected $primaryKey = 'goods_id';

    public $timestamps = false;

    protected $fillable = [
        'ru_id',
        'cat_id',
        'goods_name',
        'goods_sn',
        'goods_bar_code',
        'goods_price',
        'goods_number',
        'warn_number',
        'commission',
        'goods_img',
        'goods_thumb',
        'original_img',
        'goods_desc',
        'start_time',
        'end_time',
        'delivery_time',
        'add_time',
        'group_limit',
        'groupbuy_desc',
        'review_status',
        'review_reason',
        'is_delete',
        'click_count',
        'sale_num',
        'link_goods_id',
        'link_product_id'
    ];

    protected $guarded = [];

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
    public function getCatId()
    {
        return $this->cat_id;
    }

    /**
     * @return mixed
     */
    public function getGoodsName()
    {
        return $this->goods_name;
    }

    /**
     * @return mixed
     */
    public function getGoodsSn()
    {
        return $this->goods_sn;
    }

    /**
     * @return mixed
     */
    public function getGoodsBarCode()
    {
        return $this->goods_bar_code;
    }

    /**
     * @return mixed
     */
    public function getGoodsPrice()
    {
        return $this->goods_price;
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
    public function getWarnNumber()
    {
        return $this->warn_number;
    }

    /**
     * @return mixed
     */
    public function getCommission()
    {
        return $this->commission;
    }

    /**
     * @return mixed
     */
    public function getGoodsImg()
    {
        return $this->goods_img;
    }

    /**
     * @return mixed
     */
    public function getGoodsThumb()
    {
        return $this->goods_thumb;
    }

    /**
     * @return mixed
     */
    public function getOriginalImg()
    {
        return $this->original_img;
    }

    /**
     * @return mixed
     */
    public function getGoodsDesc()
    {
        return $this->goods_desc;
    }

    /**
     * @return mixed
     */
    public function getStartTime()
    {
        return $this->start_time;
    }

    /**
     * @return mixed
     */
    public function getEndTime()
    {
        return $this->end_time;
    }

    /**
     * @return mixed
     */
    public function getDeliveryTime()
    {
        return $this->delivery_time;
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
    public function getGroupLimit()
    {
        return $this->group_limit;
    }

    /**
     * @return mixed
     */
    public function getGroupbuyDesc()
    {
        return $this->groupbuy_desc;
    }

    /**
     * @return mixed
     */
    public function getReviewStatus()
    {
        return $this->review_status;
    }

    /**
     * @return mixed
     */
    public function getReviewReason()
    {
        return $this->review_reason;
    }

    /**
     * @return mixed
     */
    public function getIsDelete()
    {
        return $this->is_delete;
    }

    /**
     * @return mixed
     */
    public function getClickCount()
    {
        return $this->click_count;
    }

    /**
     * @return mixed
     */
    public function getSaleNum()
    {
        return $this->sale_num;
    }

    /**
     * @return mixed
     */
    public function getLinkGoodsId()
    {
        return $this->link_goods_id;
    }

    /**
     * @return mixed
     */
    public function getLinkProductId()
    {
        return $this->link_product_id;
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
    public function setCatId($value)
    {
        $this->cat_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setGoodsName($value)
    {
        $this->goods_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setGoodsSn($value)
    {
        $this->goods_sn = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setGoodsBarCode($value)
    {
        $this->goods_bar_code = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setGoodsPrice($value)
    {
        $this->goods_price = $value;
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
    public function setWarnNumber($value)
    {
        $this->warn_number = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCommission($value)
    {
        $this->commission = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setGoodsImg($value)
    {
        $this->goods_img = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setGoodsThumb($value)
    {
        $this->goods_thumb = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setOriginalImg($value)
    {
        $this->original_img = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setGoodsDesc($value)
    {
        $this->goods_desc = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setStartTime($value)
    {
        $this->start_time = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setEndTime($value)
    {
        $this->end_time = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setDeliveryTime($value)
    {
        $this->delivery_time = $value;
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
    public function setGroupLimit($value)
    {
        $this->group_limit = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setGroupbuyDesc($value)
    {
        $this->groupbuy_desc = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setReviewStatus($value)
    {
        $this->review_status = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setReviewReason($value)
    {
        $this->review_reason = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsDelete($value)
    {
        $this->is_delete = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setClickCount($value)
    {
        $this->click_count = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSaleNum($value)
    {
        $this->sale_num = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setLinkGoodsId($value)
    {
        $this->link_goods_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setLinkProductId($value)
    {
        $this->link_product_id = $value;
        return $this;
    }


}
