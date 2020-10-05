<?php

namespace App\Http\Controllers;


use App\Services\Common\CommonService;

class AjaxCartController extends InitController
{
    protected $commonService;

    public function __construct(
        CommonService $commonService
    )
    {
        $this->commonService = $commonService;
    }

    public function index()
    {
        $act = addslashes(trim(request()->input('act', '')));

        $result = ['error' => 0, 'content' => ''];

        /*------------------------------------------------------ */
        //-- 查询购物车商品数量
        /*------------------------------------------------------ */
        if ($act == 'cart_number') {
            $result = $this->commonService->cartNumber();
        }

        return response()->json($result);
    }
}