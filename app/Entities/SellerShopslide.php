<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class SellerShopslide
 */
class SellerShopslide extends Model
{
    protected $table = 'seller_shopslide';

    public $timestamps = false;

    protected $fillable = [
        'ru_id',
        'img_url',
        'img_link',
        'img_desc',
        'img_order',
        'slide_type',
        'is_show',
        'seller_theme',
        'install_img'
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
    public function getImgUrl()
    {
        return $this->img_url;
    }

    /**
     * @return mixed
     */
    public function getImgLink()
    {
        return $this->img_link;
    }

    /**
     * @return mixed
     */
    public function getImgDesc()
    {
        return $this->img_desc;
    }

    /**
     * @return mixed
     */
    public function getImgOrder()
    {
        return $this->img_order;
    }

    /**
     * @return mixed
     */
    public function getSlideType()
    {
        return $this->slide_type;
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
    public function getSellerTheme()
    {
        return $this->seller_theme;
    }

    /**
     * @return mixed
     */
    public function getInstallImg()
    {
        return $this->install_img;
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
    public function setImgUrl($value)
    {
        $this->img_url = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setImgLink($value)
    {
        $this->img_link = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setImgDesc($value)
    {
        $this->img_desc = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setImgOrder($value)
    {
        $this->img_order = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSlideType($value)
    {
        $this->slide_type = $value;
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
    public function setSellerTheme($value)
    {
        $this->seller_theme = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setInstallImg($value)
    {
        $this->install_img = $value;
        return $this;
    }
}
