<?php

namespace App\Custom\Distribute\Services;

use App\Custom\Distribute\Models\DrpActivityDetailes;
use App\Custom\Distribute\Repositories\AccountLogRepository;
use App\Custom\Distribute\Repositories\DrpAccountLogRepository;
use App\Custom\Distribute\Repositories\DrpLogRepository;
use App\Custom\Distribute\Repositories\DrpShopRepository;
use App\Custom\Distribute\Repositories\DrpTransferLogRepository;
use App\Custom\Distribute\Sdk\Mchpay;
use App\Models\AccountLog;
use App\Models\DrpShop;
use App\Models\Goods;
use App\Models\OrderGoods;
use App\Models\OrderInfo;
use App\Models\Users;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Common\ConfigService;
use App\Services\Goods\GoodsCommonService;
use App\Services\User\UserRankService;
use App\Services\User\VerifyService;

/**
 * Class DistributeService
 * @package App\Custom\Distribute\Services
 */
class DistributeService
{
    protected $timeRepository;
    protected $baseRepository;
    protected $accountLogRepository;
    protected $dscRepository;
    protected $drpTransferLogRepository;
    protected $configService;
    protected $drpCommonService;
    protected $drpShopRepository;
    protected $drpLogRepository;
    protected $drpAccountLogRepository;

    public function __construct(
        TimeRepository $timeRepository,
        BaseRepository $baseRepository,
        AccountLogRepository $accountLogRepository,
        DscRepository $dscRepository,
        DrpTransferLogRepository $drpTransferLogRepository,
        ConfigService $configService,
        DrpCommonService $drpCommonService,
        DrpShopRepository $drpShopRepository,
        DrpLogRepository $drpLogRepository,
        DrpAccountLogRepository $drpAccountLogRepository
    )
    {
        $this->timeRepository = $timeRepository;
        $this->baseRepository = $baseRepository;
        $this->accountLogRepository = $accountLogRepository;
        $this->dscRepository = $dscRepository;
        $this->drpTransferLogRepository = $drpTransferLogRepository;
        $this->configService = $configService;
        $this->drpCommonService = $drpCommonService;
        $this->drpShopRepository = $drpShopRepository;
        $this->drpLogRepository = $drpLogRepository;
        $this->drpAccountLogRepository = $drpAccountLogRepository;
    }

    /**
     * 用户订单商品
     * @param int $user_id
     * @param string $goods_ids
     * @return mixed
     */
    public function orderGoodsCount($user_id = 0, $goods_ids = '')
    {
        if (empty($user_id) || empty($goods_ids)) {
            return 0;
        }

        if (is_string($goods_ids)) {
            $goods_id_arr = explode(',', $goods_ids);

            $model = OrderGoods::where('user_id', $user_id)->whereIn('goods_id', $goods_id_arr);

            // 已确认、已支付、已收货订单
            $model = $model->whereHas('getOrder', function ($query) {
                $query->where('order_status', OS_CONFIRMED)->where('pay_status', PS_PAYED)->where('shipping_status', SS_RECEIVED);
            });

            $count = $model->count('goods_id');

            return $count;
        }

        return 0;
    }

