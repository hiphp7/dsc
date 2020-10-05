<?php

namespace App\Services\Cart;

use App\Repositories\Common\DscRepository;

class CartsertService
{
    protected $dscRepository;

    public function __construct(
        DscRepository $dscRepository
    )
    {
        $this->dscRepository = $dscRepository;
    }

    /**
     * 调用购物车信息
     *
     * @param int $type
     * @param int $num
     * @param int $store_id
     * @return array
     * @throws \Exception
     */
    public function insertCartInfo($type = 0, $num = 0, $store_id = 0)
    {
        $num = !empty($num) ? intval($num) : 0;

        $limit = 0;

        if ($type == 1) {
            $limit = "4";
        }

        if (!empty($num) && $num > 0) {
            $limit = $num;
        }

        $where = [
            'store_id' => $store_id,
            'stages_qishu' => -1,
            'limit' => $limit
        ];

        $cart_value = '';
        $arr = [];
        if ($type > 0 || $type == 4) {
            $arr = app(CartGoodsService::class)->getGoodsCartList($where);

            if ($arr) {
                foreach ($arr as $k => $v) {
                    $cart_value = !empty($cart_value) ? $cart_value . ',' . $v['rec_id'] : $v['rec_id'];

                    $arr[$k] = $v;
                }
            }
        }

        $row = app(CartService::class)->getCartInfo($where);

        if ($row) {
            $number = intval($row['number']);
            $amount = floatval($row['amount']);
        } else {
            $number = 0;
            $amount = 0;
        }

        if ($type == 1) {
            $cart = ['goods_list' => $arr, 'number' => $number, 'amount' => $this->dscRepository->getPriceFormat($amount, false), 'goods_list_count' => count($arr)];

            return $cart;
        } elseif ($type == 2) {
            $cart = ['goods_list' => $arr, 'number' => $number, 'amount' => $this->dscRepository->getPriceFormat($amount, false), 'goods_list_count' => count($arr)];

            return $cart;
        } else {
            $GLOBALS['smarty']->assign('number', $number);
            $GLOBALS['smarty']->assign('amount', $amount);

            if ($type == 4) {
                $GLOBALS['smarty']->assign('cart_info', $row);
                $GLOBALS['smarty']->assign('cart_value', $cart_value); //by wang
                $GLOBALS['smarty']->assign('goods', $arr);
            } else {
                $GLOBALS['smarty']->assign('goods', []);
            }

            $GLOBALS['smarty']->assign('str', sprintf(lang('common.cart_info'), $number, $this->dscRepository->getPriceFormat($amount, false)));

            $output = $GLOBALS['smarty']->fetch('library/cart_info.lbi');
            return $output;
        }
    }
}