<?php

namespace App\Services\Goods;

use App\Models\Attribute;
use App\Models\GoodsAttr;
use App\Repositories\Common\BaseRepository;

class GoodsAttrPriceManageService
{
    protected $baseRepository;

    public function __construct(
        BaseRepository $baseRepository

    )
    {
        $this->baseRepository = $baseRepository;
    }

    public function getGoodsLattrList($goods_id, $goods_type)
    {
        $res = GoodsAttr::whereHas('getGoodsAttribute', function ($query) {
            $query->where('attr_type', '<>', 0);
            $query->orderBy('sort_order');
        });
        $res = $res->with(['getGoods' => function ($query) use ($goods_id) {
            $query->select('goods_id', 'goods_sn');
        }]);
        $res = $res->where('goods_id', $goods_id);
        $res = $this->baseRepository->getToArrayGet($res);

        foreach ($res as $key => $value) {
            $res[$key]['goods_sn'] = '';
            if (isset($value['get_goods']) && !empty($value['get_goods'])) {
                $res[$key]['goods_sn'] = $value['get_goods']['goods_sn'];
            }
            $goods_type_name = GoodsType::where('cat_id', $goods_type)->value('cat_name');
            $attr_name = Attribute::where('attr_id', $value['attr_id'])->value('attr_name');
            $res[$key]['attr_all_value'] = $goods_type_name . '-' . $attr_name . '-' . $value['attr_value'];
        }

        return $res;
    }
}
