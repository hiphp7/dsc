<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class KdniaoEorderConfig
 */
class KdniaoEorderConfig extends Model
{
    protected $table = 'kdniao_eorder_config';

    public $timestamps = false;

    protected $fillable = [
        'ru_id',
        'shipping_id',
        'shipper_code',
        'customer_name',
        'customer_pwd',
        'month_code',
        'send_site',
        'pay_type',
        'template_size'
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
    public function getShippingId()
    {
        return $this->shipping_id;
    }

    /**
     * @return mixed
     */
    public function getShipperCode()
    {
        return $this->shipper_code;
    }

    /**
     * @return mixed
     */
    public function getCustomerName()
    {
        return $this->customer_name;
    }

    /**
     * @return mixed
     */
    public function getCustomerPwd()
    {
        return $this->customer_pwd;
    }

    /**
     * @return mixed
     */
    public function getMonthCode()
    {
        return $this->month_code;
    }

    /**
     * @return mixed
     */
    public function getSendSite()
    {
        return $this->send_site;
    }

    /**
     * @return mixed
     */
    public function getPayType()
    {
        return $this->pay_type;
    }

    /**
     * @return mixed
     */
    public function getTemplateSize()
    {
        return $this->template_size;
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
    public function setShippingId($value)
    {
        $this->shipping_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setShipperCode($value)
    {
        $this->shipper_code = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCustomerName($value)
    {
        $this->customer_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCustomerPwd($value)
    {
        $this->customer_pwd = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setMonthCode($value)
    {
        $this->month_code = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSendSite($value)
    {
        $this->send_site = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPayType($value)
    {
        $this->pay_type = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setTemplateSize($value)
    {
        $this->template_size = $value;
        return $this;
    }
}
