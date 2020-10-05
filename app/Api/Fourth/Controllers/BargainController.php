<?php

namespace App\Api\Fourth\Controllers;

use App\Api\Foundation\Controllers\Controller;
use App\Models\Users;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Bargain\BargainService;
use App\Services\Cart\CartCommonService;
use App\Services\Goods\GoodsAttrService;
use App\Services\Goods\GoodsGalleryService;
use App\Services\Goods\GoodsMobileService;
use App\Services\Goods\GoodsProdutsService;
use App\Services\Goods\GoodsWarehouseService;
use App\Services\Wechat\WechatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Class BargainController
 * @package App\Api\Fourth\Controllers
 */
class BargainController extends Controller
{
    protected $bargainService;
    protected $commonService;
    protected $goodsMobileService;
    protected $goodsAttrService;
    protected $cartCommonService;
    protected $timeRepository;
    protected $wechatService;
    protected $baseRepository;
    protected $goodsGalleryService;
    protected $dscRepository;
    protected $goodsWarehouseService;

    public function __construct(
        BargainService $bargainService,
        GoodsMobileService $goodsMobileService,
        GoodsAttrService $goodsAttrService,
        CartCommonService $cartCommonService,
        TimeRepository $timeRepository,
        WechatService $wechatService,
        BaseRepository $baseRepository,
        GoodsGalleryService $goodsGalleryService,
        DscRepository $dscRepository,
        GoodsWarehouseService $goodsWarehouseService
    )
    {
        $this->bargainService = $bargainService;
        $this->goodsMobileService = $goodsMobileService;
        $this->goodsAttrService = $goodsAttrService;
        $this->cartCommonService = $cartCommonService;
        $this->timeRepository = $timeRepository;
        $this->wechatService = $wechatService;
        $this->baseRepository = $baseRepository;
        $this->goodsGalleryService = $goodsGalleryService;
        $this->dscRepository = $dscRepository;
        $this->goodsWarehouseService = $goodsWarehouseService;
    }

    /**
     * 砍价  --  首页
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        // 获取广告位
        $banner = $this->bargainService->bargainPositions('1020');

        return $this->succeed(['banner' => $banner]);
    }

    /**
     * 砍价  --  商品列表
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function goods(Request $request)
    {
        //验证参数
        $this->validate($request, [
            'page' => 'required|integer',
            'size' => 'required|integer|max:50'
        ]);

        $goods_list = $this->bargainService->GoodsList($request->get('page'), $request->get('size'));

        return $this->succeed($goods_list);
    }

    /**
     * 砍价  --  详情
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function detail(Request $request)
    {
        //验证参数
        $this->validate($request, [
            'id' => 'required|integer',   //砍价活动id
        ]);

        // 返回用户ID
        $user_id = $this->authorization();

        //接收参数
        $id = $request->get('id');
        $bs_id = $request->get('bs_id', 0);

        //返回数据
        $result = [
            'error' => 0,
            'goods_img' => '',         //商品相册
            'goods_info' => '',        //商品信息
            'bargain_info' => '',      //砍价信息
            'bargain_list' => '',      //亲友帮
            'bargain_ranking' => '',   //排行榜
            'bargain_hot' => '',       //砍价爆款
            'goods_properties' => ''   // 商品属性 规格
        ];

        $time = $this->timeRepository->getGmTime();

        // 商品信息
        $goodsInfo = $this->bargainService->goodsInfo($id);

        if (empty($goodsInfo)) {
            return $this->responseNotFound();
        }

        //初始值
        $goodsInfo['bs_id'] = 0;         // 创建砍价活动id
        $goodsInfo['add_bargain'] = 0;   // 是否参与当前活动
        $goodsInfo['is_add_bargain'] = 0;// 互砍时是否参与当前活动
        $goodsInfo['bargain_join'] = 0;  // 已砍价
        $goodsInfo['bargain_bar'] = 0;  // 进度条
        $goodsInfo['final_price'] = '';  // 已砍到价格
        $goodsInfo['bargain_end'] = '';  // 活动到期
        // 当前时间
        $goodsInfo['current_time'] = $time;
        /*获取商品规格参数*/
        $goodsInfo['attr_parameter'] = $this->goodsAttrService->goodsAttrParameter($goodsInfo['goods_id']);

