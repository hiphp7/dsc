<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class GoodsAttr
 */
class GoodsAttr extends Model
{
    protected $table = 'goods_attr';

    protected $primaryKey = 'goods_attr_id';

    public $timestamps = false;

    protected $fillable = [
        'goods_id',
        'attr_id',
        'attr_value',
        'color_value',
        'attr_price',
        'attr_sort',
        'attr_img_flie',
        'attr_gallery_flie',
        'attr_img_site',
        'attr_checked',
        'attr_value1',
        'lang_flag',
        'attr_img',
        'attr_thumb',
        'img_flag',
        'attr_pid',
        'admin_id',
        'cloud_id'
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
    public function getAttrId()
    {
        return $this->attr_id;
    }

    /**
     * @return mixed
     */
    public function getAttrValue()
    {
        return $this->attr_value;
    }

    /**
     * @return mixed
     */
    public function getColorValue()
    {
        return $this->color_value;
    }

    /**
     * @return mixed
     */
    public function getAttrPrice()
    {
        return $this->attr_price;
    }

    /**
     * @return mixed
     */
    public function getAttrSort()
    {
        return $this->attr_sort;
    }

    /**
     * @return mixed
     */
    public function getAttrImgFlie()
    {
        return $this->attr_img_flie;
    }

    /**
     * @return mixed
     */
    public function getAttrGalleryFlie()
    {
        return $this->attr_gallery_flie;
    }

    /**
     * @return mixed
     */
    public function getAttrImgSite()
    {
        return $this->attr_img_site;
    }

    /**
     * @return mixed
     */
    public function getAttrChecked()
    {
        return $this->attr_checked;
    }

    /**
     * @return mixed
     */
    public function getAttrValue1()
    {
        return $this->attr_value1;
    }

    /**
     * @return mixed
     */
    public function getLangFlag()
    {
        return $this->lang_flag;
    }

    /**
     * @return mixed
     */
    public function getAttrImg()
    {
        return $this->attr_img;
    }

    /**
     * @return mixed
     */
    public function getAttrThumb()
    {
        return $this->attr_thumb;
    }

    /**
     * @return mixed
     */
    public function getImgFlag()
    {
        return $this->img_flag;
    }

    /**
     * @return mixed
     */
    public function getAttrPid()
    {
        return $this->attr_pid;
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
    public function getCloudId()
    {
        return $this->cloud_id;
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
    public function setAttrId($value)
    {
        $this->attr_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAttrValue($value)
    {
        $this->attr_value = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setColorValue($value)
    {
        $this->color_value = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAttrPrice($value)
    {
        $this->attr_price = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAttrSort($value)
    {
        $this->attr_sort = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAttrImgFlie($value)
    {
        $this->attr_img_flie = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAttrGalleryFlie($value)
    {
        $this->attr_gallery_flie = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAttrImgSite($value)
    {
        $this->attr_img_site = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAttrChecked($value)
    {
        $this->attr_checked = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAttrValue1($value)
    {
        $this->attr_value1 = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setLangFlag($value)
    {
        $this->lang_flag = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAttrImg($value)
    {
        $this->attr_img = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAttrThumb($value)
    {
        $this->attr_thumb = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setImgFlag($value)
    {
        $this->img_flag = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAttrPid($value)
    {
        $this->attr_pid = $value;
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
    public function setCloudId($value)
    {
        $this->cloud_id = $value;
        return $this;
    }
}
