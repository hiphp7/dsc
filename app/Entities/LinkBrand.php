<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class LinkBrand
 */
class LinkBrand extends Model
{
    protected $table = 'link_brand';

    public $timestamps = false;

    protected $fillable = [
        'bid',
        'brand_id'
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
    public function getBrandId()
    {
        return $this->brand_id;
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
    public function setBrandId($value)
    {
        $this->brand_id = $value;
        return $this;
    }
}
