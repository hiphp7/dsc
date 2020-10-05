<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ValueCardType
 */
class ValueCardType extends Model
{
    protected $table = 'value_card_type';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'vc_desc',
        'vc_value',
        'vc_prefix',
        'vc_dis',
        'vc_limit',
        'use_condition',
        'use_merchants',
        'spec_goods',
        'spec_cat',
        'vc_indate',
        'is_rec',
        'add_time'
    ];

    protected $guarded = [];


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
    public function getVcDesc()
    {
        return $this->vc_desc;
    }

    /**
     * @return mixed
     */
    public function getVcValue()
    {
        return $this->vc_value;
    }

    /**
     * @return mixed
     */
    public function getVcPrefix()
    {
        return $this->vc_prefix;
    }

    /**
     * @return mixed
     */
    public function getVcDis()
    {
        return $this->vc_dis;
    }

    /**
     * @return mixed
     */
    public function getVcLimit()
    {
        return $this->vc_limit;
    }

    /**
     * @return mixed
     */
    public function getUseCondition()
    {
        return $this->use_condition;
    }

    /**
     * @return mixed
     */
    public function getUseMerchants()
    {
        return $this->use_merchants;
    }

    /**
     * @return mixed
     */
    public function getSpecGoods()
    {
        return $this->spec_goods;
    }

    /**
     * @return mixed
     */
    public function getSpecCat()
    {
        return $this->spec_cat;
    }

    /**
     * @return mixed
     */
    public function getVcIndate()
    {
        return $this->vc_indate;
    }

    /**
     * @return mixed
     */
    public function getIsRec()
    {
        return $this->is_rec;
    }

    /**
     * @return mixed
     */
    public function getAddTime()
    {
        return $this->add_time;
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
    public function setVcDesc($value)
    {
        $this->vc_desc = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setVcValue($value)
    {
        $this->vc_value = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setVcPrefix($value)
    {
        $this->vc_prefix = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setVcDis($value)
    {
        $this->vc_dis = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setVcLimit($value)
    {
        $this->vc_limit = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setUseCondition($value)
    {
        $this->use_condition = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setUseMerchants($value)
    {
        $this->use_merchants = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSpecGoods($value)
    {
        $this->spec_goods = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSpecCat($value)
    {
        $this->spec_cat = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setVcIndate($value)
    {
        $this->vc_indate = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsRec($value)
    {
        $this->is_rec = $value;
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
}