        if ($goodsInfo['is_on_sale'] == 0) {
            $result = ['error' => 1, 'msg' => lang('bargain.goods_not_on_sale')];
            $this->succeed($result);
        }
        if ($goodsInfo['status'] == 1 || $time > $goodsInfo['end_time']) {
            $goodsInfo['bargain_end'] = 1;
        }

        // 是否帮助砍价
        if ($bs_id > 0) {
            $goodsInfo['bs_id'] = $bs_id;
        }

        // 验证是否参与当前活动标示
        $add_bargain = $this->bargainService->isAddBargain($id, $user_id, $bs_id);
        if ($add_bargain) {
            $goodsInfo['bs_id'] = $add_bargain['id'] ?? 0;
            $bs_id = $goodsInfo['bs_id'];
            $goodsInfo['add_bargain'] = 1;    //已参与砍价
        }

        //排行榜
        $bargain_ranking = $this->bargainService->getBargainRanking($id);
        $goodsInfo['ranking_num'] = count($bargain_ranking);  //几人参与活动
        $result['bargain_ranking'] = $bargain_ranking;//排行榜

        /* --验证是否砍价-- */
        if (!empty($bs_id)) {
            //互砍模式中验证是否参与当前活动
            $add_bargain = $this->bargainService->isAddBargain($id, $user_id);
            if ($add_bargain) {
                $goodsInfo['is_add_bargain'] = 1;    //已参与
            }

            // 验证已砍价信息
            $bargain_info = $this->bargainService->isBargainJoin($bs_id, $user_id);
            if ($bargain_info) {
                $goodsInfo['bargain_join'] = 1;    // 已砍价标示
                //用户名、头像
                $user_info = Users::find($bargain_info['user_id']);
                $bargain_info['user_name'] = !empty($user_info->nick_name) ? setAnonymous($user_info->nick_name) : setAnonymous($user_info->user_name);
                $bargain_info['user_picture'] = $this->dscRepository->getImagePath($user_info->user_picture);

                //排行榜
                $bargain_info['ranking_num'] = count($bargain_ranking);  // 几人参与活动
                $rank = $this->baseRepository->getKeyPluck($bargain_ranking, 'user_id');
                // 获取参与砍价信息
                $tatistics_log = $this->bargainService->bargainStatisticsLog($bs_id);
                $rank = array_search($tatistics_log['user_id'], $rank);
                $bargain_info['rank'] = $rank + 1;//排行名次

                $bargain_log = $this->bargainService->bargainLog($bs_id);// 参与活动记录
                $bargain_info['final_price'] = $bargain_log['final_price'];// 已砍到价格
                $bargain_info['subtract_price'] = $this->dscRepository->getPriceFormat($bargain_info['subtract_price'], true); //砍掉价格

                $result['bargain_info'] = $bargain_info;   // 砍价记录
            }

            //亲友帮
            $bargain_list = $this->bargainService->getBargainStatistics($bs_id);
            $bargain_num = count($bargain_list);
            $goodsInfo['bargain_num'] = $bargain_num;//参与砍价人数
            $result['bargain_list'] = $bargain_list;

            //砍后价格,选择属性
            $bargain_log = $this->bargainService->bargainLog($bs_id);//参与活动记录
            $goodsInfo['final_price'] = $bargain_log['final_price'];//已砍到价格

            //获取选中活动属性原价，底价
            if ($bargain_log['goods_attr_id']) {
                $spec = explode(",", $bargain_log['goods_attr_id']);
                sort($spec);
                $goodsInfo['shop_price'] = $this->bargainService->getFinalPrice($goodsInfo['goods_id'], '', true, $spec, $this->warehouse_id, $this->area_id, $this->area_city);//原价
                $goodsInfo['target_price'] = $this->bargainService->bargainTargetPrice($id, $goodsInfo['goods_id'], $spec, $this->warehouse_id, $this->area_id, $this->area_city, $goodsInfo['model_attr']);//底价
                // 商品属性文字输出
                $attrName = $this->goodsAttrService->getAttrNameById($spec);

                $attrNameStr = '';
                foreach ($attrName as $v) {
                    $attrNameStr .= $v['attr_name'] . ':' . $v['attr_value'] . " \n";
                }
                $goodsInfo['attr_name'] = $attrNameStr;
            }
            //进度条
            $surplus = $goodsInfo['shop_price'] - $goodsInfo['target_price'];//差价

            //已砍价总额
            $subtract = $this->bargainService->subtractPriceSum($bs_id);
            $bargain_bar = round($subtract * 100 / $surplus, 0);//百分比
            $goodsInfo['bargain_bar'] = $bargain_bar;//进度条
        }

