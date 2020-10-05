<?php

namespace App\Api\Fourth\Controllers;

use App\Api\Foundation\Controllers\Controller;
use App\Models\UserOrderNum;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Cart\CartCommonService;
use App\Services\Flow\FlowMobileService;
use App\Services\Goods\GoodsAttrService;
use App\Services\Goods\GoodsGalleryService;
use App\Services\Goods\GoodsMobileService;
use App\Services\Goods\GoodsProdutsService;
use App\Services\Goods\GoodsWarehouseService;
use App\Services\Order\OrderRefoundService;
use App\Services\Store\StoreStreetMobileService;
use App\Services\Team\TeamService;
use App\Services\User\AccountService;
use App\Services\Wechat\WechatService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Class TeamController
 * @package App\Api\Fourth\Controllers
 */
class TeamController extends Controller
{
    protected $teamService;
    protected $goodsMobileService;
    protected $goodsAttrService;
    protected $cartCommonService;
    protected $timeRepository;
    protected $accountService;
    protected $wechatService;
    protected $goodsGalleryService;
    protected $dscRepository;
    protected $goodsWarehouseService;
    protected $config;
    protected $orderRefoundService;
    protected $baseRepository;

    public function __construct(
        TeamService $teamService,
        GoodsMobileService $goodsMobileService,
        GoodsAttrService $goodsAttrService,
        CartCommonService $cartCommonService,
        TimeRepository $timeRepository,
        AccountService $accountService,
        WechatService $wechatService,
        GoodsGalleryService $goodsGalleryService,
        DscRepository $dscRepository,
        GoodsWarehouseService $goodsWarehouseService,
        OrderRefoundService $orderRefoundService,
        BaseRepository $baseRepository
    )
    {
        //加载外部类
        $files = [
            'common'
        ];
        load_helper($files);

        $this->teamService = $teamService;
        $this->goodsMobileService = $goodsMobileService;
        $this->goodsAttrService = $goodsAttrService;
        $this->cartCommonService = $cartCommonService;
        $this->timeRepository = $timeRepository;
        $this->accountService = $accountService;
        $this->wechatService = $wechatService;
        $this->goodsGalleryService = $goodsGalleryService;
        $this->dscRepository = $dscRepository;
        $this->goodsWarehouseService = $goodsWarehouseService;
        $this->config = $this->dscRepository->dscConfig();
        $this->orderRefoundService = $orderRefoundService;
        $this->baseRepository = $baseRepository;
    }

    /**
     * 拼团首页广告位
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        // 获取拼团首页banner广告位
        $banner = $this->teamService->teamPositions('1008', 3);
        $data['banner'] = $banner;

        //拼团首页banner下广告位
        $banner_bottom = $this->teamService->teamPositions('1009');
        $data['banner_bottom'] = $banner_bottom;

        //拼团首页热门活动广告位-left
        $ad_hot_left = $this->teamService->teamPositions('1010');
        $data['ad_hot_left'] = $ad_hot_left;

        //拼团首页热门活动广告位-right
        $ad_hot_right = $this->teamService->teamPositions('1011');
        $data['ad_hot_right'] = $ad_hot_right;

        //拼团首页热门活动下广告位
        $ad_hot_bottom = $this->teamService->teamPositions('1012');
        $data['ad_hot_bottom'] = $ad_hot_bottom;

        //拼团首页精选商品广告位-left
        $ad_best_left = $this->teamService->teamPositions('1013');
        $data['ad_best_left'] = $ad_best_left;

        //拼团首页精选商品广告位-right
        $ad_best_right = $this->teamService->teamPositions('1014');
        $data['ad_best_right'] = $ad_best_right;

        // 获取拼团主频道
        $team_categories = $this->teamService->teamCategories();
        $data['team_categories'] = $team_categories;

        return $this->succeed($data);
    }

    /**
     * 拼团首页商品列表,频道商品列表
     * @param Request $request
     * @return mixed
     * @throws ValidationException
     */
    public function goods(Request $request)
    {
        //验证参数
        $this->validate($request, [
            'page' => 'required|integer',
            'size' => 'required|integer',
            'tc_id' => 'required|integer' //拼团顶级频道id
        ]);

        $team_goods_list = $this->teamService->getGoods($request->get('tc_id'), $request->get('page'), $request->get('size'));

        return $this->succeed($team_goods_list);
    }

