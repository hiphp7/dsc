<?php

namespace App\Models;

use App\Entities\MerchantsShopInformation as Base;

/**
 * Class MerchantsShopInformation
 */
class MerchantsShopInformation extends Base
{
    public function goods()
    {
        return $this->hasOne('App\Models\Goods', 'user_id', 'user_id');
    }

    public function getGoods()
    {
        return $this->hasMany('App\Models\Goods', 'user_id', 'user_id');
    }

    public function sellershopinfo()
    {
        return $this->hasOne('App\Models\SellerShopinfo', 'ru_id', "user_id");
    }

    public function collectstore()
    {
        return $this->hasOne('App\Models\CollectStore', 'ru_id', "user_id");
    }

    /**
     * 关联会员
     *
     * @access  public
     * @param user_id
     * @return  array
     */
    public function getUsers()
    {
        return $this->hasOne('App\Models\Users', 'user_id', 'user_id');
    }

    /**
     * 关联店铺信息
     *
     * @access  public
     * @param ru_id
     * @return  array
     */
    public function getSellerShopinfo()
    {
        return $this->hasOne('App\Models\SellerShopinfo', 'ru_id', 'user_id');
    }

    /**
     * 关联店铺比例
     *
     * @access  public
     * @param ru_id
     * @return  array
     */
    public function getMerchantsServer()
    {
        return $this->hasOne('App\Models\MerchantsServer', 'user_id', 'user_id');
    }

    /**
     * 关联区域
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getRsRegion()
    {
        return $this->hasOne('App\Models\RsRegion', 'region_id', 'region_id');
    }

    public function getMerchantsStepsFields()
    {
        return $this->hasOne('App\Models\MerchantsStepsFields', 'user_id', 'user_id');
    }

}