    /**
     * 选中商品列表
     * @param int $user_id
     * @param string $goods_ids
     * @return array
     */
    public function selectGoodsList($user_id = 0, $goods_ids = '')
    {
        if (empty($goods_ids)) {
            return [];
        }

        if (is_string($goods_ids)) {
            $goods_id_arr = explode(',', $goods_ids);

            $model = Goods::where('is_on_sale', 1)
                ->where('is_alone_sale', 1)
                ->where('is_delete', 0)
                ->where('review_status', '>', 2);

            $model = $model->whereIn('goods_id', $goods_id_arr);

            if (isset($user_id) && $user_id > 0) {
                $rank = app(UserRankService::class)->getUsersRankInfo($user_id);
                $user_rank = isset($rank['rank_id']) ? $rank['rank_id'] : 1;
                $user_discount = isset($rank['discount']) ? $rank['discount'] : 100;
            } else {
                $user_rank = 1;
                $user_discount = 100;
            }

            $model = $model->with([
                'getMemberPrice' => function ($query) use ($user_rank) {
                    $query->where('user_rank', $user_rank);
                }
            ]);

            $list = $model->select('goods_id', 'goods_name', 'goods_thumb', 'shop_price', 'promote_price', 'buy_drp_show', 'membership_card_id')->orderBy('sort_order', 'ASC')
                ->orderBy('goods_id', 'DESC')
                ->get();
            $list = $list ? $list->toArray() : [];

            if ($list) {
                foreach ($list as $k => $goods) {
                    $list[$k]['goods_thumb'] = empty($goods['goods_thumb']) ? '' : $this->dscRepository->getImagePath($goods['goods_thumb']);

                    $price = [
                        'user_price' => isset($goods['get_member_price']['user_price']) ? $goods['get_member_price']['user_price'] : 0,
                        'percentage' => isset($goods['get_member_price']['percentage']) ? $goods['get_member_price']['percentage'] : 0,
                        'shop_price' => isset($goods['shop_price']) ? $goods['shop_price'] : 0,
                        'promote_price' => isset($goods['promote_price']) ? $goods['promote_price'] : 0,
                    ];

                    $price = app(GoodsCommonService::class)->getGoodsPrice($price, $user_discount / 100, $goods);

                    $list[$k]['shop_price'] = round($price['shop_price'], 2);
                    $list[$k]['shop_price_formated'] = $this->dscRepository->getPriceFormat($price['shop_price']);

                    // 用户已购买商品
                    $list[$k]['is_buy'] = $this->is_buy_goods($user_id, $goods['goods_id']);
                }
            }

            return $list;
        }

        return [];
    }

    /**
     * 是否购买此商品
     * @param int $user_id
     * @param int $goods_id
     * @return int
     */
    public function is_buy_goods($user_id = 0, $goods_id = 0)
    {
        if (empty($user_id)) {
            return 0;
        }

        // membership_card_id已加入购买成为分销商商品 条件
        $model = OrderGoods::where('user_id', $user_id)->where('goods_id', $goods_id)->where('membership_card_id', '>', 0);

        // 已确认、已支付、已收货订单
        $model = $model->whereHas('getOrder', function ($query) {
            $query->whereIn('order_status', [OS_CONFIRMED, OS_SPLITED, OS_SPLITING_PART])->where('pay_status', PS_PAYED)->where('shipping_status', SS_RECEIVED);
        });

        $count = $model->count('goods_id');

        return $count ? 1 : 0;
    }

    /**
     * 获取购买指定商品订单金额
     * @param int $user_id
     * @param int $membership_card_id
     * @return array
     */
    public function getBuyGoodsOrder($user_id = 0, $membership_card_id = 0)
    {
        if (empty($user_id) || empty($membership_card_id)) {
            return [];
        }

        $model = OrderGoods::where('user_id', $user_id)->where('membership_card_id', $membership_card_id);

        // 已确认、已支付、已收货订单
        $model = $model->whereHas('getOrder', function ($query) {
            $query->whereIn('order_status', [OS_CONFIRMED, OS_SPLITED, OS_SPLITING_PART])->where('pay_status', PS_PAYED)->where('shipping_status', SS_RECEIVED);
        });

        $model = $model->with([
            'getOrder' => function ($query) {
                $query->select('order_id', 'money_paid', 'surplus', 'parent_id');
            }
        ]);

        $model = $model->select('rec_id', 'order_id', 'goods_price', 'goods_number')->orderBy('rec_id', 'DESC')->first();

        $order = $model ? $model->toArray() : [];

        if (!empty($order)) {
            $order = collect($order)->merge($order['get_order'])->except('get_order')->all();
        }

        return $order;
    }

    /**
     * 会员消费积分
     * @param int $user_id
     * @return int
     */
    public function userPayPoint($user_id = 0)
    {
        if (empty($user_id)) {
            return 0;
        }

        $pay_point = Users::where('user_id', $user_id)->value('pay_points');

        return $pay_point;
    }