    /**
     * 拼团首页商品列表,频道商品列表
     * @param Request $request
     * @return mixed
     * @throws ValidationException
     */
    public function categories(Request $request)
    {
        //验证参数
        $this->validate($request, [
            'tc_id' => 'required|integer'  //拼团顶级频道id
        ]);

        // 获取频道广告位
        $banner = $this->teamService->categoriesAdsense($request->get('tc_id'), 'banner');
        $data['banner'] = $banner;

        $ads_left = $this->teamService->categoriesAdsense($request->get('tc_id'), 'left');
        $data['ads_left'] = $ads_left;

        $ads_right = $this->teamService->categoriesAdsense($request->get('tc_id'), 'right');
        $data['ads_right'] = $ads_right;

        // 获取拼团主频道
        $team_categories = $this->teamService->teamCategories();
        $data['team_categories'] = $team_categories;

        // 获取拼团子频道
        $team_categories_child = $this->teamService->teamCategories($request->get('tc_id'));
        $data['team_categories_child'] = $team_categories_child;

        // 获取主频道名称
        $data['title'] = $this->teamService->teamCategoriesInfo($request->get('tc_id'));

        return $this->succeed($data);
    }

    /**
     * 下单提示轮播
     * @param Request $request
     * @return mixed
     * @throws ValidationException
     */
    public function virtualOrder(Request $request)
    {
        //验证参数
        $this->validate($request, []);

        // 获取随机用户信息
        $user_list = $this->teamService->virtualOrder($this->uid);

        return $this->succeed($user_list);
    }

    /**
     * 拼团子频道商品列表
     * @param Request $request
     * @return mixed
     * @throws ValidationException
     */
    public function goodsList(Request $request)
    {
        $this->validate($request, [
            'page' => 'required|integer',
            'size' => 'required|integer',
            'tc_id' => 'required|integer',  //拼团子频道id
            'sort_key' => 'required|integer', //排序方式 0默认，1新品，2销量，3价格
            'sort_value' => 'required|string', //排序 ASC  DESC
        ]);

        $team_goods_list = $this->teamService->getGoodsList($request->get('tc_id'), $request->get('keyword'), $request->get('sort_key'), $request->get('sort_value'), $request->get('page'), $request->get('size'));

        return $this->succeed($team_goods_list);
    }

    /**
     * 拼团排行
     * @param Request $request
     * @return mixed
     * @throws ValidationException
     */
    public function teamRanking(Request $request)
    {
        $this->validate($request, [
            'page' => 'required|integer',
            'size' => 'required|integer',
            'status' => 'required|integer', //0热门，1新品，2优选，3精品
        ]);

        $team_goods_list = $this->teamService->teamRankingList($request->get('status'), $request->get('page'), $request->get('size'));

        return $this->succeed($team_goods_list);
    }

