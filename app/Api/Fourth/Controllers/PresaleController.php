<?php

namespace App\Api\Fourth\Controllers;

use App\Api\Foundation\Controllers\Controller;
use App\Libraries\Shop;
use App\Models\Cart;
use App\Models\CollectGoods;
use App\Models\GoodsAttr;
use App\Models\GoodsGallery;
use App\Models\MerchantsShopInformation;
use App\Models\OrderInfo;
use App\Models\PresaleCat;
use App\Models\Products;
use App\Models\ProductsArea;
use App\Models\ProductsWarehouse;
use App\Models\Region;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\SessionRepository;
use App\Services\Activity\PresaleService;
use App\Services\Cart\CartCommonService;
use App\Services\Comment\CommentService;
use App\Services\Common\AreaService;
use App\Services\Goods\GoodsAttrService;
use App\Services\Goods\GoodsMobileService;
use App\Services\Goods\GoodsService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Class PresaleController
 * @package App\Api\Fourth\Controllers
 */
class PresaleController extends Controller
{
    protected $presaleService;
    protected $goodsMobileService;
    protected $goodsService;
    protected $dscRepository;
    protected $goodsAttrService;
    protected $commentService;
    protected $sessionRepository;
    protected $shop;
    protected $area;
    protected $config;
    private $goods_id;
    protected $cartCommonService;

    public function __construct(
        PresaleService $presaleService,
        Shop $shop,
        GoodsAttrService $goodsAttrService,
        GoodsMobileService $goodsMobileService,
        AreaService $area,
        GoodsService $goodsService,
        DscRepository $dscRepository,
        CommentService $commentService,
        SessionRepository $sessionRepository,
        CartCommonService $cartCommonService
    )
    {
        $this->presaleService = $presaleService;
        $this->shop = $shop;
        $this->goodsAttrService = $goodsAttrService;
        $this->area = $area;
        $this->goodsMobileService = $goodsMobileService;
        $this->goodsService = $goodsService;
        $this->dscRepository = $dscRepository;
        $this->commentService = $commentService;
        $this->sessionRepository = $sessionRepository;
        $this->config = $this->dscRepository->dscConfig();
        $this->cartCommonService = $cartCommonService;
    }

    protected function initialize()
    {
        parent::initialize();

        $files = [
            'clips',
            'common',
            'time',
            'main',
            'function',
            'ecmoban',
            'order',
        ];

        //加载语言包
        $this->dscRepository->helpersLang('presale');

        load_helper($files);
    }

    /**
     * 预售聚合页
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function index(Request $request)
    {
        //数据验证
        $this->validate($request, []);

        $pre_goods = $this->presaleService->getPreCat();

        return $this->succeed($pre_goods);
    }

    /**
     * 预售列表页
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function list(Request $request)
    {
        $cat_id = $request->get('cat_id', 0);
        $sort = $request->get('sort', 'act_id');
        $status = $request->get('status', 0);
        $order = $request->get('order', 'DESC');
        $keywords = $request->get('keywords', '');

        $res['pre_goods'] = $this->presaleService->getPreGoods([$cat_id], 0, 0, 0, 0, $sort, $status, $order, $keywords);

        // 预售分类
        $cat_res = PresaleCat::where('parent_id', 0)->get();

        $page_title = '';
        if ($cat_res) {
            foreach ($cat_res as $key => $row) {
                if (stristr($cat_id, $row['cat_id'])) {
                    $cat_res[$key]['selected'] = 1;
                    $page_title .= $cat_res[$key]['cat_name'];
                }
                $cat_res[$key]['goods'] = $this->presaleService->getCatGoods($row['cat_id']);
                $cat_res[$key]['count_goods'] = count($this->presaleService->getCatGoods($row['cat_id']));
            }
        }

        $page_title .= lang('presale.presale_title');

        $res['pre_cat'] = $cat_res;
        $res['cat_id'] = $cat_id;
        $res['page_title'] = $page_title;

        return $this->succeed($res);
    }

    /**
     * 预售详情
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function detail(Request $request)
    {
        $result = [];
        $preid = $request->get('act_id', 0);

        $presale = $this->presaleService->presaleInfo($preid, 1, $this->uid);
        if (empty($presale) || $preid <= 0) {
            $result['error'] = 1;
            $result['message'] = lang('presale.presale_goods_not_exist');
            return $this->failed($result);
        }

        $now = gmtime();
        $presale['gmt_end_date'] = local_strtotime($presale['end_time']);
        $presale['gmt_start_date'] = local_strtotime($presale['start_time']);
        if ($presale['gmt_start_date'] >= $now) {
            $presale['no_start'] = 1;
        }
        $result['presale'] = $presale;

        /* 取得预售商品信息 */
        $this->goods_id = $presale['goods_id'];