    /**
     * 会员信息
     * @param int $user_id
     * @param array $columns
     * @return int
     */
    public function userInfo($user_id = 0, $columns = [])
    {
        if (empty($user_id)) {
            return 0;
        }

        $model = Users::where('user_id', $user_id);

        if (!empty($columns)) {
            $model = $model->select($columns);
        }

        $result = $model->first();

        $result = $result ? $result->toArray() : [];

        return $result;
    }

    /**
     * 转换权益卡领取类型
     * @param string $receive_type
     * @return int
     */
    public function transformReceiveType($receive_type = '')
    {
        // 0,免费申请 1,购买商品 2,消费金额 3,消费积分 4,指定金额购买  5 订单购买
        $apply_channel = 0;
        switch ($receive_type) {
            case 'goods':
                $apply_channel = 1;
                break;
            case 'order':
                $apply_channel = 2;
                break;
            case 'integral':
                $apply_channel = 3;
                break;
            case 'buy':
                $apply_channel = 4;
                break;
            case 'order_buy':
                $apply_channel = 5;
                break;
            case 'free':
                $apply_channel = 0;
                break;
        }

        return $apply_channel;
    }

    /**
     * 兼容原申请类型 转换为 权益卡领取类型
     * @param int $apply_channel
     * @return int
     */
    public function transformApplyChannel($apply_channel = 0)
    {
        // free,免费申请 goods,购买商品 order,消费金额 integral,消费积分 buy,指定金额购买 order_buy:订单购买
        $receive_type = '';
        switch ($apply_channel) {
            case 1:
                $receive_type = 'goods';
                break;
            case 2:
                $receive_type = 'order';
                break;
            case 3:
                $receive_type = 'integral';
                break;
            case 4:
                $receive_type = 'buy';
                break;
            case 5:
                $receive_type = 'order_buy';
                break;
            case 0:
                $receive_type = 'free';
                break;
        }

        return $receive_type;
    }

    /**
     * 增加记录
     * @param int $user_id
     * @param array $account_log
     * @return bool
     */
    public function insert_drp_account_log($user_id = 0, $account_log = [])
    {
        if (empty($user_id) || empty($account_log)) {
            return false;
        }

        $drp_account_log_id = $this->drpAccountLogRepository->insert_drp_account_log($user_id, $account_log);

        return $drp_account_log_id;
    }

    /**
     * 修改记录
     * @param int $log_id
     * @param int $user_id
     * @param array $account_log
     * @return bool
     */
    public function update_drp_account_log($log_id = 0, $user_id = 0, $account_log = [])
    {
        if (empty($log_id) || empty($user_id) || empty($account_log)) {
            return false;
        }

        $drp_account_log_id = $this->drpAccountLogRepository->update_drp_account_log($log_id, $user_id, $account_log);

        return $drp_account_log_id;
    }

    /**
     * 查询记录
     * @param int $log_id
     * @param int $user_id
     * @return bool
     */
    public function get_drp_account_log($log_id = 0, $user_id = 0)
    {
        if (empty($log_id) || empty($user_id)) {
            return false;
        }

        return $this->drpAccountLogRepository->get_drp_account_log($log_id, $user_id);
    }

    /**
     * 修改分销商
     * @param int $user_id
     * @param array $data
     * @return bool
     */
    public function editDrpShop($user_id = 0, $data = [])
    {
        if (empty($user_id) || empty($data)) {
            return false;
        }

        // 过滤表字段
        $data = $this->baseRepository->getArrayfilterTable($data, 'drp_shop');

        // 将数组null值转为空
        array_walk_recursive($data, function (&$val, $key) {
            $val = is_null($val) ? '' : $val;
        });

        return DrpShop::where('user_id', $user_id)->update($data);
    }