    /**
     * 拼团商品详情
     * @param Request $request
     * @return mixed
     * @throws ValidationException
     */
    public function detail(Request $request)
    {

        //验证参数
        $this->validate($request, [
            'goods_id' => 'required|integer',
            'team_id' => 'required|integer'   //拼团开团id
        ]);

        $result = [
            'goods_img' => '',         // 商品相册
            'goods_info' => '',        // 商品信息
            'team_log' => '',          // 已成功开团信息
            'new_goods' => '',         // 拼团新品
            'store_detail' => '',      // 店铺信息
            'goods_properties' => ''   // 商品属性 规格
        ];

        $goods_id = $request->get('goods_id', 0);
        $team_id = $request->get('team_id', 0);

        $time = $this->timeRepository->getGmTime();

        //是否收藏
        $collect = $this->teamService->findOne($goods_id, $this->uid);

        // 商品信息
        $goodsInfo = $this->teamService->goodsDetail($goods_id, $this->uid);
        if (empty($goodsInfo)) {
            $result['error'] = 1;
            $result['msg'] = lang('team.team_is_end');
            return $this->succeed($result);
        }
        $goodsInfo['team_price'] = $this->dscRepository->getPriceFormat($goodsInfo['team_price'], true);
        $goodsInfo['shop_price'] = $this->dscRepository->getPriceFormat($goodsInfo['shop_price'], true);
        $goodsInfo['market_price'] = $this->dscRepository->getPriceFormat($goodsInfo['market_price'], true);
        /*获取商品规格参数*/
        $goodsInfo['attr_parameter'] = $this->goodsAttrService->goodsAttrParameter($goodsInfo['goods_id']);
        //初始值
        $goodsInfo['team_id'] = 0;

        //验证参团活动是否结束
        if ($team_id) {
            $team_info = $this->teamService->teamIsFailure($team_id);
            if ($team_info) {
                if ($team_info['is_team'] != 1 || $team_info['status'] == 1) {
                    $result['error'] = 1;
                    $result['msg'] = lang('team.team_is_end');
                    return $this->succeed($result);
                }
            }
            $goodsInfo['team_id'] = $team_id;
        }

        if ($goodsInfo['is_on_sale'] == 0) {
            $result['error'] = 1;
            $result['msg'] = lang('team.goods_not_on_sale');
            return $this->succeed($result);
        }

        $goodsInfo['is_collect'] = empty($collect) ? 0 : 1;
        $result['goods_info'] = $goodsInfo;  //商品信息

        //获取该商品已成功开团信息
        $team_log = $this->teamService->teamGoodsLog($goods_id);
        if ($team_log) {
            foreach ($team_log as $key => $val) {
                $val = $val['get_team_goods'] ? array_merge($val, $val['get_team_goods']) : $val;
                $val = $val['get_order_info'] ? array_merge($val, $val['get_order_info']) : $val;
                $val = $val['get_users'] ? array_merge($val, $val['get_users']) : $val;

                $validity_time = $val['start_time'] + ($val['validity_time'] * 3600);
                $team_log[$key]['end_time'] = $validity_time; //剩余时间
                $team_log[$key]['current_time'] = $time;
                //统计该拼团已参与人数
                $team_num = $this->teamService->surplusNum($val['team_id']);
                $team_log[$key]['surplus'] = $val['team_num'] - $team_num;//还差几人
                // 用户名、头像
                $team_log[$key]['user_name'] = $val['nick_name'] ? setAnonymous($val['nick_name']) : setAnonymous($val['user_name']);

                if (empty($val['user_picture'])) {
                    $val['user_picture'] = asset('img/user_default.png');
                }

                $team_log[$key]['user_picture'] = $this->dscRepository->getImagePath($val['user_picture']);

                //验证是否参团
                $team_log[$key]['is_team'] = 0;
                $team_join = $this->teamService->teamJoin($this->uid, $val['team_id']);
                if ($team_join > 0) {
                    $team_log[$key]['is_team'] = 1;
                }
            }
            $result['team_log'] = $team_log;
        }

        //获取拼团新品
        $result['new_goods'] = $this->teamService->teamNewGoods('is_new', $goodsInfo['user_id']);

        // 商品相册
        $data = ['goods_id' => $goods_id];
        $goodsGallery = $this->goodsGalleryService->getGalleryList($data);
        if ($goodsGallery) {
            foreach ($goodsGallery as $k => $v) {
                $goodsGallery[$k] = $v['img_url'];
            }
        } else {
            $goodsGallery[] = $goodsInfo['goods_img'];
        }

        $result['goods_img'] = $goodsGallery;

        // 店铺信息
        if ($goodsInfo['user_id'] > 0) {
            $store = app(StoreStreetMobileService::class);
            $result['store_detail'] = $store->StoreDetail($goodsInfo['user_id']);
        }

        // 商品属性 规格
        $result['goods_properties'] = $this->goodsAttrService->goodsAttr($goods_id);

        if ($result['goods_properties']) {
            $result['attr_name'] = '';
            foreach ($result['goods_properties'] as $k => $v) {
                $select_key = 0;
                if ($v['attr_key'][0]['attr_type'] == 0) {
                    unset($result['goods_properties'][$k]);
                    continue;
                }
                foreach ($v['attr_key'] as $key => $val) {
                    if ($val['attr_checked'] == 1) {
                        $select_key = $key;
                        break;
                    }
                }
                //默认选择第一个属性为checked
                if ($select_key == 0) {
                    $result['goods_properties'][$k]['attr_key'][0]['attr_checked'] = 1;
                }
                if ($result['attr_name']) {
                    $result['attr_name'] = $result['attr_name'] . '' . $v['attr_key'][$select_key]['attr_value'];
                } else {
                    $result['attr_name'] = $v['attr_key'][$select_key]['attr_value'];
                }
                $attr_str[] = $v['attr_key'][$select_key]['goods_attr_id'];
            }
            $result['goods_properties'] = $arr = collect($result['goods_properties'])->values()->all();
        }
        //默认第一个属性的库存
        if (!empty($attr_str)) {
            $result['goods_info']['goods_number'] = $this->goodsWarehouseService->goodsAttrNumber($goods_id, $attr_str, $this->warehouse_id, $this->area_id, $this->area_city);
        }

        return $this->succeed($result);
    }

