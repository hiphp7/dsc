<?php

namespace App\Custom\Distribute\Services;

use App\Custom\Distribute\Models\DrpRewardLog;
use App\Custom\Distribute\Models\DrpTransferLog;
use App\Custom\Distribute\Models\DrpUpgradeCondition;
use App\Custom\Distribute\Models\DrpUpgradeValues;
use App\Models\DrpShop;
use App\Models\DrpUserCredit;
use App\Models\OrderGoods;
use App\Models\OrderInfo;
use App\Models\Users;
use App\Models\WechatUser;
use App\Repositories\Common\TimeRepository;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;


/**
 * 后台与前台 公用 service
 * Class DrpCommonService
 * @package App\Custom\Distribute\Services
 */
class DrpCommonService
{
    protected $timeRepository;

    public function __construct(
        TimeRepository $timeRepository
    )
    {
        $this->timeRepository = $timeRepository;
    }

    /**
     * 查询openid
     * @param int $user_id
     * @return mixed
     */
    public function get_openid($user_id = 0)
    {
        if ($user_id == 0) {
            return '';
        }

        $openid = WechatUser::where(['ect_uid' => $user_id])->orderBy('uid', 'DESC')->value('openid');

        return $openid;
    }

    /**
     * 查询分销商信息
     * @param int $user_id
     * @return array|bool
     */
    public function getDrpShopAccount($user_id = 0)
    {
        if (empty($user_id)) {
            return false;
        }

        $result = DrpShop::where('user_id', $user_id)->first();

        return $result ? $result->toArray() : [];
    }

    /**
     * 提现申请更新分销商佣金
     * @param int $user_id
     * @param int $shop_money
     * @param int $frozen_money
     * @param int $deposit_fee
     */
    public function updateDrpShopAccount($user_id = 0, $shop_money = 0, $frozen_money = 0, $deposit_fee = 0)
    {
        // 更新信息
        $shop_money = $shop_money + $deposit_fee;
        $update_log = [
            'frozen_money' => DB::raw("frozen_money  + ('$frozen_money')"),
        ];

        DrpShop::where('user_id', $user_id)->increment('shop_money', $shop_money, $update_log);
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

        //检测分销商是否是特殊等级,直接获取等级信息
        $credit_id = DrpShop::where('user_id', $user_id)->value('credit_id');

        if ($credit_id > 0) {
            $credit_id = DrpUserCredit::where('id', $credit_id)->value('id');
        } else {
            // 统计分销商所属已分成订单佣金
            $goods_price = OrderGoods::from('order_goods as o')
                ->leftjoin('drp_log as a', 'o.order_id', '=', 'a.order_id')
                ->where('a.is_separate', 1)
                ->where('a.separate_type', '!=', '-1')
                ->where('a.user_id', $user_id)
                ->sum('money');

            $goods_price = $goods_price ? $goods_price : 0;

            $credit_id = DrpUserCredit::where('max_money', '>=', $goods_price)
                ->orderBy('max_money', 'ASC')
                ->value('id');
        }

        if ($credit_id > 0) {
            $data = ['credit_id' => $credit_id];
            return DrpShop::where('user_id', $user_id)->update($data);
        }
        return false;
    }

    /**
     * 处理分销商升级条件
     * @param int $user_id 用户ID
     * @param int $trigger_type
     * @param int $order_id 订单ID
     * @return bool
     */
    public function drp_upgrade_main_con($user_id = 0, $trigger_type = 0, $order_id = 0)
    {
        if ($user_id == 0 || $trigger_type == 0) {
            return false;
        }
        //与订单相关条件,未传订单ID
        if ($trigger_type == 1 && $order_id == 0) {
            return false;
        }
        switch ($trigger_type) {
            /*
             * $trigger_type
             * 1 订单相关  前台
             * 2 会员相关   前台:会员注册
             * 3 会员相关   后台：分销商审核
             * 4 提现相关  后台
             * */
            case 1:
                return app(DistributeService::class)->order_accomplish_dispose($user_id, $order_id);
                break;
            case 2:
                return app(DistributeService::class)->user_upgrade_stage_register($user_id);
                break;
            case 3:
                return app(DistributeManageService::class)->user_upgrade_background_augit($user_id);
                break;
            case 4:
                return $this->drp_transfer_upgrade($user_id);

                break;
            default:
                return false;
        }

        return false;
    }

