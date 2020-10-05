<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class AttributeImg
 */
class AttributeImg extends Model
{
    protected $table = 'attribute_img';

    public $timestamps = false;

    protected $fillable = [
        'attr_id',
        'attr_values',
        'attr_img',
        'attr_site'
    ];

    protected $guarded = [];


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
    public function getAttrValues()
    {
        return $this->attr_values;
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
    public function getAttrSite()
    {
        return $this->attr_site;
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
    public function setAttrValues($value)
    {
        $this->attr_values = $value;
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
    public function setAttrSite($value)
    {
        $this->attr_site = $value;
        return $this;
    }
}
