<?php

namespace App\Services\Category;

use App\Models\Attribute;
use App\Models\GoodsAttr;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;

class CategoryAttributeService
{
    protected $config;
    protected $baseRepository;
    protected $dscRepository;

    public function __construct(
        BaseRepository $baseRepository,
        DscRepository $dscRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
    }

    /**
     * 获得分类商品属性类型
     *
     * @param int $attr_id
     * @param array $children
     * @param int $warehouse_id
     * @param int $area_id
     * @param int $area_city
     * @param array $keywords
     * @param string $cat_type
     * @return mixed
     */
    public function getCatAttribute($attr_id = 0, $children = [], $warehouse_id = 0, $area_id = 0, $area_city = 0, $keywords = [], $cat_type = 'cat_id')
    {
        $arr = [
            'warehouse_id' => $warehouse_id,
            'area_id' => $area_id,
            'area_city' => $area_city,
            'children' => $children,
            'keywords' => $keywords,
            'cat_type' => $cat_type
        ];

        $res = Attribute::select('attr_name', 'attr_cat_type')->where('attr_id', $attr_id);

        $res = $res->where(function ($query) use ($arr) {
            $query->whereHas('getGoodsAttr', function ($query) use ($arr) {
                $query->whereHas('getGoods', function ($query) use ($arr) {

                    $query = $this->dscRepository->getAreaLinkGoods($query, $arr['area_id'], $arr['area_city']);

                    $query = $query->where('is_on_sale', 1)
                        ->where('is_alone_sale', 1)
                        ->where('is_delete', 0);

                    if ($this->config['review_goods']) {
                        $query = $query->where('review_status', '>', 2);
                    }

                    if ($arr['keywords']) {
                        $keywordsParam = [
                            'keywords' => $arr['keywords'],
                        ];

                        $query = $query->where(function ($query) use ($keywordsParam) {
                            foreach ($keywordsParam['keywords'] as $key => $val) {
                                $query->where(function ($query) use ($val) {
                                    $query = $query->orWhere('goods_name', 'like', '%' . $val . '%');

                                    $query = $query->orWhere('goods_sn', 'like', '%' . $val . '%');

                                    $query->orWhere('keywords', 'like', '%' . $val . '%');
                                });
                            }
                        });
                    }

                    $query->where(function ($query) use ($arr) {
                        $query = $query->whereIn($arr['cat_type'], $arr['children']);

                        if ($arr['cat_type'] == 'cat_id') {
                            $query->orWhere(function ($query) use ($arr) {
                                $query->whereHas('getGoodsCat', function ($query) use ($arr) {
                                    $query->where('cat_id', $arr['children']);
                                });
                            });
                        }
                    });
                });
            });
        });

        $res = $this->baseRepository->getToArrayFirst($res);

        return $res;
    }

    /**
     * 获得分类商品属性规格
     *
     * @param int $attr_id
     * @param array $children
     * @param int $warehouse_id
     * @param int $area_id
     * @param int $area_city
     * @param array $keywords
     * @param string $cat_type
     * @return mixed
     */
    public function getCatAttributeAttrList($attr_id = 0, $children = [], $warehouse_id = 0, $area_id = 0, $area_city = 0, $keywords = [], $cat_type = 'cat_id')
    {
        $arr = [
            'warehouse_id' => $warehouse_id,
            'area_id' => $area_id,
            'area_city' => $area_city,
            'children' => $children,
            'keywords' => $keywords,
            'cat_type' => $cat_type
        ];

        $res = GoodsAttr::selectRaw('min(goods_attr_id) AS goods_id, attr_id, attr_value, color_value')->where('attr_id', $attr_id);

        $res = $res->whereHas('getGoods', function ($query) use ($arr) {
            if ($arr['keywords']) {
                $keywordsParam = [
                    'keywords' => $arr['keywords'],
                ];

                $query = $query->where(function ($query) use ($keywordsParam) {
                    foreach ($keywordsParam['keywords'] as $key => $val) {
                        $query->where(function ($query) use ($val) {
                            $query = $query->orWhere('goods_name', 'like', '%' . $val . '%');

                            $query = $query->orWhere('goods_sn', 'like', '%' . $val . '%');

                            $query->orWhere('keywords', 'like', '%' . $val . '%');
                        });
                    }
                });
            }

            $query = $this->dscRepository->getAreaLinkGoods($query, $arr['area_id'], $arr['area_city']);

            if ($this->config['review_goods']) {
                $query = $query->where('review_status', '>', 2);
            }

            $query = $query->where('is_on_sale', 1)
                ->where('is_alone_sale', 1)
                ->where('is_delete', 0);

            $query->where(function ($query) use ($arr) {

                $query = $query->whereIn($arr['cat_type'], $arr['children']);

                if ($arr['cat_type'] == 'cat_id') {
                    $query->orWhere(function ($query) use ($arr) {
                        $query->whereHas('getGoodsCat', function ($query) use ($arr) {
                            $query->where('cat_id', $arr['children']);
                        });
                    });
                }
            });
        });

        $res = $res->groupBy('attr_value')
            ->orderBy('attr_sort', 'desc')
            ->orderBy('goods_attr_id', 'desc');

        $res = $this->baseRepository->getToArrayGet($res);

        return $res;
    }
}