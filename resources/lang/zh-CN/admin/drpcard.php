<?php

return [

    /*
    |--------------------------------------------------------------------------
    | drpcard Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used by the drpcard
    |
    */

    // 分销权益卡
    'drp_card' => '分销权益卡',
    'drp_card_tips' => [
        '设置分销权益卡，当用户申请或购买后，即可获得分销会员资格。不同的会员卡可设置不同的权益，为这些会员提供尊贵会员级别的超值优惠，刺激会员在商城下单消费；',
        '分销员可帮助商城推广商品，推广越多返佣越多，通过分润返佣加速裂变传播，拉新促客，以客推客，从而获得更多会员以及提高商城销量；',
        '会员卡下如果没有会员的情况下，可以在编辑页删除，如果有会员的情况下，只能编辑会员卡信息，不可删除会员卡。',
        '已停发的卡，用户端将不可出售，但已购买/领取的权益卡，还可以继续使用并享受相关的权益；'
    ],
    'add_drp_card' => '添加分销权益卡',
    'edit_drp_card' => '编辑分销权益卡',
    'sync' => '同步历史数据',
    'sync_ok' => '同步成功',
    'drp_card_image' => '权益卡图片',
    'background_img_notice' => '建议尺寸：1029*480像素，小于1M，支持jpg、png、jpeg格式',
    'drp_card_name' => '权益卡名称',
    'drp_card_name_not_null' => '权益卡名称不能为空',
    'drp_card_name_repeat' => '权益卡名称已存在',
    'drp_card_limit' => '权益卡数量超出限制',
    'drp_card_condition' => '领取条件',
    'drp_card_expiry' => '有效期',
    'expiry_type_forever' => '永久有效',
    'expiry_type_days' => '领卡时起',
    'expiry_type_days_part' => '天内有效',
    'expiry_date_start' => '开始时间',
    'to' => '至',
    'expiry_date_end' => '结束时间',
    'receive_config' => '领取设置',
    'receive_type_free' => '免费领取',
    'receive_type_buy' => '付费购买',
    'placeholder_for_buy' => '请输入付费金额',
    'receive_type_goods' => '购买指定商品',
    'select_goods_menu' => '选择商品',
    'select_goods_length_limit' => '最多可选择10件商品',
    'receive_type_order' => '消费金额累积',
    'placeholder_for_order' => '请输入指定金额',
    'receive_type_integral' => '消费积分兑换',
    'placeholder_for_integral' => '请输入积分兑换值',
    'receive_type_order_buy' => '订单购买成为分销商',

    'drp_card_desc' => '权益卡描述',
    'card_bg_set' => '背景设置',
    'member_view' => '查看成员',
    'bind_rights_list' => '绑定的会员权益',
    'confirm_unbind_rights' => '您确定要删除此会员权益吗？',
    'confirm_drop_card' => '您确定要删除此分销权益卡吗？',
    'cannot_drop_card' => '此分销权益卡下有关联会员禁止删除！',
    'please_input_value' => '请输入大于0的值',
    'disabled_card' => '停止发放',
    'card_enable_1' => '发放中',
    'card_enable_0' => '已停发',
    'confirm_disabled_card' => '您确定停止发放【:card_name】会员权益卡？停发后，在用户端，会员不可以领取此权益卡，已领取权益卡的会员，还可继续使用。',
    'bind_rights' => '选择会员权益',
    'bind_rights_tips' => [
        '打勾表示选择，灰色的勾表示已选择过',
        '没有想要选择的会员权益，请在【会员】-【会员权益】下，<a href="../user_rights">点击安装</a> >>'
    ],
    'rights_type' => '权益类型',
    'rights_choice' => '选择',
    'rights_desc' => '权益描述',
    'edit_rights' => '编辑会员权益',
    'edit_rights_tips' => [
        '修改分销会员卡权益的优惠方式；'
    ],

    'membership_card_id_empty' => '权益卡id不能为空',
    'id_empty' => '权益id不能为空',
    'all' => '全部',

    'goods_info' => '商品信息',
    'price' => '价格',

];