        $arr = [
            'goods_id' => $this->goods_id,
            'warehouse_id' => $this->warehouse_id,
            'area_id' => $this->area_id,
            'area_city' => $this->area_city,
            'uid' => $this->uid
        ];

        $goods = $this->goodsMobileService->getGoodsInfo($arr);

        if (empty($goods)) {
            $result['error'] = 1;
            $result['message'] = lang('presale.presale_goods_not_exist');
            return $this->failed($result);
        }

        $basic_info = isset($goods['get_seller_shop_info']) && $goods['get_seller_shop_info'] ? $goods['get_seller_shop_info'] : [];

        // 预售销量
        $res = OrderInfo::where('extension_id', $preid)->count();
        if ($res) {
            $goods['sales_volume'] = $res;
        } else {
            $goods['sales_volume'] = 0;
        }

        // 检查是否已经存在于用户的收藏夹
        if ($this->uid) {
            $rs = CollectGoods::where('user_id', $this->uid)->where('goods_id', $this->goods_id)->count();
            if ($rs > 0) {
                $result['goosd_collect'] = 1;
            }
        }

        $goods['goods_desc'] = $presale['act_desc'] ?? $goods['goods_desc'];
        $result['goods'] = $goods;
        $result['type'] = 0;

        //评分 start
        $comment_all = $this->commentService->getCommentsPercent($this->goods_id);

        if ($goods['user_id'] > 0) {
            //商家所有商品评分类型汇总
            $merchants_goods_comment = $this->commentService->getMerchantsGoodsComment($goods['user_id']);
            $result['merch_cmt'] = $merchants_goods_comment;
        }
        $result['comment_all'] = $comment_all;

        //查询一条好评
        $result['goods_id'] = $this->goods_id;

        if ($basic_info) {
            $basic_info['ru_id'] = $basic_info['ru_id'];
            $basic_info['province'] = $basic_info['province'];
            $basic_info['city'] = $basic_info['city'];
            $basic_info['kf_type'] = $basic_info['kf_type'];
            $basic_info['kf_ww'] = $basic_info['kf_ww'];
            $basic_info['kf_qq'] = $basic_info['kf_qq'];
            $basic_info['meiqia'] = $basic_info['meiqia'];
            $basic_info['shop_name'] = $basic_info['shop_name'];
            $basic_info['kf_appkey'] = $basic_info['kf_appkey'];
        } else {
            $basic_info['ru_id'] = 0;
            $basic_info['province'] = '';
            $basic_info['city'] = '';
            $basic_info['kf_type'] = '';
            $basic_info['kf_ww'] = '';
            $basic_info['kf_qq'] = '';
            $basic_info['meiqia'] = '';
            $basic_info['shop_name'] = '';
            $basic_info['kf_appkey'] = '';
        }

        // 商家客服
        $basic_info['is_IM'] = MerchantsShopInformation::where('user_id', $goods['user_id'])->value('is_IM');
        $basic_info['is_IM'] = $basic_info['is_IM'] ? $basic_info['is_IM'] : 0;

