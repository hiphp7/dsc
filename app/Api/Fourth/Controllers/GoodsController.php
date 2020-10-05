<?php

namespace App\Api\Fourth\Controllers;

use App\Api\Foundation\Controllers\Controller;
use App\Api\Fourth\Transformers\GoodsTransformer;
use App\Custom\Distribute\Services\DistributeGoodsService;
use App\Custom\Distribute\Services\DistributeService;
use App\Models\Region;
use App\Repositories\Common\CommonRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Drp\DrpService;
use App\Services\Goods\GoodsAttrService;
use App\Services\Goods\GoodsMobileService;
use App\Services\Goods\GoodsProdutsService;
use App\Services\Goods\GoodsWarehouseService;
use App\Services\Store\StoreService;
use App\Services\User\UserCommonService;
use Exception;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Class GoodsController
 * @package App\Api\Fourth\Controllers
 */
class GoodsController extends Controller
{
    protected $goodsMobileService;
    protected $goodsTransformer;
    protected $galleryService;
    protected $storeService;
    protected $userCommonService;
    protected $discountService;
    protected $dscRepository;
    protected $goodsAttrService;
    protected $goodsWarehouseService;
    protected $config;

    public function __construct(
        GoodsMobileService $goodsMobileService,
        GoodsTransformer $goodsTransformer,
        StoreService $storeService,
        UserCommonService $userCommonService,
        DscRepository $dscRepository,
        GoodsAttrService $goodsAttrService,
        GoodsWarehouseService $goodsWarehouseService
    )
    {
        //加载外部类
        $files = [
            'clips',
            'order',
        ];
        load_helper($files);

        $this->goodsMobileService = $goodsMobileService;
        $this->goodsTransformer = $goodsTransformer;
        $this->storeService = $storeService;
        $this->userCommonService = $userCommonService;
        $this->dscRepository = $dscRepository;
        $this->goodsAttrService = $goodsAttrService;
        $this->goodsWarehouseService = $goodsWarehouseService;
        $this->config = $this->dscRepository->dscConfig();
    }

