<?php

namespace App\Models;

use App\Entities\SeckillGoods as Base;

/**
 * Class SeckillGoods
 */
class SeckillGoods extends Base
{
    /**
     * 关联秒杀时间
     *
     * @access  public
     * @param brand_id
     * @return  array
     */
    public function getSeckillTimeBucket()
    {
        return $this->hasOne('App\Models\SeckillTimeBucket', 'id', 'tb_id');
    }

    /**
     * 关联秒杀商品
     *
     * @access  public
     * @param brand_id
     * @return  array
     */
    public function getGoods()
    {
        return $this->hasOne('App\Models\Goods', 'goods_id', 'goods_id');
    }

    /**
     * 关联秒杀主题
     *
     * @access  public
     * @param brand_id
     * @return  array
     */
    public function getSeckill()
    {
        return $this->hasOne('App\Models\Seckill', 'sec_id', 'sec_id');
    }
}
