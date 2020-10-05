<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class TouchNav
 */
class TouchNav extends Model
{
    protected $table = 'touch_nav';

    public $timestamps = false;

    protected $fillable = [
        'ctype',
        'cid',
        'name',
        'ifshow',
        'vieworder',
        'opennew',
        'url',
        'type',
        'pic'
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
    public function getPic()
    {
        return $this->pic;
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
    public function setPic($value)
    {
        $this->pic = $value;
        return $this;
    }
}
