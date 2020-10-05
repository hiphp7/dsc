<?php

namespace App\Api\Fourth\Controllers;

use App\Api\Foundation\Controllers\Controller;
use App\Services\Visual\VisualService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Class VisualController
 * @package App\Api\Fourth\Controllers
 */
class VisualController extends Controller
{
    protected $visualService;

    public function __construct(
        VisualService $visualService
    )
    {
        $this->visualService = $visualService;
    }

    /**
     * APP
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $data = $this->visualService->Index();

        return $this->succeed($data);
    }

    /**
     * 默认
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function default(Request $request)
    {
        $ru_id = $request->input('ru_id', 0);
        $type = $request->input('type', 'index');

        $cache_id = md5(serialize($request->all()));
        $data = cache()->rememberForever('visual.default' . $cache_id, function () use ($ru_id, $type) {
            return $this->visualService->Default($ru_id, $type);
        });

        return $this->succeed($data);
    }

    /**
     * app广告
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function appnav(Request $request)
    {
        $data = $this->visualService->AppNav();

        return $this->succeed($data);
    }

    /**
     * 公告
     *
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function article(Request $request)
    {
        $cat_id = $request->input('cat_id', 0);
        $num = $request->input('num', 10);

        $cache_id = md5(serialize($request->all()));
        $data = cache()->rememberForever('visual.article' . $cache_id, function () use ($cat_id, $num) {
            return $this->visualService->Article($cat_id, $num);
        });

        return $this->succeed($data);
    }

    /**
     * 分类商品
     *
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function product(Request $request)
    {
        $number = $request->input('number', 10);
        $type = $request->input('type');
        $ru_id = $request->input('ru_id', 0);
        $cat_id = $request->input('cat_id', 0);
        $brand_id = $request->input('brand_id', 0);

        $cache_id = md5(serialize($request->all()));
        $data = cache()->rememberForever('visual.product' . $cache_id, function () use ($cat_id, $type, $ru_id, $number, $brand_id) {
            return $this->visualService->Product($this->uid, $cat_id, $type, $ru_id, $number, $brand_id, $this->warehouse_id, $this->area_id, $this->area_city);
        });

        return $this->succeed($data);
    }

    /**
     * 选中的商品
     *
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function checked(Request $request)
    {
        $goods_id = $request->input('goods_id', 0);
        $ru_id = $request->input('ru_id', 0);

        $cache_id = md5(serialize($request->all()));
        $data = cache()->rememberForever('visual.checked' . $cache_id, function () use ($goods_id, $ru_id) {
            return $this->visualService->Checked($goods_id, $ru_id, $this->warehouse_id, $this->area_id, $this->area_city, $this->uid);
        });

        return $this->succeed($data);
    }

    /**
     * 秒杀商品
     *
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function seckill(Request $request)
    {
        $number = 10;
        if ($request->exists('number')) {
            $number = (int)$request->input('number', 10);
        } elseif ($request->exists('num')) {
            $number = (int)$request->input('num', 10);
        }

        $data = $this->visualService->Seckill($number);

        return $this->succeed($data);
    }

    /**
     * 店铺街
     *
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function store(Request $request)
    {
        $childrenNumber = $request->input('childrenNumber', 0);
        $number = $request->input('number', 10);

        $data = $this->visualService->Store($childrenNumber, $number);

        return $this->succeed($data);
    }

    /**
     * 店铺街详情
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function storein(Request $request)
    {
        $ru_id = $request->input('ru_id', 0);

        $data = $this->visualService->StoreIn($ru_id, $this->uid);

        return $this->succeed($data);
    }

    /**
     * 店铺街底部
     *
     * @param int $id
     * @return JsonResponse
     * @throws Exception
     */
    public function storedown(Request $request)
    {
        $ru_id = $request->input('ru_id', 0);

        $cache_id = md5(serialize($request->all()));
        $data = cache()->rememberForever('visual.storedown' . $cache_id, function () use ($ru_id) {
            return $this->visualService->StoreDown($ru_id);
        });

        return $this->succeed($data);
    }

    /**
     * 店铺街关注
     *
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function addcollect(Request $request)
    {
        $ru_id = $request->input('ru_id', 0);

        if (!$this->uid) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $data = $this->visualService->AddCollect($ru_id, $this->uid);

        return $this->succeed($data);
    }

    /**
     * 显示页面
     *
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function view(Request $request)
    {
        $id = $request->input('id', 0);
        $type = $request->input('type', 'index');
        $default = $request->input('default', 0);
        $ru_id = $request->input('ru_id', 0);
        $number = $request->input('number', 10);
        $page_id = $request->input('page_id', 0);
        $cache_id = md5(serialize($request->input('id', 0)));
        $data = cache()->rememberForever('visual.view' . $cache_id, function () use ($id, $type, $default, $ru_id, $number, $page_id) {
            return $this->visualService->View($id, $type, $default, $ru_id, $number, $page_id);
        });

        return $this->succeed($data);
    }
}