    /**
     * 商品详情
     * @param Request $request
     * @param CommonRepository $commonRepository
     * @return JsonResponse
     * @throws Exception
     */
    public function show(Request $request, CommonRepository $commonRepository)
    {
        $arr = [
            'goods_id' => $request->input('goods_id', 0),
            'warehouse_id' => $this->warehouse_id,
            'area_id' => $this->area_id,
            'area_city' => $this->area_city,
            'is_alone_sale' => 1,
            'is_on_sale' => 1
        ];
        $arr['uid'] = $this->uid;

        // 获取推荐人id
        $parent_id = $request->input('parent_id', 0);

        $data = $this->goodsMobileService->getGoodsInfo($arr);

        if (empty($data)) {
            return $this->responseNotFound();
        }

        if (isset($data['is_delete']) && $data['is_delete'] == 1) {
            return $this->responseNotFound();
        }

        if (file_exists(MOBILE_DRP)) {
            if (isset($data['membership_card_id']) && $data['membership_card_id'] > 0) {
                // 是否已购买成为分销商商品 已购买不显示
                $data['is_buy_drp'] = app(DistributeService::class)->is_buy_goods($arr['uid'], $arr['goods_id']);
                $data['is_show'] = empty($data['is_buy_drp']) ? 1 : 0;
            }
        }

        if (isset($data['is_show']) && $data['is_show'] == 0) {
            return $this->responseNotFound();
        }

        /* 检测是否秒杀商品 */
        $sec_goods_id = $this->goodsMobileService->getIsSeckill($arr['goods_id']);
        $data['seckill_id'] = $sec_goods_id ? $sec_goods_id : 0;

        $where = [
            'goods_id' => $arr['goods_id'],
            'is_confirm' => 1
        ];
        $store_goods = $this->storeService->getStoreCount($where);

        $store_products = $this->storeService->getStoreProductCount($where);

        if ($store_goods > 0 || $store_products > 0) {
            $data['store_count'] = 1;
        } else {
            $data['store_count'] = 0;
        }

        // 促销信息
        $data['goods_promotion'] = $this->goodsMobileService->getPromotionInfo($arr['uid'], $data['goods_id'], $data['user_id'], $data);

        // 组合套餐
        $group_count = get_group_goods_count($data['goods_id']);
        if ($group_count) {
            // 会员等级
            $user_rank = $this->userCommonService->getUserRankByUid($arr['uid']);
            if ($user_rank) {
                $user_rank['discount'] = $user_rank['discount'] / 100;
            } else {
                $user_rank['rank_id'] = 1;
                $user_rank['discount'] = 1;
            }
            $fittings_list = get_goods_fittings([$data['goods_id']], $arr['warehouse_id'], $arr['area_id'], 0, '', 0, [], $arr['uid'], $user_rank);

            if (!empty($fittings_list)) {
                // 节省金额最高 排序最前
                $fittings_list = get_array_sort($fittings_list, 'spare_price_ori', 'DESC');

                $fittings_list = array_values($fittings_list); // 重新对数组键排序 值不变

                $data['fittings'] = $fittings_list;
            }
        }

        //判断是否支持退货服务
        $is_return_service = 0;
        if (isset($data['goods_cause']) && $data['goods_cause']) {
            $goods_cause = explode(',', $data['goods_cause']);

            $fruit1 = [1, 2, 3]; //退货，换货，仅退款
            $intersection = array_intersect($fruit1, $goods_cause); //判断商品是否设置退货相关
            if (!empty($intersection)) {
                $is_return_service = 1;
            }
        }
        //判断是否设置包退服务  如果设置了退换货标识，没有设置包退服务  那么修正包退服务为已选择
        if ($is_return_service == 1 && isset($data['goods_extends']['is_return']) && !$data['goods_extends']['is_return']) {
            $data['goods_extend']['is_return'] = 1;
        }

        $sellerInfo = isset($data['get_seller_shop_info']) && $data['get_seller_shop_info'] ? $data['get_seller_shop_info'] : [];

        $data['basic_info'] = $sellerInfo;
        if ($sellerInfo) {
            $province = Region::where('region_id', $sellerInfo['province'])->value('region_name');
            $province = $province ? $province : '';

            $city = Region::where('region_id', $sellerInfo['city'])->value('region_name');
            $city = $city ? $city : '';

            $data['basic_info']['province_name'] = $province;
            $data['basic_info']['city_name'] = $city;
        }

        // 更新商品点击量
        $this->goodsMobileService->updateGoodsClick($arr['goods_id']);

        /**
         * 商品分销
         */
        if (file_exists(MOBILE_DRP)) {
            $data['is_drp'] = 1;// 是否有分销模块

            $drp_config = app(DrpService::class)->drpConfig();
            // 控制商品详情页‘我要分销’按钮
            $data['is_show_drp'] = $drp_config['isdrp']['value'] ?? 0;

            // 记录推荐人id
            if ($parent_id > 0) {
                $drp_affiliate = $drp_config['drp_affiliate_on']['value'] ?? 0;
                // 开启分销
                if ($drp_affiliate == 1) {
                    // 分销内购模式
                    $isdistribution = $drp_config['isdistribution']['value'] ?? 0;
                    if ($isdistribution == 2) {
                        /**
                         *  2. 自动模式
                         *  mode 1: 业绩归属 上级分销商 + 自己（条件：推荐自己微店内商品或自己推荐的链接）
                         *  mode 0：业绩归属 推荐人或上级分销商 + 自己（条件：推荐自己微店内商品或自己推荐的链接）
                         */
                        // 推荐自己微店内商品或自己推荐的链接
                        $is_drp_type = app(DrpService::class)->isDrpTypeGoods($arr['uid'], $data['goods_id'], $data['cat_id']);
                        if ($is_drp_type === true || $parent_id == $arr['uid']) {
                            $commonRepository->setDrpShopAffiliate($arr['uid'], $parent_id);
                        }

                    } else {
                        $commonRepository->setDrpShopAffiliate($arr['uid'], $parent_id);
                    }
                }

                //如有上级推荐人（分销商），且关系在有效期内，更新推荐时间 1.4.3
                app(DrpService::class)->updateBindTime($arr['uid'], $parent_id);
            }
        } else {
            $data['is_drp'] = 0;// 是否有分销模块
            $data['is_show_drp'] = 0;
        }

        if (file_exists(app_path('Custom/Distribute'))) {
            //计算商品的佣金显示前台
            if ($data['is_distribution'] > 0 || $data['membership_card_id'] > 0) {
                $data['commission_money'] = app(DistributeGoodsService::class)->calculate_goods_commossion($arr['uid'], $data);

                $data['commission_money'] = empty($data['commission_money']) ? 0 : $this->dscRepository->getPriceFormat($data['commission_money']);
            }
        }

        return $this->succeed($data);
    }

