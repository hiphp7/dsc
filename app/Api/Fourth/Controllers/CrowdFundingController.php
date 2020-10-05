<?php

namespace App\Api\Fourth\Controllers;

use App\Api\Foundation\Controllers\Controller;
use App\Models\Users;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\CrowdFund\CrowdFlowService;
use App\Services\CrowdFund\CrowdFundService;
use App\Services\Flow\FlowUserService;
use App\Services\Order\OrderCommonService;
use App\Services\Payment\PaymentService;
use App\Services\User\UserAddressService;
use App\Services\User\UserRankService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CrowdFundingController extends Controller
{
    protected $crowdFundService;
    protected $crowdFlowService;
    protected $timeRepository;
    protected $paymentService;
    protected $dscRepository;
    protected $userRankService;
    protected $config;
    protected $flowUserService;
    protected $orderCommonService;
    protected $userAddressService;

    public function __construct(
        CrowdFundService $crowdFundService,
        CrowdFlowService $crowdFlowService,
        TimeRepository $timeRepository,
        PaymentService $paymentService,
        DscRepository $dscRepository,
        UserRankService $userRankService,
        FlowUserService $flowUserService,
        OrderCommonService $orderCommonService,
        UserAddressService $userAddressService
    )
    {
        //加载外部类
        $files = [
            'common',
            'clips',
            'order',
            'base',
        ];
        load_helper($files);

        $this->crowdFundService = $crowdFundService;
        $this->crowdFlowService = $crowdFlowService;
        $this->timeRepository = $timeRepository;
        $this->paymentService = $paymentService;
        $this->dscRepository = $dscRepository;
        $this->userRankService = $userRankService;
        $this->config = $this->dscRepository->dscConfig();
        $this->flowUserService = $flowUserService;
        $this->orderCommonService = $orderCommonService;
        $this->userAddressService = $userAddressService;
    }

    /**
     * 众筹  --  首页
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function index(Request $request)
    {
        //验证参数
        $this->validate($request, []);

        // 获取众筹顶级分类
        $category = $this->crowdFundService->getZcCategoryParents();
        $categorylist = $category['cate_one'] ?? [];

        return $this->succeed($categorylist);
    }

    /**
     * 众筹  --  商品
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function goods(Request $request)
    {
        //验证参数
        $this->validate($request, [
            'status' => 'required|string', //综合排序  id(活动id)  new(最新上线)  amount（金额最多） join_num（支持最多）
            'cat_id' => 'required|integer',
            'page' => 'required|integer',
            'size' => 'required|integer'
        ]);
        $cat_id = $request->get('cat_id');

        // 获取众筹分类id
        $str_id = '';
        if ($cat_id > 0) {
            $category = $this->crowdFundService->getZcCategoryParents($cat_id, 1);
            $str_id = $category['str_id'];
        }

        $order = $request->get('status', 'id'); // 综合排序
        $keyword = $request->get('keyword', '');

        $goods = $this->crowdFundService->getZcProjectList($keyword, $str_id, $order, $request->get('page'), $request->get('size'));

        return $this->succeed($goods);
    }

    /**
     * 众筹  --  详情
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function show(Request $request)
    {
        //验证参数
        $this->validate($request, [
            'id' => 'required|integer'
        ]);

        // 返回用户ID
        $user_id = $this->authorization();
        // 接收参数
        $id = $request->get('id');
        // 返回数组
        $result = [
            'user_info' => '',          // 发起人信息
            'info' => '',                // 众筹项目
            'goods' => '',            // 众筹方案
            'progress' => '',            // 众筹项目动态
            'backer_list' => '',        // 众筹项目支持者
            'topic_list' => ''        // 众筹项目话题
        ];

        // 发起人信息
        $result['user_info'] = $this->crowdFundService->getInitiatorInfo($id);

        // 众筹项目
        $info = $this->crowdFundService->zcGoodsInfo($id);

        // 检查是否已经存在于用户的收藏夹
        $collect = $this->crowdFundService->getZcFocus($id, $user_id);
        $info['is_collect'] = 0;
        if ($collect > 0) {
            $info['is_collect'] = 1;
        }
        $result['info'] = $info;

        // 众筹方案列表
        $result['goods'] = $this->crowdFundService->getZcGoods($id);

        // 众筹项目动态
        $result['progress'] = $this->crowdFundService->getZcProgress($id);

        // 众筹项目支持者
        $result['backer_list'] = $this->crowdFundService->getBackerList($id);

        // 众筹项目话题
        $result['topic_list'] = $this->crowdFundService->getTopicList($id);

        return $this->succeed($result);
    }

    /**
     * 众筹  --  关注
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function focus(Request $request)
    {
        //验证参数
        $this->validate($request, [
            'id' => 'required|integer'
        ]);

        $time = $this->timeRepository->getGmTime();

        // 返回用户ID
        $user_id = $this->authorization();
        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $pid = $request->get('id');

        // 检查是否已经存在于用户的收藏夹
        $collect = $this->crowdFundService->getZcFocus($pid, $user_id);

        if ($collect > 0) {
            $rs = $this->crowdFundService->deleteFocus($pid, $user_id);

            $result['list'] = $rs;
            $result['error'] = 0;
            $result['msg'] = lang('crowdfunding.movefollow_zc_succeed');
        } else {
            $data = [
                'user_id' => $user_id,
                'pid' => $request->get('id'),
                'add_time' => $time
            ];

            $rs = $this->crowdFundService->addFocus($data);

            $result['list'] = $rs;
            $result['error'] = 0;
            $result['msg'] = lang('crowdfunding.follow_zc_succeed');
        }

        return $this->succeed($result);
    }

    /**
     * 众筹  --  发布话题
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function topic(Request $request)
    {
        //验证参数
        $this->validate($request, [
            'id' => 'required|integer',
            'topic_id' => 'required|integer'
        ]);

        // 返回用户ID
        $user_id = $this->authorization();
        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $pid = $request->get('id');
        $topic_content = $request->get('content', '');
        $topic_id = $request->get('topic_id');

        $time = $this->timeRepository->getGmTime();

        $data = [
            'parent_topic_id' => $topic_id,
            'topic_status' => 1,
            'topic_content' => $topic_content,
            'user_id' => $user_id,
            'pid' => $pid,
            'add_time' => $time
        ];

        // 发布话题
        $topic_list = $this->crowdFundService->addTopic($data);
        $result['error'] = 0;
        $result['msg'] = lang('common.lang_crowd_art_succeed');
        $result['topic_list'] = $topic_list;

        return $this->succeed($result);
    }

    /**
     * 众筹  --  选择方案
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function property(Request $request)
    {
        //验证参数
        $this->validate($request, [
            'id' => 'required|integer',   // 方案id
            'pid' => 'required|integer',  // 活动id
            'number' => 'required|integer'
        ]);

        // 接收参数
        $number = $request->get('number');

        // 选中方案信息
        $goods = $this->crowdFundService->getZcGoodsInfo($request->get('pid'), $request->get('id'));

        if ($goods['limit'] >= 0) {
            $surplus_num = $goods['limit'] - $goods['backer_num'];
            if ($number <= 0) {
                $res ['qty'] = 1;
            } else {
                $res ['qty'] = $number;
            }
            if ($number > $surplus_num) {
                $res ['msg'] = lang('crowdfunding.zc_goods_number_limit');
                $res ['error'] = 1;
                $this->succeed($res);
            }
        }

        $res ['result'] = $this->dscRepository->getPriceFormat($goods['price']);

        return $this->succeed($res);
    }

    /**
     * 众筹  --  众筹描述
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function properties(Request $request)
    {
        //验证参数
        $this->validate($request, [
            'id' => 'required|integer'
        ]);

        // 众筹描述
        $info = $this->crowdFundService->getProperties($request->get('id'));

        return $this->succeed($info);
    }

    /**
     * 众筹  --  话题
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function topicList(Request $request)
    {
        //验证参数
        $this->validate($request, [
            'id' => 'required|integer',
            'page' => 'required|integer',
            'size' => 'required|integer'
        ]);

        $pid = $request->get('id');
        $page = $request->get('page', 1);
        $size = $request->get('size', 10);

        // 众筹项目话题
        $topic_list = $this->crowdFundService->getTopicList($pid, true, $page, $size);

        return $this->succeed($topic_list);
    }

    /**
     * 众筹  --  订单确认
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function checkout(Request $request)
    {
        //验证参数
        $this->validate($request, [
            'pid' => 'required|integer',   // 活动id
            'id' => 'required|integer',    // 方案id
            'number' => 'required|integer'
        ]);

        // 返回用户ID
        $user_id = $this->authorization();
        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $pid = $request->get('pid');
        $id = $request->get('id');
        $number = $request->get('number');

        /* 取得购物类型 */
        $flow_type = $request->get('flow_type', CART_GENERAL_GOODS);

        $result = [
            'default_address' => '', // 获取用户默认收货地址
            'cart_goods' => '',      // 购物车商品total
            'total' => '',           // 计算订单的费用
            'pid' => $pid,    // 活动id
            'id' => $id,      // 方案id
            'number' => $number
        ];

        // 获取用户默认收货地址
        $address = $this->userAddressService->getDefaultByUserId($user_id);
        /* 检查收货人信息是否完整 */
        if (!$this->flowUserService->checkConsigneeInfo($address, $flow_type, $user_id)) {
            $result['error'] = 'address';
            $result['msg'] = lang('common.lang_crowd_not_address');
        }

        $default_address = [];
        if ($address) {
            $default_address['consignee'] = $address['consignee'];
            $default_address['mobile'] = $address['mobile'];
            $default_address['province'] = $this->crowdFundService->get_region_name($address['province']);
            $default_address['city'] = $this->crowdFundService->get_region_name($address['city']);
            $default_address['district'] = $this->crowdFundService->get_region_name($address['district']);
            $default_address['address'] = $address['address'];
            $default_address['mobile'] = $address['mobile'];
        }
        $result['default_address'] = $default_address;

        // 购物车商品
        $cart_goods = $this->crowdFlowService->getCartGoods($pid, $id, $number);
        $result['cart_goods'] = $cart_goods;

        $shengyu = $cart_goods['limit'] - $cart_goods['backer_num'];
        // 众筹方案开启“无限额”购，limit 为 -1 ，$shengyu 不会等于0
        if ($shengyu == 0) {
            $result['error'] = 1;
            $result['msg'] = lang('crowdfunding.Sold_out');
            return $this->succeed($result);
        }

        /* 取得货到付款手续费 */
        $cod_fee = 0;

        /*取得支付列表*/
        $payment_list = $this->paymentService->availablePaymentList(0, $cod_fee);
        if ($payment_list) {
            foreach ($payment_list as $key => $payment) {
                if ($payment ['pay_code'] == 'cod') {
                    unset($payment_list [$key]);
                }
            }
            $result['payment_list'] = $payment_list;
        }
        // 取得订单信息
        $order = flow_order_info($user_id);
        $result['order'] = $order;

        // 计算订单的费用
        $total = $this->crowdFlowService->getOrderFee($cart_goods);
        $result['total'] = $total;

        /*判断余额是否足够*/
        $list['use_surplus'] = 0;
        if ($this->config['use_surplus'] == 1) {
            $use_surplus = Users::where('user_id', $user_id)->value('user_money');
            if ($use_surplus > $total['amount']) {
                $result['use_surplus'] = 1;
            }
        }

        return $this->succeed($result);
    }

    /**
     * 众筹  --  订单确认
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function done(Request $request)
    {
        //验证参数
        $this->validate($request, [
            'pid' => 'required|integer',   // 活动id
            'id' => 'required|integer',    // 方案id
            'number' => 'required|integer'
        ]);

        $pay_id = $request->input('pay_id', 0);
        $is_surplus = $request->input('is_surplus', 1);

        // 返回用户ID
        $user_id = $this->authorization();
        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $gmtime = $this->timeRepository->getGmTime();

        // 获取用户默认收货地址
        $consignee = $this->userAddressService->getDefaultByUserId($user_id);

        // 判断重复商品订单 是否支付
        $zc_order_num = $this->crowdFlowService->getZcOrderNum($user_id, $request->get('id'));
        if ($zc_order_num > 0) {
            $result['error'] = 1;
            $result['msg'] = lang('common.lang_crowd_not_pay');
            return $this->succeed($result);
        }

        // 购物车商品
        $cart_goods = $this->crowdFlowService->getCartGoods($request->get('pid'), $request->get('id'), $request->get('number'));
        $shengyu = $cart_goods['limit'] - $cart_goods['backer_num'];
        // 众筹方案开启“无限额”购，limit 为 -1，$shengyu不会等于0
        if ($shengyu == 0) {
            $result['error'] = 1;
            $result['msg'] = lang('crowdfunding.Sold_out');
            return $this->succeed($result);
        }

        if (empty($pay_id) && $is_surplus == 0) {
            $result['msg'] = lang('flow.please_checked_pay');
            return $this->succeed($result);
        }

        //快递配送方式
        $order = [
            'pay_id' => $pay_id,
            'user_id' => $user_id,
            'surplus' => 0,  // 输入余额
            'add_time' => $gmtime,
            'order_status' => OS_CONFIRMED,
            'shipping_status' => SS_UNSHIPPED,
            'pay_status' => PS_UNPAYED,
            'postscript' => $request->get('postscript', ''),
        ];

        /* 检查积分余额是否合法 */
        if ($user_id > 0) {
            $user_info = $this->crowdFundService->getUserInfo($user_id);

            $order['surplus'] = min($order['surplus'], $user_info['user_money'] + $user_info['credit_line']);
            if ($order['surplus'] < 0) {
                $order['surplus'] = 0;
            }
        } else {
            $order['surplus'] = 0;
        }

        /** 收货人信息 */
        $order['consignee'] = $consignee['consignee'];
        $order['country'] = $consignee['country'];
        $order['province'] = $consignee['province'];
        $order['city'] = $consignee['city'];
        $order['mobile'] = $consignee['mobile'];
        $order['tel'] = $consignee['tel'];
        $order['zipcode'] = $consignee['zipcode'];
        $order['district'] = $consignee['district'];
        $order['address'] = $consignee['address'];

        // 计算订单的费用
        $total = $this->crowdFlowService->getOrderFee($cart_goods);

        $order['goods_amount'] = $total['goods_price'];

        $order['order_amount'] = number_format($total['amount'], 2, '.', '');

        // 配送方式
        $shipping_id = $this->crowdFlowService->getSellerShopinfoShipping();
        if ($shipping_id) {
            $shipping = $this->crowdFlowService->getShippingInfo($shipping_id);
            $order['shipping_name'] = addslashes($shipping['shipping_name']);
            $order['shipping_id'] = $shipping['shipping_id'];
        } else {
            $order['shipping_name'] = '';
        }

        /* 配送费用 */
        $order['shipping_fee'] = 0;
        if ($total['shipping_fee'] > 0) {
            $order['shipping_fee'] = $total['shipping_fee'];
        }

        /* 支付方式 */
        if ($order['pay_id'] > 0) {
            $payment = payment_info($order['pay_id']);
            $order['pay_name'] = addslashes($payment['pay_name']);
        }

        /* 如果全部使用余额支付，检查余额是否足够 */
        if ($is_surplus == 1 && $order['order_amount'] > 0) {
            if ($order['surplus'] > 0) { //余额支付里如果输入了一个金额
                $order['order_amount'] = $order['order_amount'] + $order['surplus'];
                $order['surplus'] = 0;
            }
            if ($order['order_amount'] > ($user_info['user_money'] + $user_info['credit_line'])) {
                $result['error'] = 1;
                $result['msg'] = lang('shopping_flow.balance_not_enough');
                return $this->succeed($result);
            } else {
                $order['surplus'] = $order['order_amount'];
                $order['order_amount'] = 0;
            }

            $payment = payment_info('balance', 1);
            $order['pay_name'] = isset($payment['pay_name']) ? addslashes($payment['pay_name']) : '';
            $order['pay_id'] = $payment['pay_id'] ?? 0;
        }

        /* 如果订单金额为0（使用余额或积分或红包支付），修改订单状态为已确认、已付款 */
        if ($order['order_amount'] <= 0) {
            $order['order_status'] = OS_CONFIRMED;
            $order['confirm_time'] = $gmtime;
            $order['pay_status'] = PS_PAYED;
            $order['pay_time'] = $gmtime;
            $order['order_amount'] = 0;
        }

        // 订单来源
        $order['from_ad'] = '0';
        $order['referer'] = 'touch';

        $order['is_zc_order'] = 1;
        $order['zc_goods_id'] = $request->get('id');

        // 插入订单
        $order['order_sn'] = $this->orderCommonService->getOrderSn(); //获取新订单号
        $order['order_id'] = $this->crowdFlowService->addOrderInfo($order);

        /* 处理余额、积分、红包 */
        if ($order['user_id'] > 0 && $order['surplus'] > 0) {
            log_account_change($order['user_id'], $order['surplus'] * (-1), 0, 0, 0, '支付订单:' . $order['order_sn']);
            // 付款更新众筹信息
            $this->crowdFlowService->updateZcProject($order['order_id']);
        }

        /* 插入支付日志 */
        $order['log_id'] = insert_pay_log($order['order_id'], $order['order_amount'], PAY_ORDER);

        return $this->succeed($order['order_sn']);
    }

    /**
     * 众筹  --  众筹中心
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function user(Request $request)
    {
        //验证参数
        $this->validate($request, []);

        // 返回用户ID
        $user_id = $this->authorization();
        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        // 会员等级
        $rank = $this->userRankService->getUserRankInfo($user_id);
        // 会员信息
        $user_info = $this->crowdFundService->getUserInfo($user_id);

        if ($user_info) {
            $user_info['user_name'] = !empty($user_info['nick_name']) ? $user_info['nick_name'] : $user_info['user_name'];
            $user_info['user_picture'] = $this->dscRepository->getImagePath($user_info['user_picture']);
            $user_info['rank_name'] = $rank['rank_name'];
        }

        return $this->succeed($user_info);
    }

    /**
     * 众筹  --  众筹中心项目推荐
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function crowdBest(Request $request)
    {
        //验证参数
        $this->validate($request, [
            'page' => 'required|integer',
            'size' => 'required|integer'
        ]);

        $goods = $this->crowdFundService->getZcProjectList('', '', '', $request->get('page'), $request->get('size'), 'is_best');

        return $this->succeed($goods);
    }

    /**
     * 众筹  --  我的关注
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function myFocus(Request $request)
    {
        //验证参数
        $this->validate($request, [
            'status' => 'required|integer', // 0全部  1进行中  2已完成
            'page' => 'required|integer',
            'size' => 'required|integer'
        ]);

        // 返回用户ID
        $user_id = $this->authorization();
        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $focus_list = $this->crowdFundService->getMyFocus($user_id, $request->get('status'), $request->get('page'), $request->get('size'));

        return $this->succeed($focus_list);
    }

    /**
     * 众筹  --  我的支持
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function crowdBuy(Request $request)
    {
        //验证参数
        $this->validate($request, [
            'status' => 'required|integer', // 0全部  1进行中  2已完成
            'page' => 'required|integer',
            'size' => 'required|integer'
        ]);

        // 返回用户ID
        $user_id = $this->authorization();
        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $buy_list = $this->crowdFundService->getCrowdBuy($user_id, $request->get('status'), $request->get('page'), $request->get('size'));

        return $this->succeed($buy_list);
    }

    /**
     * 众筹  --  我的订单
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function order(Request $request)
    {
        //验证参数
        $this->validate($request, [
            'status' => 'required|integer', // 0全部  1待支付  2代发货 3待收货 4已完成
            'page' => 'required|integer',
            'size' => 'required|integer'
        ]);

        // 返回用户ID
        $user_id = $this->authorization();
        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $order = $this->crowdFundService->getOrderList($user_id, $request->get('status'), $request->get('page'), $request->get('size'));

        return $this->succeed($order);
    }

    /**
     * 众筹  --  我的订单详情
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function detail(Request $request)
    {
        //验证参数
        $this->validate($request, [
            'order_id' => 'required|integer'
        ]);

        // 返回用户ID
        $user_id = $this->authorization();
        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        $order = $this->crowdFundService->getOrderDetail($user_id, $request->get('order_id'));

        return $this->succeed($order);
    }
}
