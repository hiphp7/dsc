<?php

namespace App\Plugins\UserRights\Drp\Services;

use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\CommonRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Drp\DrpConfigService;
use App\Services\UserRights\UserRightsService;


class DrpRightsService
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

    /**
     * 付费购买分成
     * @param string $code
     * @param int $user_id
     * @param int $drp_account_log_id
     * @param int $parent_id
     * @param int $money
     * @return bool
     */
    public function buyDrpLog($code = '', $user_id = 0, $drp_account_log_id = 0, $parent_id = 0, $money = 0)
    {
        if (empty($code) || empty($user_id) || empty($money)) {
            return false;
        }

        // 会员卡分销权益
        $userRights = $this->userRightsService->userRightsInfo($code);
        if (empty($userRights)) {
            return false;
        }

        if (isset($userRights['enable']) && isset($userRights['install']) && $userRights['enable'] == 1 && $userRights['install'] == 1) {
            $rights_configure = $userRights['rights_configure'];
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

                $row['user_id'] = $user_id;
                $row['parent_id'] = $parent_id;
                $is_separate = 0;

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

                    // 当前分销商 会员权益卡信息
                    $cardRights = $this->userRightsService->membershipCardInfoByUserId($row['user_id'], $userRights['id']);
                    $card_rights_configure = $cardRights['0']['rights_configure'] ?? [];
                    $membership_card_id = $cardRights['0']['membership_card_id'] ?? 0;

                    // 权益卡分成比例
                    $card_per = 0;
                    if (!empty($card_rights_configure)) {
                        $card_per = $card_rights_configure[$i]['value'] ?? 0;
                    }

                    $level_percent = ((float)$card_per / 100);// 分成比例
                    $setmoney = round($money * $level_percent, 2);//佣金
                    $setpoint = 0;

                    //插入drp_log
                    if ($row['user_id'] > 0) {
                        $log_type = 1; // 付费购买
                        $this->userRightsService->writeDrpLog(0, $drp_account_log_id, $row['user_id'], $row['user_name'], $setmoney, $setpoint, $i, $is_separate, 0, $membership_card_id, $log_type, $level_percent);
                    }
                }
            }
        }

        return false;
    }

    /**
     * 指定商品购买分成
     * @param string $code
     * @param int $user_id
     * @param int $order_id
     * @param int $parent_id
     * @param int $money
     * @return bool
     */
    public function goodsDrpLog($code = '', $user_id = 0, $order_id = 0, $parent_id = 0, $money = 0)
    {
        if (empty($code) || empty($user_id) || empty($money)) {
            return false;
        }

        // 会员卡分销权益
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

                $row['user_id'] = $user_id;
                $row['parent_id'] = $parent_id;
                $is_separate = 0;

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

                    // 当前分销商 会员权益卡信息
                    $cardRights = $this->userRightsService->membershipCardInfoByUserId($row['user_id'], $userRights['id']);
                    $card_rights_configure = $cardRights['0']['rights_configure'] ?? [];
                    $membership_card_id = $cardRights['0']['membership_card_id'] ?? 0;

                    // 权益卡分成比例
                    $card_per = 0;
                    if (!empty($card_rights_configure)) {
                        $card_per = $card_rights_configure[$i]['value'] ?? 0;
                    }

                    $level_percent = ((float)$card_per / 100);// 分成比例
                    $setmoney = round($money * $level_percent, 2);//佣金
                    $setpoint = 0;

                    // 插入drp_log
                    if ($row['user_id'] > 0) {
                        $log_type = 2; // 指定商品购买 分成
                        $this->userRightsService->writeDrpLog($order_id, 0, $row['user_id'], $row['user_name'], $setmoney, $setpoint, $i, $is_separate, 0, $membership_card_id, $log_type, $level_percent);
                    }
                }
            }
        }

        return false;
    }

    /**
     * 订单购买 开通会员权益卡分成
     * @param string $code
     * @param int $user_id
     * @param int $drp_account_log_id
     * @param int $parent_id
     * @param int $money
     * @return bool
     */
    public function orderBuyDrpLog($code = '', $user_id = 0, $drp_account_log_id = 0, $parent_id = 0, $money = 0)
    {
        if (empty($code) || empty($user_id) || empty($money)) {
            return false;
        }

        // 会员卡分销权益
        $userRights = $this->userRightsService->userRightsInfo($code);
        if (empty($userRights)) {
            return false;
        }

        if (isset($userRights['enable']) && isset($userRights['install']) && $userRights['enable'] == 1 && $userRights['install'] == 1) {
            $rights_configure = $userRights['rights_configure'];
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

                $row['user_id'] = $user_id;
                $row['parent_id'] = $parent_id;
                $is_separate = 0;

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

                    // 当前分销商 会员权益卡信息
                    $cardRights = $this->userRightsService->membershipCardInfoByUserId($row['user_id'], $userRights['id']);
                    $card_rights_configure = $cardRights['0']['rights_configure'] ?? [];
                    $membership_card_id = $cardRights['0']['membership_card_id'] ?? 0;

                    // 权益卡分成比例
                    $card_per = 0;
                    if (!empty($card_rights_configure)) {
                        $card_per = $card_rights_configure[$i]['value'] ?? 0;
                    }

                    $level_percent = ((float)$card_per / 100);// 分成比例
                    $setmoney = round($money * $level_percent, 2);//佣金
                    $setpoint = 0;

                    //插入drp_log
                    if ($row['user_id'] > 0) {
                        $log_type = 3; // 订单购买 开通会员权益卡 分成
                        $this->userRightsService->writeDrpLog(0, $drp_account_log_id, $row['user_id'], $row['user_name'], $setmoney, $setpoint, $i, $is_separate, 0, $membership_card_id, $log_type, $level_percent);
                    }
                }
            }
        }

        return false;
    }

}