    /**
     * 获得促销商品
     * @param Request $request
     * @return JsonResponse
     */
    public function promoteGoods(Request $request)
    {
        $arr = [
            'warehouse_id' => $this->warehouse_id,
            'area_id' => $this->area_id,
            'area_city' => $this->area_city,
        ];
        $arr['uid'] = $this->uid;

        $data = $this->goodsMobileService->getPromoteGoods($arr);

        return $this->succeed($data);
    }

    /**
     * 获得推荐商品
     * @param Request $request
     * @return JsonResponse
     */
    public function recommendGoods(Request $request)
    {
        $arr = [
            'warehouse_id' => $this->warehouse_id,
            'area_id' => $this->area_id,
            'area_city' => $this->area_city,
        ];

        $arr['uid'] = $this->uid;

        $data = $this->goodsMobileService->getRecommendGoods($arr);

        return $this->succeed($data);
    }

    /**
     * 获得切换属性价格
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function AttrPrice(Request $request)
    {
        $goods_id = $request->input('goods_id', 0);
        $num = $request->input('num', 0);
        $store_id = $request->input('store_id', 0);
        $attr_id = $request->input('attr_id', 0);

        $data = $this->goodsMobileService->goodsPropertiesPrice($this->uid, $goods_id, $attr_id, $num, $this->warehouse_id, $this->area_id, $this->area_city, $store_id);

        return $this->succeed($data);
    }

    /**
     * 猜你喜欢
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function GoodsGuess(Request $request)
    {
        $link_cats = $request->input('link_cats', []);
        $link_goods = $request->input('link_goods', []);
        $is_volume = $request->input('is_volume', 0);

        $data = $this->goodsMobileService->getUserOrderGoodsGuess($this->uid, $link_cats, $link_goods, $this->warehouse_id, $this->area_id, $this->area_city, $is_volume);

        return $this->succeed($data);
    }

    /**
     * 生成商品分享海报
     * @param Request $request
     * @return JsonResponse
     * @throws FileNotFoundException
     * @throws ValidationException
     */
    public function SharePoster(Request $request)
    {
        // 数据验证
        $this->validate($request, [
            'goods_id' => 'required|integer'
        ]);

        $goods_id = $request->input('goods_id', 0);
        $share_type = $request->input('share_type', 0); // 分享类型 0 分享， 1 分销

        if (empty($this->uid)) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $data = $this->goodsMobileService->createsharePoster($this->uid, $goods_id, $this->warehouse_id, $this->area_id, $this->area_city, $share_type);

        if (isset($data['file']) && $data['file']) {
            // 同步镜像上传到OSS
            $this->ossMirror($data['file'], true);
        }

        return $this->succeed($data['url']);
    }

