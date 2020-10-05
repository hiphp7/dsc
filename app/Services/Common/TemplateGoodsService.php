<?php

namespace App\Services\Common;

use App\Models\Brand;
use App\Models\Goods;
use App\Repositories\Common\DscRepository;
use App\Services\Category\CategoryService;
use App\Services\Goods\GoodsCommonService;

class TemplateGoodsService
{
    protected $categoryService;
    protected $dscRepository;
    protected $config;
    protected $goodsCommonService;

    public function __construct(
        CategoryService $categoryService,
        DscRepository $dscRepository,
        GoodsCommonService $goodsCommonService
    )
    {
        $this->categoryService = $categoryService;
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
        $this->goodsCommonService = $goodsCommonService;
    }

    /**
     * 获得指定的品牌下的商品
     *
     * @param int $brand_id 品牌的ID
     * @param int $cat_id 分类编号
     * @param int $num 数量
     * @param int $warehouse_id
     * @param int $area_id
     * @param int $area_city
     * @return array
     */
    public function getAssignBrandGoods($brand_id = 0, $cat_id = 0, $num = 0, $warehouse_id = 0, $area_id = 0, $area_city = 0)
    {
        $res = Goods::where('is_on_sale', 1)->where('is_alone_sale', 1)->where('is_delete', 0);

        if ($brand_id) {
            $res = $res->where('brand_id', $brand_id);
        }

        if ($cat_id) {
            $cat_list = $this->categoryService->getCatListChildren($cat_id);
            $res = $res->whereIn('cat_id', $cat_list);
        }

        $res = $this->dscRepository->getAreaLinkGoods($res, $area_id, $area_city);

        $where = [
            'area_id' => $area_id,
            'area_city' => $area_city,
            'area_pricetype' => $this->config['area_pricetype']
        ];

        $user_rank = session('user_rank');
        $res = $res->with([
            'getMemberPrice' => function ($query) use ($user_rank) {
                $query->where('user_rank', $user_rank);
            }, 'getWarehouseGoods' => function ($query) use ($warehouse_id) {
                $query->where('region_id', $warehouse_id);
            }, 'getWarehouseAreaGoods' => function ($query) use ($where) {
                $query = $query->where('region_id', $where['area_id']);

                if ($where['area_pricetype'] == 1) {
                    $query->where('city_id', $where['area_city']);
                }
            }
        ]);

        $res = $res->orderBy('sort_order', 'ASC')->orderBy('goods_id', 'DESC');

        if ($num > 0) {
            $res = $res->take($num);
        }

        $res = $res->get();
        $res = $res ? $res->toArray() : [];

        $idx = 0;
        $goods = [];
        foreach ($res as $row) {
            if ($row['promote_price'] > 0) {
                $promote_price = $this->goodsCommonService->getBargainPrice($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
            } else {
                $promote_price = 0;
            }

            $goods[$idx]['id'] = $row['goods_id'];
            $goods[$idx]['name'] = $row['goods_name'];
            $goods[$idx]['comments_number'] = $row['comments_number'];
            $goods[$idx]['sales_volume'] = $row['sales_volume'];
            $goods[$idx]['short_name'] = $this->config['goods_name_length'] > 0 ?
                $this->dscRepository->subStr($row['goods_name'], $this->config['goods_name_length']) : $row['goods_name'];
            $goods[$idx]['market_price'] = $this->dscRepository->getPriceFormat($row['market_price']);
            $goods[$idx]['shop_price'] = $this->dscRepository->getPriceFormat($row['shop_price']);
            $goods[$idx]['promote_price'] = $promote_price > 0 ? $this->dscRepository->getPriceFormat($promote_price) : '';
            $goods[$idx]['brief'] = $row['goods_brief'];
            $goods[$idx]['thumb'] = $this->dscRepository->getImagePath($row['goods_thumb']);
            $goods[$idx]['goods_img'] = $this->dscRepository->getImagePath($row['goods_img']);
            $goods[$idx]['url'] = $this->dscRepository->buildUri('goods', ['gid' => $row['goods_id']], $row['goods_name']);
            $goods[$idx]['brand_id'] = $row['brand_id'];

            $idx++;
        }

        /* 分类信息 */
        $brand['id'] = $brand_id;
        $brand['name'] = Brand::where('brand_id', $brand_id)->value('brand_name');
        $brand['url'] = $this->dscRepository->buildUri('brand', ['bid' => $brand_id], $brand['name']);

        $brand_goods = ['brand' => $brand, 'goods' => $goods];

        return $brand_goods;
    }
}