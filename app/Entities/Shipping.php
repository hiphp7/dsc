<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Shipping
 */
class Shipping extends Model
{
    protected $table = 'shipping';

    protected $primaryKey = 'shipping_id';

    public $timestamps = false;

    protected $fillable = [
        'shipping_code',
        'shipping_name',
        'shipping_desc',
        'insure',
        'support_cod',
        'enabled',
        'shipping_print',
        'print_bg',
        'config_lable',
        'print_model',
        'shipping_order',
        'customer_name',
        'customer_pwd',
        'month_code',
        'send_site'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getShippingCode()
    {
        return $this->shipping_code;
    }

    /**
     * @return mixed
     */
    public function getShippingName()
    {
        return $this->shipping_name;
    }

    /**
     * @return mixed
     */
    public function getShippingDesc()
    {
        return $this->shipping_desc;
    }

    /**
     * @return mixed
     */
    public function getInsure()
    {
        return $this->insure;
    }

    /**
     * @return mixed
     */
    public function getSupportCod()
    {
        return $this->support_cod;
    }

    /**
     * @return mixed
     */
    public function getEnabled()
    {
        return $this->enabled;
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
    public function getPrintBg()
    {
        return $this->print_bg;
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
    public function getPrintModel()
    {
        return $this->print_model;
    }

    /**
     * @return mixed
     */
    public function getShippingOrder()
    {
        return $this->shipping_order;
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
     * @param $value
     * @return $this
     */
    public function setShippingCode($value)
    {
        $this->shipping_code = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setShippingName($value)
    {
        $this->shipping_name = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setShippingDesc($value)
    {
        $this->shipping_desc = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setInsure($value)
    {
        $this->insure = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSupportCod($value)
    {
        $this->support_cod = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setEnabled($value)
    {
        $this->enabled = $value;
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
    public function setPrintBg($value)
    {
        $this->print_bg = $value;
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
    public function setPrintModel($value)
    {
        $this->print_model = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setShippingOrder($value)
    {
        $this->shipping_order = $value;
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
}