        //砍价爆款
        $result['bargain_hot'] = $this->bargainService->GoodsList(1, 10, 'is_hot');

        $goodsInfo['goods_img'] = $this->dscRepository->getImagePath($goodsInfo['goods_img']);
        $goodsInfo['goods_thumb'] = $this->dscRepository->getImagePath($goodsInfo['goods_thumb']);
        $goodsInfo['shop_price'] = $this->dscRepository->getPriceFormat($goodsInfo['shop_price'], true);   //原价
        $goodsInfo['goods_price'] = $goodsInfo['shop_price'];
        $goodsInfo['target_price'] = $this->dscRepository->getPriceFormat($goodsInfo['target_price'], true);          //底价
        $goodsInfo['market_price_formated'] = $this->dscRepository->getPriceFormat($goodsInfo['market_price'], true);

        // 商品详情图 PC
        if (empty($goodsInfo['desc_mobile']) && !empty($goodsInfo['goods_desc'])) {
            $goodsInfo['goods_desc'] = $this->dscRepository->getContentImgReplace($goodsInfo['goods_desc']);
        }
        if (!empty($goodsInfo['desc_mobile'])) {
            // 处理手机端商品详情 图片（手机相册图） data/gallery_album/
            $goodsInfo['goods_desc'] = $this->dscRepository->getContentImgReplace($goodsInfo['desc_mobile'], 'goods_desc', 1);
        }

        $result['goods_info'] = $goodsInfo;

        // 商品相册
        $data = ['goods_id' => $goodsInfo['goods_id']];
        $goodsGallery = $this->goodsGalleryService->getGalleryList($data);
        if ($goodsGallery) {
            foreach ($goodsGallery as $k => $v) {
                $goodsGallery[$k] = $v['img_url'];
            }
        } else {
            $goodsGallery[] = $goodsInfo['goods_img'];
        }

        $result['goods_img'] = $goodsGallery;

        // 商品属性 规格
        $row['attr'] = $this->goodsAttrService->goodsAttr($goodsInfo['goods_id']);
        $attr_str = [];
        if ($row['attr']) {
            $row['attr_name'] = '';
            foreach ($row['attr'] as $k => $v) {
                $select_key = 0;

                if ($v['attr_key'][0]['attr_type'] == 0) {
                    unset($row['attr'][$k]);
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
                    $row['attr'][$k]['attr_key'][0]['attr_checked'] = 1;
                }
                if ($row['attr_name']) {
                    $row['attr_name'] = $row['attr_name'] . '' . $v['attr_key'][$select_key]['attr_value'];
                } else {
                    $row['attr_name'] = $v['attr_key'][$select_key]['attr_value'];
                }
                $attr_str[] = $v['attr_key'][$select_key]['goods_attr_id'];
            }

            $result['goods_properties'] = array_values($row['attr']);
        }


