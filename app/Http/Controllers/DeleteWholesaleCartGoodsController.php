<?php

namespace App\Http\Controllers;

use App\Models\WholesaleCart;
use App\Services\Wholesale\WholesaleCartService;

/**
 * 购物流程
 */
class DeleteWholesaleCartGoodsController extends InitController
{
    protected $wholesaleCartService;

    public function __construct(
        WholesaleCartService $wholesaleCartService
    )
    {
        $this->wholesaleCartService = $wholesaleCartService;
    }

    public function index()
    {
        load_helper('wholesale');

        $result = ['error' => 0, 'message' => '', 'content' => '', 'goods_id' => '', 'index' => -1];

        $result['index'] = (int)request()->input('index', 0);
        $rec_id = (int)request()->input('id', 0);

        WholesaleCart::where('rec_id', $rec_id)->delete();

        $arr = $this->wholesaleCartService->getGoodsCartList();

        $row = $this->wholesaleCartService->getCartInfo();

        if ($row) {
            $cart_number = intval($row['cart_number']);
            $number = intval($row['number']);
            $amount = price_format(floatval($row['amount']));
        } else {
            $cart_number = 0;
            $number = 0;
            $amount = 0;
        }

        $result['cart_num'] = $cart_number;

        $this->smarty->assign('str', $cart_number);
        $this->smarty->assign('goods', $arr);

        $this->smarty->assign('number', $number);
        $this->smarty->assign('amount', $amount);
        $result['content'] = $this->smarty->fetch('library/wholesale_cart_info.lbi');
        $result['cart_content'] = $this->smarty->fetch('library/wholesale_cart_menu_info.lbi');
        return response()->json($result);
    }
}