        $info_ww = $basic_info['kf_ww'] ? explode("\r\n", $basic_info['kf_ww']) : '';
        $info_qq = $basic_info['kf_qq'] ? explode("\r\n", $basic_info['kf_qq']) : '';
        $kf_ww = $info_ww ? $info_ww[0] : '';
        $kf_qq = $info_qq ? $info_qq[0] : '';
        $basic_ww = $kf_ww ? explode('|', $kf_ww) : '';
        $basic_qq = $kf_qq ? explode('|', $kf_qq) : '';
        $basic_info['kf_ww'] = $basic_ww ? $basic_ww[1] : '';
        $basic_info['kf_qq'] = $basic_qq ? $basic_qq[1] : '';
        if (($basic_info['is_IM'] == 1 || $basic_info['ru_id'] == 0) && !empty($basic_info['kf_appkey'])) {
            $basic_info['kf_appkey'] = $basic_info['kf_appkey'];
        } else {
            $basic_info['kf_appkey'] = '';
        }

        $result['basic_info'] = $basic_info;

        if ($basic_info) {
            $province = Region::where('region_id', $basic_info['province'])->value('region_name');
            $province = $province ? $province : '';

            $city = Region::where('region_id', $basic_info['city'])->value('region_name');
            $city = $city ? $city : '';

            $result['basic_info']['province_name'] = $province;
            $result['basic_info']['city_name'] = $city;
        }

        $properties = $this->goodsAttrService->getGoodsProperties($this->goods_id, $this->warehouse_id, $this->area_id, $this->area_city);  // 获得商品的规格和属性

        $result['properties'] = $properties['pro'];                              // 商品属性
        //默认选中的商品规格 by wanglu
        $default_spe = '';
        if ($properties['spe']) {
            foreach ($properties['spe'] as $k => $v) {
                if ($v['attr_type'] == 1) {
                    if ($v['is_checked'] > 0) {
                        foreach ($v['values'] as $key => $val) {
                            $default_spe .= $val['checked'] ? $val['label'] . '、' : '';
                        }
                    } else {
                        foreach ($v['values'] as $key => $val) {
                            if ($key == 0) {
                                $default_spe .= $val['label'] . '、';
                            }
                        }
                    }
                }
            }
        }
        $result['default_spe'] = $default_spe;                              // 商品规格
        $result['specification'] = $properties['spe'];                              // 商品规格

        //获取商品的相册
        $goods_img = GoodsGallery::where('goods_id', $this->goods_id)->get();
        $goods_img = $goods_img ? $goods_img->toArray() : [];

        if ($goods_img) {
            foreach ($goods_img as $key => $val) {
                $goods_img[$key]['img_url'] = $this->dscRepository->getImagePath($val['img_url']);
                $goods_img[$key]['img_original'] = $this->dscRepository->getImagePath($val['img_original']);
                $goods_img[$key]['thumb_url'] = $this->dscRepository->getImagePath($val['thumb_url']);
            }
        }

        $result['goods_img'] = $goods_img;

        //ecmoban模板堂 --zhuo 仓库 start
        $result['province_row'] = Region::where('region_id', $this->province_id)->value('region_name');
        $result['city_row'] = Region::where('region_id', $this->city_id)->value('region_name');
        $result['district_row'] = Region::where('region_id', $this->district_id)->value('region_name');

        $goods_region['country'] = 1;
        $goods_region['province'] = $this->province_id;
        $goods_region['city'] = $this->city_id;
        $goods_region['district'] = $this->district_id;
        $result['district_row'] = $goods_region;

        //猜你喜欢
        $arr = [
            'type' => 'best',
            'warehouse_id' => $this->warehouse_id,
            'area_id' => $this->area_id,
            'area_city' => $this->area_city,
            'seller_id' => $goods['user_id'],
            'rec_type' => 1,
            'presale' => 'presale',
        ];
        $best_arr = array_merge($arr, ['type' => 'best']);
        $new_arr = array_merge($arr, ['type' => 'new']);
        $hot_arr = array_merge($arr, ['type' => 'hot']);