    /**
     * 符合分销商升级条件处理
     * @param int $user_id
     * @param string $field_name
     * @return bool
     */
    public function conclude_user_upgrade_condition($user_id = 0, $field_name = '')
    {
        if ($user_id == 0 || $field_name == '') {
            return false;
        }

        //分销商店铺以及等级信息
        $user_drp_message = Users::from('users as u')->leftjoin('drp_shop as d', 'u.user_id', '=', 'd.user_id')->where('u.user_id', '=', $user_id)->first();
        if (is_null($user_drp_message)) {
            return false;
        }

        $credit_id = $this->drp_rank_info_upgrade($user_drp_message->toArray());
        if (!isset($credit_id) || !$credit_id || $credit_id == 0) {
            return false;
        }

        $field_id = $this->judge_condition_open($field_name, $credit_id);
        if (isset($field_id) && $field_id > 0) {
            $reward_log = DrpRewardLog::where('user_id', $user_id)->where('activity_type', 1)->where('activity_id', $field_id)->where('credit_id', $credit_id)->first();
            if ($reward_log == null) {
                //未发放过奖励,需要发放奖励
                $this->reward_upgrade_award($user_id, $field_id, $credit_id);
            }
            //用户升级处理  判断用户是否符合当前所有升级条件
            return $this->upgrade_user_drp_level($user_id, $credit_id);

        }
        return false;
    }

    /**
     * 用户升级处理
     * @param int $user_id
     * @param int $credit_id
     * @return bool
     */
    public function upgrade_user_drp_level($user_id = 0, $credit_id = 0)
    {
        if ($user_id == 0 && $credit_id == 0) {
            return false;
        }
        $credit_condition = DrpUserCredit::where('id', $credit_id)->value('condition_id');
        if (!isset($credit_condition) || empty($credit_condition)) {
            //用户未设置升级条件.默认无升级条件需手动升级
            return false;
        } else {
            $credit_condition = unserialize($credit_condition);
        }

        $all_condition = DrpRewardLog::where('user_id', $user_id)->where('activity_type', 1)->where('credit_id', $credit_id)->select('activity_id')->get();
        if (!isset($all_condition) || empty($all_condition)) {
            return false;
        }

        $all_condition = Arr::dot($all_condition->toArray());
        $equal_num = 0;
        foreach ($credit_condition as $key => $val) {
            if (in_array($val['condition_id'], $all_condition)) {
                $equal_num++;
            }
        }

        if ($equal_num > 0) {//如果全部条件都要达成    添加判断条件  && $equal_num == count($credit_condition)
            //所有的升级条件全部符合 会员升级处理
            if ($credit_id > 0) {
                $new_credit_id = $credit_id;
            } else {
                $new_credit_id = 0;
            }
        }
        if (isset($new_credit_id) && $new_credit_id > 0) {
            $upgrate_user_level = DrpShop::where('user_id', $user_id)->update(array('credit_id' => $new_credit_id));
            if ($upgrate_user_level) {
                return $this->reward_money_for_user($user_id, $new_credit_id);
            }
        }
        return false;
    }