    /**
     * 商品改变属性、数量时重新计算商品价格
     * @param Request $request
     * @return mixed
     * @throws ValidationException
     */
    public function property(Request $request)
    {
        //验证参数
        $this->validate($request, [
            'goods_id' => 'required|integer',         //商品id
            'num' => 'required|integer',
            'warehouse_id' => 'required|integer',    //仓库id
            'area_id' => 'required|integer'          //地区id
        ]);

        $goods_id = $request->get('goods_id', 0);
        $num = $request->get('num', 1);
        $attr_id = $request->get('attr_id', '');
        $store_id = 0;


        $result = [
            'stock' => '',             //库存
            'market_price' => '',      //市场价
            'qty' => '',               //数量
            'spec_price' => '',        //属性价格
            'goods_price' => '',       //商品价格(最终使用价格)
            'attr_img' => ''           //商品属性图片
        ];

        // 商品信息
        $goodsInfo = $this->teamService->goodsDetail($goods_id);

        $result['stock'] = $this->goodsWarehouseService->goodsAttrNumber($goodsInfo['goods_id'], $attr_id, $this->warehouse_id, $this->area_id, $this->area_city, $store_id);

        $result['market_price'] = $this->goodsMobileService->goodsMarketPrice($goodsInfo['goods_id'], $attr_id, $this->warehouse_id, $this->area_id, $this->area_city);
        $result['market_price_formated'] = $this->dscRepository->getPriceFormat($result['market_price'], true);
        $result['qty'] = $num;
        $result['spec_price'] = app(GoodsProdutsService::class)->goodsPropertyPrice($goodsInfo['goods_id'], $attr_id, $this->warehouse_id, $this->area_id, $this->area_city);
        $result['spec_price_formated'] = $this->dscRepository->getPriceFormat($result['spec_price'], true);
        $result['goods_price'] = $this->teamService->getFinalPrice($goodsInfo['goods_id'], $num, true, $attr_id, $this->warehouse_id, $this->area_id, $this->area_city);
        $result['goods_price_formated'] = $this->dscRepository->getPriceFormat($result['goods_price'], true);

        //商品属性图片
        $attr_img = $this->goodsMobileService->getAttrImgFlie($goodsInfo['goods_id'], $attr_id);
        if (!empty($attr_img['attr_img_flie'])) {
            $result['attr_img'] = $this->dscRepository->getImagePath($attr_img['attr_img_flie']);
        }

        return $this->succeed($result);
    }

