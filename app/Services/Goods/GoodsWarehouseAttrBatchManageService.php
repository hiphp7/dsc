<?php

namespace App\Services\Goods;

use App\Models\GoodsAttr;
use App\Repositories\Common\BaseRepository;
use App\Services\Common\CommonManageService;

class GoodsWarehouseAttrBatchManageService
{
    protected $baseRepository;
    protected $commonManageService;

    public function __construct(
        BaseRepository $baseRepository,
        CommonManageService $commonManageService
    )
    {
        $this->baseRepository = $baseRepository;
        $this->commonManageService = $commonManageService;
    }

    /**
     * 商品属性列表
     */
    public function getGoodsAttrList($goods_id)
    {
        $admin_id = $this->commonManageService->getAdminId();

        $res = GoodsAttr::where('goods_id', $goods_id);
        if (empty($goods_id)) {
            $res = $res->where('admin_id', $admin_id);
        }

        $res = $res->with(['getGoodsWarehouseAttr' => function ($query) use ($goods_id) {
            $query->select('attr_price')->where('goods_id', $goods_id);
        }]);
        $res = $res->orderBy('attr_sort')->orderBy('goods_attr_id');
        $goods_attr_list = $this->baseRepository->getToArrayGet($res);

        foreach ($goods_attr_list as $key => $value) {
            $value['attr_price'] = 0;
            if (isset($value['get_goods_warehouse_attr']) && !empty($value['get_goods_warehouse_attr'])) {
                $value['attr_price'] = $value['get_goods_warehouse_attr']['attr_price'];
            }
            $goods_attr_list[$key] = $value;
        }
        return $goods_attr_list;
    }
}
