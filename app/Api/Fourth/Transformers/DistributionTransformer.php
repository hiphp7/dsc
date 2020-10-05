<?php

namespace App\Api\Fourth\Transformers;

use App\Api\Foundation\Transformer\Transformer;

/**
 * Class AddressTransformer
 * @package App\Api\Fourth\Transformer
 */
class DistributionTransformer extends Transformer
{

    /**
     * @param $item
     * @return array|mixed
     */
    public function transform($item)
    {
        return [
            'id' => $item['id'], // 店铺id
            'user_id' => $item['user_id'], // 会员id
            'shop_name' => $item['shop_name'], // 店铺名称
            'real_name' => $item['real_name'], // 真是姓名
            'mobile' => $item['mobile'], // 手机号
            'qq' => $item['qq'], // qq号
            'shop_img' => $item['shop_img'], // 店铺背景
            'create_time' => $item['create_time'], // 申请时间
            'isbuy' => $item['isbuy'], // 购买标识
            'audit' => $item['audit'], // 审核状态
            'status' => $item['status'], // 店铺状态
            'shop_money' => $item['shop_money'], // 店铺佣金
            'type' => $item['type'], // 分销类型 0 全部  1 分类  2 商品
            'credit_id' => $item['credit_id'], // 等级id
            'user_name' => $item['nickname'], // 会员昵称
            'user_picture' => $item['headimgurl'], // 店铺头像
            'apply_channel' => $item['apply_channel'], // 申请通道
            'membership_card_id' => $item['membership_card_id'], // 会员权益卡id
            'open_time' => $item['open_time'], // 开店时间
            'expiry_time' => $item['expiry_time'], // 权益卡过期时间
            'membership_status' => $item['membership_status'], // 权益卡启用状态
        ];
    }
}