    /**
     * 购买
     * @param Request $request
     * @return mixed
     * @throws ValidationException
     */
    public function teamBuy(Request $request)
    {
        //验证数据
        $this->validate($request, [
            'goods_id' => 'required|integer',     //拼团商品id
            't_id' => 'required|integer',         //拼团活动id
            'num' => 'required|integer',          //数量
            'team_id' => 'required|integer',      //拼团开团id
        ]);

        if (empty($this->uid)) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        //参数
        $goods_id = $request->get('goods_id', 0);
        $num = $request->get('num', 1);
        $attr_id = $request->input('attr_id', []);
        $t_id = $request->get('t_id', 0);
        $team_id = $request->get('team_id', 0);

        $result = [
            'error' => 0,
            'flow_type' => 0,           //购物车类型
            't_id' => 0,                //拼团活动id
            'team_id' => 0,             //拼团开团id
        ];

        if ($team_id > 0) {
            //验证该团是否参与
            $count = $this->teamService->isTeamOrderNum($this->uid, $team_id);
            if ($count > 0) {
                $result['error'] = 1;
                $result['msg'] = lang('team.team_you_joined');
                return $this->succeed($result);
            }
            //验证该团是否成功
            $team_info = $this->teamService->teamIsFailure($team_id);

            if ($team_info) {
                if ($team_info['status'] == 1) {
                    $result['error'] = 1;
                    $result['msg'] = lang('team.team_is_finished');
                    return $this->succeed($result);
                }
            }
        }
        //拼团商品信息
        $goods = $this->teamService->goodsDetail($goods_id);

        $product_info = $this->goodsAttrService->getProductsInfo($goods_id, $attr_id, $this->warehouse_id, $this->area_id, $this->area_city);

        if (empty($product_info)) {
            $product_info = ['product_number' => 0, 'product_id' => 0];
        }

        // 商品属性文字输出
        $goods_attr = $this->goodsAttrService->getGoodsAttrInfo($attr_id, 'pice', $this->warehouse_id, $this->area_id, $this->area_city);
        $goods_attr_id = isset($attr_id) ? join(',', $attr_id) : '';

        // 计算商品价格
        $goodsPrice = $this->teamService->getFinalPrice($goods['goods_id'], $num, true, $attr_id);

        //库存
        $attr_number = $this->goodsWarehouseService->goodsAttrNumber($goods_id, $attr_id, $this->warehouse_id, $this->area_id, $this->area_city);

        if ($num > $attr_number) {
            $result['error'] = 1;
            $result['msg'] = lang('team.team_goods_understock');
            return $this->succeed($result);
        }
        //验证拼团限购数量
        if ($num > $goods['astrict_num']) {
            $result['error'] = 1;
            $result['msg'] = lang('team.team_number_limited');
            return $this->succeed($result);
        }

        // 更新：清空当前会员购物车中拼团商品
        $this->cartCommonService->clearCart($this->uid, CART_TEAM_GOODS);

        // 添加参数
        $arguments = [
            'goods_id' => $goods['goods_id'],
            'user_id' => $this->uid,
            'goods_sn' => $goods['goods_sn'],
            'product_id' => $product_info['product_id'],
            'group_id' => '',
            'goods_name' => $goods['goods_name'],
            'market_price' => $goods['market_price'],
            'goods_price' => $goodsPrice,
            'goods_number' => $num,
            'goods_attr' => addslashes($goods_attr),
            'goods_attr_id' => $goods_attr_id,
            'is_real' => $goods['is_real'],
            'extension_code' => empty($extension_code) ? '' : $extension_code,
            'parent_id' => 0,
            'rec_type' => CART_TEAM_GOODS,  // 购物车商品类型
            'is_gift' => 0,
            'is_shipping' => $goods['is_shipping'],
            'can_handsel' => '',
            'model_attr' => $goods['model_attr'],
            'ru_id' => $goods['user_id'],
            'shopping_fee' => '',
            'warehouse_id' => $this->warehouse_id,
            'area_id' => $this->area_id,
            'area_city' => $this->area_city,
            'add_time' => $this->timeRepository->getGmTime(),
            'store_id' => '',
            'freight' => $goods['freight'],
            'tid' => $goods['tid'],
            'shipping_fee' => $goods['shipping_fee'],
            'store_mobile' => '',
            'take_time' => '',
            'is_checked' => '1',
        ];

        $goodsNumber = $this->teamService->addGoodsToCart($arguments);
        if ($goodsNumber) {
            $result['rec_type'] = CART_TEAM_GOODS;
            $result['t_id'] = $t_id;
            if ($team_id > 0) {
                $result['team_id'] = $team_id;
            }
        }

        return $this->succeed($result);
    }

