<?php

namespace App\Http\Controllers;

use App\Services\Common\CommonService;

/**
 * 删除购车商品
 */
class DeleteCartGoodsController extends InitController
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
        $result = $this->commonService->ajaxDeleteCartGoods();
        return response()->json($result);
    }
}
