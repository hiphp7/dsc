<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class GoodsExtend
 */
class GoodsExtend extends Model
{
    protected $table = 'goods_extend';

    protected $primaryKey = 'extend_id';

    public $timestamps = false;

    protected $fillable = [
        'goods_id',
        'is_reality',
        'is_return',
        'is_fast',
        'width',
        'height',
        'depth',
        'origincountry',
        'originplace',
        'assemblycountry',
        'barcodetype',
        'catena',
        'isbasicunit',
        'packagetype',
        'grossweight',
        'netweight',
        'netcontent',
        'licensenum',
        'healthpermitnum'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getGoodsId()
    {
        return $this->goods_id;
    }

    /**
     * @return mixed
     */
    public function getIsReality()
    {
        return $this->is_reality;
    }

    /**
     * @return mixed
     */
    public function getIsReturn()
    {
        return $this->is_return;
    }

    /**
     * @return mixed
     */
    public function getIsFast()
    {
        return $this->is_fast;
    }

    /**
     * @return mixed
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @return mixed
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @return mixed
     */
    public function getDepth()
    {
        return $this->depth;
    }

    /**
     * @return mixed
     */
    public function getOrigincountry()
    {
        return $this->origincountry;
    }

    /**
     * @return mixed
     */
    public function getOriginplace()
    {
        return $this->originplace;
    }

    /**
     * @return mixed
     */
    public function getAssemblycountry()
    {
        return $this->assemblycountry;
    }

    /**
     * @return mixed
     */
    public function getBarcodetype()
    {
        return $this->barcodetype;
    }

    /**
     * @return mixed
     */
    public function getCatena()
    {
        return $this->catena;
    }

    /**
     * @return mixed
     */
    public function getIsbasicunit()
    {
        return $this->isbasicunit;
    }

    /**
     * @return mixed
     */
    public function getPackagetype()
    {
        return $this->packagetype;
    }

    /**
     * @return mixed
     */
    public function getGrossweight()
    {
        return $this->grossweight;
    }

    /**
     * @return mixed
     */
    public function getNetweight()
    {
        return $this->netweight;
    }

    /**
     * @return mixed
     */
    public function getNetcontent()
    {
        return $this->netcontent;
    }

    /**
     * @return mixed
     */
    public function getLicensenum()
    {
        return $this->licensenum;
    }

    /**
     * @return mixed
     */
    public function getHealthpermitnum()
    {
        return $this->healthpermitnum;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setGoodsId($value)
    {
        $this->goods_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsReality($value)
    {
        $this->is_reality = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsReturn($value)
    {
        $this->is_return = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsFast($value)
    {
        $this->is_fast = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setWidth($value)
    {
        $this->width = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setHeight($value)
    {
        $this->height = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setDepth($value)
    {
        $this->depth = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setOrigincountry($value)
    {
        $this->origincountry = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setOriginplace($value)
    {
        $this->originplace = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setAssemblycountry($value)
    {
        $this->assemblycountry = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setBarcodetype($value)
    {
        $this->barcodetype = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setCatena($value)
    {
        $this->catena = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsbasicunit($value)
    {
        $this->isbasicunit = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setPackagetype($value)
    {
        $this->packagetype = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setGrossweight($value)
    {
        $this->grossweight = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setNetweight($value)
    {
        $this->netweight = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setNetcontent($value)
    {
        $this->netcontent = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setLicensenum($value)
    {
        $this->licensenum = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setHealthpermitnum($value)
    {
        $this->healthpermitnum = $value;
        return $this;
    }
}