    /**
     * 等待成团
     * @param Request $request
     * @return mixed
     * @throws ValidationException
     */
    public function teamWait(Request $request)
    {
        //验证参数
        $this->validate($request, [
            'team_id' => 'required|integer'  //开团id
        ]);

        $result = [
            'team_info' => '',
            'teamUser' => '',
        ];

        //返回用户ID
        $uid = $this->authorization();

        //获取拼团信息
        $team_info = $this->teamService->teamInfo($request->get('team_id'));
        if ($team_info) {
            //验证是否已经参团
            $team_join = $this->teamService->teamJoin($uid, $request->get('team_id'));

            if ($team_join > 0) {
                $team_info['team_join'] = 1;
            }

            $result['team_info'] = $team_info;

            //获取拼团团员信息
            $teamUser = $this->teamService->teamUserList($request->get('team_id'));
            foreach ($teamUser as $key => $val) {
                // 用户名、头像
                $teamUser[$key]['user_name'] = !empty($val['get_users']['nick_name']) ? setAnonymous($val['get_users']['nick_name']) :
                    setAnonymous($val['get_users']['user_name']);

                if (empty($val['get_users']['user_picture'])) {
                    $val['get_users']['user_picture'] = asset('img/user_default.png');
                }

                $teamUser[$key]['user_picture'] = $this->dscRepository->getImagePath($val['get_users']['user_picture']);
                unset($teamUser[$key]['get_users']);
            }
            $result['teamUser'] = $teamUser;
        }

        return $this->succeed($result);
    }

    /**
     * 拼团成员
     * @param Request $request
     * @return mixed
     * @throws ValidationException
     */
    public function teamUser(Request $request)
    {
        //验证参数
        $this->validate($request, [
            'team_id' => 'required|integer',  //开团id
        ]);

        //获取拼团团员信息
        $teamUser = $this->teamService->teamUserList($request->get('team_id'));

        $team_user = [];
        foreach ($teamUser as $key => $val) {
            $team_user[$key]['team_parent_id'] = $val['team_parent_id'];
            $team_user[$key]['team_user_id'] = $val['team_user_id'];
            $team_user[$key]['add_time'] = $this->timeRepository->getLocalDate($this->config['time_format'], $val['add_time']);

            // 用户名、头像
            $team_user[$key]['user_name'] = !empty($val['get_users']['nick_name']) ? setAnonymous($val['get_users']['nick_name']) : setAnonymous($val['get_users']['user_name']);

            if (empty($val['get_users']['user_picture'])) {
                $val['get_users']['user_picture'] = asset('img/user_default.png');
            }

            $team_user[$key]['user_picture'] = $this->dscRepository->getImagePath($val['get_users']['user_picture']);
        }

        return $this->succeed($team_user);
    }

    /**
     * 我的拼团
     * @param Request $request
     * @return mixed
     * @throws ValidationException
     */
    public function teamOrder(Request $request)
    {
        //验证参数
        $this->validate($request, [
            'status' => 'required|integer', // 0 拼团中，1成功团，2失败团
            'page' => 'required|integer',
            'size' => 'required|integer',
        ]);

        //检测拼团失败，退款到余额中
        $this->checkRefund();

        $team_order = $this->teamService->teamUserOrder($this->uid, $request->get('status'), $request->get('page'), $request->get('size'));

        return $this->succeed($team_order);
    }

