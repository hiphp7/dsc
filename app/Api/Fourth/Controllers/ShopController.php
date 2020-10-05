<?php

namespace App\Api\Fourth\Controllers;

use App\Api\Foundation\Controllers\Controller;
use App\Services\Category\CategoryService;
use App\Services\Store\StoreStreetMobileService;
use Endroid\QrCode\Exception\InvalidPathException;
use Exception;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Class ShopController
 * @package App\Api\Fourth\Controllers
 */
class ShopController extends Controller
{
    protected $storeStreetMobileService;
    protected $categoryService;

    public function __construct(
        StoreStreetMobileService $storeStreetMobileService,
        CategoryService $categoryService
    )
    {
        $this->storeStreetMobileService = $storeStreetMobileService;
        $this->categoryService = $categoryService;
    }

    /**
     * 店铺分类列表
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function catList(Request $request)
    {
        $cat_id = $request->input('cat_id', 0);

        $data = $this->categoryService->getCategoryChild($cat_id);

        return $this->succeed($data);
    }

    /**
     * 分类店铺列表
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function catShopList(Request $request)
    {
        //验证参数
        $this->validate($request, [
            'lat' => 'numeric',
            'lng' => 'numeric'
        ]);

        $cat_id = $request->input('cat_id', 0);
        $city_id = $request->input('city_id', 0) ?? 0;
        $size = $request->input('size', 10);
        $page = $request->input('page', 1);
        $sort = $request->input('sort', 'goods_id');
        $order = $request->input('order', 'DESC');

        $lat = $request->get('lat', '31.22928');
        $lng = $request->get('lng', '121.40966');

        $data = $this->storeStreetMobileService->getCatStoreList($cat_id, $this->warehouse_id, $this->area_id, $this->area_city, $size, $page, $sort, $order, $this->uid, $lat, $lng, $city_id);

        return $this->succeed($data);
    }

    /**
     * 店铺商品列表
     *
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function shopGoodsList(Request $request)
    {
        $ru_id = $request->input('store_id', 0);
        $cat_id = $request->input('cat_id', 0);

        $size = $request->input('size', 10);
        $page = $request->input('page', 1);
        $sort = $request->input('sort', 'goods_id');
        $order = $request->input('order', 'DESC');
        $type = $request->input('type', '');

        $keywords = $request->input('keywords', '');
        $brand_id = $request->input('brand_id', 0);

        // 扩展其他字段
        $where_ext = [
            'warehouse_id' => $this->warehouse_id,
            'area_id' => $this->area_id,
            'area_city' => $this->area_city,
        ];
        // 筛选属性
        $filter_attr = [];

        if ($cat_id == 0) {
            $children = 0;
        } else {
            $children = $this->categoryService->getMerchantsCatListChildren($cat_id);
        }

        $data = $this->storeStreetMobileService->getStoreGoodsList($this->uid, $ru_id, $children, $keywords, $brand_id, $size, $page, $sort, $order, $filter_attr, $where_ext, $type);

        return $this->succeed($data);
    }

    /**
     * 店铺详情
     *
     * @param Request $request
     * @return JsonResponse
     * @throws InvalidPathException
     * @throws FileNotFoundException
     */
    public function shopDetail(Request $request)
    {
        $ru_id = $request->input('ru_id', 0);

        $user_id = $this->authorization();

        $data = $this->storeStreetMobileService->StoreDetail($ru_id, $user_id);

        if (isset($data['shop_qrcode_file']) && $data['shop_qrcode_file']) {
            // 同步镜像上传到OSS
            $this->ossMirror($data['shop_qrcode_file'], true);
        }

        return $this->succeed($data);
    }

    /**
     * 店铺品牌
     * @param Request $request
     * @return JsonResponse
     */
    public function shopBrand(Request $request)
    {
        $ru_id = $request->input('ru_id', 0);

        $data = $this->storeStreetMobileService->StoreBrand($ru_id);

        return $this->succeed($data);
    }

    /**
     * 附近地图
     * @param Request $request
     * @return JsonResponse
     */
    public function map(Request $request)
    {
        $lat = $request->get('lat', '31.22928');
        $lng = $request->get('lng', '121.40966');

        $data = $this->storeStreetMobileService->StoreMap($lat, $lng);

        return $this->succeed($data);
    }
}
