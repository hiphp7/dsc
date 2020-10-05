<?php

namespace App\Custom\Distribute\Services;

use App\Services\UserRights\UserRightsService;

class DistributeGoodsService
{
    protected $userRightsService;

    public function __construct(
        UserRightsService $userRightsService
    )
    {
        $this->userRightsService = $userRightsService;
    }

    /**
     * 计算用户购买本商品的一级佣金
     * @param int $user_id
     * @param array $goods
     * @return float|int
     */
    public function calculate_goods_commossion($user_id = 0, $goods = [])
    {
        if (empty($user_id) || empty($goods)) {
            return 0;
        }

        if (isset($goods['membership_card_id']) && $goods['membership_card_id'] > 0) {

            $drp_money = $goods['shop_price_original'];

            // 会员卡分销权益
            $drpRights = $this->userRightsService->userRightsInfo('drp');
            if (!empty($drpRights)) {
                if (isset($drpRights['enable']) && isset($drpRights['install']) && $drpRights['enable'] == 1 && $drpRights['install'] == 1) {
                    // 当前分销商 会员权益卡信息
                    $drpCardRights = $this->userRightsService->membershipCardInfoByUserId($user_id, $drpRights['id']);
                    $drp_card_rights_configure = $drpCardRights['0']['rights_configure'] ?? [];
                }

                // 权益卡分成比例
                $card_per = 0;
                if (!empty($drp_card_rights_configure)) {
                    $card_per = $drp_card_rights_configure[0]['value'] ?? 0;
                }

                $level_percent = ((float)$card_per / 100);// 分成比例
                $setmoney = round($drp_money * $level_percent, 2);//佣金

                return $setmoney;
            }

        } elseif (isset($goods['is_distribution']) && $goods['is_distribution'] > 0) {
            $drp_money = 0;
            if ($goods['dis_commission_type'] == 0) {
                //按比例进行佣金分配
                $drp_money = $goods['shop_price'] * $goods['dis_commission'] / 100;
            } else if ($goods['dis_commission_type'] == 1) {
                //按照后台设置佣金数量进行发放
                $drp_money = $goods['dis_commission'];
            }

            // 商品分销权益
            $userRights = $this->userRightsService->userRightsInfo('drp_goods');
            if (!empty($userRights)) {
                if (isset($userRights['enable']) && isset($userRights['install']) && $userRights['enable'] == 1 && $userRights['install'] == 1) {
                    // 当前分销商 会员权益卡信息
                    $cardRights = $this->userRightsService->membershipCardInfoByUserId($user_id, $userRights['id']);
                    $card_rights_configure = $cardRights['0']['rights_configure'] ?? [];
                }

                $card_per = 0;
                if (!empty($card_rights_configure)) {
                    $card_per = $card_rights_configure[0]['value'] ?? 0;
                }

                $level_percent = ((float)$card_per / 100);// 分成比例
                $setmoney = round($drp_money * $level_percent, 2);//佣金
                return $setmoney;
            }
        }

        return 0;
    }

}