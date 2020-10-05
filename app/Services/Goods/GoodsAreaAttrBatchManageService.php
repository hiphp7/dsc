<?php

namespace App\Services\Goods;

use App\Models\GoodsAttr;
use App\Repositories\Common\BaseRepository;
use App\Services\Common\CommonManageService;

class GoodsAreaAttrBatchManageService
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
     *
     * @param int $goods_id
     * @return mixed
     */
    public function getGoodsAttrList($goods_id = 0)
    {
        $admin_id = $this->commonManageService->getAdminId();

        $res = GoodsAttr::select('goods_attr_id', 'goods_id', 'attr_id', 'attr_value', 'attr_sort', 'admin_id');

        if (empty($goods_id)) {
            $res = $res->where('admin_id', $admin_id);
        }

        $res = $res->with([
            'getGoodsWarehouseAreaAttr' => function ($query) use ($goods_id) {
                $query->select('goods_attr_id', 'attr_price')->where('goods_id', $goods_id);
            }
        ]);

        $res = $res->where('goods_id', $goods_id)
            ->orderBy('attr_sort')
            ->orderBy('goods_attr_id');
        $goods_attr_list = $this->baseRepository->getToArrayGet($res);

        return $goods_attr_list;
    }
}
