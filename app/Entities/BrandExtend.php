<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class BrandExtend
 */
class BrandExtend extends Model
{
    protected $table = 'brand_extend';

    public $timestamps = false;

    protected $fillable = [
        'brand_id',
        'is_recommend'
    ];

    protected $guarded = [];


    /**
     * @return mixed
     */
    public function getBrandId()
    {
        return $this->brand_id;
    }

    /**
     * @return mixed
     */
    public function getIsRecommend()
    {
        return $this->is_recommend;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setBrandId($value)
    {
        $this->brand_id = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setIsRecommend($value)
    {
        $this->is_recommend = $value;
        return $this;
    }
}
