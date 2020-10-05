<?php

return [
    /*
    |--------------------------------------------------------------------------
    | affiliate_ck Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during affiliate_ck for various
    | messages. You are free to modify
    | these language lines according to your application's requirements.
    |
    */

    'separate_name' => '推荐分成',
    'order_id' => '订单号',
    'affiliate_separate' => '分成',
    'affiliate_cancel' => '取消',
    'affiliate_rollback' => '撤销',
    'log_info' => '操作信息',
    'edit_ok' => '操作成功',
    'edit_fail' => '操作失败',
    'separate_info' => '订单号 %s, 分成:金钱 %s 积分 %s',
    'separate_info2' => '用户ID %s ( %s ), 分成:金钱 %s 积分 %s',
    'sch_order' => '搜索订单号/用户名/昵称',

    'sch_stats' => [
        'name' => '操作状态',
        'info' => '按操作状态查找:',
        'all' => '全部',
        '0' => '等待处理',
        '1' => '已分成',
        '2' => '取消分成',
        '3' => '已撤销',
    ],

    'order_stats' => [
        'name' => '订单状态',
        '0' => '未确认',
        '1' => '已确认',
        '2' => '已取消',
        '3' => '无效',
        '4' => '退货',
    ],
    'js_languages' => [
        'cancel_confirm' => '您确定要取消分成吗？此操作不能撤销。',
        'rollback_confirm' => '您确定要撤销此次分成吗？',
        'separate_confirm' => '您确定要分成吗？',
    ],
    'loginfo' => [
        '0' => '用户id:',
        '1' => '现金:',
        '2' => '积分:',
        'cancel' => '分成被管理员取消！',
    ],
    'separate_type' => '分成类型',
    'batch_separate' => '批量分成',
    'separate_by' => [
        '0' => '推荐注册分成',
        '1' => '推荐订单分成',
        '-1' => '推荐注册分成',
        '-2' => '推荐订单分成',
    ],

    'show_affiliate_orders' => '此列表显示此用户推荐的订单信息。',
    'back_note' => '返回会员编辑页面',

    /* 页面顶部操作提示 */
    'operation_prompt_content' => [
        '0' => '分成订单信息管理。',
        '1' => '可搜索分成订单号查询分成订单。',
    ],


];