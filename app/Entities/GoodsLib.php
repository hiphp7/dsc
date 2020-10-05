<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class GoodsLib
 */
class GoodsLib extends Model
{
    protected $table = 'goods_lib';

    protected $primaryKey = 'goods_id';

    public $timestamps = false;

    protected $fillable = [
        'cat_id',
        'lib_cat_id',
        'goods_sn',
        'bar_code',
        'goods_name',
        'goods_name_style',
        'brand_id',
        'goods_weight',
        'market_price',
        'cost_price',
        'shop_price',
        'keywords',
        'goods_brief',
        'goods_desc',
        'desc_mobile',
        'goods_thumb',
        'goods_img',
        'original_img',
        'is_real',
        'extension_code',
        'add_time',
        'sort_order',
        'last_update',
        'goods_type',
        'is_check',
        'largest_amount',
        'pinyin_keyword',
        'lib_goods_id',
        'is_on_sale',
        'from_seller'
    ];

    protected $guarded = [];


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
    public function getLibCatId()
    {
        return $this->lib_cat_id;
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
    public function getBarCode()
    {
        return $this->bar_code;
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
    public function getGoodsNameStyle()
    {
        return $this->goods_name_style;
    }

    /**
     * @return mixed
     */
    public function getBrandId()
    {
        return $this->brand_id;
    }

    /**
     * @return mixed
     */
    public function getGoodsWeight()
    {
        return $this->goods_weight;
    }

    /**
     * @return mixed
     */
    public function getMarketPrice()
    {
        return $this->market_price;
    }

    /**
     * @return mixed
     */
    public function getCostPrice()
    {
        return $this->cost_price;
    }

    /**
     * @return mixed
     */
    public function getShopPrice()
    {
        return $this->shop_price;
    }

    /**
     * @return mixed
     */
    public function getKeywords()
    {
        return $this->keywords;
    }

    /**
     * @return mixed
     */
    public function getGoodsBrief()
    {
        return $this->goods_brief;
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
    public function getDescMobile()
    {
        return $this->desc_mobile;
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
    public function getGoodsImg()
    {
        return $this->goods_img;
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
    public function getIsReal()
    {
        return $this->is_real;
    }

    /**
     * @return mixed
     */
    public function getExtensionCode()
    {
        return $this->extension_code;
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
    public function getSortOrder()
    {
        return $this->sort_order;
    }

    /**
     * @return mixed
     */
    public function getLastUpdate()
    {
        return $this->last_update;
    }

    /**
     * @return mixed
     */
    public function getGoodsType()
    {
        return $this->goods_type;
    }

    /**
     * @return mixed
     */
    public function getIsCheck()
    {
        return $this->is_check;
    }

    /**
     * @return mixed
     */
    public function getLargestAmount()
    {
        return $this->largest_amount;
    }

    /**
     * @return mixed
     */
    public function getPinyinKeyword()
    {
        return $this->pinyin_keyword;
    }

    /**
     * @return mixed
     */
    public function getLibGoodsId()
    {
        return $this->lib_goods_id;
    }

    /**
     * @return mixed
     */
    public function getIsOnSale()
    {
        return $this->is_on_sale;
    }

    /**
     * @return mixed
     */
    public function getFromSeller()
    {
        return $this->from_seller;
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
    public function setLibCatId($value)
    {
        $this->lib_cat_id = $value;
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
    public function setBarCode($value)
    {
        $this->bar_code = $value;
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
    public function setGoodsNameStyle($value)
    {
        $this->goods_name_style = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setBrandId($value)
    {
        $this->brand_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setGoodsWeight($value)
    {
        $this->goods_weight = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setMarketPrice($value)
    {
        $this->market_price = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCostPrice($value)
    {
        $this->cost_price = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setShopPrice($value)
    {
        $this->shop_price = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setKeywords($value)
    {
        $this->keywords = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setGoodsBrief($value)
    {
        $this->goods_brief = $value;
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
    public function setDescMobile($value)
    {
        $this->desc_mobile = $value;
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
    public function setGoodsImg($value)
    {
        $this->goods_img = $value;
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
    public function setIsReal($value)
    {
        $this->is_real = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setExtensionCode($value)
    {
        $this->extension_code = $value;
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
    public function setSortOrder($value)
    {
        $this->sort_order = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setLastUpdate($value)
    {
        $this->last_update = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setGoodsType($value)
    {
        $this->goods_type = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsCheck($value)
    {
        $this->is_check = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setLargestAmount($value)
    {
        $this->largest_amount = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPinyinKeyword($value)
    {
        $this->pinyin_keyword = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setLibGoodsId($value)
    {
        $this->lib_goods_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsOnSale($value)
    {
        $this->is_on_sale = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setFromSeller($value)
    {
        $this->from_seller = $value;
        return $this;
    }
}