    /**
     * 返还分销商所有升级条件的奖励
     * @param int $user_id
     * @param int $credit_id
     * @return bool
     */
    public function reward_money_for_user($user_id = 0, $credit_id = 0)
    {
        if (empty($user_id) || empty($credit_id)) {
            return false;
        }
        $all_reward_log = DrpRewardLog::where('award_status', 0)->where('user_id', $user_id)->where('credit_id', $credit_id)->where('activity_type', 1)->orderBy('award_money', 'desc')->first();
        $all_reward_log = $all_reward_log ? $all_reward_log->toArray() : [];
        if (empty($all_reward_log)) {
            return false;
        }
        $all_reward_id = [];
        $reward_status = $this->reward_user_money($user_id, $all_reward_log['award_money'], $all_reward_log['award_type']);
        if ($reward_status) {
            $all_reward_id[] = $reward_status['reward_id'];
        }

        if (empty($all_reward_id)) {
            return false;
        }
        $status = DrpRewardLog::where('reward_id', $all_reward_id)->update(['award_status' => 1]);
        return $status;
    }

    /**
     * 为用户的账户增加余额或积分
     * @param int $user_id
     * @param int $award_money
     * @param int $award_type 1 余额 0 积分
     * @return bool
     */
    public function reward_user_money($user_id = 0, $award_money = 0, $award_type = 0)
    {
        if (empty($user_id) || empty($award_money)) {
            return false;
        }
        if ($award_type == 1) {
            //奖励余额
            $update_incerase = Users::where('user_id', $user_id)->increment('user_money', $award_money);
        } else if ($award_type == 0) {
            //奖励积分
            $update_incerase = Users::where('user_id', $user_id)->increment('pay_points', $award_money);
        }

        if (!isset($update_incerase) || !$update_incerase) {
            return false;
        }

        return true;
    }

    /**
     * 会员触发升级条件进行升级处理
     * @param int $user_id
     * @param int $condition_id
     * @param int $credit_id
     * @return bool
     */
    public function reward_upgrade_award($user_id = 0, $condition_id = 0, $credit_id = 0)
    {
        if ($user_id == 0 || $condition_id == 0 || $credit_id == 0) {
            return false;
        }

        $upgra_value = DrpUpgradeValues::where('credit_id', $credit_id)->where('condition_id', $condition_id)->first();
        if (!isset($upgra_value) || empty($upgra_value)) {
            return false;
        }
        $upgra_value = $upgra_value->toArray();
        if (!isset($upgra_value['award_num']) || empty($upgra_value['award_num']) || $upgra_value['award_num'] == 0) {
            return false;
        }

//        if ($upgra_value['type'] == 1) {
//            //奖励余额
//            $update_incerase = Users::where('user_id', $user_id)->increment('user_money', $upgra_value['award_num']);
//        } else if ($upgra_value['type'] == 0) {
//            //奖励积分
//            $update_incerase = Users::where('user_id', $user_id)->increment('pay_points', $upgra_value['award_num']);
//        }
//
//        if (!isset($update_incerase) || !$update_incerase) {
//            return false;
//        }

        $data = array();
        $data['user_id'] = $user_id;
        $data['activity_id'] = $condition_id;
        $data['award_money'] = $upgra_value['award_num'];
        $data['award_type'] = $upgra_value['type'];
        $data['activity_type'] = 1;
        $data['award_status'] = 0;
        $data['participation_status'] = 1;
        $data['add_time'] = $this->timeRepository->getGmTime();
        $data['credit_id'] = $credit_id;
        return DrpRewardLog::insertGetId($data);

    }

    /**
     * 通过条件名和分销商等级判断升级条件是否存在  存在输出条件ID  不存在输出false
     * @param $field_name
     * @param $credit_id
     * @return bool
     */
    public function judge_condition_open($field_name, $credit_id)
    {
        $condition_id = DrpUpgradeCondition::where('name', $field_name)->value('id');
        $credit_condition = DrpUserCredit::where('id', $credit_id)->value('condition_id');
        if (!isset($credit_condition) || empty($credit_condition)) {
            //用户未设置升级条件.默认无升级条件需手动设置
            return false;
        } else {
            $credit_condition = unserialize($credit_condition);
        }
        if (isset($credit_condition) && !empty($credit_condition)) {
            foreach ($credit_condition as $key => $val) {
                if ($val['condition_id'] == $condition_id) {
                    return $condition_id;
                }
            }
        }
        return false;
    }

