<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class SellerShopwindow
 */
class SellerShopwindow extends Model
{
    protected $table = 'seller_shopwindow';

    public $timestamps = false;

    protected $fillable = [
        'win_type',
        'win_goods_type',
        'win_order',
        'win_goods',
        'win_name',
        'win_color',
        'win_img',
        'win_img_link',
        'ru_id',
        'is_show',
        'win_custom',
        'seller_theme'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getWinType()
    {
        return $this->win_type;
    }

    /**
     * @return mixed
     */
    public function getWinGoodsType()
    {
        return $this->win_goods_type;
    }

    /**
     * @return mixed
     */
    public function getWinOrder()
    {
        return $this->win_order;
    }

    /**
     * @return mixed
     */
    public function getWinGoods()
    {
        return $this->win_goods;
    }

    /**
     * @return mixed
     */
    public function getWinName()
    {
        return $this->win_name;
    }

    /**
     * @return mixed
     */
    public function getWinColor()
    {
        return $this->win_color;
    }

    /**
     * @return mixed
     */
    public function getWinImg()
    {
        return $this->win_img;
    }

    /**
     * @return mixed
     */
    public function getWinImgLink()
    {
        return $this->win_img_link;
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
    public function getIsShow()
    {
        return $this->is_show;
    }

    /**
     * @return mixed
     */
    public function getWinCustom()
    {
        return $this->win_custom;
    }

    /**
     * @return mixed
     */
    public function getSellerTheme()
    {
        return $this->seller_theme;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setWinType($value)
    {
        $this->win_type = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setWinGoodsType($value)
    {
        $this->win_goods_type = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setWinOrder($value)
    {
        $this->win_order = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setWinGoods($value)
    {
        $this->win_goods = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setWinName($value)
    {
        $this->win_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setWinColor($value)
    {
        $this->win_color = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setWinImg($value)
    {
        $this->win_img = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setWinImgLink($value)
    {
        $this->win_img_link = $value;
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
    public function setIsShow($value)
    {
        $this->is_show = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setWinCustom($value)
    {
        $this->win_custom = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSellerTheme($value)
    {
        $this->seller_theme = $value;
        return $this;
    }
}
