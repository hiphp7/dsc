<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class SellerShopheader
 */
class SellerShopheader extends Model
{
    protected $table = 'seller_shopheader';

    public $timestamps = false;

    protected $fillable = [
        'content',
        'headtype',
        'headbg_img',
        'shop_color',
        'seller_theme',
        'ru_id'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @return mixed
     */
    public function getHeadtype()
    {
        return $this->headtype;
    }

    /**
     * @return mixed
     */
    public function getHeadbgImg()
    {
        return $this->headbg_img;
    }

    /**
     * @return mixed
     */
    public function getShopColor()
    {
        return $this->shop_color;
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
    public function getRuId()
    {
        return $this->ru_id;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setContent($value)
    {
        $this->content = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setHeadtype($value)
    {
        $this->headtype = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setHeadbgImg($value)
    {
        $this->headbg_img = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setShopColor($value)
    {
        $this->shop_color = $value;
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
    public function setRuId($value)
    {
        $this->ru_id = $value;
        return $this;
    }
}