    /**
     * 组合套餐 配件
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function Fittings(Request $request)
    {
        $result = [
            'error' => 0,
            'goods' => '',       // 商品信息
            'fittings' => '',    // 配件
            'comboTab' => '',    // 配件类型
        ];
        // 数据验证
        $this->validate($request, [
            'goods_id' => 'required|integer'
        ]);

        $goods_id = $request->input('goods_id', 0);

        // 清空配件购物车
        $this->goodsMobileService->clearCartCombo($this->uid, $goods_id);

        // 主商品信息
        $arr = [
            'goods_id' => $goods_id,
            'warehouse_id' => $request->input('warehouse_id', 0),
            'area_id' => $request->input('area_id', 0),
            'area_city' => $request->input('area_city', 0),
        ];
        $arr['uid'] = $this->uid;

        $goods = $this->goodsMobileService->getGoodsInfo($arr);

        if (empty($goods)) {
            return $this->responseNotFound();
        } else {
            $result['goods'] = $goods; // 商品信息

            // 组合套餐
            $group_count = get_group_goods_count($goods_id);
            if ($group_count) {
                // 配件类型
                $comboTabIndex = get_cfg_group_goods();
                $comboTab = [];
                foreach ($comboTabIndex as $key => $row) {
                    $val = $key - 1;
                    $comboTab[$val]['group_id'] = $key;
                    $comboTab[$val]['text'] = $row;
                }

                $result['comboTab'] = $comboTab;
                // 会员等级
                $user_rank = $this->userCommonService->getUserRankByUid($arr['uid']);
                if ($user_rank) {
                    $user_rank['discount'] = $user_rank['discount'] / 100;
                } else {
                    $user_rank['rank_id'] = 1;
                    $user_rank['discount'] = 1;
                }

                $fittings_list = get_goods_fittings([$goods_id], $arr['warehouse_id'], $arr['area_id'], 0, '', 0, [], $arr['uid'], $user_rank);

                // 节省金额最高 排序最前
                $fittings_list = get_array_sort($fittings_list, 'spare_price_ori', 'DESC');

                $fittings_list = array_values($fittings_list); // 重新对数组键排序 值不变

                foreach ($fittings_list as $ke => $val) {
                    /*获取商品属性*/
                    $row = [];
                    $row['attr'] = $this->goodsAttrService->goodsAttr($val['goods_id']);