    /**
     * 通过用户ID获取用户的上三代信息
     * @param int $user_id 用户ID
     * @param int $type 0  查询parent_id  1  查询drp_parent_id
     * @return array|bool
     */
    public function gain_user_drp_message($user_id = 0, $type = 0)
    {
        if ($user_id == 0) {
            return [];
        }
        if ($type == 0) {
            $field = 'parent_id';
        } else if ($type == 1) {
            $field = 'drp_parent_id';
        }

        if (isset($field) && !empty($field)) {
            $all_user = array();
            $user = Users::where('user_id', '=', $user_id)->first();
            if (isset($user) && !empty($user)) {
                $user = $user->toArray();
                if ($user["{$field}"] != 0) {
                    $user_onep = Users::where('user_id', '=', $user["{$field}"])->first();
                }
                $all_user[] = $user;
            }

            if (isset($user_onep) && !empty($user_onep)) {
                $user_onep = $user_onep->toArray();
                if ($user_onep["{$field}"] != 0) {
                    $user_twop = Users::where('user_id', '=', $user_onep["{$field}"])->first();
                }
                $all_user[] = $user_onep;
            }

            if (isset($user_twop) && !empty($user_twop)) {
                $user_twop = $user_twop->toArray();
                if ($user_twop["{$field}"] != 0) {
                    $user_threep = Users::where('user_id', '=', $user_twop["{$field}"])->first();
                }
                $all_user[] = $user_twop;
            }

            if (isset($user_threep) && !empty($user_threep)) {
                $all_user[] = $user_threep->toArray();
            }

            if (isset($all_user) && !empty($all_user)) {
                return $all_user;
            }
        }
        return [];
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

        //获取提现记录
        $transfer_log = DrpTransferLog::where('user_id', $user_id)->where('check_status', 1)->where('deposit_status', 1)->where('finish_status', 1)->first();

        if (is_null($transfer_log)) {
            return false;
        }

        //获取需要的升级条件 提现佣金额度
        $reality_condition = $this->drp_upgrade_condoition($transfer_log->user_id, 'withdraw_all_money');
        if (!$reality_condition) {
            return false;
        }
        //获取用户累计提现佣金
        $all_transfer_money = DrpTransferLog::where('user_id', $transfer_log->user_id)->where('check_status', 1)->where('deposit_status', 1)->where('finish_status', 1)->sum('money');
        if (!$transfer_log || $all_transfer_money < $reality_condition) {
            return false;
        }

        return $this->conclude_user_upgrade_condition($user_id, 'withdraw_all_money');
    }

    /**
     * 获取当前分销商某一项升级条件的所需值
     * @param int $user_id
     * @param string $field_name 字段name
     * @return bool
     */
    public function drp_upgrade_condoition($user_id = 0, $field_name = '')
    {
        if ($user_id == 0 || empty($field_name)) {
            return false;
        }
        //分销商信息
        $user_drp_message = Users::from('users as u')->leftjoin('drp_shop as d', 'u.user_id', '=', 'd.user_id')->where('u.user_id', '=', $user_id)->first();
        if (!isset($user_drp_message) || empty($user_drp_message)) {
            return false;
        }

        $credit_id = $this->drp_rank_info_upgrade($user_drp_message->toArray());
        if (!isset($credit_id) || !$credit_id || $credit_id == 0) {
            return false;
        }

        $credit_condition = DrpUserCredit::where('id', $credit_id)->value('condition_id');
        if (!isset($credit_condition) || empty($credit_condition)) {
            //用户未设置升级条件.默认无升级条件需手动设置
            return false;
        } else {
            $credit_condition = unserialize($credit_condition);
        }

        $condition_id = DrpUpgradeCondition::where('name', $field_name)->value('id');
        if (!isset($condition_id) || empty($condition_id)) {
            return false;
        }

        foreach ($credit_condition as $key => $val) {
            if ($val['condition_id'] == $condition_id) {
                return DrpUpgradeValues::where('id', $val['value_id'])->where('credit_id', $credit_id)->value('value');
            }
        }
        return false;
    }

