<?php

namespace App\Models;

use App\Entities\GoodsActivity as Base;

/**
 * Class GoodsActivity
 */
class GoodsActivity extends Base
{
    /**
     * 关联商品
     *
     * @access  public
     * @param goods_id
     * @return  array
     */
    public function getGoods()
    {
        return $this->hasOne('App\Models\Goods', 'goods_id', 'goods_id');
    }

    /**
     * 关联活动商品日志
     *
     * @access  public
     * @param goods_id
     * @return  array
     */
    public function getAuctionLog()
    {
        return $this->hasOne('App\Models\AuctionLog', 'act_id', 'act_id');
    }

    /**
     * 关联活动商品日志
     *
     * @access  public
     * @param goods_id
     * @return  array
     */
    public function getSnatchLog()
    {
        return $this->hasOne('App\Models\SnatchLog', 'snatch_id', 'act_id');
    }

    /**
     * 关联超值礼包商品
     *
     * @access  public
     * @param package_id
     * @return  array
     */
    public function getPackageGoods()
    {
        return $this->hasOne('App\Models\PackageGoods', 'package_id', 'act_id');
    }

    /**
     * 关联超值礼包商品列表
     *
     * @access  public
     * @param package_id
     * @return  array
     */
    public function getPackageGoodsList()
    {
        return $this->hasMany('App\Models\PackageGoods', 'package_id', 'act_id');
    }

    /**
     * 关联活动订单条件查询
     *
     * @access  public
     * @objet  $order
     * @return  array
     */
    public function scopeSearchKeyword($query, $auction = [])
    {
        $time = $this->getGmtime();
        if (isset($auction->keyword)) {
            if ($auction->idTxt == 'submitDate') {
                $status_keyword = $auction->status_keyword;
            } elseif ($auction->idTxt == 'status_list') {
                $status_keyword = $auction->keyword;
            } elseif ($auction->idTxt == 'is_going' || $auction->idTxt == 'is_finished') {
                $status_keyword = $auction->keyword;
            }

            if ($auction->type == 'text') { //商品名称模糊查询
                if ($auction->keyword == $GLOBALS['_LANG']['user_keyword']) {
                    $auction->keyword = '';
                }

                $query = $query->where('goods_name', 'like', "'%" . $this->getMysqlLikeQuote($auction->keyword) . "%'");
            } elseif ($auction->type == 'dateTime' || $auction->type == 'order_status' || $auction->type == 'is_going' || $auction->type == 'is_finished') {
                $time = $this->getGmtime();
                //综合状态
                switch ($status_keyword) {
                    case 1:

                        $query = $query->where('is_finished', 0)->where('end_time', '>', $time)->where('start_time', '<', $time);
                        break;

                    case 3:
                        $query = $query->where(function ($query) use ($time) {
                            $query->where('is_finished', '>', 0)->orWhere(function ($query) use ($time) {
                                $query->where('is_finished', 0)->where('end_time', '<', $time);
                            });
                        });
                        break;
                }
            }
        } else {
            if ($auction == 1) {
                $query = $query->where('is_finished', 0)->where('end_time', '>', $time)->where('start_time', '<', $time);
            } elseif ($auction == 2) {
                $query->where('is_finished', '>', 0)->orWhere(function ($query) use ($time) {
                    $query->where('is_finished', 0)->where('end_time', '<', $time);
                });
            }
        }

        return $query;
    }

    /**
     * 获得当前格林威治时间的时间戳
     *
     * @return  integer
     */
    private function getGmtime()
    {
        return (time() - date('Z'));
    }

    /**
     * 对 MYSQL LIKE 的内容进行转义
     *
     * @access      public
     * @param string      string  内容
     * @return      string
     */
    public function getMysqlLikeQuote($str)
    {
        return strtr($str, ["\\\\" => "\\\\\\\\", '_' => '\_', '%' => '\%', "\'" => "\\\\\'"]);
    }

    /**
     * 关联会员等级价查询
     *
     * @access  public
     * @param goods_id
     * @return  array
     */
    public function getMemberPrice()
    {
        return $this->hasOne('App\Models\MemberPrice', 'goods_id', 'goods_id');
    }

    /**
     * 关联仓库商品信息查询
     *
     * @access  public
     * @param goods_id
     * @return  array
     */
    public function getWarehouseGoods()
    {
        return $this->hasOne('App\Models\WarehouseGoods', 'goods_id', 'goods_id');
    }

    /**
     * 关联仓库地区商品查询
     *
     * @access  public
     * @param goods_id
     * @return  array
     */
    public function getWarehouseAreaGoods()
    {
        return $this->hasOne('App\Models\WarehouseAreaGoods', 'goods_id', 'goods_id');
    }

    /**
     * 关联地区显示商品
     *
     * @access  public
     * @param goods_id
     * @return  array
     */
    public function getLinkAreaGoods()
    {
        return $this->hasOne('App\Models\LinkAreaGoods', 'goods_id', 'goods_id');
    }

    /**
     * 关联商品扩展
     *
     * @access  public
     * @param goods_id
     * @return  array
     */
    public function getGoodsExtend()
    {
        return $this->hasOne('App\Models\GoodsExtend', 'goods_id', 'goods_id');
    }

    /**
     * 关联商品扩展
     *
     * @access  public
     * @param goods_id
     * @return  array
     */
    public function getMerchantsShopInformation()
    {
        return $this->hasOne('App\Models\MerchantsShopInformation', 'user_id', 'user_id');
    }
}