        $result['best_goods'] = $this->goodsMobileService->getRecommendGoods($best_arr);// 推荐商品
        $result['new_goods'] = $this->goodsMobileService->getRecommendGoods($new_arr);// 最新商品
        $result['hot_goods'] = $this->goodsMobileService->getRecommendGoods($hot_arr);// 热卖商品

        if ($goods['user_id'] > 0) {
            $shop_info = get_merchants_shop_info($goods['user_id']);
            if (!empty($shop_info)) {
                $adress = get_license_comp_adress($shop_info['license_comp_adress']);
                $result['shop_info'] = $shop_info;
                $result['adress'] = $adress;
            }
        }

        $province_list = $this->area->getWarehouseProvince();
        $result['province_list'] = $province_list; //省、直辖市

        $city_list = $this->area->getRegionCityCounty($this->province_id);
        if ($city_list) {
            foreach ($city_list as $k => $v) {
                $city_list[$k]['district_list'] = $this->area->getRegionCityCounty($v['region_id']);
            }
        }
        $result['city_list'] = $city_list; //省、直辖市

        $district_list = $this->area->getRegionCityCounty($this->city_id);
        $result['district_list'] = $district_list; //市下级县

        $result['goods_id'] = $this->goods_id; //商品ID
        $result['user_id'] = $this->uid;
        $result['shop_price_type'] = $goods['model_price']; //商品价格运营模式 0代表统一价格（默认） 1、代表仓库价格 2、代表地区价格
        $result['region_id'] = $this->warehouse_id; //仓库ID
        $result['area_id'] = $this->area_id; //地区ID

        $warehouse_list = get_warehouse_list_goods();
        $result['warehouse_list'] = $warehouse_list; //仓库列

        $warehouse_name = get_warehouse_name_id($this->warehouse_id);
        $result['warehouse_name'] = $warehouse_name; //仓库名称

        $area = [
            'region_id' => $this->warehouse_id,  //仓库ID
            'province_id' => $this->province_id,
            'city_id' => $this->city_id,
            'district_id' => $this->district_id,
            'goods_id' => $this->goods_id,
            'user_id' => $this->uid,
            'area_id' => $this->area_id,  //地区ID
            'merchant_id' => $goods['user_id'],
        ];
        $result['area'] = $area;
        $result['cfg'] = $this->config;
        $result['current_time'] = $now;

        //查询关联商品描述 start
        if (empty($goods['desc_mobile']) && empty($goods['goods_desc'])) {
            $GoodsDesc = $this->goodsMobileService->getLinkGoodsDesc($goods['goods_id'], $goods['user_id']);
            $link_desc = $GoodsDesc ? $GoodsDesc['goods_desc'] : '';

            if (!empty($link_desc)) {
                $result['goods_desc'] = $link_desc;
            }
        }
        //查询关联商品描述 end

