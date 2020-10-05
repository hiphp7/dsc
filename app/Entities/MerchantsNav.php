<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class MerchantsNav
 */
class MerchantsNav extends Model
{
    protected $table = 'merchants_nav';

    public $timestamps = false;

    protected $fillable = [
        'ctype',
        'cid',
        'cat_id',
        'name',
        'ifshow',
        'vieworder',
        'opennew',
        'url',
        'type',
        'ru_id'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getCtype()
    {
        return $this->ctype;
    }

    /**
     * @return mixed
     */
    public function getCid()
    {
        return $this->cid;
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
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getIfshow()
    {
        return $this->ifshow;
    }

    /**
     * @return mixed
     */
    public function getVieworder()
    {
        return $this->vieworder;
    }

    /**
     * @return mixed
     */
    public function getOpennew()
    {
        return $this->opennew;
    }

    /**
     * @return mixed
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
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
    public function setCtype($value)
    {
        $this->ctype = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCid($value)
    {
        $this->cid = $value;
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
    public function setName($value)
    {
        $this->name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIfshow($value)
    {
        $this->ifshow = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setVieworder($value)
    {
        $this->vieworder = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setOpennew($value)
    {
        $this->opennew = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setUrl($value)
    {
        $this->url = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setType($value)
    {
        $this->type = $value;
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
