<?php

namespace App\Services\Brand;

use App\Extensions\Pinyin;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Goods;
use App\Models\GoodsCat;
use App\Models\Products;
use App\Models\ProductsArea;
use App\Models\ProductsWarehouse;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Goods\GoodsApiService;
use App\Services\Goods\GoodsCommonService;
use App\Services\Goods\GoodsMobileService;
use App\Services\User\UserCommonService;
use App\Services\Visual\VisualService;

/**
 * 商城品牌
 *
 * Class BrandMobileService
 * @package App\Services\Brand
 */
class BrandMobileService
{
    protected $goodsMobileService;
    protected $goodsApiService;
    protected $pinyin;
    protected $visualService;
    protected $userCommonService;
    protected $timeRepository;
    protected $dscRepository;
    protected $goodsCommonService;
    protected $config;

    public function __construct(
        GoodsMobileService $goodsMobileService,
        GoodsApiService $goodsApiService,
        Pinyin $pinyin,
        VisualService $visualService,
        UserCommonService $userCommonService,
        TimeRepository $timeRepository,
        DscRepository $dscRepository,
        GoodsCommonService $goodsCommonService
    )
    {
        $this->goodsMobileService = $goodsMobileService;
        $this->goodsApiService = $goodsApiService;
        $this->pinyin = $pinyin;
        $this->visualService = $visualService;
        $this->userCommonService = $userCommonService;
        $this->timeRepository = $timeRepository;
        $this->dscRepository = $dscRepository;
        $this->goodsCommonService = $goodsCommonService;
        $this->config = $this->dscRepository->dscConfig();
    }

    /**
     * 品牌首页
     *
     * @param int $num
     * @return array
     */
    public function index($num = 20)
    {
        $brand = Brand::where('is_show', 1)
            ->where('is_delete', 0)
            ->where('audit_status', 1)
            ->orderBy('sort_order', 'ASC')
            ->orderBy('brand_id', 'DESC')
            ->limit($num)
            ->get();
        $brand = $brand ? $brand->toArray() : [];

        if (empty($brand)) {
            return [];
        }

        $res = [];
        foreach ($brand as $key => $val) {
            if ($key == 0) {
                $res['best'] = $val;
                $time = $this->timeRepository->getGmTime();
                $res['best']['time'] = $this->timeRepository->getLocalDate('m-d', $time);
                $res['best']['brand_logo'] = $this->dscRepository->getImagePath('data/brandlogo/' . $val['brand_logo']);
                $res['best']['index_img'] = $this->dscRepository->getImagePath('data/indeximg/' . $val['index_img']);
            } elseif ($key > 0 && $key < 5) {
                $res['hot'][$key] = $val;
                $res['hot'][$key]['brand_logo'] = $this->dscRepository->getImagePath('data/brandlogo/' . $val['brand_logo']);
                $res['hot'][$key]['index_img'] = $this->dscRepository->getImagePath('data/indeximg/' . $val['index_img']);
                $res['hot'][$key]['goods_count'] = $this->BrandGoodsCount($val['brand_id']);
                $res['hot'][$key]['goods'] = $this->BrandGoods($val['brand_id']);
                $res['hot'][$key]['brand_msg'] = [];
                $res['hot'][$key]['brand_msg']['brand_name'] = $val['brand_name'];
                $res['hot'][$key]['brand_msg']['brand_logo'] = $this->dscRepository->getImagePath('data/brandlogo/' . $val['brand_logo']);
                $res['hot'][$key]['brand_msg']['brand_id'] = $val['brand_id'];
                $res['hot'][$key]['brand_msg']['goods_count'] = $res['hot'][$key]['goods_count'];
            } else {
                $res['recommend'][$key] = $val;
                $res['recommend'][$key]['brand_logo'] = $this->dscRepository->getImagePath('data/brandlogo/' . $val['brand_logo']);
                $res['recommend'][$key]['index_img'] = $this->dscRepository->getImagePath('data/indeximg/' . $val['index_img']);
            }
        }
        $res['hot'] = array_merge($res['hot']);
        $res['recommend'] = array_merge($res['recommend']);

        return $res;
    }

