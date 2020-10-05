<?php

namespace App\Services\Category;

use App\Models\Brand;
use App\Models\Goods;
use App\Models\GoodsCat;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Ads\AdsService;

class CategoryBrandService
{
    protected $baseRepository;
    protected $config;
    protected $categoryService;
    protected $dscRepository;
    protected $adsService;

    public function __construct(
        BaseRepository $baseRepository,
        CategoryService $categoryService,
        DscRepository $dscRepository,
        AdsService $adsService
    )
    {
        $this->baseRepository = $baseRepository;
        $this->categoryService = $categoryService;
        $this->dscRepository = $dscRepository;
        $this->adsService = $adsService;
        $this->config = $this->dscRepository->dscConfig();
    }

    /**
     * 获得分类下的品牌
     *
     * @param array $children
     * @return array
     */
    public function getCategoryFilterBrandList($children = [])
    {
        $children = $this->baseRepository->getExplode($children);

        /* 查询分类商品数据 */
        $res = Goods::select('goods_id', 'goods_name', 'brand_id')
            ->where('is_on_sale', 1)
            ->where('is_alone_sale', 1)
            ->where('goods_number', '>', 0)
            ->where('is_delete', 0);

        if ($this->config['review_goods']) {
            $res = $res->where('review_status', '>', 2);
        }

        if (empty($keywords) && $children) {
            $res = $res->whereIn('cat_id', $children);
        }

        $res = $res->with('getBrand');

        $res = $res->groupBy('brand_id');

        $res = $this->baseRepository->getToArrayGet($res);

        $arr = [];
        if ($res) {
            foreach ($res as $k => $v) {
                if ($v['get_brand']) {
                    $brand = $v['get_brand'];
                    $arr[$k]['brand_id'] = $brand['brand_id'];
                    $arr[$k]['brand_name'] = $brand['brand_name'];
                }
            }

            $arr = array_values($arr);
        }

        return $arr;
    }

    /**
     * 获得分类品牌
     *
     * @param int $cat_id
     * @param array $children
     * @param int $warehouse_id
     * @param int $area_id
     * @param int $area_city
     * @param array $keywords
     * @param array $sort
     * @param string $order
     * @param int $size
     * @param string $cat_type
     * @param int $merchant_id
     * @return mixed
     */
    public function getCatBrand($cat_id = 0, $children = [], $warehouse_id = 0, $area_id = 0, $area_city = 0, $keywords = [], $sort = [], $order = 'asc', $size = 0, $cat_type = 'cat_id', $merchant_id = 0)
    {
        $extension_goods = [];
        if ($cat_id > 0 && $children) {
            $extension_goods = GoodsCat::select('goods_id')->whereIn('cat_id', $children);
            $extension_goods = $this->baseRepository->getToArrayGet($extension_goods);
            $extension_goods = $this->baseRepository->getFlatten($extension_goods);
        }

        $arr = [
            'cat_id' => $cat_id,
            'children' => $children,
            'keywords' => $keywords,
            'cat_type' => $cat_type,
            'merchant_id' => $merchant_id,
            'warehouse_id' => $warehouse_id,
            'area_id' => $area_id,
            'area_city' => $area_city,
            'extension_goods' => $extension_goods
        ];

        $res = Brand::where('is_show', 1);

        $res = $res->where(function ($query) use ($arr) {
            $query->whereHas('getGoods', function ($query) use ($arr) {

                if ($arr['merchant_id'] > 0) {
                    $query = $query->where('user_id', $arr['merchant_id']);
                }

                $query = $query->where(function ($query) use ($arr) {
                    $query->orWhere(function ($query) use ($arr) {
                        if ($arr['keywords']) {
                            $query->where(function ($query) use ($arr) {
                                foreach ($arr['keywords'] as $key => $val) {
                                    $query->orWhere(function ($query) use ($val) {
                                        $val = $this->dscRepository->mysqlLikeQuote(trim($val));

                                        $query = $query->orWhere('goods_name', 'like', '%' . $val . '%');
                                        $query->orWhere('keywords', 'like', '%' . $val . '%');
                                    });
                                }

                                $query->orWhere('goods_sn', 'like', '%' . $arr['keywords'][0] . '%');
                            });
                        }
                    });
                });

                /* 关联地区 */
                $query = $this->dscRepository->getAreaLinkGoods($query, $arr['area_id'], $arr['area_city']);

                if ($arr['cat_id'] > 0) {
                    $query = $query->whereIn($arr['cat_type'], $arr['children']);
                }

                if ($arr['extension_goods']) {
                    $query = $query->orWhere(function ($query) use ($arr) {
                        $query->whereIn('goods_id', $arr['extension_goods']);
                    });
                }

                if ($this->config['review_goods']) {
                    $query = $query->where('review_status', '>', 2);
                }

                $query->where('is_on_sale', 1)
                    ->where('is_alone_sale', 1)
                    ->where('is_delete', 0)
                    ->where('is_show', 1);
            });
        });

        $res = $res->with([
            'getGoodsList' => function ($query) {
                $query->where('is_on_sale', 1)
                    ->where('is_alone_sale', 1)
                    ->where('is_delete', 0)
                    ->where('is_show', 1);
            }
        ]);

        if ($sort) {
            if (is_array($sort)) {
                $sort = implode(",", $sort);
                $res = $res->orderByRaw($sort . " " . $order);
            } else {
                $res = $res->orderBy($sort, $order);
            }
        }

        if ($size) {
            $res = $res->take($size);
        }

        $res = $this->baseRepository->getToArrayGet($res);
        $res = $this->baseRepository->getArrayUnique($res);

        if ($res) {
            foreach ($res as $key => $val) {
                $val['goods_num'] = $val['get_goods_list'] ? collect($val['get_goods_list'])->count() : 0;
                $res[$key] = $val;
            }
        }

        return $res;
    }

