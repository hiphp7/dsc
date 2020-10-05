<?php

namespace App\Custom\Distribute\Services;

use App\Custom\Distribute\Models\DrpActivityDetailes;
use App\Custom\Distribute\Models\DrpActivityRewardLog;
use App\Custom\Distribute\Models\DrpRewardLog;
use App\Custom\Distribute\Models\DrpUpgradeCondition;
use App\Custom\Distribute\Models\DrpUpgradeValues;
use App\Custom\Distribute\Repositories\AccountLogRepository;
use App\Custom\Distribute\Repositories\AdminLogRepository;
use App\Custom\Distribute\Repositories\DrpAccountLogRepository;
use App\Custom\Distribute\Repositories\DrpTransferLogRepository;
use App\Custom\Distribute\Sdk\Mchpay;
use App\Models\DrpShop;
use App\Models\DrpUserCredit;
use App\Models\Goods;
use App\Models\OrderGoods;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Common\ConfigService;
use App\Services\Merchant\MerchantCommonService;
use Illuminate\Support\Arr;

/**
 * Class DistributeManageService
 * @package App\Custom\Distribute\Services
 */
class DistributeManageService
{
    protected $timeRepository;
    protected $dscRepository;
    protected $adminLogRepository;
    protected $drpTransferLogRepository;
    protected $configService;
    protected $drpCommonService;
    protected $publicCategoryService;

    public function __construct(
        TimeRepository $timeRepository,
        DscRepository $dscRepository,
        AdminLogRepository $adminLogRepository,
        DrpTransferLogRepository $drpTransferLogRepository,
        ConfigService $configService,
        DrpCommonService $drpCommonService,
        PublicCategoryService $publicCategoryService,
        AccountLogRepository $accountLogRepository
    )
    {
        $this->timeRepository = $timeRepository;
        $this->dscRepository = $dscRepository;
        $this->adminLogRepository = $adminLogRepository;
        $this->drpTransferLogRepository = $drpTransferLogRepository;
        $this->configService = $configService;
        $this->drpCommonService = $drpCommonService;
        $this->publicCategoryService = $publicCategoryService;
        $this->accountLogRepository = $accountLogRepository;
    }

    /**
     * 获取分销商店铺信息
     * @param int $user_id
     * @return array
     */
    public function drp_shop_user($user_id = 0)
    {
        if (empty($user_id)) {
            return [];
        }

        $res = DrpShop::where("user_id", $user_id)->first();
        return $res ? $res->toArray() : [];
    }

    /**
     * 管理员后台操作日志记录
     * @param string $log_info
     * @param string $action 操作的类型
     * @param string $content 操作的内容
     * @param int $admin_id 管理员id
     * @param int $admin_id
     * @return bool|mixed
     */
    public function admin_log($log_info = '', $action = '', $content = '', $admin_id = 0)
    {
        if (empty($log_info)) {
            return false;
        }

        $this->adminLogRepository->admin_log($log_info, $action, $content, $admin_id);
    }

    /**
     * 搜索商品
     * @param string $keywords
     * @param int $cat_id
     * @param int $brand_id
     * @param array $offset
     * @param array $filter
     * @return array
     */
    public function goodsListSearch($keywords = '', $cat_id = 0, $brand_id = 0, $offset = [], $filter = [])
    {
        $model = Goods::where('is_on_sale', 1)
            ->where('is_alone_sale', 1)
            ->where('is_delete', 0)
            ->where('review_status', '>', 2);

        if ($cat_id > 0) {
            $cat_arr = get_children_new($cat_id);
            $model = $model->whereIn('cat_id', $cat_arr);
        }
        if ($brand_id > 0) {
            $model = $model->where('brand_id', $brand_id);
        }

        // 已选择商品id
        $select_goods_id = [];
        if (!empty($filter)) {
            $filter['select_goods_id'] = $filter['select_goods_id'] ?? '';
            $select_goods_id = empty($filter['select_goods_id']) ? '' : explode(',', $filter['select_goods_id']);
        }

        if ($keywords) {
            $model = $model->where('goods_name', 'like', '%' . $keywords . '%')
                ->orWhere('goods_sn', 'like', '%' . $keywords . '%')
                ->orWhere('keywords', 'like', '%' . $keywords . '%');
        }

        $total = $model->count();

        if (!empty($offset)) {
            $model = $model->offset($offset['start'])
                ->limit($offset['limit']);
        }

        $list = $model->select('goods_id', 'goods_name', 'goods_thumb', 'is_distribution')->orderBy('sort_order', 'ASC')
            ->orderBy('goods_id', 'DESC')
            ->get();
        $list = $list ? $list->toArray() : [];

        if ($list) {
            foreach ($list as $k => $value) {
                $list[$k]['goods_thumb'] = empty($value['goods_thumb']) ? '' : $this->dscRepository->getImagePath($value['goods_thumb']);

                $list[$k]['checked'] = 0;
                if (!empty($select_goods_id) && in_array($value['goods_id'], $select_goods_id)) {
                    $list[$k]['checked'] = 1;
                }
            }
        }

        return ['list' => $list, 'total' => $total];
    }

    /**
     * 编辑商品
     * @param int $goods_id
     * @param array $data
     * @return bool
     */
    public function editGoods($goods_id = 0, $data = [])
    {
        if (empty($goods_id) || empty($data)) {
            return false;
        }

        return Goods::where('goods_id', $goods_id)->update($data);
    }

    /**
     * 分销商提现申请列表
     * @param string $keywords
     * @param array $offset
     * @param array $condition
     * @return array
     */
    public function transferLogList($keywords = '', $offset = [], $condition = [])
    {
        $result = $this->drpTransferLogRepository->transferLogList($keywords, $offset, $condition);

        if (!empty($result['list'])) {
            $time_format = $this->configService->getConfig('time_format');

            foreach ($result['list'] as $key => $val) {
                if (isset($val['drp_shop']) && $val['drp_shop']) {
                    $result['list'][$key]['shop_name'] = $val['drp_shop']['shop_name'] ?? '';
                }

                $result['list'][$key]['add_time_format'] = empty($val['add_time']) ? '' : $this->timeRepository->getLocalDate($time_format, $val['add_time']);
                $result['list'][$key]['check_status_format'] = empty($val['check_status']) ? L('check_status_0') : L('check_status_' . $val['check_status']);
                $result['list'][$key]['deposit_type_format'] = empty($val['deposit_type']) ? L('deposit_type_0') : L('deposit_type_' . $val['deposit_type']);
                $result['list'][$key]['deposit_status_format'] = empty($val['deposit_status']) ? L('deposit_status_0') : L('deposit_status_1');

                $result['list'][$key]['finish_status_format'] = empty($val['finish_status']) ? L('finish_status_0') : L('finish_status_1');
            }
        }

        return $result;
    }

    /**
     * 申请记录
     * @param int $id
     * @return bool
     */
    public function transferLogInfo($id = 0)
    {
        if (empty($id)) {
            return false;
        }

        $log = $this->drpTransferLogRepository->transferLogInfo($id);

        if (!empty($log)) {
            $log['bank_info'] = empty($log['bank_info']) ? '' : \GuzzleHttp\json_decode($log['bank_info'], true);

            $openid = $this->drpCommonService->get_openid($log['user_id']);
            if ($openid) {
                $log['openid'] = $openid;
            }
        }

        return $log;
    }