    /**
     * 品牌详情
     *
     * @param int $brand_id
     * @param int $cat_id
     * @return array
     */
    public function detail($brand_id, $cat_id = 0)
    {
        $brand = Brand::where('brand_id', $brand_id)
            ->first();
        $brand = $brand ? $brand->toArray() : [];

        if (empty($brand)) {
            return [];
        }

        $res = [];
        $res['category'] = $this->BrandCate($brand_id);
        $res['all_goods'] = $this->BrandGoodsCount($brand_id, $cat_id);
        $res['new_goods'] = $this->BrandGoodsCount($brand_id, $cat_id, 'new');
        $res['hot_goods'] = $this->BrandGoodsCount($brand_id, $cat_id, 'hot');
        $res['goods_count'] = $res['all_goods'];

        $res['brand_msg'] = [];
        if ($brand) {
            $res['brand_msg']['brand_id'] = $brand['brand_id'];
            $res['brand_msg']['brand_name'] = $brand['brand_name'];
            $res['brand_msg']['brand_logo'] = $this->dscRepository->getImagePath('data/brandlogo/' . $brand['brand_logo']);
            $res['brand_msg']['goods_count'] = $res['goods_count'];
        }

        return $res;
    }

    /**
     * 获得品牌下的商品
     *
     * @param $uid
     * @param int $brand_id
     * @param int $cat_id
     * @param string $type
     * @param int $size
     * @param int $page
     * @param string $sort
     * @param string $order
     * @param array $ext
     * @return array
     */
    public function BrandGoodsList($uid, $brand_id = 0, $cat_id = 0, $type = '', $size = 10, $page = 1, $sort = '', $order = 'DESC', $ext = [])
    {

        $warehouse_id = $ext['warehouse_id'] ?? 0;
        $area_id = $ext['area_id'] ?? 0;
        $area_city = $ext['area_city'] ?? 0;

        if ($cat_id == 0) {
            $children = 0;
        } else {
            $children = $this->visualService->get_children($cat_id);
        }

        $extension_goods = [];
        /* 查询扩展分类数据 */
        if ($children) {
            $extension_goods = GoodsCat::select('goods_id')->whereIn('cat_id', $children)->get();
            $extension_goods = $extension_goods ? $extension_goods->toArray() : [];
            $extension_goods = isset($extension_goods) ? collect($extension_goods)->flatten()->all() : [];
        }

        /* 查询分类商品数据 */
        $res = Goods::where('is_on_sale', 1)
            ->where('is_alone_sale', 1)
            ->where('is_delete', 0)
            ->where('brand_id', $brand_id)
            ->where('review_status', '>', 2);

        $goodsParam = [
            'children' => $children,
            'extension_goods' => $extension_goods
        ];

        // 子分类 或 扩展分类
        $res = $res->where(function ($query) use ($goodsParam) {
            if (isset($goodsParam['children']) && $goodsParam['children']) {
                $query = $query->whereIn('cat_id', $goodsParam['children']);
            }
            if (isset($goodsParam['extension_goods']) && $goodsParam['extension_goods']) {
                $query->orWhere(function ($query) use ($goodsParam) {
                    $query->whereIn('goods_id', $goodsParam['extension_goods']);
                });
            }
        });

        $res = $this->dscRepository->getAreaLinkGoods($res, $area_id, $area_city);

        $where = [
            'area_id' => $area_id,
            'area_city' => $area_city,
            'area_pricetype' => $this->config['area_pricetype']
        ];

        if ($uid > 0) {
            $rank = $this->userCommonService->getUserRankByUid($uid);
            $user_rank = $rank['rank_id'];
            $user_discount = $rank['discount'];
        } else {
            $user_rank = 1;
            $user_discount = 100;
        }

        $res = $res->with([
            'getMemberPrice' => function ($query) use ($user_rank) {
                $query->where('user_rank', $user_rank);
            },
            'getWarehouseGoods' => function ($query) use ($warehouse_id) {
                $query->where('region_id', $warehouse_id);
            },
            'getWarehouseAreaGoods' => function ($query) use ($where) {
                $query = $query->where('region_id', $where['area_id']);

                if ($where['area_pricetype'] == 1) {
                    $query->where('city_id', $where['area_city']);
                }
            },
            'getBrand',
            'getShopInfo'
        ]);

        if ($type == 'new') {
            $res = $res->where('is_new', 1);
        }
        if ($type == 'hot') {
            $res = $res->where('is_hot', 1);
        }
        if ($type == 'best') {
            $res = $res->where('is_best', 1);
        }

        $start = ($page - 1) * $size;

        if ($sort) {
            $res = $res->orderBy($sort, $order);
        }

        $res = $res->skip($start)
            ->take($size)
            ->orderBy('sort_order', 'ASC')
            ->get();

        $res = $res ? $res->toArray() : [];

        $arr = [];
        if ($res) {
            foreach ($res as $k => $row) {
                $price = [
                    'model_price' => isset($row['model_price']) ? $row['model_price'] : 0,
                    'user_price' => isset($row['get_member_price']['user_price']) ? $row['get_member_price']['user_price'] : 0,
                    'percentage' => isset($row['get_member_price']['percentage']) ? $row['get_member_price']['percentage'] : 0,
                    'warehouse_price' => isset($row['get_warehouse_goods']['warehouse_price']) ? $row['get_warehouse_goods']['warehouse_price'] : 0,
                    'region_price' => isset($row['get_warehouse_area_goods']['region_price']) ? $row['get_warehouse_area_goods']['region_price'] : 0,
                    'shop_price' => isset($row['shop_price']) ? $row['shop_price'] : 0,
                    'warehouse_promote_price' => isset($row['get_warehouse_goods']['warehouse_promote_price']) ? $row['get_warehouse_goods']['warehouse_promote_price'] : 0,
                    'region_promote_price' => isset($row['get_warehouse_area_goods']['region_promote_price']) ? $row['get_warehouse_area_goods']['region_promote_price'] : 0,
                    'promote_price' => isset($row['promote_price']) ? $row['promote_price'] : 0,
                    'wg_number' => isset($row['get_warehouse_goods']['region_number']) ? $row['get_warehouse_goods']['region_number'] : 0,
                    'wag_number' => isset($row['get_warehouse_area_goods']['region_number']) ? $row['get_warehouse_area_goods']['region_number'] : 0,
                    'goods_number' => isset($row['goods_number']) ? $row['goods_number'] : 0
                ];

                $price = $this->goodsCommonService->getGoodsPrice($price, $user_discount / 100, $row);

                $row['shop_price'] = $price['shop_price'];
                $row['promote_price'] = $price['promote_price'];
                $row['goods_number'] = $price['goods_number'];

                //$arr[$k] = $row;

                $arr[$k]['model_price'] = $row['model_price'];

                if ($row['promote_price'] > 0) {
                    $promote_price = $this->goodsCommonService->getBargainPrice($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
                } else {
                    $promote_price = 0;
                }

                $arr[$k]['sort_order'] = $row['sort_order'];

                $arr[$k]['goods_id'] = $row['goods_id'];
                $arr[$k]['goods_name'] = $row['goods_name'];
                $arr[$k]['name'] = $row['goods_name'];
                $arr[$k]['goods_brief'] = $row['goods_brief'];
                $arr[$k]['sales_volume'] = $row['sales_volume'];
                $arr[$k]['is_promote'] = $row['is_promote'];

                $arr[$k]['market_price'] = $row['market_price'];
                $arr[$k]['market_price_format'] = $this->dscRepository->getPriceFormat($row['market_price']);
                $arr[$k]['shop_price'] = $row['shop_price'];
                $arr[$k]['shop_price_format'] = $this->dscRepository->getPriceFormat($row['shop_price']);
                $arr[$k]['type'] = $row['goods_type'];
                $arr[$k]['promote_price'] = ($promote_price > 0) ? $this->dscRepository->getPriceFormat($promote_price) : '';
                $arr[$k]['goods_thumb'] = $this->dscRepository->getImagePath($row['goods_thumb']);
                $arr[$k]['goods_img'] = $this->dscRepository->getImagePath($row['goods_img']);

                $arr[$k]['self_run'] = $row['get_shop_info'] ? $row['get_shop_info']['self_run'] : 0;

                //ecmoban模板堂 --zhuo start
                if ($row['model_attr'] == 1) {
                    $prod = ProductsWarehouse::where('goods_id', $row['goods_id'])->where('warehouse_id', $warehouse_id)->first();
                    $prod = $prod ? $prod->toArray() : [];
                } elseif ($row['model_attr'] == 2) {
                    $prod = ProductsArea::where('goods_id', $row['goods_id'])->where('area_id', $area_id)->first();
                    $prod = $prod ? $prod->toArray() : [];
                } else {
                    $prod = Products::where('goods_id', $row['goods_id'])->first();
                    $prod = $prod ? $prod->toArray() : [];
                }

                if (empty($prod)) { //当商品没有属性库存时
                    $arr[$k]['prod'] = 1;
                } else {
                    $arr[$k]['prod'] = 0;
                }

                $arr[$k]['goods_number'] = $row['goods_number'];
            }
        }

        return $arr;
    }

    /**
     * 品牌下商品数量
     *
     * @param $brand_id
     * @param int $cat_id
     * @param string $type
     * @return int
     */
    public function BrandGoodsCount($brand_id, $cat_id = 0, $type = '')
    {
        $res = Goods::where('brand_id', $brand_id)
            ->where('is_delete', 0)
            ->where('is_on_sale', 1)
            ->where('is_alone_sale', 1)
            ->where('review_status', '>', 2);

        if ($cat_id > 0) {
            $children = $this->visualService->get_children($cat_id);
            $res = $res->whereIn('cat_id', $children);
        }

        if ($type == 'new') {
            $res = $res->where('is_new', 1);
        }
        if ($type == 'hot') {
            $res = $res->where('is_hot', 1);
        }
        if ($type == 'best') {
            $res = $res->where('is_best', 1);
        }

        $res = $res->count() ? $res->count() : 0;

        return $res;
    }


    /**
     * 首页品牌下商品简易显示
     *
     * @param $brand_id
     * @param int $num
     * @return int
     */
    public function BrandGoods($brand_id, $num = 3)
    {
        $res = Goods::select('goods_id', 'goods_name', 'goods_thumb')
            ->where('brand_id', $brand_id)
            ->where('is_delete', 0)
            ->where('is_on_sale', 1)
            ->where('is_alone_sale', 1)
            ->where('review_status', '>', 2)
            ->orderBy('goods_id', 'DESC')
            ->limit($num)
            ->get();
        $res = $res ? $res->toArray() : 0;

        foreach ($res as $k => $val) {
            $res[$k]['goods_thumb'] = $this->dscRepository->getImagePath($val['goods_thumb']);
        }

        return $res;
    }

    /**
     * 获得与指定品牌相关的分类
     *
     * @param $brand_id
     * @return array
     */
    public function BrandCate($brand_id)
    {
        $arr[] = [
            'cat_id' => 0,
            'cat_name' => '所有分类'
        ];

        $res = Category::whereRaw(1);

        $res = $res->whereHas('getGoods', function ($query) use ($brand_id) {
            $query->where('brand_id', $brand_id);
        });

        $res = $res->orderBy('cat_id');

        $res = $res->get();

        $res = $res ? $res->toArray() : [];

        if ($res) {
            foreach ($res as $row) {
                $arr[] = $row;
            }
        }

        return $arr;
    }

    /**
     * 获得品牌列表
     *
     * @return array
     */
    public function get_brands_letter()
    {
        $res = Brand::select('brand_id', 'brand_name', 'brand_logo', 'brand_first_char')
            ->where('is_show', 1)
            ->get();

        $res = $res ? $res->toArray() : [];

        $arr = [];
        foreach ($res as $row) {
            $brand['brand_id'] = $row['brand_id'];
            $brand['brand_name'] = trim($row['brand_name']);
            $brand['brand_logo'] = $this->dscRepository->getImagePath('data/brandlogo/' . $row['brand_logo']);
            $first = !empty($row['brand_first_char']) ? $row['brand_first_char'] : $this->getLetter($brand['brand_name']);
            $arr[$first]['info'] = $first ? $first : 'A';
            $arr[$first]['list'][] = $brand;
        }

        if ($arr) {
            ksort($arr);
            $arr = $this->arr2map($arr);
        }

        return $arr;
    }

    /*重新排列数组*/
    public function arr2map($arr)
    {
        $m = array();
        foreach ($arr as $o) {
            $m[] = $o;
        }
        return $m;
    }

    /**
     * 获取首字母
     *
     * @param $str
     * @return string
     */
    public function getLetter($str)
    {
        $i = 0;
        while ($i < strlen($str)) {
            $tmp = bin2hex(substr($str, $i, 1));
            if ($tmp >= 'B0') { //汉字
                $pyobj = $this->pinyin->output($str);
                $pinyin = isset($pyobj[0]) ? $pyobj[0] : '';
                return strtoupper(substr($pinyin, 0, 1));
                $i += 2;
            } else {
                return strtoupper(substr($str, $i, 1));
                $i++;
            }
        }
    }
}
