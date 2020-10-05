<?php

namespace App\Api\Fourth\Controllers;

use App\Api\Foundation\Controllers\Controller;
use App\Services\Category\CategoryBrandService;
use App\Services\Category\CategoryGoodsService;
use App\Services\Category\CategoryService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Class CatalogController
 * @package App\Api\Fourth\Controllers
 */
class CatalogController extends Controller
{
    protected $categoryService;
    protected $categoryGoodsService;
    protected $categoryBrandService;

    public function __construct(
        CategoryService $categoryService,
        CategoryGoodsService $categoryGoodsService,
        CategoryBrandService $categoryBrandService
    )
    {
        $this->categoryService = $categoryService;
        $this->categoryGoodsService = $categoryGoodsService;
        $this->categoryBrandService = $categoryBrandService;
    }

    /**
     * 分类导航页
     * @param int $id
     * @return JsonResponse
     * @throws Exception
     */
    public function index($id = 0)
    {
        $data = $this->categoryService->getMobileCategoryChild($id);

        return $this->succeed($data);
    }

    /**
     * @param $catalog
     * @return JsonResponse
     * @throws Exception
     */
    public function show($catalog)
    {
        $data = $this->categoryService->getCategory($catalog);

        return $this->succeed($data);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function shopcat(Request $request)
    {
        $ru_id = $request->input('ru_id');

        $data = $this->categoryService->getShopCat(0, $ru_id);

        return $this->succeed($data);
    }

    /**
     * 分类商品列表
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function goodslist(Request $request)
    {
        $keywords = $request->input('keywords', []);
        $sc_ds = $request->input('sc_ds', '');
        $cat_id = $request->input('cat_id', 0);
        $intro = $request->input('intro', ''); // 推荐 精品 热门
        $brand = $request->input('brand', []);

        $price_min = $request->input('min', 0);
        $price_max = $request->input('max', 0);
        $price_min = floatval($price_min);
        $price_max = floatval($price_max);

        $ext = $request->input('ext', '');
        $self = $request->input('self', 0);
        $size = $request->input('size', 10);
        $page = $request->input('page', 1);
        $sort = $request->input('sort', 'goods_id');
        $order = $request->input('order', 'DESC');
        $filter_attr = $request->input('filter_attr');
        $goods_num = $request->input('goods_num', 0);

        $ship = $request->input('ship', 0); // 是否支持配送
        $promotion = $request->input('promotion', 0); // 是否促销
        $cou_id = $request->input('cou_id', 0); // 优惠券条件

        $ru_id = $request->input('ru_id', 0);

        $where_ext = [
            'warehouse_id' => $this->warehouse_id,
            'area_id' => $this->area_id,
            'area_city' => $this->area_city,
            'intro' => $intro,
            'ext' => $ext,
            'self' => $self,
            'ship' => $ship,
            'promotion' => $promotion,
            'cou_id' => $cou_id,
            'ru_id' => $ru_id,
            'sc_ds' => $sc_ds
        ];

        if ($cat_id == 0) {
            $children = 0;
        } else {
            $children = $this->categoryService->getCatListChildren($cat_id);
        }

        $data = $this->categoryGoodsService->getMobileCategoryGoodsList($this->uid, $keywords, $children, $brand, $price_min, $price_max, $filter_attr, $where_ext, $goods_num, $size, $page, $sort, $order);

        return $this->succeed($data);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function brandList(Request $request)
    {
        $cat_id = $request->input('cat_id', 0);

        $children = $this->categoryService->getCatListChildren($cat_id);

        $data = $this->categoryBrandService->getCategoryFilterBrandList($children);

        return $this->succeed($data);
    }
}