    /**
     * 提现交易详情
     * @param int $id
     * @return array|bool
     */
    public function transferQuery($id = 0)
    {
        if (empty($id)) {
            return false;
        }

        $log = $this->transferLogInfo($id);

        if (empty($log)) {
            return [];
        }

        $deposit_type = $log['deposit_type'] ?? 0;

        $log['deposit_data'] = empty($log['deposit_data']) ? '' : \GuzzleHttp\json_decode($log['deposit_data'], true);

        $log['deposit_data_format'] = $log['deposit_data'];
        $log['bank_info_format'] = $this->transformData($log['bank_info']);

        // 已审核、已提现、未到账 查询API
        if ($deposit_type > 0 && $log['check_status'] == 1 && $log['deposit_status'] == 1 && $log['finish_status'] == 0) {
            $mchpayObj = new Mchpay();

            $partner_trade_no = $log['trade_no'];
            // 查询API
            if ($deposit_type == 1) {
                $param = ['partner_trade_no' => $partner_trade_no];
                $respond = $mchpayObj->MchPayQuery($param);
            }
            if ($deposit_type == 2) {
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
                    $deposit_fee = $log['deposit_fee'] ?? 0;// 手续费
                    $frozen_money = '-' . ($log['money'] + $deposit_fee);
                    $this->drpCommonService->updateDrpShopAccount($log['user_id'], 0, $frozen_money);
                }
                $this->drpTransferLogRepository->transferLogUpdate($id, $data);
            }
        }

