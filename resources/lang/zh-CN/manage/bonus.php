<?php

return [

    /*
    |--------------------------------------------------------------------------
    | bonus Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during bonus for various
    | messages that we need to display to the user. You are free to modify
    | these language lines according to your application's requirements.
    |
    */
    /* 红包类型字段信息 */
    'bonus_type' => '红包类型',
    'bonus_list' => '红包列表',
    'type_name' => '类型名称',
    'type_money' => '红包金额',
    'min_goods_amount' => '最小订单金额',
    'notice_min_goods_amount' => '只有商品总金额达到这个数的订单才能使用这种红包',
    'min_amount' => '订单下限',
    'max_amount' => '订单上限',
    'send_startdate' => '发放起始日期',
    'send_enddate' => '发放结束日期',
    'add_bonus_type' => '添加红包类型',

    'use_startdate' => '使用起始日期',
    'use_enddate' => '使用结束日期',
    'send_count' => '发放量',
    'use_count' => '使用量',
    'send_method' => '如何发放此类型红包',
    'send_type' => '发放类型',
    'param' => '参数',
    'no_use' => '未使用',
    'yuan' => '元',
    'user_list' => '会员列表',
    'type_name_empty' => '红包类型名称不能为空！',
    'type_money_empty' => '红包金额不能为空！',
    'min_amount_empty' => '红包类型的订单下限不能为空！',
    'max_amount_empty' => '红包类型的订单上限不能为空！',
    'send_count_empty' => '红包类型的发放数量不能为空！',
    'not_select_any_data' => '没有选择任何数据',
    'bonus_audited_type_set_ok' => '红包类型审核状态设置成功',

    'send_by' => [
        SEND_BY_USER => '按用户发放',
        SEND_BY_GOODS => '按商品发放',
        SEND_BY_ORDER => '按订单金额发放',
        SEND_BY_PRINT => '线下发放的红包',
        SEND_BY_GET => '自行领取',
    ],
    'report_form' => '报表下载',
    'send' => '发放',
    'bonus_excel_file' => '线下红包信息列表',
    'batch_drop_ok' => '批量删除成功',

    'goods_cat' => '选择商品分类',
    'goods_brand' => '商品品牌',
    'goods_key' => '商品关键字',
    'all_goods' => '可选商品',
    'send_bouns_goods' => '发放此类型红包的商品',
    'remove_bouns' => '移除红包',
    'all_remove_bouns' => '全部移除',
    'goods_already_bouns' => '该商品已经发放过其它类型的红包了!',
    'send_user_empty' => '您没有选择需要发放红包的会员，请返回!',
    'batch_drop_success' => '成功删除了 %d 个用户红包',
    'sendbonus_count' => '共发送了 %d 个红包。',
    'send_bouns_error' => '发送会员红包出错, 请返回重试！',
    'no_select_bonus' => '您没有选择需要删除的用户红包',
    'bonustype_edit' => '编辑红包类型',
    'bonustype_view' => '查看详情',
    'drop_bonus' => '删除红包',
    'send_bonus' => '发放红包',
    'continus_add' => '继续添加红包类型',
    'back_list' => '返回红包类型列表',
    'continue_add' => '继续添加红包',
    'back_bonus_list' => '返回红包列表',
    'validated_email' => '只给通过邮件验证的用户发放红包',
    'attradd_succed' => '操作成功!',

    /* js提示信息 */
    'js_languages' => [
        'type_name_empty' => '请输入红包类型名称',
        'type_money_empty' => '请输入红包类型价格',
        'order_money_empty' => '请输入订单金额',
        'type_money_isnumber' => '类型金额必须为数字格式',
        'order_money_isnumber' => '订单金额必须为数字格式',
        'bonus_sn_empty' => '请输入红包的序列号',
        'bonus_sn_number' => '红包的序列号必须是数字',
        'bonus_sum_empty' => '请输入您要发放的红包数量',
        'bonus_sum_number' => '红包的发放数量必须是一个整数',
        'bonus_type_empty' => '请选择红包的类型金额',
        'user_rank_empty' => '您没有指定会员等级',
        'user_name_empty' => '您至少需要选择一个会员',
        'invalid_min_amount' => '请输入订单下限（大于0的数字）',
        'send_start_lt_end' => '红包发放开始日期不能大于结束日期',
        'use_start_lt_end' => '红包使用开始日期不能大于结束日期',
        'min_order_total' => '请输入最小订单金额',
    ],

    'send_count_error' => '红包的发放数量必须是一个整数',

    'order_min_money_notic' => '只要订单金额达到该数值，就会发放红包给用户',
    'order_max_money_notic' => '0表示没有上限',
    'type_money_notic' => '此类型的红包可以抵销的金额',
    'send_startdate_notic' => '只有当前时间介于起始日期和截止日期之间时，此类型的红包才可以发放',
    'use_startdate_notic' => '只有当前时间介于起始日期和截止日期之间时，此类型的红包才可以使用',
    'type_name_exist' => '此类型的名称已经存在!',
    'type_money_beyond' => '红包金额不得大于最小订单金额!',
    'type_money_error' => '金额必须是数字并且不能小于 0 !',
    'bonus_sn_notic' => '提示：红包序列号由六位序列号种子加上四位随机数字组成',
    'creat_bonus' => '生成了 ',
    'creat_bonus_num' => ' 个红包序列号',
    'bonus_sn_error' => '红包序列号必须是数字!',
    'send_user_notice' => '给指定的用户发放红包时,请在此输入用户名, 多个用户之间请用逗号(,)分隔开<br />如:liry, wjz, zwj',

    /* 红包信息字段 */
    'bonus_id' => '编号',
    'bonus_type_id' => '类型金额',
    'send_bonus_count' => '红包数量',
    'start_bonus_sn' => '起始序列号',
    'bonus_sn' => '红包序列号',
    'user_id' => '使用会员',
    'used_time' => '使用时间',
    'order_id' => '订单号',
    'send_mail' => '发邮件',
    'emailed' => '邮件通知',

    'mail_status' => [
        BONUS_NOT_MAIL => '未发',
        BONUS_MAIL_FAIL => '已发失败',
        BONUS_MAIL_SUCCEED => '已发成功',
    ],

    'sendtouser' => '给指定用户发放红包',
    'senduserrank' => '按用户等级发放红包',
    'select_user_grade' => '请选择会员等级',
    'userrank' => '用户等级',
    'select_rank' => '选择会员等级...',
    'keywords' => '关键字：',
    'userlist' => '会员列表',
    'send_to_user' => '给下列用户发放红包',
    'search_users' => '搜索会员',
    'confirm_send_bonus' => '确定发送红包',
    'bonus_not_exist' => '该红包不存在',
    'success_send_mail' => '%d 封邮件已被加入邮件列表',
    'send_continue' => '继续发放红包',

    'start_enddate' => '发放起止日期',
    'use_start_enddate' => '使用起止日期',
    'send_continue' => '继续发放红包',
    'bind_password' => '绑定密码',
    'copy_url' => '复制链接',
    'user_name_send' => '根据会员名称发放红包',
    'user_grade_send' => '根据会员等级发放红包',
    'no_offline_send_bonus' => '（非线下发放红包）',

    /* 教程名称 */
    'tutorials_bonus_list_one' => '商城红包使用说明',

    /* 页面顶部操作提示 */
    'operation_prompt_content' => [
        'send' => [
            '0' => '根据会员等级发放红包，也可搜索具体会员发放红包。',
            '1' => '请合理发放红包。',
            '2' => '被发红包的会员可在个人主页中的账户中心查看红包信息，如果红包类型是线下发放的红包需要输入卡号和密码。',
        ],
        'info' => [
            '0' => '点击添加/编辑红包类型进入红包信息页面，填写信息有：类型名称、红包金额、最小订单金额等。',
            '1' => '点击确定完成添加/编辑红包，即可生成/编辑一条红包列表。',
        ],
        'list' => [
            '0' => '展示所有红包类型相关信息。',
            '1' => '可进行使用类型、活动名称搜索相关信息。',
            '2' => '只有自行领取的红包类型，才可以复制链接。',
        ],
        'view' => [
            '0' => '如果红包类型是线下发放的红包，则查看的是发放红包的个数列表，展示生成所有红包的序列号和密码。',
            '1' => '除此红包类型查看的是红包发放的情况，如发放的会员的使用情况，可进行发送邮件或删除等操作。',
        ]
    ]

];