    /**
     * 微信企业付款所需 银行卡 收款方开户行
     * 目前支持 17 家
     * @return array
     */
    public function bank_list()
    {
        $list = [
            [
                'bank_name' => '工商银行',
                'bank_code' => '1002',
            ],
            [
                'bank_name' => '农业银行',
                'bank_code' => '1005',
            ],
            [
                'bank_name' => '中国银行',
                'bank_code' => '1026',
            ],
            [
                'bank_name' => '建设银行',
                'bank_code' => '1003',
            ],
            [
                'bank_name' => '招商银行',
                'bank_code' => '1001',
            ],
            [
                'bank_name' => '邮储银行',
                'bank_code' => '1066',
            ],
            [
                'bank_name' => '交通银行',
                'bank_code' => '1020',
            ],
            [
                'bank_name' => '浦发银行',
                'bank_code' => '1004',
            ],
            [
                'bank_name' => '民生银行',
                'bank_code' => '1006',
            ],
            [
                'bank_name' => '兴业银行',
                'bank_code' => '1009',
            ],
            [
                'bank_name' => '平安银行',
                'bank_code' => '1010',
            ],
            [
                'bank_name' => '中信银行',
                'bank_code' => '1021',
            ],
            [
                'bank_name' => '华夏银行',
                'bank_code' => '1025',
            ],
            [
                'bank_name' => '广发银行',
                'bank_code' => '1027',
            ],
            [
                'bank_name' => '光大银行',
                'bank_code' => '1022',
            ],
            [
                'bank_name' => '北京银行',
                'bank_code' => '4836',
            ],
            [
                'bank_name' => '宁波银行',
                'bank_code' => '1056',
            ],
            [
                'bank_name' => '上海银行',
                'bank_code' => '1024',
            ]
        ];

        return $list;
    }

    /**
     * 会员实名认证
     * @param int $user_id
     * @return array
     */
    public function bank_info($user_id = 0)
    {
        // 会员实名认证填写的银行卡
        $verify_info = app(VerifyService::class)->infoVerify($user_id);

        return $verify_info;
    }

    /**
     * 查询openid
     * @param int $user_id
     * @return mixed
     */
    public function get_openid($user_id = 0)
    {
        return $this->drpCommonService->get_openid($user_id);
    }

    /**
     * 分销商提现申请
     * @param int $user_id
     * @param array $data
     * @return bool
     */
    public function depositApply($user_id = 0, $data = [])
    {
        if (empty($data)) {
            return false;
        }

        if ($data['deposit_type'] == 2) {
            // 手续费
            $data['deposit_fee'] = deposit_fee($data['money']);
        }

        $res = $this->drpTransferLogRepository->transferLogCreate($user_id, $data);

        if (!empty($res)) {

            if ($data['deposit_type'] > 0) {
                // 申请提现的资金进入冻结状态
                $deposit_fee = $data['deposit_fee'] ?? 0;// 手续费

                $shop_money = '-' . ($data['money'] + $deposit_fee);
                $frozen_money = $data['money'] + $deposit_fee;

                $this->drpCommonService->updateDrpShopAccount($user_id, $shop_money, $frozen_money);
            }
        }

        return $res;
    }

