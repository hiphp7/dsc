<?php

return [

    /*
    |--------------------------------------------------------------------------
    | auction Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during auction for various
    | messages that we need to display to the user. You are free to modify
    | these language lines according to your application's requirements.
    |
    */

    'is_hot' => '热销',
    'ishot' => '热销',
    'isnothot' => '非热销',

    /* menu */
    'auction_list' => '拍卖活动列表',
    'add_auction' => '添加拍卖活动',
    'edit_auction' => '编辑拍卖活动',
    'auction_log' => '拍卖活动出价记录',
    'continue_add_auction' => '继续添加拍卖活动',
    'back_auction_list' => '返回拍卖活动列表',
    'add_auction_ok' => '添加拍卖活动成功',
    'edit_auction_ok' => '编辑拍卖活动成功',
    'settle_deposit_ok' => '处理冻结的保证金成功',
    'auction_audited_set_ok' => '拍卖活动审核状态设置成功',

    /* list */
    'act_is_going' => '仅显示进行中的活动',
    'act_name' => '拍卖活动名称',
    'goods_name' => '商品名称',
    'start_time' => '开始时间',
    'end_time' => '结束时间',
    'deposit' => '保证金',
    'start_price' => '起拍价',
    'end_price' => '一口价',
    'amplitude' => '加价幅度',
    'auction_not_exist' => '您要操作的拍卖活动不存在',
    'auction_cannot_remove' => '该拍卖活动已经有人出价，不能删除',
    'batch_drop_ok' => '操作完成（已经有人出价的拍卖活动不能删除）',
    'no_record_selected' => '没有选择记录',

    /* info */
    'label_act_name' => '拍卖活动名称：',
    'notice_act_name' => '如果留空，取拍卖商品的名称（该名称仅用于后台，前台不会显示）',
    'label_act_desc' => '拍卖活动描述：',
    'label_search_goods' => '根据商品编号、名称或货号搜索商品',
    'label_goods_name' => '拍卖商品名称：',
    'label_start_time' => '拍卖开始时间：',
    'label_end_time' => '拍卖结束时间：',
    'label_status' => '当前状态：',
    'label_start_price' => '起拍价：',
    'label_end_price' => '一口价：',
    'label_no_top' => '无封顶',
    'label_amplitude' => '加价幅度：',
    'label_deposit' => '保证金：',
    'bid_user_count' => '已有 %s 个买家出价',
    'settle_frozen_money' => '怎样处理买家的冻结资金？',
    'unfreeze' => '解冻保证金',
    'deduct' => '扣除保证金',
    'invalid_status' => '当前状态不正确',
    'no_deposit' => '没有保证金需要处理',
    'unfreeze_auction_deposit' => '解冻拍卖活动的保证金：%s',
    'deduct_auction_deposit' => '扣除拍卖活动的保证金：%s',

    'auction_status' => [
        PRE_START => '未开始',
        UNDER_WAY => '进行中',
        FINISHED => '已结束',
        SETTLED => '已结束',
    ],

    'pls_search_goods' => '请先搜索商品',
    'search_goods' => '搜索商品',
    'search_result_empty' => '没有找到商品，请重新搜索',

    'pls_select_goods' => '请选择拍卖商品',
    'goods_not_exist' => '您要拍卖的商品不存在',

    'js_languages' => [
        'start_price_not_number' => '起拍价格式不正确（数字）',
        'end_price_not_number' => '一口价格式不正确（数字）',
        'end_gt_start' => '一口价应该大于起拍价',
        'amplitude_not_number' => '加价幅度格式不正确（数字）',
        'deposit_not_number' => '保证金格式不正确（数字）',
        'start_lt_end' => '拍卖开始时间不能大于结束时间',
        'search_is_null' => '没有搜索到任何商品，请重新搜索',
        'good_name_not_null' => '请选择商品',
        'start_price_not_null' => '请输入起拍价',
        'amplitude_not_null' => '请输入加价幅度',
        'batch_drop_confirm' => '您确实要删除选中的拍卖活动吗？',
    ],

    /* log */
    'bid_user' => '买家',
    'bid_price' => '出价',
    'bid_time' => '时间',
    'status' => '状态',

    'label_start_end_time' => '拍卖起止时间：',
    'basic_info' => '基本信息',
    'auction_desc' => '拍卖介绍',
    'promise' => '服务保障',
    'ensure' => '竞拍攻略',

    'auction_explain' => '商城拍卖活动说明',

    /* 页面顶部操作提示 */
    'operation_prompt_content' => [
        '0' => '列表页可根据条件，如商品名称、店铺名称等搜索出具体参加拍卖活动的商品活动信息。',
        '1' => '列表页展示了所有的拍卖活动名称、商家名称、商品名称、起始时间等信息列表。',
        '2' => '可添加、查看、编辑、删除或批量删除拍卖活动操作。',

        '3' => '该页面展示了拍卖活动的出价记录。',
        '4' => '主要展示了买家、出价、时间、状态的信息列表。',
    ],

    'operating_hints' => '操作提示',
    'warn_submit_will_recheck' => '温馨提示：提交操作将会重新审核，请慎重提交确定。',
    'whether_hot' => '是否热销',

];