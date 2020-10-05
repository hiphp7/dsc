<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Class SellerShopinfoChangelog
 */
class SellerShopinfoChangelog extends Model
{
    protected $table = 'seller_shopinfo_changelog';

    public $timestamps = false;

    protected $fillable = [
        'ru_id',
        'data_key',
        'data_value'
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
    public function getDataKey()
    {
        return $this->data_key;
    }

    /**
     * @return mixed
     */
    public function getDataValue()
    {
        return $this->data_value;
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
    public function setDataKey($value)
    {
        $this->data_key = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setDataValue($value)
    {
        $this->data_value = $value;
        return $this;
    }
}
