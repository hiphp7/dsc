<?php

namespace App\Api\Fourth\Controllers;

use App\Api\Foundation\Controllers\Controller;
use App\Models\CouponsUser;
use App\Repositories\Common\TimeRepository;
use App\Services\Activity\CouponsService;
use App\Services\Category\CategoryService;
use App\Services\Store\StoreStreetMobileService;
use Endroid\QrCode\Exception\InvalidPathException;
use Exception;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Class StoreController
 * @package App\Api\Fourth\Controllers
 */
class StoreController extends Controller
{
    protected $storeStreetMobileService;
    protected $categoryService;
    protected $couponsService;
    protected $timeRepository;

    public function __construct(
        StoreStreetMobileService $storeStreetMobileService,
        CategoryService $categoryService,
        CouponsService $couponsService,
        TimeRepository $timeRepository
    )
    {
        $this->storeStreetMobileService = $storeStreetMobileService;
        $this->categoryService = $categoryService;
        $this->couponsService = $couponsService;
        $this->timeRepository = $timeRepository;
    }

    /**
     * 店铺分类列表
     *
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
     *
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function catStoreList(Request $request)
    {
        $cat_id = $request->input('cat_id', 0);
        $size = $request->input('size', 10);
        $page = $request->input('page', 1);
        $sort = $request->input('sort', 'goods_id');
        $order = $request->input('order', 'DESC');

        $lat = $request->get('lat', '31.22928');
        $lng = $request->get('lng', '121.40966');
        $city_id = $request->get('city_id', 0);

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
    public function storeGoodsList(Request $request)
    {
        $ru_id = $request->input('store_id', 0);
        $cat_id = $request->input('cat_id', 0);
        $size = $request->input('size', 10);
        $page = $request->input('page', 1);
        $sort = $request->input('sort', 'goods_id');
        $order = $request->input('order', 'DESC');

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

        $data = $this->storeStreetMobileService->getStoreGoodsList($this->uid, $ru_id, $children, $keywords, $brand_id, $size, $page, $sort, $order, $filter_attr, $where_ext);

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
    public function storeDetail(Request $request)
    {
        $ru_id = $request->input('ru_id', 0);

        $uid = $this->authorization();

        $data = $this->storeStreetMobileService->StoreDetail($ru_id, $uid);

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
    public function storeBrand(Request $request)
    {
        $ru_id = $request->input('ru_id', 0);

        $data = $this->storeStreetMobileService->StoreBrand($ru_id);

        return $this->succeed($data);
    }

    /**
     * 店铺优惠券
     * @param Request $request
     * @return JsonResponse
     */
    public function storeCoupons(Request $request)
    {
        $ru_id = $request->get('ru_id', '');
        $user_id = $this->authorization();

        $cou_data = $this->couponsService->getCouponsList([1, 2, 3, 4, 5], '', 'cou_id', 'desc', 0, 10, []);

        if (!empty($cou_data) && isset($cou_data)) {

            foreach ($cou_data as $key => $value) {

                if ($value['ru_id'] != $ru_id) {
                    unset($cou_data[$key]);
                    continue;
                }
                $rec_id = CouponsUser::where('user_id', $user_id)->where('cou_id', $value['cou_id'])->value('uc_id');
                if (isset($rec_id) && !empty($rec_id)) {

                    unset($cou_data[$key]);
                    continue;
                }
                $cou_data[$key]['cou_start_time'] = $this->timeRepository->getLocalDate("Y-m-d", $value['cou_start_time']);
                $cou_data[$key]['cou_end_time'] = $this->timeRepository->getLocalDate("Y-m-d", $value['cou_end_time']);
            }
        }
        return $this->succeed($cou_data);
    }
}