    /**
     * 计算获取分销商等级
     * @param array $drp_shop
     * @return bool|string
     */
    public function drp_rank_info($drp_shop = [])
    {
        //检测分销商是否是特殊等级,直接获取等级信息
        if (isset($drp_shop['credit_id']) && $drp_shop['credit_id'] > 0) {
            $rank_info = DrpUserCredit::select('id', 'credit_name', 'min_money', 'max_money')
                ->where(['id' => $drp_shop['credit_id']])
                ->first();
        } else {
            //统计分销商所属订单金额
            $goods_price = OrderGoods::from('order_goods as o')
                ->leftjoin('drp_log as a', 'o.order_id', '=', 'a.order_id')
                ->where('a.is_separate', 1)
                ->where('a.separate_type', '!=', '-1')
                ->where('a.user_id', $drp_shop['user_id'])
                ->sum('money');

            $goods_price = $goods_price ? $goods_price : 0;

            $rank_info = DrpUserCredit::select('id', 'credit_name', 'min_money', 'max_money')
                ->where('min_money', '<=', $goods_price)
                ->where('max_money', '>', $goods_price)
                ->orderBy('min_money', 'ASC')
                ->first();

            $rank_info = $rank_info ? $rank_info->toArray() : [];
        }
        if (!empty($rank_info) && $rank_info) {
            return $rank_info['id'];
        } else {
            return false;
        }

    }

    /**
     * 获取分销商分销等级(分销商升级专用)
     * @param array $drp_shop
     * @return bool|int
     */
    public function drp_rank_info_upgrade($drp_shop = [])
    {
        //检测分销商是否是特殊等级,直接获取等级信息
        if (isset($drp_shop['credit_id']) && $drp_shop['credit_id'] > 0) {
            $rank_info = DrpUserCredit::select('id', 'credit_name', 'min_money', 'max_money')
                ->where(['id' => $drp_shop['credit_id']])
                ->first();
            $credit_id = $rank_info['id'];
        } else {
            $credit_id = 0;
        }

        $all_credit = DrpUserCredit::orderBy('min_money', 'asc')->get(['id']);
        $all_credit = $all_credit ? $all_credit->toArray() : [];
        if (empty($all_credit)) {
            return false;
        }

        if ($credit_id == 0) {
            $grade_id = $all_credit[0]['id'];
        } else if ($credit_id == $all_credit[2]['id']) {
            return false;
        } else {
            foreach ($all_credit as $key => $val) {
                if ($credit_id == $val['id']) {
                    $grade_id = $all_credit[$key + 1]['id'];
                }
            }
        }
        return isset($grade_id) ? $grade_id : false;
    }

