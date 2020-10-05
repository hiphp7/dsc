<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class SellerDomain
 */
class SellerDomain extends Model
{
    protected $table = 'seller_domain';

    public $timestamps = false;

    protected $fillable = [
        'domain_name',
        'ru_id',
        'is_enable',
        'validity_time'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getDomainName()
    {
        return $this->domain_name;
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
    public function getIsEnable()
    {
        return $this->is_enable;
    }

    /**
     * @return mixed
     */
    public function getValidityTime()
    {
        return $this->validity_time;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setDomainName($value)
    {
        $this->domain_name = $value;
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
    public function setIsEnable($value)
    {
        $this->is_enable = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setValidityTime($value)
    {
        $this->validity_time = $value;
        return $this;
    }
}