    /**
     * 分销商提现申请记录（前台）
     * @param int $user_id
     * @param int $deposit_status
     * @param int $page
     * @param int $size
     * @return array|bool
     */
    public function depositApplyList($user_id = 0, $deposit_status = -1, $page = 1, $size = 10)
    {
        if (empty($user_id)) {
            return false;
        }

        $result = $this->drpTransferLogRepository->transferLogListForUser($user_id, $deposit_status, $page, $size);

        if (!empty($result['list'])) {

            $time_format = $this->configService->getConfig('time_format');

            foreach ($result['list'] as $key => $val) {
                if (isset($val['drp_shop']) && $val['drp_shop']) {
                    $result['list'][$key]['shop_name'] = $val['drp_shop']['shop_name'] ?? '';
                }

                $result['list'][$key]['bank_info'] = empty($val['bank_info']) ? '' : \GuzzleHttp\json_decode($val['bank_info'], true);
                $result['list'][$key]['deposit_data'] = empty($val['deposit_data']) ? '' : \GuzzleHttp\json_decode($val['deposit_data'], true);

                $result['list'][$key]['add_time_format'] = empty($val['add_time']) ? '' : $this->timeRepository->getLocalDate($time_format, $val['add_time']);
                $result['list'][$key]['check_status_format'] = empty($val['check_status']) ? L('check_status_0') : L('check_status_' . $val['check_status']);
                $result['list'][$key]['deposit_type_format'] = empty($val['deposit_type']) ? L('deposit_type_0') : L('deposit_type_' . $val['deposit_type']);
                $result['list'][$key]['deposit_status_format'] = empty($val['deposit_status']) ? L('deposit_status_0') : L('deposit_status_1');

                $result['list'][$key]['finish_status_format'] = empty($val['finish_status']) ? L('finish_status_0') : L('finish_status_1');

                // 已审核、已提现、未到账 查询API 提现进度
                if ($val['deposit_type'] > 0 && $val['check_status'] == 1 && $val['deposit_status'] == 1 && $val['finish_status'] == 0) {
                    $mchpayObj = new Mchpay();

                    $partner_trade_no = $val['trade_no'];
                    // 查询API
                    if ($val['deposit_type'] == 1) {
                        $param = ['partner_trade_no' => $partner_trade_no];
                        $respond = $mchpayObj->MchPayQuery($param);
                    }
                    if ($val['deposit_type'] == 2) {
                        $param = ['partner_trade_no' => $partner_trade_no];
                        $respond = $mchpayObj->MchPayBankQuery($param);
                    }

                    if (isset($respond) && $respond['return_code'] == 'SUCCESS' && $respond['result_code'] == 'SUCCESS') {
                        // 更新状态 已转账，并保存交易数据
                        $data['deposit_data'] = \GuzzleHttp\json_encode($respond);

                        // 代付订单状态：SUCCESS（付款成功）
                        if ($respond['status'] == 'SUCCESS') {
                            // 已到账
                            $data['finish_status'] = 1;
                            // 更新分销商佣金 扣除冻结佣金
                            $deposit_fee = $val['deposit_fee'] ?? 0;// 手续费
                            $frozen_money = '-' . ($val['money'] + $deposit_fee);
                            $this->drpCommonService->updateDrpShopAccount($val['user_id'], 0, $frozen_money);
                        }
                        $this->drpTransferLogRepository->transferLogUpdate($val['id'], $data);
                    }
                }
            }
        }

        return $result;
    }

    /**
     * 更新分销商等级
     * @param int $user_id
     * @return mixed
     */
    public function updateDrpUser($user_id = 0)
    {
        if (empty($user_id)) {
            return false;
        }

        return $this->drpCommonService->updateDrpUser($user_id);
    }

    /**
     * 用户升级订单相关处理
     * @param int $user_id
     * @param int $order_id
     * @return bool
     */
    public function order_accomplish_dispose($user_id = 0, $order_id = 0)
    {
        if (empty($user_id) || empty($order_id)) {
            return false;
        }
        $all_user = app(DrpCommonService::class)->gain_user_drp_message($user_id, 1);
        if (empty($all_user)) {
            return false;
        }

        $this->drp_goods_stair_all($user_id, $order_id);

        foreach ($all_user as $key => $val) {
            $all_user_id = app(DrpCommonService::class)->judge_drp_user_id(array($val['user_id']));
            if (empty($all_user_id)) {
                //当前用户不符合正常分销商规则,跳出本次循环
                continue;
            }
            if ($key == 1) {//执行自身上级的一代订单信息处理
                //一级分销订单总额
                $this->drp_order_stair_all($val['user_id'], 'all_direct_order_money');
                //一级分销订单总笔数
                $this->drp_order_stair_all($val['user_id'], 'all_direct_order_num');
            }
            //分销订单总金额 下级三代
            $this->drp_order_stair_all($val['user_id'], 'all_order_money');
            //分销订单总笔数 下级三代
            $this->drp_order_stair_all($val['user_id'], 'all_order_num');
        }
        //自购订单金额
        $this->drp_order_stair_all($val['user_id'], 'all_self_order_money');
        //自购订单数量
        $this->drp_order_stair_all($val['user_id'], 'all_self_order_num');

        return true;
    }