    /**
     * 获取分销商升级条件的各项实际值
     * @param int $user_id
     * @param string $condition_name
     * @param int $order_id
     * @return bool
     */
    public function distributor_upgrade_dispose_order($user_id = 0, $condition_name = '')
    {
        if ($user_id == 0 || $condition_name == '') {
            return false;
        }
        //用户分销信息
        $user_drp_message = Users::from('users as u')->leftjoin('drp_shop as d', 'u.user_id', '=', 'd.user_id')->where('u.user_id', '=', $user_id)->first();
        if (!isset($user_drp_message) || empty($user_drp_message)) {
            return false;
        }

        $user_drp_message = $user_drp_message->toArray();
        if ($user_drp_message['shop_name'] == '' || $user_drp_message['status'] != 1 || $user_drp_message['audit'] != 1) {
            //店铺不存在或店铺未经审核,无法参与升级活动
            return false;
        }

        switch ($condition_name) {
            /*
             * $layer 查询层级 1直属 2二代 3所有
             * $level 查询类型 1 会员 2 分销商
             * $order_field 查询订单状态 1 订单总金额  2  订单总数量
             * */
            case "all_order_money":
                //分销订单总金额
                $layer = 3;
                $level = 2;
                $order_field = 1;
                break;
            case "all_direct_order_money":
                //一级分销订单总额
                $layer = 1;
                $level = 2;
                $order_field = 1;
                break;
            case "all_order_num":
                //分销订单总笔数
                $layer = 3;
                $level = 2;
                $order_field = 2;
                break;
            case "all_direct_order_num":
                //一级分销订单总数
                $layer = 1;
                $level = 2;
                $order_field = 2;
                break;
            case "all_self_order_money":
                //自购订单金额
                return $this->statistics_order_commission([$user_id], 1);
                break;
            case "all_self_order_num":
                //自购订单数量
                return $this->statistics_order_commission([$user_id], 2);
                break;
            default:
                return false;
        }
        $all_lower_user = $this->get_drp_lower($user_id, $layer, $level);
        if (isset($all_lower_user) && !empty($all_lower_user) && isset($order_field) && !empty($order_field)) {
            return $this->statistics_order_commission($all_lower_user, $order_field);
        }
        return false;
    }

    /**
     * 根据多用户信息进行订单数据的查询
     * @param array $users
     * @param int $order_field 1 总金额  2  总数量
     * @return bool
     */
    public function statistics_order_commission($users = array(), $order_field = 1)
    {
        if (!isset($users) || empty($users)) {
            return false;
        }
        if ($order_field == 1) {
            //查询订单总金额
            return OrderInfo::whereIn('user_id', $users)->where('shipping_status', 2)->where('pay_status', PS_PAYED)->sum('money_paid');
        } else if ($order_field == 2) {
            //查询订单总数量
            return OrderInfo::whereIn('user_id', $users)->where('shipping_status', 2)->where('pay_status', PS_PAYED)->count();
        }
        return false;
    }

    /**
     * 获取分销商id 条件 已审核未冻结状态
     * @param array $all_user_id
     * @return array|bool
     */
    public function judge_drp_user_id($all_user_id = [])
    {
        $all_drp_user = DrpShop::whereIn('user_id', $all_user_id)->where('status', 1)->where('audit', 1)->get('user_id');
        $all_user_id = $all_drp_user ? $all_drp_user->toArray() : [];
        if (!empty($all_user_id)) {
            return Arr::flatten($all_user_id);
        }
        return [];
    }

    /**
     * 查询分销商下级信息
     * @param int $user_id 分销商ID
     * @param int $layer 查询层级 1 查询直属下级 2 查询该分销商的第二代所有下级 3 查询该分销商所有下级(三代 包含一代二代所有)
     * @param int $level 查询类型 1  查询该分销商的下级会员   2  查询该分销商的下级分销商
     * @return array|bool
     */
    public function get_drp_lower($user_id = 0, $layer = 1, $level = 1)
    {
        if ($level == 2) {
            //查询的是下级分销商 使用数据库字段  drp_parent_id
            $field = "drp_parent_id";
        } else if ($level == 1) {
            //查询的是下级会员  使用数据库字段  parent_id
            $field = "parent_id";
        }

        if (isset($field)) {
            $all_user = Users::where($field, $user_id)->get('user_id');
            $all_user = $all_user ? $all_user->toArray() : [];
            if (isset($all_user) && !empty($all_user)) {
                $all_user = $all_user;
            } else {
                $all_user = [$user_id];
            }
        } else {
            return false;
        }

        if ($layer == 1 && !empty($all_user)) {
            //查询直属下级
            return Arr::flatten($all_user);
        } else if ($layer == 2 && !empty($all_user) && $field) {
            //查询二代下级(仅二代)
            $all_user_two = Users::whereIn($field, $all_user)->get('user_id');
            if (isset($all_user_two) && !empty($all_user_two)) {
                $all_user_two = $all_user_two->toArray();
            }
            return Arr::flatten($all_user_two);
        } else if ($layer == 3 && !empty($all_user) && $field) {
            //查询所有下级(三代 包含一代二代所有)
            $all_user_two = Users::whereIn($field, $all_user)->get('user_id');
            if (isset($all_user_two) && !empty($all_user_two)) {
                //二代存在  查询三代并合并一代
                $all_user_two = $all_user_two->toArray();
                $all_user_three = Users::whereIn($field, $all_user_two)->get('user_id');
                //二代存在  查询三代
                if (isset($all_user_three) && !empty($all_user_three)) {
                    $all_user_three = $all_user_three->toArray();
                }
                //合并 一代二代
                $all_user = Arr::collapse([Arr::flatten($all_user), Arr::flatten($all_user_two)]);
            }
            if (isset($all_user_three) && !empty($all_user_three)) {
                $all_user = Arr::collapse([Arr::flatten($all_user), Arr::flatten($all_user_three)]);
            }

            if ($level == 1) {
                return $all_user;
            } else if ($level == 2) {
                return $this->judge_drp_user_id($all_user);
            }
            return false;
        }
        return false;
    }