        return $this->succeed($result);
    }

    /**
     * 预售商品价格
     * @param Request $request
     * @return JsonResponse
     */
    public function price(Request $request)
    {
        $attr = $request->get('attr');
        $number = $request->get('number', 1);
        $goods_id = $request->get('gid', 0);
        $presale_id = $request->get('act_id', 0);
        $attr_id = is_array($attr) && !empty($attr) ? $attr : (!empty($attr) ? explode(',', $attr) : []);

        $data = $this->goodsMobileService->goodsPropertiesPrice($this->uid, $goods_id, $attr_id, $number, $this->warehouse_id, $this->area_id, $this->area_city);

        if (empty($data)) {
            return $this->responseNotFound();
        }

        $presale = $this->presaleService->presaleInfo($presale_id, $number);

        $data['formated_deposit'] = $presale['formated_deposit'];
        $data['formated_final_payment'] = $presale['formated_final_payment'];

        $data['formated_final_payment_new'] = price_format($data['goods_price'] - $presale['deposit']);

        return $this->succeed($data);
    }

    /**
     * 商品购买
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function buy(Request $request)
    {
        $result = [];
        $province_id = $request->get("province_id", 0);
        $presale_id = $request->get("act_id", 0);
        $number = $request->get("number", 1);

        if (empty($this->uid)) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        if ($presale_id <= 0) {
            return $this->responseNotFound();
        }

        /* 查询：取得数量 */
        $number = $number < 1 ? 1 : $number;
        /* 查询：取得预售活动信息 */
        $presale = $this->presaleService->presaleInfo($presale_id, $number);

        if (empty($presale)) {
            $result['error'] = 1;
            $result['message'] = lang('presale.presale_goods_not_exist');
            return $this->succeed($result);
        }

        /* 查询：检查预售活动是否是进行中 */
        if ($presale['status'] != GBS_UNDER_WAY) {
            $result['error'] = 1;
            $result['message'] = lang('common.presale_error_status');
            return $this->succeed($result);
        }

        $arr = [
            'goods_id' => $presale['goods_id'],
            'warehouse_id' => $this->warehouse_id,
            'area_id' => $this->area_id,
            'area_city' => $this->area_city,
            'uid' => $this->uid
        ];

        $goods = $this->goodsMobileService->getGoodsInfo($arr);//20180809

        /* 查询：取得预售商品信息 */
        if (empty($goods)) {
            $result['error'] = 1;
            $result['message'] = lang('presale.presale_goods_not_exist');
            return $this->succeed($result);
        }

        /* 查询：判断数量是否足够 */
        if (($goods['goods_number'] > 0 && $number > ($goods['goods_number'] - $presale['valid_goods']))) {
            $result['error'] = 1;
            $result['message'] = lang('common.gb_error_goods_lacking');
            return $this->succeed($result);
        }

        /* 查询：取得规格 */
        $specs = $request->get("goods_spec", '');
        $_specs = '';
        /* 查询：如果商品有规格则取规格商品信息 配件除外 */
        if ($specs) {
            $_specs = is_array($specs) ? implode(',', $specs) : $specs;
            $product_info = $this->goodsAttrService->getProductsInfo($goods['goods_id'], $specs, $this->warehouse_id, $this->area_id, $this->area_city);
        }

        empty($product_info) ? $product_info = ['product_number' => 0, 'product_id' => 0] : '';

        if ($goods['model_attr'] == 1) {
            $prod = ProductsWarehouse::where('goods_id', $goods['goods_id'])->where('warehouse_id', $this->warehouse_id)->first();
        } elseif ($goods['model_attr'] == 2) {
            $prod = ProductsArea::where('goods_id', $goods['goods_id'])->where('area_id', $this->area_id);

            if ($this->config['area_pricetype'] == 1) {
                $prod = $prod->where('city_id', $this->area_city);
            }

            $prod = $prod->first();
        } else {
            $prod = Products::where('goods_id', $goods['goods_id'])->first();
        }
        $prod = $prod ? $prod->toArray() : [];

        /* 检查：库存 */
        if ($this->config['use_storage'] == 1) {
            /* 查询：判断指定规格的货品数量是否足够 */
            if ($prod && $number > $product_info['product_number']) {
                $result['error'] = 1;
                $result['message'] = lang('flow.Stock_goods_null');
                return $this->succeed($result);
            } else {
                /* 查询：判断数量是否足够 */
                if ($number > $goods['goods_number']) {
                    $result['error'] = 1;
                    $result['message'] = lang('flow.Stock_goods_null');
                    return $this->succeed($result);
                }
            }
        }

        /* 查询：查询规格名称和值，不考虑价格 */
        $attr_list = [];
        /* 获得商品的规格 */
        $goods_attr_id = !is_array($specs) ? explode(",", $specs) : $specs;
        $res = GoodsAttr::select('attr_id', 'attr_value')->whereIn('goods_attr_id', $goods_attr_id);
        $res = $res->with([
            'getGoodsAttribute' => function ($query) {
                $query->select('attr_id', 'attr_name');
            }
        ]);
        $res = $res->get();
        $res = $res ? $res->toArray() : [];

        foreach ($res as $row) {
            $row = $row['get_goods_attribute'] ? array_merge($row, $row['get_goods_attribute']) : $row;
            $attr_list[] = $row['attr_name'] . ': ' . $row['attr_value'];
        }
        $goods_attr = join(chr(13) . chr(10), $attr_list);

        /* 更新：清空购物车中所有预售商品 */
        $this->cartCommonService->clearCart($this->uid, CART_PRESALE_GOODS);

        if ($this->uid) {
            $sess = "";
        } else {
            $sess = $this->sessionRepository->realCartMacIp();
        }
        //ecmoban模板堂 --zhuo end
        //ecmoban模板堂 --zhuo start 限购
        $nowTime = gmtime();
        $start_date = $goods['xiangou_start_date'];
        $end_date = $goods['xiangou_end_date'];

        if ($goods['is_xiangou'] == 1 && $nowTime > $start_date && $nowTime < $end_date) {
            if ($presale['total_goods'] >= $goods['xiangou_num']) {
                $result['error'] = 1;
                $result['message'] = sprintf(lang('flow.purchase_Prompt'), $goods['goods_name']);
                return $this->succeed($result);
            } else {
                if ($goods['xiangou_num'] > 0) {
                    if ($goods['is_xiangou'] == 1 && $presale['total_goods'] + $number > $goods['xiangou_num']) {
                        $result['message'] = sprintf(lang('flow.purchasing_prompt'), $goods['goods_name']);
                        $number = $goods['xiangou_num'] - $presale['total_goods'];
                    }
                }
            }
        }

        if (isset($product_info['product_price'])) {
            if ($this->config['add_shop_price'] == 1) {
                $goods['goods_price'] = $goods['shop_price'] + $product_info['product_price'];
            } else {
                $goods['goods_price'] = $product_info['product_price'];
            }
        } else {
            $goods['goods_price'] = $goods['shop_price'];
        }
        //ecmoban模板堂 --zhuo end 限购

        /* 更新：加入购物车 */
        $cart = [
            'user_id' => $this->uid,
            'session_id' => $sess,
            'goods_id' => $presale['goods_id'],
            'product_id' => $product_info['product_id'],
            'goods_sn' => addslashes($goods['goods_sn']),
            'goods_name' => addslashes($goods['goods_name']),
            'market_price' => $goods['market_price'],
            'goods_price' => $goods['goods_price'],
            'goods_number' => $number,
            'goods_attr' => addslashes($goods_attr),
            'goods_attr_id' => $_specs,
            'ru_id' => $goods['user_id'],
            'warehouse_id' => $this->warehouse_id,
            'area_id' => $this->area_id,
            'area_city' => $this->area_city,
            'is_real' => $goods['is_real'],
            'extension_code' => 'presale',
            'parent_id' => 0,
            'rec_type' => CART_PRESALE_GOODS,
            'is_gift' => 0,
            'freight' => $goods['freight'],
            'tid' => $goods['tid'],
            'shipping_fee' => $goods['shipping_fee']
        ];

        Cart::insertGetId($cart);

        $result = [
            'error' => 0,
            'rec_type' => CART_PRESALE_GOODS,          //购物车类型
            'extension_code' => 'presale',             //扩展信息
            'presale_id' => $presale['act_id']         //预售ID
        ];

        return $this->succeed($result);
    }

    /**
     * 新品发布
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function new(Request $request)
    {
        $cat_id = $request->get('cat_id', 0);
        $sort = $request->get('sort', 'act_id');
        $status = $request->get('status', 0);
        $order = $request->get('order', 'DESC');
        $keywords = $request->get('keywords', '');

        $res['pre_goods'] = $this->presaleService->getNewPreGoods([$cat_id], 0, 0, 0, 0, $sort, $status, $order, $keywords);
        $res['pre_cat'] = $this->presaleService->getPreCat();
        $res['cat_id'] = $cat_id;
        $res['page_title'] = lang('presale.presale_new_goods');

        return $this->succeed($res);
    }
}
