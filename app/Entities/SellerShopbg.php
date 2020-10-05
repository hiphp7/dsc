<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class SellerShopbg
 */
class SellerShopbg extends Model
{
    protected $table = 'seller_shopbg';

    public $timestamps = false;

    protected $fillable = [
        'bgimg',
        'bgrepeat',
        'bgcolor',
        'show_img',
        'is_custom',
        'ru_id',
        'seller_theme'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getBgimg()
    {
        return $this->bgimg;
    }

    /**
     * @return mixed
     */
    public function getBgrepeat()
    {
        return $this->bgrepeat;
    }

    /**
     * @return mixed
     */
    public function getBgcolor()
    {
        return $this->bgcolor;
    }

    /**
     * @return mixed
     */
    public function getShowImg()
    {
        return $this->show_img;
    }

    /**
     * @return mixed
     */
    public function getIsCustom()
    {
        return $this->is_custom;
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
    public function getSellerTheme()
    {
        return $this->seller_theme;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setBgimg($value)
    {
        $this->bgimg = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setBgrepeat($value)
    {
        $this->bgrepeat = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setBgcolor($value)
    {
        $this->bgcolor = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setShowImg($value)
    {
        $this->show_img = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsCustom($value)
    {
        $this->is_custom = $value;
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
    public function setSellerTheme($value)
    {
        $this->seller_theme = $value;
        return $this;
    }
}