    /**
     * 通过订单ID获取订单总金额
     * @param array $all_log
     * @return int
     */
    public function get_order_all_money($all_log = [])
    {
        if (empty($all_log)) {
            return 0;
        }
        $all_order_id = [];
        foreach ($all_log as $key => $val) {
            $all_order_id[] = $val['order_id'];
        }
        $all_money_order = OrderInfo::whereIn('order_id', $all_order_id)->get(['money_paid', 'surplus']);
        $all_money_order = $all_money_order ? $all_money_order->toArray() : [];
        if (empty($all_money_order)) {
            return 0;
        }
        $all_num = 0;
        foreach ($all_money_order as $key => $val) {
            $all_num += $val['money_paid'];
            $all_num += $val['surplus'];
        }
        return $all_num;
    }

    /**
     * 处理订单中和分销商活动有关的数据
     * @param int $order_id
     * @param int $drp_user_id
     * @param array $all_activitys
     * @return bool
     */
    public function add_activity_order_share_num($order_id = 0, $drp_user_id = 0, $all_activitys = [])
    {
        if (empty($order_id) || empty($all_activitys) || empty($drp_user_id)) {
            return false;
        }

        $user_id = $all_activitys['user_id'];

        if (empty($user_id)) {
            return false;
        }

        foreach ($all_activitys['all_activity'] as $key => $val) {
            //获取分销商活动该产品绑定的分销商ID
            $drp_user = app(DistributeManageService::class)->get_drp_user_activity($user_id, $val['id']);
            if ($drp_user == 0) {
                $drp_user = $drp_user_id;
            }
            $judge_order_is_set = app(DistributeManageService::class)->find_activity_order_mess($drp_user, $order_id, $val['id']);
            if (!$judge_order_is_set) {
                //奖励已发放过,跳出循环
                continue;
            }

            if ($drp_user == 0) {
                continue;
            }
            //进行分销商活动分享购物数量累加  返回账户当前数量
            $add_num_res = app(DistributeManageService::class)->add_drp_activity_share_num($drp_user, $val['id'], $all_activitys['goods_num'][$val['goods_id']] ? $all_activitys['goods_num'][$val['goods_id']] : 1, 'completeness_place');
            if (!$add_num_res) {
                //执行失败  跳出循环
                continue;
            }
            //添加累加数量纪录
            $add_log_res = app(DistributeManageService::class)->add_drp_activity_share_num_log($user_id, $val['id'], $drp_user, $order_id, $all_activitys['goods_num'][$val['goods_id']] ? $all_activitys['goods_num'][$val['goods_id']] : 1);
            if ($add_log_res) {
                if ($add_num_res >= $val['act_type_place']) {
                    //分销商达成活动分享要求,判断是否完成整个分销商活动
                    app(DistributeManageService::class)->get_drp_activity_award($drp_user, $val['id']);
                }
            }
        }
    }

}
