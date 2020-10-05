<?php

namespace App\Plugins\UserRights\DrpGoods\Services;

use App\Models\DrpLog;
use App\Models\OrderInfo;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\CommonRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Drp\DrpConfigService;
use App\Services\UserRights\UserRightsService;


class DrpGoodsRightsService
{
    protected $baseRepository;
    protected $timeRepository;
    protected $dscRepository;
    protected $userRightsService;
    protected $commonRepository;
    protected $drpConfigService;

    public function __construct(
        BaseRepository $baseRepository,
        TimeRepository $timeRepository,
        DscRepository $dscRepository,
        UserRightsService $userRightsService,
        CommonRepository $commonRepository,
        DrpConfigService $drpConfigService
    )
    {
        $this->baseRepository = $baseRepository;
        $this->timeRepository = $timeRepository;
        $this->dscRepository = $dscRepository;
        $this->userRightsService = $userRightsService;
        $this->commonRepository = $commonRepository;
        $this->drpConfigService = $drpConfigService;
    }

    // 原 addDrpLog 方法
    public function orderDrpLog($code = '', $user_id = 0)
    {
        //获取已分成最大订单号
        $last_oid = DrpLog::query()->max('order_id');
        $last_oid = $last_oid ? $last_oid : 0;

        // 主表查询
        $model = OrderInfo::query()->where('main_count', 0)
            ->where('order_id', '>', $last_oid);

        // 订单有分销商品时，才显示
        $model = $model->whereHas('getOrderGoods', function ($query) {
            $query->select('drp_money')->where('drp_money', '>', 0);
        });

        $order = $model->select('order_id')
            ->orderby('order_id', 'ASC');
        $order = $this->baseRepository->getToArrayGet($order);

        if (empty($order)) {
            return false;
        }

        // 商品分销权益
        $userRights = $this->userRightsService->userRightsInfo($code);
        if (empty($userRights)) {
            return false;
        }

        if (isset($userRights['enable']) && isset($userRights['install']) && $userRights['enable'] == 1 && $userRights['install'] == 1) {
            $rights_configure = $userRights['rights_configure'] ?? [];
            if (empty($rights_configure)) {
                return false;
            }

            $drp_config = $this->drpConfigService->drpConfig();
            $drp_affiliate = $drp_config['drp_affiliate_on']['value'] ?? 0;
            // 开启分销
            if ($drp_affiliate == 1) {
                // 分销业绩归属模式
                $drp_affiliate_mode = $drp_config['drp_affiliate_mode']['value'] ?? 1;
                // 分销内购模式
                $isdistribution = $drp_config['isdistribution']['value'] ?? 0;

                $affiliate_drp_id = $this->commonRepository->getDrpShopAffiliate($user_id, 1);

                foreach ($order as $key => $value) {

                    $model = OrderInfo::query()->where('order_id', $value['order_id']);
                    $model = $model->whereHas('getOrderGoods');
                    $model = $model->with([
                        'goods' => function ($query) {
                            $query->select('order_id', 'drp_money')->where('drp_money', '>', 0);
                        }
                    ]);
                    $model = $model->select('order_id', 'order_sn', 'drp_is_separate', 'user_id', 'parent_id');
                    $row = $this->baseRepository->getToArrayFirst($model);

                    // 获取订单商品分销金额总和
                    $drp_money = $this->baseRepository->getSum($row['goods'], 'drp_money');
                    $is_separate = $row['drp_is_separate'] ?? 0;

                    // 遍历
                    foreach ($rights_configure as $i => $item) {

                        if ($isdistribution == 0) {
                            /**
                             *  0. 禁用内购模式
                             *  mode 1: 业绩归属 上级分销商
                             *  mode 0: 业绩归属 推荐人或上级分销商
                             */
                            if ($drp_affiliate_mode == 1) {
                                // 分销业绩归属:1
                                $user_id = $row['user_id'];
                            } else {
                                // 分销业绩归属:0
                                $user_id = ($i == 0) ? $row['parent_id'] : $row['user_id'];
                            }

                            // 从上级分销商开始分成
                            $user_model = $this->userRightsService->getParentDrpShopUser($user_id);
                            $user = $user_model['get_parent_drp_shop'] ?? [];

                        } elseif ($isdistribution == 1) {
                            /**
                             *  1. 内购模式
                             *  mode 1: 业绩归属 上级分销商 + 自己
                             *  mode 0: 业绩归属 推荐人或上级分销商 + 自己
                             */
                            if ($drp_affiliate_mode == 1) {
                                // 分销业绩归属:1
                                $user_id = $row['user_id'];
                            } else {
                                // 分销业绩归属:0
                                $user_id = ($i == 0) ? $row['parent_id'] : $row['user_id'];
                            }

                            if ($i == 0) {
                                // 从当前分销商开始分成
                                $user_model = $this->userRightsService->getDrpShopUser($user_id);
                                $user = $user_model['get_drp_shop'] ?? [];
                                $user['user_name'] = $user_model['user_name'] ?? '';

                                if (!$user_model) {
                                    // 当前下单会员不是分销商，从上级分销商开始分成
                                    $user_model = $this->userRightsService->getParentDrpShopUser($user_id);
                                    $user = $user_model['get_parent_drp_shop'] ?? [];
                                }
                            } else {
                                // 从上级分销商开始分成
                                $user_model = $this->userRightsService->getParentDrpShopUser($user_id);
                                $user = $user_model['get_parent_drp_shop'] ?? [];
                            }

                        } elseif ($isdistribution == 2) {
                            /**
                             *  2. 自动模式
                             *  mode 1: 业绩归属 上级分销商 + 自己（条件：推荐自己微店内商品或自己推荐的链接）
                             *  mode 0：业绩归属 推荐人或上级分销商 + 自己（条件：推荐自己微店内商品或自己推荐的链接）
                             */
                            if ($drp_affiliate_mode == 1) {
                                // 分销业绩归属:1
                                $user_id = ($i == 0) ? ($affiliate_drp_id > 0 ? $affiliate_drp_id : $row['user_id']) : $row['user_id'];
                            } else {
                                // 分销业绩归属:0
                                $user_id = ($i == 0) ? $row['parent_id'] : $row['user_id'];
                            }

                            if ($i == 0) {
                                // 推荐自己微店内商品或自己推荐的链接
                                if ($affiliate_drp_id > 0 || $affiliate_drp_id == $row['user_id']) {
                                    // 从当前下单分销商开始分成
                                    $user_model = $this->userRightsService->getDrpShopUser($user_id);
                                    $user = $user_model['get_drp_shop'] ?? [];
                                    $user['user_name'] = $user_model['user_name'] ?? '';

                                } else {
                                    // 从上级分销商开始分成
                                    $user_model = $this->userRightsService->getParentDrpShopUser($user_id);
                                    $user = $user_model['get_parent_drp_shop'] ?? [];
                                }

                            } else {
                                // 从上级分销商开始分成
                                $user_model = $this->userRightsService->getParentDrpShopUser($user_id);
                                $user = $user_model['get_parent_drp_shop'] ?? [];
                            }
                        }

                        if (empty($user)) {
                            continue;
                        }

                        $row['user_id'] = $user['user_id'] ?? 0;
                        $row['user_name'] = $user['user_name'] ?? '';

                        if (!isset($user['drp_shop_id']) || is_null($user['drp_shop_id']) || empty($user['drp_shop_id'])) {
                            break;
                        }

                        // 当前分销商绑定的会员权益卡信息
                        $cardRights = $this->userRightsService->membershipCardInfoByUserId($row['user_id'], $userRights['id']);
                        $card_rights_configure = $cardRights['0']['rights_configure'] ?? [];
                        $membership_card_id = $cardRights['0']['membership_card_id'] ?? 0;

                        $card_per = 0;
                        if (!empty($card_rights_configure)) {
                            $card_per = $card_rights_configure[$i]['value'] ?? 0;
                        }

                        $level_percent = ((float)$card_per / 100);// 分成比例
                        $setmoney = round($drp_money * $level_percent, 2);//佣金
                        $setpoint = 0;//佣金

                        // 插入drp_log
                        if ($row['user_id'] > 0) {
                            $log_type = 0;
                            $this->userRightsService->writeDrpLog($value['order_id'], 0, $row['user_id'], $row['user_name'], $setmoney, $setpoint, $i, $is_separate, 0, $membership_card_id, $log_type, $level_percent);
                        }
                    }
                }
            }
        }

        return false;
    }

}