        return $log;
    }

    /**
     * 格式化数据 输出前端显示
     * @param array $data
     * @return array
     */
    protected function transformData($data = [])
    {
        if (empty($data)) {
            return [];
        }

        $newData = [];
        foreach ($data as $k => $v) {
            $newData[L($k)] = $v;
        }

        return $newData;
    }

    /**
     * 审核记录
     * @param int $id
     * @param int $status
     * @param array $log
     * @return bool
     */
    public function transferLogCheck($id = 0, $status = 0, $log = [])
    {
        if (empty($id)) {
            return false;
        }

        if ($status == 2) {
            // 审核拒绝 返回冻结佣金
            $shop = $this->drpCommonService->getDrpShopAccount($log['user_id']);
            $frozen_money = '-' . $shop['frozen_money'];

            // 如果有 退回手续费
            $deposit_fee = $log['deposit_fee'] ?? 0;
            $shop_money = $shop['frozen_money'] - $deposit_fee;
            $this->drpCommonService->updateDrpShopAccount($log['user_id'], $shop_money, $frozen_money);
        }

        return $this->drpTransferLogRepository->transferLogCheck($id, $status);
    }

    /**
     * 查询分销商信息
     * @param int $user_id
     * @return mixed
     */
    public function getDrpShopAccount($user_id = 0)
    {
        $shop = $this->drpCommonService->getDrpShopAccount($user_id);

        return $shop;
    }

    /**
     * 删除记录
     * @param int $id
     * @return bool
     */
    public function transferLogDelete($id = 0)
    {
        if (empty($id)) {
            return false;
        }

        return $this->drpTransferLogRepository->transferLogDelete($id);
    }

    /**
     * 微信企业付款
     * @param int $id
     * @param int $deposit_type 1 付款到零钱  2 付款到银行卡
     * @param array $data
     * @return bool
     */
    public function transferLogDeposit($id = 0, $deposit_type = 0, $data = [])
    {
        if (empty($id) || empty($data)) {
            return false;
        }

        $log = $this->transferLogInfo($id);
        if (empty($log)) {
            return false;
        }

        // 未提现 未到账
        if ($log['deposit_status'] == 0 && $log['finish_status'] == 0) {
            $mchpayObj = new Mchpay();

            // 商户订单号
            $partner_trade_no = $log['trade_no'];
            $money = $data['money'] * 100; // 转换为分

            if ($deposit_type == 1) {
                $openid = $log['openid'] ?? '';
                if ($openid) {
                    $order = [];
                    $order['partner_trade_no'] = $partner_trade_no;
                    $order['openid'] = $openid; // 用户标识
                    $order['amount'] = $money; // 企业付款金额，单位为分
                    $order['desc'] = $data['desc'];

                    $result = $mchpayObj->MchPay($order);
                }
            }

            if ($deposit_type == 2) {
                $order = [];
                $order['partner_trade_no'] = $partner_trade_no;
                $order['enc_bank_no'] = $data['enc_bank_no'];
                $order['enc_true_name'] = $data['enc_true_name'];
                $order['bank_code'] = $data['bank_code'];
                $order['amount'] = $money; // 企业付款金额，单位为分
                $order['desc'] = $data['desc']; // 企业付款操作说明信息。必填。

                // RSA公钥路径
                $rsa_public_key_path = storage_path('app/certs/wxpay/') . "rsa_public_key.pem";
                if (!file_exists($rsa_public_key_path)) {
                    // 获取RSA公钥
                    $rsa = $mchpayObj->MchPayGetPublicKey();
                    if (isset($rsa) && !empty($rsa['pub_key'])) {
                        $file_path = storage_path('app/certs/wxpay/');
                        file_write($file_path, "index.html", "");
                        file_write($file_path, "rsa_public_key.pem", $rsa['pub_key']);
                    } else {
                        return false;
                    }
                }

                $result = $mchpayObj->MchPayBank($order, $rsa_public_key_path);
            }

            if (isset($result) && $result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS') {
                // 更新状态 已转账
                $data['deposit_status'] = 1;
                // 更新保存交易数据
                $data['deposit_data'] = \GuzzleHttp\json_encode($result);

                // 查询API
                if ($deposit_type == 1) {
                    $param = ['partner_trade_no' => $partner_trade_no];
                    $respond = $mchpayObj->MchPayQuery($param);
                }
                if ($deposit_type == 2) {
                    $param = ['partner_trade_no' => $partner_trade_no];
                    $respond = $mchpayObj->MchPayBankQuery($param);
                }

                if (isset($respond) && $respond['return_code'] == 'SUCCESS' && $respond['result_code'] == 'SUCCESS') {
                    // 更新保存交易数据
                    $data['deposit_data'] = \GuzzleHttp\json_encode($respond);

                    // 代付订单状态：SUCCESS（付款成功）
                    if ($respond['status'] == 'SUCCESS') {
                        // 更新状态 已到账
                        $data['finish_status'] = 1;
                        // 更新分销商佣金 扣除冻结佣金
                        $deposit_fee = $log['deposit_fee'] ?? 0;// 手续费
                        $frozen_money = '-' . ($log['money'] + $deposit_fee);
                        $this->drpCommonService->updateDrpShopAccount($log['user_id'], 0, $frozen_money);
                    }
                }

                $this->drpTransferLogRepository->transferLogUpdate($id, $data);

                return true;
            }

        }

        return false;
    }

    /**
     * 后台会员升级条件处理  分销商审核相关
     * @param int $user_id
     * @return bool
     */
    public function user_upgrade_background_augit($user_id = 0)
    {
        if (empty($user_id)) {
            return false;
        }

        $all_parent_users = $this->drpCommonService->gain_user_drp_message($user_id, 1);
        if (is_null($all_parent_users)) {
            return false;
        }

        if (isset($all_parent_users[1]['user_id'])) {
            app(DistributeService::class)->drp_register_stair_all($all_parent_users[1]['user_id'], 'all_develop_drp_num', 1);
        }

        //统计下级总数量
        foreach ($all_parent_users as $key => $val) {
            if ($key > 0) {
                app(DistributeService::class)->drp_register_stair_all($val['user_id'], 'all_indirect_drp_num', 3);
            }
        }
        return true;
    }

    /**
     * 用户升级条件处理  分销商审核相关
     * @param $user_id
     * @param $field_name
     * @return bool
     */
    public function drp_register_stair_augit($user_id = 0, $field_name = '', $type = 0)
    {
        if (empty($user_id) || empty($field_name) || empty($type)) {
            return false;
        }
        //获取需要的升级条件 直属下级总数量
        $reality_condition = $this->drpCommonService->drp_upgrade_condoition($user_id, $field_name);
        $recommend_users_id = $this->drpCommonService->get_drp_lower($user_id, $type, 2);
        if (!$reality_condition || !$recommend_users_id) {
            return false;
        }
        if (count($recommend_users_id) >= count($reality_condition)) {
            //符合升级条件
            $this->drpCommonService->conclude_user_upgrade_condition($user_id, $field_name);
        }
        return false;
    }

    /**
     * 分销商提现升级处理
     * @param int $user_id 用户ID
     * @return bool
     */
    public function drp_transfer_upgrade($user_id = 0)
    {
        if (empty($user_id)) {
            return false;
        }

        return $this->drpCommonService->drp_transfer_upgrade($user_id);
    }

    /**
     * 搜索获得参与分销商活动的用户 用于导出Excel
     * @param $keyword
     * @return array
     */
    public function set_user_activity($keyword)
    {
        //获取所有的分销商活动参与用户
        $all_activity = DrpRewardLog::from('drp_reward_log as d')
            ->join('users as u', 'd.user_id', '=', 'u.user_id')
            ->join('drp_activity_detailes as a', 'd.activity_id', '=', 'a.id')
            ->select('d.reward_id', 'u.user_name', 'a.act_name', 'a.raward_money', 'a.raward_type', 'd.award_status', 'd.add_time')
            ->where('activity_type', 0)
            ->where('u.user_name', 'like', '%' . $keyword . '%')
            ->orderBy('u.user_id', 'desc')->get();
        if (is_null($all_activity)) {
            return [];
        }

        $all_activity = $all_activity->toArray();
        if (empty($all_activity)) {
            return [];
        }

        foreach ($all_activity as $key => $val) {
            if ($val['award_status'] == 1) {
                $all_activity[$key]['award_status'] = L('details_type_success');
            } else {
                $all_activity[$key]['award_status'] = L('details_type_loss');
            }

            if ($val['raward_type'] == 1) {
                $all_activity[$key]['raward_type'] = L('integral');
            } else {
                $all_activity[$key]['raward_type'] = L('balance');
            }
            $all_activity[$key]['add_time'] = $this->timeRepository->getLocalDate('Y-m-d H:i:s', $val['add_time']);
        }
        return $all_activity;
    }

    /**
     * 获取用户的店铺信息
     * @param $user_id
     * @return bool
     */
    public function find_user_shop($user_id)
    {
        if (empty($user_id)) {
            return [];
        }
        $user_shop = DrpShop::where('user_id', $user_id)->where('audit', 1)->where('status', 1)->first();
        if (is_null($user_shop)) {
            return [];
        }
        return $user_shop->toArray();
    }

    /**
     * 获取用户参与的活动
     * @param $user_id
     * @return DrpRewardLog[]|array|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    public function all_drp_user_activity($user_id)
    {
        if (empty($user_id)) {
            return [];
        }

        $all_user_activity = DrpRewardLog::with(['ActivityDetailes' => function ($query) {
            $query->select('id', 'act_name');
        }])
            ->where('user_id', $user_id)
            ->get();
        $all_user_activity = $all_user_activity ? $all_user_activity->toArray() : [];
        return $all_user_activity;
    }

    /**
     * 格式化活动状态(三维数组)
     * @param int $user_id
     * @param array $activity
     * @param array $activity_accomplish_status
     * @param int $is_finish_status
     * @return array
     */
    public function set_drp_activity_time($user_id = 0, $activity = [], $activity_accomplish_status = [], $is_finish_status = 1)
    {
        if (empty($activity) || empty($user_id)) {
            return [];
        }

        $now_time = $this->timeRepository->getGmTime();
        $new_activity = [];
        foreach ($activity as $key => $val) {

            $all_reward_log = DrpRewardLog::where('activity_id', $val['id'])->where('activity_type', 0)->where('user_id', $user_id)->first(['completeness_share', 'completeness_place']);
            $activity[$key]['past_due'] = '0';
            if ($is_finish_status == 1 && $val['end_time'] <= $now_time) {
                //已过期
                $activity[$key]['past_due'] = '1';
            }

            $activity[$key]['past_due_format'] = $activity[$key]['past_due'] == 1 ? L('past_due_1') : L('past_due_0');
            $activity[$key]['goods']['goods_img'] = $this->dscRepository->getImagePath($val['goods']['goods_img']);
            $activity[$key]['start_time_format'] = $this->timeRepository->getLocalDate('Y-m-d H:i:s', $val['start_time']);
            $activity[$key]['end_time_format'] = $this->timeRepository->getLocalDate('Y-m-d H:i:s', $val['end_time']);
            $activity[$key]['add_time_format'] = $this->timeRepository->getLocalDate('Y-m-d H:i:s', $val['add_time']);
            $activity[$key]['is_finish_format'] = $val['is_finish'] == 1 ? L('is_finish_1') : L('is_finish_0');
            $activity[$key]['raward_type_format'] = $val['raward_type'] == 1 ? L('raward_type_1') : L('raward_type_0');
            $activity[$key]['award_status'] = isset($activity_accomplish_status['new_activity_status'][$val['id']]) && $activity_accomplish_status['new_activity_status'][$val['id']] == 1 ? L('accomplish_status_1') : L('accomplish_status_0');
            $activity[$key]['draw_status'] = isset($activity_accomplish_status['new_activity_id']) && in_array($val['id'], $activity_accomplish_status['new_activity_id']) ? 1 : 0;

            $activity[$key]['completeness_share'] = $all_reward_log ? $all_reward_log->completeness_share : 0;
            $activity[$key]['completeness_place'] = $all_reward_log ? $all_reward_log->completeness_place : 0;
            $new_activity[] = $activity[$key];
        }
        return $new_activity;
    }

    /**
     * 通过分销商活动ID返回格式化后的分销商活动
     * @param int $user_id
     * @param int $activity_id
     * @return array|mixed
     */
    public function find_drp_activity($user_id = 0, $activity_id = 0)
    {
        if (empty($user_id) || empty($activity_id)) {
            return [];
        }
        $activity = DrpActivityDetailes::with(['Goods' => function ($query) {
            $query->select('goods_id', 'goods_name', 'goods_img');
        }])
            ->where('id', $activity_id)
            ->get();
        if (is_null($activity)) {
            return [];
        }
        $activity = $activity->toArray();
        $format_activity = $this->set_drp_activity_time($user_id, $activity);
        if (empty($format_activity)) {
            return [];
        }
        return $format_activity[0];
    }

    /**
     * 修改订单状态为未开启
     * @param int $id
     * @return bool
     */
    public function close_activity_id($id = 0)
    {
        if (empty($id)) {
            return false;
        }
        return DrpActivityDetailes::where('id', $id)->update(['is_finish' => 0]);
    }

    /**
     * 获取当前开启的所有活动
     * @param array $all_user_activity_id 已参与的活动ID
     * @param int $activity_type 查询的类型 0全部 1  未领取 2 已领取
     * @param int $is_finish 开启状态  1  已开启  0  未开启
     * @return array
     */
    public function all_drp_activity($all_user_activity = [], $activity_type = 0, $is_finish = 1, $page = 1, $size = 10, $user_id = 0)
    {
        if (empty($all_user_activity) || EMPTY($user_id)) {
            return [];
        }
        $page = $page ? $page : 1;
        $size = $size ? $size : 10;
        $is_finish = $is_finish ? $is_finish : 1;
        $activity_type = $activity_type ? $activity_type : 0;
        $all_activity = DrpActivityDetailes::with(['Goods' => function ($query) {
            $query->select('goods_id', 'goods_name', 'goods_img');
        }])
            ->where('is_finish', $is_finish);

        $offset = [
            'start' => ($page - 1) * $size,
            'limit' => $size,
        ];

        $now_time = $this->timeRepository->getGmTime();
        if ($activity_type == 1) {
            //查询未领取的活动
            $all_activity = $all_activity->whereNotIn('id', $all_user_activity['new_activity_id'])->where('end_time', '>=', $now_time);
        } else if ($activity_type == 2) {
            //查询已领取的活动
            $all_activity = $all_activity->whereIn('id', $all_user_activity['new_activity_id']);
        } else if ($activity_type == 3) {
            //查询已过期的活动
            $all_activity = $all_activity->whereNotIn('id', $all_user_activity['new_activity_id'])->where('end_time', '<', $now_time);
        } else if ($activity_type == 4) {
            //查询未过期的活动
            $all_activity = $all_activity->where('end_time', '>=', $now_time);
        }

        if (!empty($offset)) {
            $all_activity = $all_activity->offset($offset['start'])
                ->limit($offset['limit']);
        }
        $all_activity = $all_activity->get();
        if (is_null($all_activity)) {
            return [];
        }
        $all_activity = $all_activity->toArray();

        $all_activity = $this->set_drp_activity_time($user_id, $all_activity, $all_user_activity, 1);
        if (empty($all_activity)) {
            return [];
        }

        return $all_activity ? $all_activity : [];
    }

    /**
     * 获取用户参与的所有活动ID
     * @param $user_id
     * @param int $is_finish
     * @return array
     */
    public function all_drp_user_activity_id($user_id, $is_finish = 1)
    {
        $is_finish ? $is_finish : 1;
        if (empty($user_id)) {
            return [];
        }
        $all_user_activity_id = DrpRewardLog::where('user_id', $user_id)->where('activity_type', 0)->get(['activity_id', 'award_status']);

        if (empty($all_user_activity_id)) {
            return [];
        }
        $all_user_activity_id = $all_user_activity_id->toArray();

        $new_activity_id = [];
        $new_activity_status = [];
        foreach ($all_user_activity_id as $key => $val) {
            $new_activity_id[] = $val['activity_id'];
            $new_activity_status[$val['activity_id']] = $val['award_status'];
        }

        $new_activity = [];
        $new_activity['new_activity_id'] = $new_activity_id;
        $new_activity['new_activity_status'] = $new_activity_status;


        return empty($new_activity) ? [] : $new_activity;
    }

    /**
     * 通过活动ID获取活动信息
     * @param $activity_id
     * @return array
     */
    public function find_activity($activity_id)
    {
        if (empty($activity_id)) {
            return [];
        }
        $activity = DrpActivityDetailes::where('id', $activity_id)->first();
        if (is_null($activity)) {
            return [];
        }
        return $activity->toArray();
    }

    /**
     * 获取分销商单条奖励信息
     * @param $activity_id  奖励记录ID
     * @param $user_id   用户ID
     * @param int $activity_type 奖励类型 0  活动奖励  1  升级奖励
     * @return array
     */
    public function find_user_reward_activity($activity_id, $user_id, $activity_type = 0)
    {
        if (empty($activity_id) || empty($user_id)) {
            return [];
        }
        $activity_type ? $activity_type : 1;
        $reward_log = DrpRewardLog::where('user_id', $user_id)->where('activity_id', $activity_id)->where('activity_type', $activity_type)->first();

        if (is_null($reward_log)) {
            return [];
        }
        return $reward_log->toArray();
    }

    /**
     * 分销商领取分销商活动/分销商升级
     * @param $activity  活动的奖励值/升级奖励值
     * @param $user_id  用户ID
     * @param int $activity_type 奖励类型
     * @param int $credit_id 升级时绑定的等级ID
     * @return bool
     */
    public function user_draw_activity($activity, $user_id, $activity_type = 0, $credit_id = 0)
    {
        if (empty($activity) || empty($user_id)) {
            return false;
        }
        $activity_type ? $activity_type : 0;
        $credit_id ? $credit_id : 0;
        $data = [];
        $data['user_id'] = $user_id;
        $data['activity_id'] = $activity['id'];
        $data['award_money'] = $activity['raward_money'];
        $data['award_type'] = $activity['raward_type'];
        $data['activity_type'] = $activity_type;
        $data['award_status'] = 0;
        $data['participation_status'] = 0;
        $data['add_time'] = $this->timeRepository->getGmTime();
        $data['credit_id'] = $credit_id;
        $award_id = DrpRewardLog::insertGetId($data);
        if ($award_id) {
            return $award_id;
        } else {
            return false;
        }
    }

    /**
     * 更新分销商活动状态
     */
    public function renewal_activity_status()
    {
        $now_time = $this->timeRepository->getGmTime();
        DrpActivityDetailes::where('end_time', '<=', $now_time)->where('is_finish', 1)->update(['is_finish' => 0]);
    }

    /**
     * 获取分销活动的参与分销商的综合信息
     * @param int $id
     * @return array
     */
    public function set_activity_user_log($id = 0, $condition = [])
    {
        if (empty($id)) {
            return [];
        }
        $activity = DrpActivityDetailes::where('id', $id)->first(['act_name', 'start_time', 'end_time', 'goods_id', 'raward_type']);
        if (is_null($activity)) {
            return [];
        }
        $goods_name = Goods::where('goods_id', $activity->goods_id)->value('goods_name');
        if (empty($goods_name)) {
            return [];
        }

        $model = DrpRewardLog::where('activity_id', $id)
            ->where('activity_type', 0);

        // 按申请时间筛选
        if (!empty($condition)) {
            if (!empty($condition['starttime']) && !empty($condition['endtime'])) {
                $model = $model->whereBetween('add_time', [$condition['starttime'], $condition['endtime']]);
            }
        }

        $result = $model->with(['Users' => function ($query) {
            $query->select('user_id', 'user_name', 'mobile_phone');
        }])->get();

        $result = $result ? $result->toArray() : [];
        if (empty($result)) {
            return [];
        }

        $all_goods = $this->statistics_order_activity_message($id, $activity->goods_id);
        $list = [];
        foreach ($result as $key => $val) {
            $list[$key]['id'] = $val['reward_id'];
            $list[$key]['act_name'] = $activity->act_name;
            $list[$key]['start_end_time'] = $this->timeRepository->getLocalDate('Y-m-d H:i:s', $activity->start_time) . "——" . $this->timeRepository->getLocalDate('Y-m-d H:i:s', $activity->end_time);
            $list[$key]['award_money'] = $val['award_money'];
            $list[$key]['completeness_share'] = $val['completeness_share'];
            $list[$key]['completeness_place'] = $val['completeness_place'];
            $list[$key]['finish_order_num'] = isset($all_goods['finish_order_num'][$val['users']['user_id']]) ? $all_goods['finish_order_num'][$val['users']['user_id']] : 0;
            $list[$key]['unfinish_order_num'] = isset($all_goods['unfinish_order_num'][$val['users']['user_id']]) ? $all_goods['unfinish_order_num'][$val['users']['user_id']] : 0;
            $list[$key]['award_status'] = $val['award_status'] == 1 ? L('award_status_1') : L('award_status_0');
            $list[$key]['raward_type'] = $activity->raward_type == 1 ? L('raward_type_1') : L('raward_type_0');
            $list[$key]['user_name'] = $val['users']['user_name'];
            $list[$key]['mobile_phone'] = $val['users']['mobile_phone'] ? $val['users']['mobile_phone'] : '';
            $list[$key]['goods_name'] = $goods_name;
        }
        return $list;
    }

    /**
     *通过用户ID获取分销商店铺的信息(已开启未冻结的正常分销商)
     * @param int $user_id
     * @return array
     */
    public function find_drp_shop_user($user_id = 0)
    {
        if (empty($user_id)) {
            return [];
        }
        $all_drp_user = DrpShop::where('user_id', $user_id)->where('status', 1)->where('audit', 1)->get('user_id');
        return $all_drp_user ? $all_drp_user->toArray() : [];
    }

    /**
     * 获取分销商参与单个活动的参与信息
     * @param int $user_id
     * @param int $activity_id
     * @return array
     */
    public function find_drp_activity_user($user_id = 0, $activity_id = 0)
    {
        if (empty($user_id) || empty($activity_id)) {
            return [];
        }
        $activity_reward_log = DrpRewardLog::where('user_id', $user_id)->where('activity_id', $activity_id)->where('activity_type', 0)->first();
        return $activity_reward_log ? $activity_reward_log->toArray() : [];
    }

    /**
     * 获取用户参与分销商活动分享点击的记录日志
     * @param int $user_id
     * @param int $activity_id
     * @return bool
     */
    public function judge_user_activity_status($user_id = 0, $activity_id = 0)
    {
        if (empty($user_id) || empty($activity_id)) {
            return [];
        }
        $reward_log = DrpActivityRewardLog::where('user_id', $user_id)->where('activity_id', $activity_id)->where('order_id', 0)->first();
        if (is_null($reward_log)) {
            return [];
        }
        return $reward_log ? $reward_log->toArray() : [];
    }

    /**
     * 增加分销商活动分销商完成度
     * @param int $drp_user_id
     * @param int $activity_id
     * @param int $num
     * @param string $field
     * @return bool
     */
    public function add_drp_activity_share_num($drp_user_id = 0, $activity_id = 0, $num = 0, $field = 'completeness_share')
    {
        if (empty($activity_id) || empty($drp_user_id) || empty($num)) {
            return false;
        }
        $res = DrpRewardLog::where('user_id', $drp_user_id)->where('activity_id', $activity_id)->where('activity_type', 0)->increment($field, $num);

        if ($res) {
            $res_num = DrpRewardLog::where('user_id', $drp_user_id)->where('activity_id', $activity_id)->where('activity_type', 0)->value($field);
            return $res_num;
        }
        return false;
    }

    /**
     * 扣除分销商活动分销商完成度
     * @param int $drp_user_id
     * @param int $activity_id
     * @param int $num
     * @param string $field
     * @return bool
     */
    public function reduce_drp_activity_share_num($drp_user_id = 0, $activity_id = 0, $num = 0, $field = 'completeness_share')
    {
        if (empty($activity_id) || empty($drp_user_id) || empty($num)) {
            return false;
        }
        $res = DrpRewardLog::where('user_id', $drp_user_id)->where('activity_id', $activity_id)->where('activity_type', 0)->decrement($field, $num);

        if (!$res) {
            return false;
        }
        //获取奖励领取信息
        $reward_log = DrpRewardLog::with(['ActivityDetailes' => function ($query) {
            $query->select('id', 'act_type_share', 'act_type_place', 'complete_required');
        }])
            ->where('activity_id', $activity_id)
            ->where('user_id', $drp_user_id)
            ->where('activity_type', 0)
            ->first();

        $reward_log = $reward_log ? $reward_log->toArray() : [];
        if (empty($reward_log)) {
            return false;
        }
        //判断奖励领取状态
        if ($reward_log['participation_status'] == 0) {
            return true;
        }
        //扣除后还是符合完成要求
        if ($reward_log['activity_detailes']['complete_required'] == 1) {
            if ($reward_log['completeness_share'] >= $reward_log['activity_detailes']['act_type_share'] || $reward_log['completeness_place'] >= $reward_log['activity_detailes']['act_type_place']) {
                return true;
            }
        } else {
            if ($reward_log['completeness_share'] >= $reward_log['activity_detailes']['act_type_share'] && $reward_log['completeness_place'] >= $reward_log['activity_detailes']['act_type_place']) {
                return true;
            }
        }

        //修改活动领取状态
        return $this->grant_award_activity($reward_log['reward_id'], 0);
    }

    /**
     * 修改分销商活动绑定订单记录状态
     * @param int $id
     * @return bool
     */
    public function update_award_log_status($id = 0)
    {
        if (empty($id)) {
            return false;
        }
        $res = DrpActivityRewardLog::where('id', $id)->update(['is_effect' => 0]);
        if ($res) {
            return true;
        }
        return false;
    }

    /**
     * 增加会员分享点击记录
     * @param int $user_id
     * @param int $activity_id
     * @param int $drp_user_id
     * @param int $order_id
     * @param int $add_num
     * @return bool
     */
    public function add_drp_activity_share_num_log($user_id = 0, $activity_id = 0, $drp_user_id = 0, $order_id = 0, $add_num = 1)
    {
        if (empty($user_id) || empty($activity_id) || empty($drp_user_id)) {
            return false;
        }
        $data = [];
        $data['activity_id'] = $activity_id;
        $data['user_id'] = $user_id;
        $data['drp_user_id'] = $drp_user_id;
        $data['activity_id'] = $activity_id;
        $data['order_id'] = $order_id;
        $data['add_num'] = $add_num;
        $data['add_time'] = $this->timeRepository->getGmTime();
        $res = DrpActivityRewardLog::insertGetId($data);
        if ($res) {
            return $res;
        }
        return false;
    }

    /**
     * 判断分销商是否完成整个分销商活动
     * @param int $user_id
     * @param int $activity_id
     * @return bool
     */
    public function get_drp_activity_award($user_id = 0, $activity_id = 0)
    {
        if (empty($user_id) || empty($activity_id)) {
            return false;
        }
        $reward_log = DrpRewardLog::with(['ActivityDetailes' => function ($query) {
            $query->select('id', 'act_type_share', 'act_type_place', 'complete_required');
        }])
            ->where('activity_id', $activity_id)
            ->where('user_id', $user_id)
            ->where('activity_type', 0)
            ->where('participation_status', 0)
            ->first();
        if (is_null($reward_log)) {
            return false;
        }
        $reward_log = $reward_log->toArray();
        if ($reward_log['activity_detailes']['complete_required'] == 1) {
            if ($reward_log['completeness_share'] >= $reward_log['activity_detailes']['act_type_share'] || $reward_log['completeness_place'] >= $reward_log['activity_detailes']['act_type_place']) {
                return $this->grant_award_activity($reward_log['reward_id']);
            }
        } else {
            if ($reward_log['completeness_share'] >= $reward_log['activity_detailes']['act_type_share'] && $reward_log['completeness_place'] >= $reward_log['activity_detailes']['act_type_place']) {
                return $this->grant_award_activity($reward_log['reward_id']);
            }
        }
        return false;
    }

    /**
     * 修改分销商参与分销活动的奖励状态
     * @param int $reward_id
     * @param int $participation_status
     * @return bool
     */
    public function grant_award_activity($reward_id = 0, $participation_status = 1)
    {
        if (empty($reward_id)) {
            return false;
        }
        $reward_log = DrpRewardLog::where('reward_id', $reward_id)->first();
        if (is_null($reward_log)) {
            return false;
        }
        $res = DrpRewardLog::where('reward_id', $reward_id)->update(['participation_status' => $participation_status]);
        if ($res) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 通过订单ID获取与订单相关的所有分销商活动
     * @param int $order_id
     * @return array
     */
    public function get_order_goods_activity($order_id = 0)
    {
        if (empty($order_id)) {
            return [];
        }
        $all_order_goods = OrderGoods::where('order_id', $order_id)->get(['goods_id', 'goods_number', 'user_id']);

        $all_order_goods = $all_order_goods ? $all_order_goods->toArray() : [];
        if (empty($all_order_goods)) {
            return [];
        }
        $order_id_arr = [];
        $order_num_arr = [];
        $user_id = 0;
        foreach ($all_order_goods as $key => $val) {
            $user_id = $val['user_id'];
            $order_id_arr[] = $val['goods_id'];
            $order_num_arr[$val['goods_id']] = 1;//如果需要订单数量使用  $val['goods_number']
        }
        $now_time = $this->timeRepository->getGmTime();
        $all_activity = DrpActivityDetailes::where('is_finish', 1)->whereIn('goods_id', $order_id_arr)->where('end_time', '>=', $now_time)->get();
        $all_activity = $all_activity ? $all_activity->toArray() : [];
        return ['all_activity' => $all_activity, 'goods_num' => $order_num_arr, 'user_id' => $user_id];
    }

    /**
     * 通过用户和活动ID  获取分销商ID
     * @param int $user_id
     * @param int $activity_id
     * @return int
     */
    public function get_drp_user_activity($user_id = 0, $activity_id = 0)
    {
        if (empty($user_id) || empty($activity_id)) {
            return 0;
        }
        //获取分销商ID
        $act_drp_user = DrpActivityRewardLog::where('activity_id', $activity_id)->where('user_id', $user_id)->value('drp_user_id');
        return $act_drp_user ? $act_drp_user : 0;
    }

    /**
     * 检测订单是否已参与过分销活动奖励
     * @param int $user_id
     * @param int $order_id
     * @param int $activity_id
     * @return bool
     */
    public function find_activity_order_mess($user_id = 0, $order_id = 0, $activity_id = 0)
    {
        if (empty($user_id) || empty($order_id) || empty($activity_id)) {
            return false;
        }
        $activity_reward_log = DrpActivityRewardLog::where('order_id', $order_id)->where('drp_user_id', $user_id)->where('activity_id', $activity_id)->first();
        $activity_reward_log = $activity_reward_log ? $activity_reward_log->toArray() : [];
        if (empty($activity_reward_log)) {
            return true;
        }
        return false;
    }

    /**
     * 获取退款订单相关的所有分销商与分销商活动
     * @param int $order_id
     * @return array
     */
    public function repeal_order_activity($order_id = 0)
    {
        if (empty($order_id)) {
            return [];
        }
        $all_log = DrpActivityRewardLog::where('order_id', $order_id)->where('is_effect', 1)->get();
        $all_log = $all_log ? $all_log->toArray() : [];
        if (empty($all_log)) {
            return [];
        }
        return $all_log;
    }

    /**
     * 获取分销商活动相关的订单数量信息
     * @param int $activity_id
     * @param int $goods_id
     * @return array
     */
    public function statistics_order_activity_message($activity_id = 0, $goods_id = 0)
    {
        $res = ['finish_order_num' => [], 'unfinish_order_num' => []];
        if (empty($activity_id) || empty($goods_id)) {
            return $res;
        }
        //获取所有的奖励记录
        $all_reward_log = DrpActivityRewardLog::where('activity_id', $activity_id)->where('is_effect', 1)->get();
        $all_reward_log = $all_reward_log ? $all_reward_log->toArray() : [];
        if (empty($all_reward_log)) {
            return $res;
        }
        //绑定订单与分销商的关系
        $order_bind_drp_user = [];
        $all_order_id = [];
        foreach ($all_reward_log as $key => $val) {
            $order_bind_drp_user[$val['order_id']] = $val['drp_user_id'];
            $all_order_id[] = $val['order_id'];
        }
        //获取所有订单信息
        $all_order = OrderGoods::with(['getOrder' => function ($query) {
            $query->select('order_id', 'pay_status', 'order_status', 'shipping_status');
        }])
            ->whereIn('order_id', $all_order_id)
            ->where('goods_id', $goods_id)
            ->get();

        $all_order = $all_order ? $all_order->toArray() : [];
        if (empty($all_order)) {
            return $res;
        }
        //处理订单与分销商的绑定关系
        foreach ($all_order as $key => $val) {
            $num = 1;//统计订单数量  防止以后修改为订单商品数量拓展  $val['goods_number']
            if (isset($val['get_order']) && $val['get_order']['pay_status'] == 2 && $val['get_order']['shipping_status'] == 2) {
                //已完成订单
                if (isset($res['finish_order_num'][$order_bind_drp_user[$val['order_id']]])) {
                    $res['finish_order_num'][$order_bind_drp_user[$val['order_id']]] = $res['finish_order_num'][$order_bind_drp_user[$val['order_id']]] + $num;
                } else {
                    $res['finish_order_num'][$order_bind_drp_user[$val['order_id']]] = $num;
                }
            } else {
                //订单未完成
                if (isset($res['unfinish_order_num'][$order_bind_drp_user[$val['order_id']]])) {
                    $res['unfinish_order_num'][$order_bind_drp_user[$val['order_id']]] = $res['unfinish_order_num'][$order_bind_drp_user[$val['order_id']]] + $num;
                } else {
                    $res['unfinish_order_num'][$order_bind_drp_user[$val['order_id']]] = $num;
                }
            }
        }
        return $res;
    }

    /**
     * 通过活动ID获取分销商所有的应该奖励的信息
     * @param int $activity_id
     * @return array
     */
    public function get_reward_log($activity_id = 0)
    {
        if (empty($activity_id)) {
            return [];
        }
        $reward_log = DrpRewardLog::where('activity_id', $activity_id)->where('activity_type', 0)->where('award_status', 0)->where('participation_status', 1)->get();
        return $reward_log ? $reward_log->toArray() : [];
    }

    /**
     * 添加用户资金操作(积分/余额/佣金)
     * @param int $user_id
     * @param int $num
     * @param int $award_type 0 积分 1 余额
     * @return bool
     */
    public function operate_user_money($user_id = 0, $num = 0, $award_type = 0, $operate_type = 1)
    {
        if (empty($user_id) || empty($num)) {
            return false;
        }
        $num = $num * $operate_type;
        if ($operate_type == 1) {
            $change_desc = '分销商活动奖励';
        } else {
            $change_desc = '分销商活动扣除';
        }

        if ($award_type == 0) {
            //奖励积分
            $res = $this->accountLogRepository->log_account_change($user_id, 0, 0, 0, $num, $change_desc);
        } else if ($award_type == 1) {
            //奖励余额
            $res = $this->accountLogRepository->log_account_change($user_id, $num, 0, 0, 0, $change_desc);
        } else if ($award_type == 2) {
            //奖励佣金
            $res = DrpShop::where('user_id', $user_id)->increment('shop_money', $num);
        }
        if ($res) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 修改分销商活动奖励记录奖励状态
     * @param array $all_reward_user
     * @return bool
     */
    public function update_all_reward_status($all_reward_user = [])
    {
        if (empty($all_reward_user)) {
            return false;
        }
        $res = DrpRewardLog::whereIn('reward_id', $all_reward_user)->update(['award_status' => 1]);
        if ($res) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 团队累计佣金计算
     * @param array $all_team_money
     * @return array
     */
    public function count_team_drp_money($all_team_money = [])
    {
        if (empty($all_team_money)) {
            return [];
        }
        //获取所有的分销商的推荐关系
        $all_users = DrpShop::with(['getUsers' => function ($query) {
            $query->select('user_id', 'drp_parent_id');
        }])
            ->get();
        $all_users = $all_users ? $all_users->toArray() : [];
        if (empty($all_users)) {
            return [];
        }
        $all_user_parents = [];
        foreach ($all_users as $key => $val) {
            if (!empty($val['get_users'])) {
                $all_user_parents[$val['get_users']['user_id']] = $val['get_users']['drp_parent_id'];
            }
        }
        //获取所有分销商的对应佣金
        $all_user_money = [];
        foreach ($all_team_money as $key => $val) {
            $all_user_money[$val['user_id']] = $val['money'];
        }
        return $this->dispose_all_user_team_money($all_user_parents, $all_user_money);
    }

    /**
     * 获取分销商推荐关系和分销商佣金  进行团队累计佣金处理
     * @param array $all_user
     * @param array $all_money
     * @return array
     */
    public function dispose_all_user_team_money($all_user = [], $all_money = [])
    {
        if (empty($all_user) || empty($all_money)) {
            return [];
        }
        $all_user_team_money = [];
        foreach ($all_user as $key => $val) {
            $num = $all_money[$key];
            //累积自身业绩
            $user_id = $key;
            $all_user_team_money = $this->accu_user_team_money($num, $user_id, $all_user_team_money);

            //一代累积添加自己的业绩
            if (!isset($all_user[$user_id]) || $all_user[$user_id] == 0) {
                continue;
            }
            $user_id = $all_user[$key];
            $all_user_team_money = $this->accu_user_team_money($num, $user_id, $all_user_team_money);

            //二代累计添加自己的业绩
            if (!isset($all_user[$user_id]) || $all_user[$user_id] == 0) {
                continue;
            }
            $user_id = $all_user[$user_id];
            $all_user_team_money = $this->accu_user_team_money($num, $user_id, $all_user_team_money);

            //三代累计添加自己的业绩
            if (!isset($all_user[$user_id]) || $all_user[$user_id] == 0) {
                continue;
            }
            $user_id = $all_user[$user_id];
            $all_user_team_money = $this->accu_user_team_money($num, $user_id, $all_user_team_money);
        }
        return $all_user_team_money;
    }

    /**
     * 给分销商会员组累加团队佣金
     * @param int $num
     * @param int $user_id
     * @param array $all_user_team_money
     * @return array
     */
    public function accu_user_team_money($num = 0, $user_id = 0, $all_user_team_money = [])
    {
        if ($num == 0 || $user_id == 0) {
            return $all_user_team_money;
        }

        if (isset($all_user_team_money[$user_id])) {
            $all_user_team_money[$user_id] = $all_user_team_money[$user_id] + $num;
        } else {
            $all_user_team_money[$user_id] = $num;
        }

        return $all_user_team_money;
    }

    /**
     * 获取某一分销商等级所有分销商活动信息
     * @param $credit_id
     * @return array
     */
    public function get_drp_user_credit_condition($credit_id)
    {
        $all_condition = DrpUserCredit::where('id', $credit_id)->value('condition_id');
        $all_condition = $all_condition ? unserialize($all_condition) : [];
        if (empty($all_condition)) {
            return [];
        }
        $new_condition = [];
        foreach ($all_condition as $key => $val) {
            $new_condition[$val['condition_id']] = $val['value_id'];
        }
        return $new_condition;
    }

    /**
     * 查询用户已达成的升级条件
     * @param int $user_id
     * @param int $credit_id
     * @param array $all_user_condition
     * @return array
     */
    public function user_reach_upgrader_condition($user_id = 0, $credit_id = 0, $all_user_condition = [])
    {
        if (empty($user_id) || empty($credit_id)) {
            return [];
        }
        //获取用户升级奖励记录
        $all_drp_log = DrpRewardLog::where('activity_type', 1)->where('user_id', $user_id)->where('credit_id', $credit_id)->get();
        $all_drp_log = $all_drp_log ? $all_drp_log->toArray() : [];
        if (empty($all_drp_log)) {
            return [];
        }
        $condition = [];
        $res = [];
        //处理升级奖励记录的条件信息
        foreach ($all_drp_log as $key => $val) {
            if (isset($all_user_condition[$val['activity_id']])) {
                $condition_name = DrpUpgradeCondition::where('id', $val['activity_id'])->value('dsc');
                if (!$condition_name) {
                    continue;
                }
                $condition_value = DrpUpgradeValues::where('id', $all_user_condition[$val['activity_id']])->first(['value', 'type', 'award_num']);
                $condition_value = $condition_value ? $condition_value->toArray() : [];

                $condition['name'] = $condition_name;
                $condition['value'] = $condition_value['value'];
                $condition['type'] = isset($condition_value['type']) && $condition_value['type'] == 1 ? '余额' : '积分';
                $condition['award_num'] = $condition_value['award_num'] ? $condition_value['award_num'] : 0;
                $res[] = $condition;
            }
        }
        return $res;
    }

    /**
     * 查询用户未达成的升级条件
     * @param int $user_id
     * @param int $credit_id
     * @param array $all_user_condition
     * @return array
     */
    public function user_miss_upgrader_condition($user_id = 0, $credit_id = 0, $all_user_condition = [])
    {
        if (empty($user_id) || empty($credit_id)) {
            return [];
        }
        //获取用户升级奖励记录
        $all_drp_log = DrpRewardLog::where('activity_type', 1)->where('user_id', $user_id)->where('credit_id', $credit_id)->get();
        $all_drp_log = $all_drp_log ? $all_drp_log->toArray() : [];
        $reach_log = [];
        if (!empty($all_drp_log)) {
            foreach ($all_drp_log as $key => $val) {
                $reach_log[] = $val['activity_id'];
            }
        }
        $condition = [];
        $res = [];
        //处理升级奖励记录的条件信息
        foreach ($all_user_condition as $key => $val) {
            if (!in_array($key, $reach_log)) {
                $condition_name = DrpUpgradeCondition::where('id', $key)->value('dsc');

                if (!$condition_name) {
                    continue;
                }
                $condition_value = DrpUpgradeValues::where('id', $val)->first(['value', 'type', 'award_num']);
                $condition_value = $condition_value ? $condition_value->toArray() : [];
                $condition['name'] = $condition_name;
                $condition['value'] = $condition_value['value'];
                $condition['type'] = isset($condition_value['type']) && $condition_value['type'] == 1 ? '余额' : '积分';
                $condition['award_num'] = $condition_value['award_num'] ? $condition_value['award_num'] : 0;
                $res[] = $condition;
            }
        }
        return $res;
    }

    /**
     * 关闭分销商活动
     * @param int $id
     * @return bool
     */
    public function update_activity_status($id = 0)
    {
        if (empty($id)) {
            return false;
        }
        return DrpActivityDetailes::where('id', $id)->update(['is_finish' => 0]);
    }

    /**
     * 分销商活动会员列表
     * @param array $all_users
     * @param array $filter
     * @param array $offset
     * @return array
     */
    public function get_all_user_activity_list($all_users = [], $filter = [], $offset = [])
    {

        if (empty($all_users) || empty($filter)) {
            return ['total' => 0, 'all_activity' => []];
        }
        $activity_type = $filter['activity_type'];
        $seller_list = $filter['seller_list'];
        //获取所有的分销商活动参与用户
        $model = DrpRewardLog::with([
            'ActivityDetailes' => function ($query) {
                $query->select('id', 'act_name', 'raward_money', 'raward_type', 'start_time', 'end_time');
            }])
            ->with([
                    'Users' => function ($query) {
                        $query->select('user_id', 'user_name');
                    }]
            )
            ->where('activity_type', $activity_type);

        if (!empty($all_users)) {
            $model = $model->whereIn('user_id', $all_users);
        }
        $total = $model->count();

        if (!empty($offset)) {
            $model = $model->offset($offset['start'])
                ->limit($offset['limit']);
        }
        if ($seller_list > 0) {
            $all_list = DrpActivityDetailes::select('id')->where('ru_id', '>', 0)->get();
        } else {
            $all_list = DrpActivityDetailes::select('id')->where('ru_id', $filter['ru_id'] ?? 0)->get();
        }
        $all_list = $all_list ? $all_list->toArray() : [];
        if (!empty($all_list)) {
            $all_list = Arr::flatten($all_list);
            $model = $model->whereIn('activity_id', $all_list);
        }

        $all_activity = $model
            ->orderBy('user_id', 'desc')
            ->get();

        $all_activity = $all_activity ? $all_activity->toArray() : [];
        return ['total' => $total, 'all_activity' => $all_activity];

    }

    /**
     * 获取分销商活动列表
     * @param array $filter
     * @param array $offset
     * @return array
     */
    public function get_all_activity_list($filter = [], $offset = [])
    {
        //获取所有的分销商活动
        $model = DrpActivityDetailes::with([
            'Goods' => function ($query) {
                $query->select('goods_id', 'goods_name');
            }]);

        if (!empty($filter)) {

            $keywords = $filter['keywords'] ?? '';

            if (!empty($keywords)) {
                $model = $model->where('act_name', 'like', '%' . $keywords . '%');
            }
        }

        $seller_list = $filter['seller_list'] ?? 0;
        if ($seller_list > 0) {
            $model = $model->where('ru_id', '>', 0);
        } else {
            $model = $model->where('ru_id', $filter['ru_id'] ?? 0);
        }

        $total = $model->count();

        if (!empty($offset)) {
            $model = $model->offset($offset['start'])
                ->limit($offset['limit']);
        }

        $all_activity = $model->orderBy('id', 'desc')
            ->get();


        $all_activity = $all_activity ? $all_activity->toArray() : [];
        if (!empty($all_activity)) {
            foreach ($all_activity as $key => $val) {
                $all_activity[$key]['start_time'] = $this->timeRepository->getLocalDate('Y-m-d H:i:s', $val['start_time']);
                $all_activity[$key]['end_time'] = $this->timeRepository->getLocalDate('Y-m-d H:i:s', $val['end_time']);
                if ($val['ru_id'] > 0) {
                    $all_activity[$key]['ru_user_name'] = app(MerchantCommonService::class)->getShopName($val['ru_id'], 1);
                }
            }
        }
        return ['total' => $total, 'all_activity' => $all_activity];
    }

    /**
     * 分享订单结算操作处理
     * @param int $order_id
     * @param int $drp_user_id
     * @return bool
     */
    public function pay_order_activity($order_id = 0, $drp_user_id = 0)
    {
        if (empty($order_id)) {
            return false;
        }

        //获取订单中和分销商活动有关的信息
        $all_activitys = $this->get_order_goods_activity($order_id);
        if (empty($all_activitys) || empty($all_activitys['all_activity'])) {
            return false;
        }

        foreach ($all_activitys['all_activity'] as $k => $v) {
            $drp_user_id = $this->get_drp_user_activity($all_activitys['user_id'], $v['id']);
            //处理订单中和分销商活动有关的数据
            $this->drpCommonService->add_activity_order_share_num($order_id, $drp_user_id, $all_activitys);
        }


        return true;
    }

    /**
     * 逐级系统分类导航 字符串
     * @param int $cat_id
     * @param int $user_id 商家id
     * @param string $table
     * @return string
     */
    public function get_every_category($cat_id = 0, $user_id = 0, $table = 'category')
    {
        $parent_cat_list = get_select_category($cat_id, 1, true, $user_id, $table);
        $filter_category_navigation = $this->publicCategoryService->get_array_category_info($parent_cat_list, $table);
        $cat_nav = '';
        if ($filter_category_navigation) {
            foreach ($filter_category_navigation as $key => $val) {
                if ($key == 0) {
                    $cat_nav .= $val['cat_name'];
                } elseif ($key > 0) {
                    $cat_nav .= " > " . $val['cat_name'];
                }
            }
        }

        return $cat_nav;
    }

    /**
     * 设置系统分类 筛选、搜索品牌列表
     * @param int $goods_id
     * @param int $cat_id
     * @param int $user_id
     * @param int $cat_type_show
     * @param string $table
     * @return array
     */
    public function set_default_filter($goods_id = 0, $cat_id = 0, $user_id = 0, $cat_type_show = 0, $table = 'category')
    {
        $filter = [
            'filter_category_navigation' => '',
            'filter_category_list' => '',
            'filter_brand_list' => '',
        ];

        // 分类导航
        if ($cat_id > 0) {
            $parent_cat_list = get_select_category($cat_id, 1, true, $user_id, $table);
            $filter_category_navigation = $this->publicCategoryService->get_array_category_info($parent_cat_list, $table);
            $filter['filter_category_navigation'] = $filter_category_navigation;
        }

        if ($user_id) {
            $seller_shop_cat = $this->publicCategoryService->seller_shop_cat($user_id);
        } else {
            $seller_shop_cat = [];
        }

        $filter['table'] = $table;
        $filter['filter_category_list'] = get_category_list($cat_id, 0, $seller_shop_cat, $user_id, 2, $table);//分类列表
        $filter['filter_brand_list'] = search_brand_list($goods_id, $user_id);//品牌列表
        $filter['cat_type_show'] = $cat_type_show; //平台分类

        return $filter;
    }

    /**
     * 拒绝审核 退还消费积分
     * @param int $id
     * @param string $user_note
     * @return bool
     */
    public function refuse_drp_after($id = 0, $user_note = '')
    {
        if (empty($id)) {
            return false;
        }

        // 已拒绝 且申请通道为 消费积分兑换
        $drp_shop = DrpShop::where('id', $id)->where('audit', 2)->first();
        if (empty($drp_shop)) {
            return false;
        }

        $receive_type = 'integral';
        $account_log = app(DrpAccountLogRepository::class)->accountLogInfoByUser($drp_shop['user_id'], $drp_shop['membership_card_id'], $receive_type);
        if (!empty($account_log)) {
            $pay_point = abs($account_log['pay_point'] ?? 0);

            // 退还消费积分 同时记录账户变动日志
            $log_id = $this->accountLogRepository->log_account_change($drp_shop['user_id'], 0, 0, 0, $pay_point, $user_note);

            return true;
        }

        return false;
    }

    /**
     * @param int $drp_shop_user_id
     * @return mixed
     */
    public function drp_upgrade_main_con($drp_shop_user_id = 0)
    {
        return $this->drpCommonService->drp_upgrade_main_con($drp_shop_user_id, 3);
    }

}