    /**
     * 分销商升级关于购买商品
     * @param int $user_id
     * @param int $order_id
     * @return bool
     */
    public function drp_goods_stair_all($user_id = 0, $order_id = 0)
    {
        if (empty($user_id) || empty($order_id)) {
            return false;
        }
        //获取订单信息
        $order_message = OrderInfo::with(['goods' => function ($query) {
            $query->select('order_id', 'goods_id');
        }])
            ->where('order_id', $order_id)
            ->where('shipping_status', 2)
            ->where('pay_status', PS_PAYED)
            ->first();
        $order_message = $order_message ? $order_message->toArray() : [];
        if (empty($order_message)) {
            return false;
        }

        $all_goods = $order_message['goods'];
        if (empty($all_goods)) {
            return false;
        }
        //回去分销商升级需求信息
        $condition = app(DrpCommonService::class)->drp_upgrade_condoition($user_id, 'goods_id');
        if (!$condition) {
            return false;
        }
        $condition = explode(',', $condition);
        foreach ($all_goods as $key => $val) {
            if (in_array($val['goods_id'], $condition)) {
                //符合升级条件进行升级处理
                app(DrpCommonService::class)->conclude_user_upgrade_condition($user_id, 'goods_id');
            }
        }
        return false;
    }

    /**
     * 用户升级条件处理  订单相关
     * @param $user_id
     * @param $field_name
     * @return bool
     */
    public function drp_order_stair_all($user_id, $field_name)
    {
        //获取需要的升级条件
        $reality_condition = app(DrpCommonService::class)->drp_upgrade_condoition($user_id, $field_name);
        if (isset($reality_condition) && $reality_condition > 0) {
            //计算订单实际额度
            $all_money = app(DrpCommonService::class)->distributor_upgrade_dispose_order($user_id, $field_name);
            if (isset($all_money) && $all_money && $all_money >= $reality_condition) {
                //符合升级条件
                return app(DrpCommonService::class)->conclude_user_upgrade_condition($user_id, $field_name);
            }
        }
        return false;
    }

    /**
     * 前端会员升级条件处理  会员注册相关
     * @param int $user_id
     * @return bool
     */
    public function user_upgrade_stage_register($user_id = 0)
    {
        if (empty($user_id)) {
            return false;
        }

        $all_parent_users = app(DrpCommonService::class)->gain_user_drp_message($user_id, 0);
        if (is_null($all_parent_users)) {
            return false;
        }
        $this->drp_register_stair_all($all_parent_users[1]['user_id'], 'all_direct_drp_num', 1);

        //统计下级总数量
        foreach ($all_parent_users as $key => $val) {
            if ($key > 0) {
                $this->drp_register_stair_all($val['user_id'], 'all_direct_user_num', 3);
            }
        }
        return true;
    }

    /**
     * 用户升级条件处理  注册相关
     * @param $user_id
     * @param $field_name
     * @return bool
     */
    public function drp_register_stair_all($user_id = 0, $field_name = '', $type = 0)
    {
        if (empty($user_id) || empty($field_name) || empty($type)) {
            return false;
        }
        //获取需要的升级条件 直属下级总数量
        $reality_condition = app(DrpCommonService::class)->drp_upgrade_condoition($user_id, $field_name);
        //会员实际直属下级数量
        $recommend_users_id = app(DrpCommonService::class)->get_drp_lower($user_id, $type, 1);
        if (!$reality_condition || !$recommend_users_id) {
            return false;
        }
        if (count($recommend_users_id) >= count($reality_condition)) {
            //符合升级条件
            app(DrpCommonService::class)->conclude_user_upgrade_condition($user_id, $field_name);
        }
        return false;
    }

    /**
     * 通过goods_id获取分销商活动信息
     * @param int $goods_id
     * @return array
     */
    public function find_activity_mess_goods($goods_id = 0)
    {
        if (empty($goods_id)) {
            return [];
        }
        $activity = DrpActivityDetailes::where('goods_id', $goods_id)->where('is_finish', 1)->first();
        if (is_null($activity)) {
            return [];
        }
        return $activity ? $activity->toArray() : [];
    }

    /**
     * 分销商数量
     * @param string $time_type
     * @return mixed
     */
    public function drp_shop_count($time_type = '')
    {
        return $this->drpShopRepository->drp_shop_count($time_type);
    }

