<?php

namespace App\Repositories\Cart;

class CartRepository
{
    /**
     * 存储获取购物车商品rec_id
     *
     * @param array $cart_value
     * @return bool
     */
    public function pushCartValue($cart_value = [])
    {
        if ($cart_value) {

            if (session()->exists('cart_value')) {
                session()->forget('cart_value');
            }

            $cart_value = !is_array($cart_value) ? explode(',', $cart_value) : $cart_value;
            session(['cart_value' => $cart_value]);

            return true;
        }

        return false;
    }
}