        return $this->succeed($result);
    }

    /**
     * 砍价  --  改变属性、数量时重新计算商品价格
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function property(Request $request)
    {
        //验证参数
        $this->validate($request, [
            'id' => 'required|integer',              //砍价活动id
            'num' => 'required|integer',
        ]);

        $id = $request->input('id', 0);
        $num = $request->input('num', 0);
        $attr_id = $request->input('attr_id', 0); // 属性id
        $store_id = 0;

        $result = [
            'stock' => '',             //库存
            'market_price' => '',      //市场价
            'qty' => '',               //数量
            'spec_price' => '',        //属性价格
            'goods_price' => '',       //商品价格(最终使用价格)
            'target_price' => '',      //砍价底价
            'attr_img' => ''           //商品属性图片
        ];

        // 商品信息
        $goodsInfo = $this->bargainService->goodsInfo($id);

        if (empty($goodsInfo)) {
            return $this->succeed($result);
        }

        $result['target_price'] = $goodsInfo['target_price'];
        $result['stock'] = $this->goodsWarehouseService->goodsAttrNumber($goodsInfo['goods_id'], $attr_id, $this->warehouse_id, $this->area_id, $this->area_city, $store_id);

        $result['market_price'] = $this->goodsMobileService->goodsMarketPrice($goodsInfo['goods_id'], $attr_id, $this->warehouse_id, $this->area_id, $this->area_city);

        $result['market_price_formated'] = $this->dscRepository->getPriceFormat($result['market_price'], true);
        $result['qty'] = $num;
        $result['spec_price'] = app(GoodsProdutsService::class)->goodsPropertyPrice($goodsInfo['goods_id'], $attr_id, $this->warehouse_id, $this->area_id, $this->area_city);
        $result['spec_price_formated'] = $this->dscRepository->getPriceFormat($result['spec_price'], true);
        $result['goods_price'] = $goodsInfo['shop_price'];

        if (!empty($attr_id)) {
            sort($attr_id);
            $result['target_price'] = $this->bargainService->bargainTargetPrice($id, $goodsInfo['goods_id'], $attr_id, $this->warehouse_id, $this->area_id, $this->area_city, $goodsInfo['model_attr']);
            $result['goods_price'] = $this->bargainService->getFinalPrice($goodsInfo['goods_id'], $num, true, $attr_id, $this->warehouse_id, $this->area_id, $this->area_city);
        }
        $result['target_price'] = $this->dscRepository->getPriceFormat($result['target_price'], true);
        $result['goods_price'] = $this->dscRepository->getPriceFormat($result['goods_price'], true);
        //商品属性图片
        $attr_img = $this->goodsMobileService->getAttrImgFlie($goodsInfo['goods_id'], $attr_id);

        if (!empty($attr_img['attr_img_flie'])) {
            $result['attr_img'] = $this->dscRepository->getImagePath($attr_img['attr_img_flie']);
        }

        return $this->succeed($result);
    }

    /**
     * 砍价  --  记录 （发起砍价）
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function log(Request $request)
    {
        //验证参数
        $this->validate($request, [
            'id' => 'required|integer',  //砍价活动id
        ]);

        if (!$this->uid) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        //接收参数
        $id = $request->get('id');
        $attr_id = $request->get('attr_id');

        //参与活动记录
        $add_bargain = $this->bargainService->isAddBargain($id, $this->uid);
        if ($add_bargain) {
            $result = [
                'error' => 1,
                'msg' => lang('bargain.bargain_under_way')
            ];
            return $this->succeed($result);
        }

        // 商品信息
        $goodsInfo = $this->bargainService->goodsInfo($id);

        $goodsAttrId = '';
        if (!empty($attr_id)) {
            $goodsAttrId = implode(',', $attr_id);
            $final_price = $this->bargainService->getFinalPrice($goodsInfo['goods_id'], '', true, $attr_id, $this->warehouse_id, $this->area_id, $this->area_city); //原价
        } else {
            $final_price = $goodsInfo['shop_price'];
        }

        // 添加参数
        $arguments = [
            'bargain_id' => $id,
            'goods_attr_id' => $goodsAttrId,
            'user_id' => $this->uid,
            'final_price' => $final_price,
            'add_time' => $this->timeRepository->getGmTime(),
        ];

        //插入参与活动记录表
        $result = $this->bargainService->addBargain($arguments);

        // 商品属性文字输出
        $attrName = $this->goodsAttrService->getAttrNameById($attr_id);
        $attrNameStr = '';
        foreach ($attrName as $v) {
            $attrNameStr .= $v['attr_name'] . ':' . $v['attr_value'] . " \n";
        }
        $result['attr_name'] = $attrNameStr;
        $result['num'] = 1;
        $result['add_bargain'] = 1;

        //更新活动参与人数
        $this->bargainService->updateBargain($id);

        return $this->succeed($result);
    }

    /**
     * 砍价  --  砍价 （参与砍价）
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function bid(Request $request)
    {
        //验证参数
        $this->validate($request, [
            'id' => 'required|integer',      //砍价活动id
            'bs_id' => 'required|integer'    //发起活动id
        ]);

        // 返回用户ID
        $user_id = $this->authorization();
        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        //接收参数
        $id = $request->get('id');
        $bs_id = $request->get('bs_id');

        // 砍价商品信息
        $bargain = $this->bargainService->goodsInfo($id);

        //参与活动记录
        $bs_log = $this->bargainService->bargainLog($bs_id);

        //获取选中活动属性底价
        if ($bs_log['goods_attr_id']) {
            $spec = explode(",", $bs_log['goods_attr_id']);
            sort($spec);
            $bargain['target_price'] = $this->bargainService->bargainTargetPrice($id, $bargain['goods_id'], $spec, $this->warehouse_id, $this->area_id, $this->area_city, $bargain['model_attr']);//底价
        }

        //验证是参与砍价
        $number = $this->bargainService->bargainLogNumber($bs_id, $user_id);
        if ($number > 0) {
            $result = [
                'error' => 1,
                'msg' => lang('bargain.bargain_you_involved')
            ];
            return $this->succeed($result);
        }

        //砍价规则
        if ($bargain['target_price'] == $bs_log['final_price']) {
            if (is_wechat_browser() && file_exists(MOBILE_WECHAT)) {
                $pushData = [
                    'keyword1' => ['value' => $bargain['goods_name'], 'color' => '#173177'],     //商品名称
                    'keyword2' => ['value' => $bargain['target_price'], 'color' => '#173177']    //底价
                ];
                $url = dsc_url('/#/bargain/detail/' . $id);
                $this->wechatService->push_template('OPENTM410292733', $pushData, $url, $bs_log['user_id']);
            }
            $result = [
                'error' => 1,
                'msg' => lang('bargain.bargain_lowest_price')
            ];
            return $this->succeed($result);
        } else {
            // $subtract_price = rand($bargain['min_price'], $bargain['max_price']);//砍掉价格区间
            $subtract_price = $this->randomFloat($bargain['min_price'], $bargain['max_price']);
            $subtract = $bs_log['final_price'] - $subtract_price;//已砍价到
            if ($subtract < $bargain['target_price']) {
                $subtract_price = $bs_log['final_price'] - $bargain['target_price'];
            }
        }

        // 添加参数
        $arguments = [
            'bs_id' => $bs_id,
            'user_id' => $user_id,
            'subtract_price' => $subtract_price,
            'add_time' => $this->timeRepository->getGmTime(),
        ];

        //插入参与砍价记录表
        $add = $this->bargainService->addBargainStatistics($arguments);

        if ($add) {
            //更新参与砍价人数 和砍后最终购买价
            $count_num = $bs_log['count_num'] + 1;
            $final_price = $bs_log['final_price'] - $subtract_price; //砍后价格
            $this->bargainService->updateBargainStatistics($bs_id, $count_num, $final_price);

            //验证是否参与当前活动标示
            $add_bargain = 0;
            $add_bargain_info = $this->bargainService->isAddBargain($id, $user_id);

            if ($add_bargain_info) {
                $add_bargain = 1;    //已参与
            }

            $result = [
                'error' => 0,
                'subtract_price' => $subtract_price, //砍掉价格
                'final_price' => $final_price,       //砍后价格
                'add_bargain' => $add_bargain,
                'bs_id' => $bs_id,
                'bargain_join' => 1,
                'msg' => lang('bargain.bargain_success')
            ];
        } else {
            $result = [
                'error' => 1,
                'msg' => lang('bargain.bargain_fail')
            ];
        }

        return $this->succeed($result);
    }

    // 生成带有小数的随机数
    public function randomFloat($min = 0, $max = 1)
    {
        $num = $min + mt_rand() / mt_getrandmax() * ($max - $min);
        return sprintf("%.2f", $num);  //控制小数后几位
    }

    /**
     * 砍价 -- 购买
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function buy(Request $request)
    {
        //验证参数
        $this->validate($request, [
            'id' => 'required|integer',      //砍价活动id
            'bs_id' => 'required|integer',    //发起活动id
            'num' => 'required|integer',
            'goods_id' => 'required|integer',
        ]);

        // 返回用户ID
        $user_id = $this->authorization();
        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        //接收参数
        $id = $request->get('id');
        $bs_id = $request->get('bs_id');
        $num = $request->get('num');
        $goods_id = $request->get('goods_id');

        // 商品信息
        $goods = $this->bargainService->goodsInfo($id);

        if ($goods['is_on_sale'] != 1) {
            $result = [
                'error' => 1,
                'msg' => lang('bargain.bargain_fail')
            ];
            return $this->succeed($result);
        }

        // 货品
        $bs_log = $this->bargainService->bargainLog($bs_id);//参与活动记录

        $goodsAttrId = $bs_log['goods_attr_id'];  //属性
        if (!isset($goodsAttrId) || $goodsAttrId == '') {
            $goodsAttr = [];
        } else {
            $goodsAttr = explode(',', $goodsAttrId);
        }
        $product = $this->goodsMobileService->getProductByGoods($goods_id, implode('|', $goodsAttr));

        if (empty($product)) {
            $product['id'] = 0;
        }
        // 商品属性文字输出
        $attrName = $this->goodsAttrService->getAttrNameById($goodsAttr);

        $attrNameStr = '';
        foreach ($attrName as $v) {
            $attrNameStr .= $v['attr_name'] . ':' . $v['attr_value'] . " \n";
        }

        //库存
        $attr_number = $this->goodsWarehouseService->goodsAttrNumber($goods_id, $goodsAttr, $this->warehouse_id, $this->area_id, $this->area_city);

        if ($num > $attr_number) {
            $result = [
                'error' => 1,
                'msg' => lang('bargain.bargain_goods_understock')
            ];
            return $this->succeed($result);
        }

        /* 更新：清空当前会员购物车中砍价商品 */
        $this->cartCommonService->clearCart($user_id, CART_BARGAIN_GOODS);
        // 计算商品价格
        $goodsPrice = $bs_log['final_price'];

        $time = $this->timeRepository->getGmTime();

        //参数
        $arguments = [
            'goods_id' => $goods['goods_id'],
            'user_id' => $user_id,
            'goods_sn' => $goods['goods_sn'],
            'product_id' => empty($product['id']) ? '' : $product['id'],
            'group_id' => '',
            'goods_name' => $goods['goods_name'],
            'market_price' => $goods['market_price'],
            'goods_price' => $goodsPrice,
            'goods_number' => $num,
            'goods_attr' => $attrNameStr,
            'is_real' => $goods['is_real'],
            'extension_code' => empty($extension_code) ? 'bargain' : $extension_code,
            'parent_id' => 0,
            'rec_type' => CART_BARGAIN_GOODS,  // 购物车商品类型
            'is_gift' => 0,
            'is_shipping' => $goods['is_shipping'],
            'can_handsel' => '',
            'model_attr' => $goods['model_attr'],
            'goods_attr_id' => $goodsAttrId,
            'ru_id' => $goods['user_id'],
            'shopping_fee' => '',
            'warehouse_id' => '',
            'area_id' => '',
            'add_time' => $time,
            'store_id' => '',
            'freight' => $goods['freight'],
            'tid' => $goods['tid'],
            'shipping_fee' => $goods['shipping_fee'],
            'store_mobile' => '',
            'take_time' => '',
            'is_checked' => '1',
        ];

        $result = [];
        $cart_id = $this->bargainService->addGoodsToCart($arguments);
        if ($cart_id) {
            $result['error'] = 0;
            $result['msg'] = lang('bargain.bargain_added_cart');
            $result['rec_type'] = CART_BARGAIN_GOODS;
            $result['bs_id'] = $bs_id;
        }

        return $this->succeed($result);
    }

    /**
     * 砍价 -- 我参与de砍价
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function myBuy(Request $request)
    {
        //验证参数
        $this->validate($request, [
            'page' => 'required|integer',
            'size' => 'required|integer|max:50'
        ]);

        // 返回用户ID
        $user_id = $this->authorization();
        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        //接收参数
        $page = $request->get('page');
        $size = $request->get('size');

        $list = $this->bargainService->myBargain($user_id, $page, $size);

        return $this->succeed($list);
    }
}