                    if ($row['attr']) {
                        $row['attr_name'] = '';
                        foreach ($row['attr'] as $k => $v) {
                            $select_key = 0;
                            foreach ($v['attr_key'] as $key => $val) {
                                if ($val['attr_checked'] == 1) {
                                    $select_key = $key;
                                    break;
                                }
                            }
                            //默认选择第一个属性为checked
                            if ($select_key == 0) {
                                $row['attr'][$k]['attr_key'][0]['attr_checked'] = 1;
                            }
                            if ($row['attr_name']) {
                                $row['attr_name'] = $row['attr_name'] . '' . $v['attr_key'][$select_key]['attr_value'];
                            } else {
                                $row['attr_name'] = $v['attr_key'][$select_key]['attr_value'];
                            }
                        }

                        $fittings_list[$ke]['attr'] = $row['attr'];
                        $fittings_list[$ke]['attr_name'] = $row['attr_name'];
                    }
                    unset($fittings_list[$ke]['properties']);
                }
                $result['fittings'] = $fittings_list;
            }
        }

        return $this->succeed($result);
    }

    /**
     * 组合套餐 配件
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function FittingPrice(Request $request)
    {
        $goods_id = $request->input('id', 0); // 商品id

        $type = $request->input('type', 0);    // type 1 主件 0 配件
        $load_type = $request->input('onload', ''); // 首次加载
        $group = $request->input('group', '');

        $uid = $this->uid;

        $attr = trim($group['attr']);
        $attr_id = !empty($attr) ? explode(',', $attr) : [];

        $number = intval($group['number']);
        $group_name = trim($group['group_name']);
        $group_id = trim($group['group_id']);
        $parent_id = intval($group['fittings_goods']); // 主商品id

        // 主商品信息
        $arr = [
            'goods_id' => $goods_id,
            'warehouse_id' => $this->warehouse_id,
            'area_id' => $this->area_id,
            'area_city' => $this->area_city,
        ];
        $arr['uid'] = $uid;

        $goods = $this->goodsMobileService->getGoodsInfo($arr);

        if (empty($goods)) {
            return $this->responseNotFound();
        } else {
            if ($number == 0) {
                $res['number'] = $number = 1;
            } else {
                $res['number'] = $number;
            }
            // 库存
            $attr_number = $this->goodsWarehouseService->goodsAttrNumber($goods_id, $attr_id, $this->warehouse_id, $this->area_id, $this->area_city);
            $res['attr_number'] = $attr_number;

            // 限制用户购买的数量
            $res['limit_number'] = $attr_number < $number ? ($attr_number ? $attr_number : 1) : $number;

            $res['shop_price'] = $goods['shop_price'];
            $res['market_price'] = $goods['market_price'];
            // 属性价格
            $res['spec_price'] = app(GoodsProdutsService::class)->goodsPropertyPrice($goods_id, $attr_id, $this->warehouse_id, $this->area_id, $this->area_city);
            $res['spec_price_formated'] = $this->dscRepository->getPriceFormat($res['spec_price'], true);
            // 最终价格
            $res['result'] = $this->goodsMobileService->getFinalPrice($uid, $goods_id, $number, true, $attr_id, $this->warehouse_id, $this->area_id, $this->area_city);
            $res['goods_price_formated'] = $this->dscRepository->getPriceFormat($res['result'], true);
        }

        // 会员等级
        $user_rank = $this->userCommonService->getUserRankByUid($arr['uid']);
        if ($user_rank) {
            $user_rank['discount'] = $user_rank['discount'] / 100;
        } else {
            $user_rank['rank_id'] = 1;
            $user_rank['discount'] = 1;
        }
        // 组合套餐 返回区间价格
        // 1 首次加载
        if ($type == 1 && $load_type == 'onload') {
            $group_count = get_group_goods_count($goods_id);
            if ($group_count > 0) {
                $fittings_list = get_goods_fittings([$goods_id], $arr['warehouse_id'], $arr['area_id'], 0, '', 0, [], $arr['uid'], $user_rank);
                if ($fittings_list) {
                    $fittings_attr = $attr_id;
                    $goods_fittings = get_goods_fittings_info($goods_id, $this->warehouse_id, $this->area_id, $this->area_city, '', 1, '', $fittings_attr, $arr['uid'], $user_rank);

                    if (is_array($fittings_list)) {
                        foreach ($fittings_list as $vo) {
                            $fittings_index[$vo['group_id']] = $vo['group_id'];//关联数组
                        }
                    }
                    ksort($fittings_index);//重新排序

                    $merge_fittings = get_merge_fittings_array($fittings_index, $fittings_list); //配件商品重新分组

                    $fitts = get_fittings_array_list($merge_fittings, $goods_fittings);
                    for ($i = 0; $i < count($fitts); $i++) {
                        $fittings_interval = $fitts[$i]['fittings_interval'];

                        $res['fittings_interval'][$i]['fittings_minMax'] = price_format($fittings_interval['fittings_min']) . "-" . number_format($fittings_interval['fittings_max'], 2, '.', '');
                        $res['fittings_interval'][$i]['market_minMax'] = price_format($fittings_interval['market_min']) . "-" . number_format($fittings_interval['market_max'], 2, '.', '');

                        if ($fittings_interval['save_minPrice'] == $fittings_interval['save_maxPrice']) {
                            $res['fittings_interval'][$i]['save_minMaxPrice'] = price_format($fittings_interval['save_minPrice']);
                        } else {
                            $res['fittings_interval'][$i]['save_minMaxPrice'] = price_format($fittings_interval['save_minPrice']) . "-" . number_format($fittings_interval['save_maxPrice'], 2, '.', '');
                        }

                        $res['fittings_interval'][$i]['groupId'] = $fittings_interval['groupId'];
                    }
                }
            }
        } else {
            // 切换属性
            $combo_goods = get_cart_combo_goods_list($goods_id, $parent_id, $group_id, $uid);

            if ($combo_goods['combo_number'] > 0) {
                $goods_info = get_goods_fittings_info($parent_id, $this->warehouse_id, $this->area_id, $this->area_city, $group_id, 0, '', [], $uid, $user_rank);
                $fittings = get_goods_fittings([$parent_id], $this->warehouse_id, $this->area_id, $this->area_city, $group_id, 1, [], $uid, $user_rank);
            } else {
                $goods_info = get_goods_fittings_info($parent_id, $this->warehouse_id, $this->area_id, $this->area_city, '', 1, '', [], $uid, $user_rank);
                $fittings = get_goods_fittings([$parent_id], $this->warehouse_id, $this->area_id, $this->area_city, '', 1, [], $uid, $user_rank);
            }

            $fittings = array_merge($goods_info, $fittings);
            $fittings = array_values($fittings);

            $fittings_interval = get_choose_goods_combo_cart($fittings);
            if ($combo_goods['combo_number'] > 0) {
                // 配件商品没有属性时
                $res['fittings_minMax'] = price_format($fittings_interval['all_price_ori']);
                $res['market_minMax'] = price_format($fittings_interval['all_market_price']);
                $res['save_minMaxPrice'] = price_format($fittings_interval['save_price_amount']);
            } else {
                $res['fittings_minMax'] = price_format($fittings_interval['fittings_min']) . "-" . number_format($fittings_interval['fittings_max'], 2, '.', '');
                $res['market_minMax'] = price_format($fittings_interval['market_min']) . "-" . number_format($fittings_interval['market_max'], 2, '.', '');

                if ($fittings_interval['save_minPrice'] == $fittings_interval['save_maxPrice']) {
                    $res['save_minMaxPrice'] = price_format($fittings_interval['save_minPrice']);
                } else {
                    $res['save_minMaxPrice'] = price_format($fittings_interval['save_minPrice']) . "-" . number_format($fittings_interval['save_maxPrice'], 2, '.', '');
                }
            }
        }

        $goodsGroup = explode('_', $group_id);
        $res['groupId'] = $goodsGroup[2];

        if ($attr_id) {
            // 属性图片
            $attr_img = $this->goodsMobileService->getAttrImgFlie($goods_id, $attr_id);
            if (!empty($attr_img['attr_img_flie'])) {
                $res['attr_img'] = $this->dscRepository->getImagePath($attr_img['attr_img_flie']);
            }

            $res['attr_name'] = $this->goodsMobileService->getAttrName($goods_id, $attr_id);
        }

        $res['goods_id'] = $goods_id;
        $res['parent_id'] = $parent_id;
        $res['group_name'] = $group_name;
        $res['load_type'] = $load_type;
        $res['region_id'] = $this->warehouse_id;
        $res['area_id'] = $this->area_id;
        $res['area_city'] = $this->area_city;

        // 点击切换属性 保存到 临时表
        if (($type == 1 && $load_type != 1) || $type == 0) {
            $prod_attr = [];
            if (!empty($attr_id)) {
                $prod_attr = $attr_id;
            }


            if (is_spec($prod_attr) && !empty($attr_id)) {
                $product_info = $this->goodsAttrService->getProductsInfo($goods_id, $prod_attr, $this->warehouse_id, $this->area_id, $this->area_city);
            }

            $goods_attr = get_goods_attr_info_new($attr_id, 'pice');

            // 主件商品
            if ($type == 1) {
                $goods_price = $res['result'];
            } else {
                // 配件商品价格
                $goods_price = $this->goodsMobileService->groupGoodsInfo($goods_id, $parent_id);
                if ($this->config['add_shop_price'] == 1) {
                    $goods_price = $goods_price + $res['spec_price'];
                }
            }

            // 更新信息
            $cart_combo_data = array(
                'goods_attr_id' => implode(',', $attr_id),
                'product_id' => $product_info['product_id'] ?? 0,
                'goods_attr' => addslashes($goods_attr),
                'goods_price' => $goods_price
            );

            $this->goodsMobileService->updateCartCombo($uid, $group_id, $goods_id, $cart_combo_data);
        }

        return $this->succeed($res);
    }

    /**
     * 商品视频列表
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function goodsVideo(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'page' => 'required|integer',
            'size' => 'required|integer',
        ]);

        $page = $request->input('page');
        $size = $request->input('size');

        $uid = $this->uid;
        $where = [
            'warehouse_id' => $this->warehouse_id,
            'area_id' => $this->area_id,
            'area_city' => $this->area_city,
        ];

        $goods_video_list = $this->goodsMobileService->getVideoList($size, $page, $uid, $where);

        return $this->succeed($goods_video_list);
    }
}
