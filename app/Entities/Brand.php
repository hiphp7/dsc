<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Brand
 */
class Brand extends Model
{
    protected $table = 'brand';

    protected $primaryKey = 'brand_id';

    public $timestamps = false;

    protected $fillable = [
        'brand_name',
        'brand_letter',
        'brand_first_char',
        'brand_logo',
        'index_img',
        'brand_bg',
        'brand_desc',
        'site_url',
        'sort_order',
        'is_show',
        'is_delete',
        'audit_status',
        'add_time',
        'name_pinyin'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getBrandName()
    {
        return $this->brand_name;
    }

    /**
     * @return mixed
     */
    public function getBrandLetter()
    {
        return $this->brand_letter;
    }

    /**
     * @return mixed
     */
    public function getBrandFirstChar()
    {
        return $this->brand_first_char;
    }

    /**
     * @return mixed
     */
    public function getBrandLogo()
    {
        return $this->brand_logo;
    }

    /**
     * @return mixed
     */
    public function getIndexImg()
    {
        return $this->index_img;
    }

    /**
     * @return mixed
     */
    public function getBrandBg()
    {
        return $this->brand_bg;
    }

    /**
     * @return mixed
     */
    public function getBrandDesc()
    {
        return $this->brand_desc;
    }

    /**
     * @return mixed
     */
    public function getSiteUrl()
    {
        return $this->site_url;
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
    public function getIsShow()
    {
        return $this->is_show;
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
    public function getAuditStatus()
    {
        return $this->audit_status;
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
    public function getNamePinyin()
    {
        return $this->name_pinyin;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setBrandName($value)
    {
        $this->brand_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setBrandLetter($value)
    {
        $this->brand_letter = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setBrandFirstChar($value)
    {
        $this->brand_first_char = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setBrandLogo($value)
    {
        $this->brand_logo = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIndexImg($value)
    {
        $this->index_img = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setBrandBg($value)
    {
        $this->brand_bg = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setBrandDesc($value)
    {
        $this->brand_desc = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSiteUrl($value)
    {
        $this->site_url = $value;
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
    public function setIsShow($value)
    {
        $this->is_show = $value;
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
    public function setAuditStatus($value)
    {
        $this->audit_status = $value;
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
    public function setNamePinyin($value)
    {
        $this->name_pinyin = $value;
        return $this;
    }
}
