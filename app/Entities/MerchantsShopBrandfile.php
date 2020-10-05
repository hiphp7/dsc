<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class MerchantsShopBrandfile
 */
class MerchantsShopBrandfile extends Model
{
    protected $table = 'merchants_shop_brandfile';

    protected $primaryKey = 'b_fid';

    public $timestamps = false;

    protected $fillable = [
        'bid',
        'qualificationNameInput',
        'qualificationImg',
        'expiredDateInput',
        'expiredDate_permanent'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getBid()
    {
        return $this->bid;
    }

    /**
     * @return mixed
     */
    public function getQualificationNameInput()
    {
        return $this->qualificationNameInput;
    }

    /**
     * @return mixed
     */
    public function getQualificationImg()
    {
        return $this->qualificationImg;
    }

    /**
     * @return mixed
     */
    public function getExpiredDateInput()
    {
        return $this->expiredDateInput;
    }

    /**
     * @return mixed
     */
    public function getExpiredDatePermanent()
    {
        return $this->expiredDate_permanent;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setBid($value)
    {
        $this->bid = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setQualificationNameInput($value)
    {
        $this->qualificationNameInput = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setQualificationImg($value)
    {
        $this->qualificationImg = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setExpiredDateInput($value)
    {
        $this->expiredDateInput = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setExpiredDatePermanent($value)
    {
        $this->expiredDate_permanent = $value;
        return $this;
    }
}
