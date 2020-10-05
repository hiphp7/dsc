<?php

namespace App\Repositories\Flow;


class FlowRepository
{
    /**
     * 重新组合购物流程商品数组
     *
     * @param array $cart_list
     * @return array
     */
    public function getNewGroupCartGoods($cart_list = [])
    {
        $cart_goods = [];
        if ($cart_list) {
            foreach ($cart_list as $key => $goods) {
                foreach ($goods['goods_list'] as $k => $list) {
                    $cart_goods[] = $list;
                }
            }
        }

        return $cart_goods;
    }
}