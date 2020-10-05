<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ShippingTpl
 */
class ShippingTpl extends Model
{
    protected $table = 'shipping_tpl';

    protected $primaryKey = 'st_id';

    public $timestamps = false;

    protected $fillable = [
        'shipping_id',
        'ru_id',
        'print_bg',
        'print_model',
        'config_lable',
        'shipping_print',
        'update_time'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getShippingId()
    {
        return $this->shipping_id;
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
    public function getPrintBg()
    {
        return $this->print_bg;
    }

    /**
     * @return mixed
     */
    public function getPrintModel()
    {
        return $this->print_model;
    }

    /**
     * @return mixed
     */
    public function getConfigLable()
    {
        return $this->config_lable;
    }

    /**
     * @return mixed
     */
    public function getShippingPrint()
    {
        return $this->shipping_print;
    }

    /**
     * @return mixed
     */
    public function getUpdateTime()
    {
        return $this->update_time;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setShippingId($value)
    {
        $this->shipping_id = $value;
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
    public function setPrintBg($value)
    {
        $this->print_bg = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPrintModel($value)
    {
        $this->print_model = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setConfigLable($value)
    {
        $this->config_lable = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setShippingPrint($value)
    {
        $this->shipping_print = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setUpdateTime($value)
    {
        $this->update_time = $value;
        return $this;
    }
}