    /**
     * 检测拼团失败，退款到余额或原路退款（在线支付）
     */
    public function checkRefund()
    {
        //失败拼团订单
        $order_list = $this->teamService->teamUserOrderRefund();

        if ($order_list) {
            foreach ($order_list as $key => $val) {
                $order = [
                    'order_amount' => $val['order_amount']
                ];
                // - 订单如果使用了余额 退余额
                $surplus = empty($val['surplus']) ? 0 : $val['surplus'];
                if ($surplus > 0) {
                    $order['surplus'] = 0;
                    $order['money_paid'] = empty($val['money_paid']) ? 0 : $val['money_paid'] - $surplus;
                    $order['order_amount'] = $order['order_amount'] + $surplus;
                    // 退款到账户余额 并记录会员账目明细
                    $change_desc = lang('team.team_order_fail_refound') . $val['order_sn'] . '，金钱：' . $surplus;
                    $this->accountService->logAccountChange($val['user_id'], $surplus, 0, 0, 0, $change_desc,
                        ACT_TRANSFERRED);
                }

                // - 订单在线支付部分 原路退款
                $money_paid = empty($val['money_paid']) ? 0 : $val['money_paid'];
                if ($money_paid > 0) {
                    $order['money_paid'] = 0;
                    $order['order_amount'] = $order['order_amount'] + $money_paid;
                    // 原路退款
                    $refundOrder = [
                        'order_id' => $val['order_id'],
                        'pay_id' => $val['pay_id'],
                        'pay_status' => $val['pay_status'],
                        'referer' => $val['referer'],
                        'return_sn' => $val['order_sn']
                    ];
                    $this->orderRefoundService->refoundPay($refundOrder, $money_paid);
                }

                // - 订单使用了储值卡 退储值卡
                $use_val = $this->orderRefoundService->returnValueCardMoney($val['order_id']);
                if ($use_val > 0) {
                    $order['order_amount'] = $order['order_amount'] + $use_val;
                }

                //记录订单操作记录
                $action_note = lang('team.team_order_fail_refound');

                // 修改订单状态为已取消，付款状态为未付款
                $order['order_status'] = OS_CANCELED;
                $order['to_buyer'] = lang('team.cancel_order_reason'); // 拼团失败
                $order['pay_status'] = PS_UNPAYED;
                $order['pay_time'] = 0;
                $order['shipping_status'] = $val['shipping_status'];
                $this->teamService->orderActionChange($val['order_id'], 'admin', $order, $action_note);

                // 检查商品库存
                //--库存管理 use_storage 1为开启 0为未启用-- stock_dec_time：0发货时,  1 SDT_PLACE 为下单时, 2 SDT_PAID 为付款时
                if ($this->config['use_storage'] == '1' && ($this->config['stock_dec_time'] == SDT_PLACE || $this->config['stock_dec_time'] == SDT_PAID)) {
                    app(FlowMobileService::class)->changeOrderGoodsStorage($val['order_id'], false, SDT_PLACE, $this->warehouse_id, $this->area_id, $this->area_city);
                }

                // 拼团失败退款通知
                if (is_wechat_browser() && file_exists(MOBILE_WECHAT)) {
                    $pushData = [
                        'keyword1' => ['value' => $val['order_sn'], 'color' => '#173177'],
                        'keyword2' => ['value' => $val['goods_name'], 'color' => '#173177'],
                        'keyword3' => ['value' => lang('team.team_order_fail_refound'), 'color' => '#173177'],
                        'keyword4' => ['value' => $this->dscRepository->getPriceFormat($order['team_price'], true), 'color' => '#173177']
                    ];
                    $url = dsc_url('/#/user/orderDetail/' . $val['order_id']);
                    $this->wechatService->push_template('OPENTM400940587', $pushData, $url, $val['user_id']);
                }

                /* 更新会员订单信息 */
                $dbRaw = [
                    'order_team_num' => "order_team_num - 1",
                ];
                $dbRaw = $this->baseRepository->getDbRaw($dbRaw);
                UserOrderNum::where('user_id', $val['user_id'])->update($dbRaw);
            }
        }
    }
}
