<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Wholesale
 */
class Wholesale extends Model
{
    protected $table = 'wholesale';

    protected $primaryKey = 'goods_id';

    public $timestamps = false;

    protected $fillable = [
        'goods_sn',
        'brand_id',
        'promote_price',
        'goods_weight',
        'retail_price',
        'warn_number',
        'goods_brief',
        'goods_desc',
        'goods_thumb',
        'goods_img',
        'original_img',
        'add_time',
        'sort_order',
        'is_delete',
        'is_best',
        'is_new',
        'is_hot',
        'last_update',
        'is_xiangou',
        'xiangou_start_date',
        'xiangou_end_date',
        'xiangou_num',
        'sales_volume',
        'goods_product_tag',
        'goods_unit',
        'goods_cause',
        'bar_code',
        'goods_service',
        'is_shipping',
        'keywords',
        'pinyin_keyword',
        'desc_mobile',
        'suppliers_id',
        'cat_id',
        'goods_name',
        'rank_ids',
        'goods_price',
        'enabled',
        'review_status',
        'review_content',
        'price_model',
        'goods_type',
        'goods_number',
        'moq',
        'is_recommend',
        'is_promote',
        'start_time',
        'end_time',
        'shipping_fee',
        'freight',
        'tid',
        'standard_goods',
        'export_type',
        'export_type_ext'
    ];

    protected $guarded = [];


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
    public function getBrandId()
    {
        return $this->brand_id;
    }

    /**
     * @return mixed
     */
    public function getPromotePrice()
    {
        return $this->promote_price;
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
    public function getRetailPrice()
    {
        return $this->retail_price;
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
    public function getIsDelete()
    {
        return $this->is_delete;
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
    public function getIsNew()
    {
        return $this->is_new;
    }

    /**
     * @return mixed
     */
    public function getIsHot()
    {
        return $this->is_hot;
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
    public function getIsXiangou()
    {
        return $this->is_xiangou;
    }

    /**
     * @return mixed
     */
    public function getXiangouStartDate()
    {
        return $this->xiangou_start_date;
    }

    /**
     * @return mixed
     */
    public function getXiangouEndDate()
    {
        return $this->xiangou_end_date;
    }

    /**
     * @return mixed
     */
    public function getXiangouNum()
    {
        return $this->xiangou_num;
    }

    /**
     * @return mixed
     */
    public function getSalesVolume()
    {
        return $this->sales_volume;
    }

    /**
     * @return mixed
     */
    public function getGoodsProductTag()
    {
        return $this->goods_product_tag;
    }

    /**
     * @return mixed
     */
    public function getGoodsUnit()
    {
        return $this->goods_unit;
    }

    /**
     * @return mixed
     */
    public function getGoodsCause()
    {
        return $this->goods_cause;
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
    public function getGoodsService()
    {
        return $this->goods_service;
    }

    /**
     * @return mixed
     */
    public function getIsShipping()
    {
        return $this->is_shipping;
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
    public function getPinyinKeyword()
    {
        return $this->pinyin_keyword;
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
    public function getSuppliersId()
    {
        return $this->suppliers_id;
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
    public function getRankIds()
    {
        return $this->rank_ids;
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
    public function getEnabled()
    {
        return $this->enabled;
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
    public function getReviewContent()
    {
        return $this->review_content;
    }

    /**
     * @return mixed
     */
    public function getPriceModel()
    {
        return $this->price_model;
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
    public function getGoodsNumber()
    {
        return $this->goods_number;
    }

    /**
     * @return mixed
     */
    public function getMoq()
    {
        return $this->moq;
    }

    /**
     * @return mixed
     */
    public function getIsRecommend()
    {
        return $this->is_recommend;
    }

    /**
     * @return mixed
     */
    public function getIsPromote()
    {
        return $this->is_promote;
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
    public function getShippingFee()
    {
        return $this->shipping_fee;
    }

    /**
     * @return mixed
     */
    public function getFreight()
    {
        return $this->freight;
    }

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
    public function getStandardGoods()
    {
        return $this->standard_goods;
    }

    /**
     * @return mixed
     */
    public function getExportType()
    {
        return $this->export_type;
    }

    /**
     * @return mixed
     */
    public function getExportTypeExt()
    {
        return $this->export_type_ext;
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
    public function setBrandId($value)
    {
        $this->brand_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPromotePrice($value)
    {
        $this->promote_price = $value;
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
    public function setRetailPrice($value)
    {
        $this->retail_price = $value;
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
    public function setIsDelete($value)
    {
        $this->is_delete = $value;
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
    public function setIsNew($value)
    {
        $this->is_new = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsHot($value)
    {
        $this->is_hot = $value;
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
    public function setIsXiangou($value)
    {
        $this->is_xiangou = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setXiangouStartDate($value)
    {
        $this->xiangou_start_date = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setXiangouEndDate($value)
    {
        $this->xiangou_end_date = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setXiangouNum($value)
    {
        $this->xiangou_num = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSalesVolume($value)
    {
        $this->sales_volume = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setGoodsProductTag($value)
    {
        $this->goods_product_tag = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setGoodsUnit($value)
    {
        $this->goods_unit = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setGoodsCause($value)
    {
        $this->goods_cause = $value;
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
    public function setGoodsService($value)
    {
        $this->goods_service = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsShipping($value)
    {
        $this->is_shipping = $value;
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
    public function setPinyinKeyword($value)
    {
        $this->pinyin_keyword = $value;
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
    public function setSuppliersId($value)
    {
        $this->suppliers_id = $value;
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
    public function setRankIds($value)
    {
        $this->rank_ids = $value;
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
    public function setEnabled($value)
    {
        $this->enabled = $value;
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
    public function setReviewContent($value)
    {
        $this->review_content = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPriceModel($value)
    {
        $this->price_model = $value;
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
    public function setGoodsNumber($value)
    {
        $this->goods_number = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setMoq($value)
    {
        $this->moq = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsRecommend($value)
    {
        $this->is_recommend = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsPromote($value)
    {
        $this->is_promote = $value;
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
    public function setShippingFee($value)
    {
        $this->shipping_fee = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setFreight($value)
    {
        $this->freight = $value;
        return $this;
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
    public function setStandardGoods($value)
    {
        $this->standard_goods = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setExportType($value)
    {
        $this->export_type = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setExportTypeExt($value)
    {
        $this->export_type_ext = $value;
        return $this;
    }
}