    /**
     * 获得分类商品属性规格
     *
     * @param int $cat_id
     * @param int $warehouse_id
     * @param int $area_id
     * @param int $area_city
     * @return bool|\Illuminate\Cache\CacheManager|mixed
     * @throws \Exception
     */
    public function getCategoryBrandsAd($cat_id = 0, $warehouse_id = 0, $area_id = 0, $area_city = 0)
    {
        $cache_name = "get_category_brands_ad_" . '_' . $cat_id . '_' . $warehouse_id . '_' . $area_id . '_' . $area_city;
        $arr = cache($cache_name);
        $arr = !is_null($arr) ? $arr : false;

        if ($arr === false) {
            $arr['ad_position'] = '';
            $arr['brands'] = '';

            $cat_name = '';
            for ($i = 1; $i <= $this->config['auction_ad']; $i++) {
                $cat_name .= "'cat_tree_" . $cat_id . "_" . $i . "',";
            }

            $cat_name = substr($cat_name, 0, -1);
            $arr['ad_position'] = $this->adsService->getAdPostiChild($cat_name);

            // 获取分类下品牌
            $sort = ['sort_order', 'brand_id'];
            $children = $this->categoryService->getCatListChildren($cat_id);
            $brands = $this->getCatBrand($cat_id, $children, $warehouse_id, $area_id, $area_city, [], $sort, 'asc', 20);

            if ($brands) {
                foreach ($brands as $key => $val) {
                    $temp_key = $key;
                    $brands[$temp_key]['brand_name'] = $val['brand_name'];
                    $brands[$temp_key]['url'] = route('category', ['id' => $cat_id, 'brand' => $val['brand_id']]);

                    $brands[$temp_key]['brand_logo'] = $this->dscRepository->getImagePath(DATA_DIR . '/brandlogo/' . $val['brand_logo']);

                    // 判断品牌是否被选中
                    $brands[$temp_key]['selected'] = 0;
                }
            }

            $arr['brands'] = $brands;

            cache()->forever($cache_name, $arr);
        }

        return $arr;
    }
}