    /**
     * 分销商信息
     * @param int $user_id
     * @param array $columns
     * @return mixed
     */
    public function drp_shop_info($user_id = 0, $columns = [])
    {
        $info = $this->drpShopRepository->drp_shop_info($user_id, $columns);

        if (!empty($info)) {
            // 开发
            if (isset($info['screenshot']) && !empty($info['screenshot'])) {
                $info['screenshot'] = \GuzzleHttp\json_decode($info['screenshot']);
            }
        }

        return $info;
    }

    /**
     * 购买指定商品成为分销商 获取的佣金
     * @param int $user_id
     * @param array $offset
     * @return array|bool
     */
    public function drp_invite_list($user_id = 0, $offset = [])
    {
        if (empty($user_id)) {
            return false;
        }

        $result = $this->drpLogRepository->drp_invite_list($user_id, $offset);
        // 所有下级分销商符合条件的佣金总额
        $result['total_child_shop_money'] = $this->drpLogRepository->drp_invite_count($user_id);

        if (!empty($result['list'])) {
            $time_format = $this->configService->getConfig('time_format');

            foreach ($result['list'] as $key => $val) {
                $val = collect($val)->merge($val['drp_shop'])->except('drp_shop')->all();
                if (isset($val['drp_log']) && !empty($val['drp_log'])) {
                    $val['drp_log']['money_format'] = $this->dscRepository->getPriceFormat($val['drp_log']['money']);
                    $val['drp_log']['time_format'] = $this->timeRepository->getLocalDate($time_format, $val['drp_log']['time']);

                    if (isset($val['drp_log']['order_info']) && !empty($val['drp_log']['order_info'])) {
                        $val['drp_log'] = collect($val['drp_log'])->merge($val['drp_log']['order_info'])->except('order_info')->all();
                    }
                }

                // 会员信息
                $val['user_name'] = !empty($val['nick_name']) ? $val['nick_name'] : $val['user_name'];
                $val['create_time_format'] = $this->timeRepository->getLocalDate($time_format, $val['create_time']);

                $result['list'][$key] = $val;
            }
        }

        return $result;
    }


    /**
     * 记录分销商分销资金变动
     * @param int $user_id 用户id
     * @param int $shop_money 可用余额变动
     * @param int $frozen_money 冻结余额变动
     * @param int $rank_points 等级积分变动
     * @param int $pay_points 消费积分变动
     * @param string $change_desc 变动说明
     * @param int $change_type 变动类型：参见常量文件
     * @return bool
     */
    public function drp_log_account_change($user_id = 0, $shop_money = 0, $frozen_money = 0, $rank_points = 0, $pay_points = 0, $change_desc = '', $change_type = 0)
    {
        if (empty($user_id)) {
            return false;
        }

        $drp = DrpShop::query()->where('user_id', $user_id)->first();

        if (empty($drp)) {
            return false;
        }

        if ($drp->shop_money == 0 && $shop_money < 0) {
            $shop_money = 0;
        }
        if ($drp->shop_points == 0 && $pay_points < 0) {
            $pay_points = 0;
        }

        // 分销分成
        if ($change_type == ACT_SEPARATE) {

            /* 更新分销用户信息 */
            $drp->shop_money += $shop_money;
            $drp->shop_points += $pay_points;
            $drp->save();
        }

        // 佣金转到余额
        if ($change_type == ACT_TRANSFERRED) {
            /* 插入帐户变动记录 */
            $account_log = [
                'user_id' => $user_id,
                'user_money' => -$shop_money,
                'frozen_money' => $frozen_money,
                'rank_points' => -$rank_points,
                'pay_points' => -$pay_points,
                'change_time' => $this->timeRepository->getGmTime(),
                'change_desc' => $change_desc,
                'change_type' => $change_type
            ];
            AccountLog::insert($account_log);

            /* 更新用户信息 */
            $users = Users::where('user_id', $user_id)->first();
            $users->user_money -= $shop_money;
            $users->frozen_money -= $frozen_money;
            $users->rank_points -= $rank_points;
            $users->pay_points -= $pay_points;
            $users->save();
        }

        return true;
    }
}
