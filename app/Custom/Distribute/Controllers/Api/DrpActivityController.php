<?php

namespace App\Custom\Distribute\Controllers\Api;

use App\Api\Foundation\Controllers\Controller as FrontController;
use App\Custom\CustomView;
use App\Custom\Distribute\Services\DistributeManageService;
use App\Custom\Distribute\Services\DistributeService;
use App\Custom\Distribute\Services\DrpCommonService;
use App\Repositories\Common\TimeRepository;
use Illuminate\Http\Request;


class DrpActivityController extends FrontController
{
    use CustomView;

    protected $timeRepository;
    protected $DistributeManageService;
    protected $DistributeService;

    public function __construct(
        TimeRepository $timeRepository,
        DistributeManageService $DistributeManageService,
        DistributeService $DistributeService
    )
    {
        $this->timeRepository = $timeRepository;
        $this->DistributeManageService = $DistributeManageService;
        $this->DistributeService = $DistributeService;
    }

    protected function initialize()
    {
        $this->load_helper('helpers');

        // 当前模块语言包
        $_lang = $this->load_lang(['common', 'drp']);
        L($_lang);
    }

    /**
     * 获取当前所有活动
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function all_activity(Request $request)
    {
        //$activity_type  0  全部  1  未领取  2  已领取
        $activity_type = $request->input('activity_type', 0);
        $page = $request->input('page', 1);
        $size = $request->input('size', 10);

        // 获取用户ID
        $user_id = $this->authorization();
        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }
        //判断用户是否是分销商
        $user_shop = $this->DistributeManageService->find_user_shop($user_id);
        if (empty($user_shop)) {
            return $this->setStatusCode(1)->failed(L('drp_shop_empty_user'));
        }

        //获取用户参与的所有活动ID
        $all_user_activity = $this->DistributeManageService->all_drp_user_activity_id($user_id);
        //查找符合要求的活动
        $all_activity = $this->DistributeManageService->all_drp_activity($all_user_activity, $activity_type, 1, $page, $size, $user_id);
        return $this->succeed($all_activity);
    }

    /**
     * 分销商申请领取分销商活动
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function user_draw_activity(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'activity_id' => 'required|integer',
        ]);
        $activity_id = $request->input('activity_id', 0);

        // 获取用户ID
        $user_id = $this->authorization();
        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        //判断用户是否是分销商
        $user_shop = $this->DistributeManageService->find_user_shop($user_id);
        if (empty($user_shop)) {
            return $this->setStatusCode(1)->failed(L('drp_shop_empty_user'));
        }
        if ($activity_id == 0) {
            return $this->setStatusCode(1)->failed(L('empty_user_draw_activity'));
        }

        //判断活动信息
        $activity = $this->DistributeManageService->find_activity($activity_id);
        if (empty($activity)) {
            return $this->setStatusCode(1)->failed(L('empty_user_draw_activity'));
        }

        //判断分销商是否领取过该活动
        $user_reward_activity = $this->DistributeManageService->find_user_reward_activity($activity_id, $user_id);
        if (!empty($user_reward_activity)) {
            return $this->setStatusCode(1)->failed(L('user_draw_activity_exist'));
        }

        //分销商领取活动操作
        $draw_activity = $this->DistributeManageService->user_draw_activity($activity, $user_id);
        if ($draw_activity) {
            return $this->setStatusCode(1)->succeed(L('user_draw_activity_success'));
        } else {
            return $this->setStatusCode(1)->failed(L('user_draw_activity_error'));
        }
    }

    /**
     * 获取分销商活动详情
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function get_activity_details(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'activity_id' => 'required|integer',
        ]);
        $activity_id = $request->input('activity_id', 0);
        // 获取用户ID
        $user_id = $this->authorization();
        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        //判断用户是否是分销商
        $user_shop = $this->DistributeManageService->find_user_shop($user_id);
        if (empty($user_shop)) {
            return $this->setStatusCode(1)->failed(L('drp_shop_empty_user'));
        }
        if ($activity_id == 0) {
            return $this->setStatusCode(1)->failed(L('empty_user_draw_activity'));
        }

        //获取分销商活动详情
        $activity = $this->DistributeManageService->find_drp_activity($user_id, $activity_id);
        return $this->succeed($activity);
    }

    /**
     * 分销活动 -- 前台分享处理
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function share_goods_activity(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'goods_id' => 'required|integer',
        ]);
        $drp_user_id = $request->input('drp_user_id', 0);
        $goods_id = $request->input('goods_id', 0);
        if (empty($drp_user_id)) {
            return $this->setStatusCode(1)->failed(L('empty_drp_parent_id'));
        }
        if (empty($goods_id)) {
            return $this->setStatusCode(1)->failed(L('error_goods_activity_parameter'));
        }
        //更新分销商活动状态
//        $this->DistributeManageService->renewal_activity_status();

        // 获取用户ID
        $user_id = $this->authorization();
        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        //获取分销活动信息
        $activity = $this->DistributeService->find_activity_mess_goods($goods_id);
        if (empty($activity)) {
            return $this->setStatusCode(1)->failed(L('empty_user_draw_activity'));
        }
        $now_time = $this->timeRepository->getGmTime();
        if ($activity['end_time'] < $now_time) {
            return $this->setStatusCode(1)->failed(L('overtime_user_draw_activity'));
        }

        //获取分销商信息
        $drp_user_shop = $this->DistributeManageService->find_drp_shop_user($drp_user_id);
        if (empty($drp_user_shop)) {
            return $this->setStatusCode(1)->failed(L('empty_user_drp_shop'));
        }

        //判断分销商是否实际参与过活动
        $drp_user_reward_log = $this->DistributeManageService->find_drp_activity_user($drp_user_id, $activity['id']);
        if (empty($drp_user_reward_log)) {
//            $this->DistributeManageService->user_draw_activity($activity, $drp_user_id);
            return $this->setStatusCode(1)->failed(L('empty_user_drp_activity'));
        }

        //判断用户是否已参与过活动的点击次数
        $user_join_activity_status = $this->DistributeManageService->judge_user_activity_status($user_id, $activity['id']);
        if (!empty($user_join_activity_status)) {
            //用户已经提供过点击次数
            return $this->setStatusCode(1)->failed(L('user_repetition_join_activity'));
        }

        //增加点击量
        $add_num = $this->DistributeManageService->add_drp_activity_share_num($drp_user_id, $activity['id'], 1);
        if (!$add_num) {
            //增加失败
            return $this->setStatusCode(1)->failed(L('operation_failure'));
        }

        //增加点击量记录
        $add_log = $this->DistributeManageService->add_drp_activity_share_num_log($user_id, $activity['id'], $drp_user_id);
        if (!$add_log) {
            //添加失败  扣除点击量
            $this->DistributeManageService->reduce_drp_activity_share_num($user_id, $activity['id'], $drp_user_id);
            //添加失败
            return $this->setStatusCode(1)->failed(L('operation_failure'));
        }

        if ($add_num >= $activity['act_type_share']) {
            //分销商达成活动分享要求,判断是否完成整个分销商活动
            $this->DistributeManageService->get_drp_activity_award($user_id, $activity['id']);
        }
        return $this->succeed(L('share_activity_success'));
    }

    /**
     * 分享订单结算操作处理
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function pay_order_activity(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'order_id' => 'required|integer',
        ]);
        $order_id = $request->input('order_id', 0);
        $drp_user_id = $request->input('drp_user_id', 0);
        if (empty($order_id)) {
            return $this->setStatusCode(1)->failed(L('error_goods_activity_parameter'));
        }
        //更新分销商活动状态
//        $this->DistributeManageService->renewal_activity_status();

        // 获取用户ID
        $user_id = $this->authorization();
        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        //获取订单中和分销商活动有关的信息
        app(DrpCommonService::class)->add_activity_order_share_num($order_id, $drp_user_id, $all_activitys);

        return $this->succeed(L('place_activity_success'));
    }

    /**
     * 用户撤单分销商活动处理接口
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function repeal_order_activity_dispose(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'order_id' => 'required|integer',
        ]);
        $order_id = $request->input('order_id', 0);
        if (empty($order_id)) {
            return $this->setStatusCode(1)->failed(L('error_goods_activity_parameter'));
        }
        //获取订单中和分销商活动有关的信息
        $all_activitys_log = $this->DistributeManageService->repeal_order_activity($order_id);
        if (empty($all_activitys_log)) {
            return $this->succeed(L('place_activity_success'));
        }
        foreach ($all_activitys_log as $key => $val) {
            //扣除分销商活动统计数数量
            $res_mess = $this->DistributeManageService->reduce_drp_activity_share_num($val['drp_user_id'], $val['activity_id'], $val['add_num'], 'completeness_place');
            if (!$res_mess) {
                //执行失败  跳出循环
                continue;
            }
            //修改日志记录状态
            $this->DistributeManageService->update_award_log_status($val['id']);
        }
        return $this->succeed(L('place_activity_success'));
    }

    /**
     * 获取分销条件信息
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function drp_user_upgrade_condition(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'condition_status' => 'required|integer',
        ]);
        //0 未完成条件  1  已完成条件
        $condition_status = $request->input('condition_status', 0);

        // 获取用户ID
        $user_id = $this->authorization();
        if (!$user_id) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }

        //判断用户状态
        $user_shop = $this->DistributeManageService->find_user_shop($user_id);
        if (empty($user_shop)) {
            return $this->setStatusCode(1)->failed(L('empty_user_drp_shop'));
        }

        if ($user_shop['credit_id'] == 12) {
            return $this->setStatusCode(1)->failed(L('user_highest_level'));
        }
        $credit_id = app(DrpCommonService::class)->drp_rank_info_upgrade($user_shop);
        if (!$credit_id) {
            return $this->setStatusCode(1)->failed(L('empty_user_drp_shop'));
        }
        //获取当前等级所有升级活动
        $all_user_condition = $this->DistributeManageService->get_drp_user_credit_condition($credit_id);
        if ($condition_status == 0) {
            //查询用户未达成的升级条件
            $all_doncition = $this->DistributeManageService->user_miss_upgrader_condition($user_id, $credit_id, $all_user_condition);
        } else if ($condition_status == 1) {
            //查询用户已达成的升级条件
            $all_doncition = $this->DistributeManageService->user_reach_upgrader_condition($user_id, $credit_id, $all_user_condition);
        }
        if (isset($all_doncition) && !empty($all_doncition)) {
            return $this->succeed($all_doncition);
        }

        return $this->setStatusCode(1)->failed(L('error_goods_activity_parameter'));
    }

}