<?php

namespace App\Models;

use App\Entities\Users as Base;

/**
 * Class Users
 */
class Users extends Base
{
    public function getUserPictureAttribute()
    {
        return empty($this->attributes['user_picture']) ? '' : $this->attributes['user_picture'];
    }

    /**
     * 关联会员父级
     *
     * @access  public
     * @param parent_id
     * @return  array
     */
    public function getUserParent()
    {
        return $this->hasOne('App\Models\Users', 'user_id', 'parent_id');
    }

    /**
     * 关联会员红包
     *
     * @access  public
     * @param user_id
     * @return  array
     */
    public function getUserBonus()
    {
        return $this->hasOne('App\Models\UserBonus', 'user_id', 'user_id');
    }

    /**
     * 关联会员红包
     *
     * @access  public
     * @param user_id
     * @return  array
     */
    public function getUserBonusList()
    {
        return $this->hasMany('App\Models\UserBonus', 'user_id', 'user_id');
    }

    /**
     * 关联会员优惠券
     *
     * @access  public
     * @param user_id
     * @return  array
     */
    public function getCouponsUserList()
    {
        return $this->hasMany('App\Models\CouponsUser', 'user_id', 'user_id');
    }

    /**
     * 关联会员支付密码
     *
     * @access  public
     * @param user_id
     * @return  array
     */
    public function getUsersPaypwd()
    {
        return $this->hasOne('App\Models\UsersPaypwd', 'user_id', 'user_id');
    }

    /**
     * 关联会员身份认证
     *
     * @access  public
     * @param user_id
     * @return  array
     */
    public function getUsersReal()
    {
        return $this->hasOne('App\Models\UsersReal', 'user_id', 'user_id');
    }

    /**
     * 关联微信会员
     *
     * @access  public
     * @param ect_uid
     * @return  array
     */
    public function getWechatUser()
    {
        return $this->hasOne('App\Models\WechatUser', 'ect_uid', 'user_id');
    }

    /**
     * 关联社会化登录用户
     * @param user_id
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getConnectUser()
    {
        return $this->hasOne('App\Models\ConnectUser', 'user_id', 'user_id');
    }

    /**关联储值卡
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function getValueCard()
    {
        return $this->hasMany('App\Models\ValueCard', 'user_id', 'user_id');
    }

    /**
     * 关联回复商品
     *
     * @access  public
     * @param user_id
     * @return  array
     */
    public function getDiscussCircle()
    {
        return $this->hasOne('App\Models\DiscussCircle', 'user_id', 'user_id');
    }

    /**
     * 关联第三方会员
     *
     * @access  public
     * @param user_id
     * @return  array
     */
    public function getUsersAuth()
    {
        return $this->hasOne('App\Models\UsersAuth', 'user_id', 'user_id');
    }

    /**
     * 关联订单
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getOrder()
    {
        return $this->hasOne('App\Models\OrderInfo', 'user_id', 'user_id');
    }

    /**
     * 关联订单列表
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function getOrderList()
    {
        return $this->hasMany('App\Models\OrderInfo', 'user_id', 'user_id');
    }

    /**
     * 关联分销店铺
     *
     * @return \Illuminate\Database\Eloquent\Relations\hasOne
     */
    public function getDrpShop()
    {
        return $this->hasOne('App\Models\DrpShop', 'user_id', 'user_id');
    }

    /**
     * 关联父级分销商
     * @return \Illuminate\Database\Eloquent\Relations\hasOne
     */
    public function getParentDrpShop()
    {
        return $this->hasOne('App\Models\DrpShop', 'user_id', 'drp_parent_id');
    }

    /**
     * 关联父级分销会员
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function getDrpParent()
    {
        return $this->hasOne('App\Models\Users', 'user_id', 'drp_parent_id');
    }
}
