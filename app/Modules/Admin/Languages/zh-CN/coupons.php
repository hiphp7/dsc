<?php

/* 优惠券类型字段信息 */
$_LANG['coupons_type'] = '优惠券类型';
$_LANG['coupons_list'] = '优惠券列表';
$_LANG['type_name'] = '类型名称';
$_LANG['type_money'] = '优惠券金额';
$_LANG['min_goods_amount'] = '最小订单金额';
$_LANG['notice_min_goods_amount'] = '只有商品总金额达到这个数的订单才能使用这种优惠券';
$_LANG['min_amount'] = '订单下限';
$_LANG['max_amount'] = '订单上限';
$_LANG['send_startdate'] = '开始时间';
$_LANG['send_enddate'] = '结束时间';

$_LANG['use_startdate'] = '使用起始日期';
$_LANG['use_enddate'] = '使用结束日期';
$_LANG['send_count'] = '发放数量';
$_LANG['use_count'] = '使用数量';
$_LANG['send_method'] = '如何发放此类型优惠券';
$_LANG['send_type'] = '发放类型';
$_LANG['param'] = '参数';
$_LANG['no_use'] = '未使用';
$_LANG['no_overdue'] = '未过期';
$_LANG['yuan'] = '元';
$_LANG['user_list'] = '会员列表';
$_LANG['type_name_empty'] = '优惠券类型名称不能为空！';
$_LANG['type_money_empty'] = '优惠券金额不能为空！';
$_LANG['min_amount_empty'] = '优惠券类型的订单下限不能为空！';
$_LANG['max_amount_empty'] = '优惠券类型的订单上限不能为空！';
$_LANG['send_count_empty'] = '优惠券类型的发放数量不能为空！';

$_LANG['send_by'][SEND_BY_USER] = '按用户发放';
$_LANG['send_by'][SEND_BY_GOODS] = '按商品发放';
$_LANG['send_by'][SEND_BY_ORDER] = '按订单金额发放';
$_LANG['send_by'][SEND_BY_PRINT] = '线下发放的优惠券';
$_LANG['report_form'] = '报表下载';
$_LANG['send'] = '发放';
$_LANG['coupons_excel_file'] = '线下优惠券信息列表';

$_LANG['goods_cat'] = '选择商品分类';
$_LANG['goods_brand'] = '商品品牌';
$_LANG['goods_key'] = '商品关键字';
$_LANG['all_goods'] = '可选商品';
$_LANG['send_bouns_goods'] = '发放此类型优惠券的商品';
$_LANG['remove_bouns'] = '移除优惠券';
$_LANG['all_remove_bouns'] = '全部移除';
$_LANG['goods_already_bouns'] = '该商品已经发放过其它类型的优惠券了!';
$_LANG['send_user_empty'] = '您没有选择需要发放优惠券的会员，请返回!';
$_LANG['batch_drop_success'] = '成功删除了 %d 个用户优惠券';
$_LANG['sendcoupons_count'] = '共发送了 %d 个优惠券。';
$_LANG['send_bouns_error'] = '发送会员优惠券出错, 请返回重试！';
$_LANG['no_select_coupons'] = '您没有选择需要删除的用户优惠券';
$_LANG['couponstype_edit'] = '编辑优惠券类型';
$_LANG['couponstype_view'] = '查看详情';
$_LANG['drop_coupons'] = '删除优惠券';
$_LANG['send_coupons'] = '发放优惠券';
$_LANG['continus_add'] = '添加优惠券';
$_LANG['back_list'] = '返回优惠券类型列表';
$_LANG['continue_add'] = '继续添加优惠券';
$_LANG['back_coupons_list'] = '返回优惠券列表';
$_LANG['validated_email'] = '只给通过邮件验证的用户发放优惠券';

$_LANG['coupons_adopt_status_set_success'] = '优惠券审核状态设置成功';

/* 优惠券列表 */
$_LANG['coupons_name'] = '优惠券名称';
//$_LANG['coupons_type'] = '类型';
$_LANG['goods_steps_name'] = '商家名称';
$_LANG['use_limit'] = '使用门槛';
$_LANG['coupons_value'] = '面值';
$_LANG['give_out_amount'] = '总发行量';
$_LANG['valid_date'] = '有效范围';
$_LANG['is_overdue'] = '是否过期';

/* 提示信息 */
$_LANG['attradd_succed'] = '操作成功!';
$_LANG['js_languages']['type_name_empty'] = '请输入优惠券类型名称!';
$_LANG['js_languages']['type_money_empty'] = '请输入优惠券类型价格!';
$_LANG['js_languages']['order_money_empty'] = '请输入订单金额!';
$_LANG['js_languages']['type_money_isnumber'] = '类型金额必须为数字格式!';
$_LANG['js_languages']['order_money_isnumber'] = '订单金额必须为数字格式!';
$_LANG['js_languages']['coupons_sn_empty'] = '请输入优惠券的序列号!';
$_LANG['js_languages']['coupons_sn_number'] = '优惠券的序列号必须是数字!';
$_LANG['send_count_error'] = '优惠券的发放数量必须是一个整数!';
$_LANG['js_languages']['coupons_sum_empty'] = '请输入您要发放的优惠券数量!';
$_LANG['js_languages']['coupons_sum_number'] = '优惠券的发放数量必须是一个整数!';
$_LANG['js_languages']['coupons_type_empty'] = '请选择优惠券的类型金额!';
$_LANG['js_languages']['user_rank_empty'] = '您没有指定会员等级!';
$_LANG['js_languages']['user_name_empty'] = '您至少需要选择一个会员!';
$_LANG['js_languages']['invalid_min_amount'] = '请输入订单下限（大于0的数字）';
$_LANG['js_languages']['send_start_lt_end'] = '优惠券发放开始日期不能大于结束日期';
$_LANG['js_languages']['use_start_lt_end'] = '优惠券使用开始日期不能大于结束日期';
$_LANG['js_languages']['range_exists'] = '该选项已存在';
$_LANG['js_languages']['allow_user_rank'] = '允许参与的会员等级,一个不选表示没有任何会员能参与';
$_LANG['js_languages']['coupons_title_empty'] = '请输入优惠券标题';
$_LANG['js_languages']['coupons_zhang_empty'] = '请输入优惠券张数';
$_LANG['js_languages']['coupons_face_empty'] = '请输入优惠券面值';
$_LANG['js_languages']['coupons_total_isnumber'] = '金额必须是数字格式';
$_LANG['js_languages']['coupons_total_min'] = '金额必须大于0';
$_LANG['js_languages']['coupons_threshold_empty'] = '请输入优惠券使用门槛';
$_LANG['js_languages']['coupons_data_empty'] = '有效开始时间不能为空';
$_LANG['js_languages']['coupons_data_invalid_gt'] = '有效结束时间必须大于有效开始时间';
$_LANG['js_languages']['coupons_cat_exist'] = '分类已经存在了';
$_LANG['js_languages']['coupons_set_goods'] = '请设置商品';
$_LANG['js_languages']['continus_add'] = '添加优惠券';

$_LANG['order_money_notic'] = '只要订单金额达到该数值，就会发放优惠券给用户';
$_LANG['type_money_notic'] = '此类型的优惠券可以抵销的金额';
$_LANG['send_startdate_notic'] = '只有当前时间介于起始日期和截止日期之间时，此类型的优惠券才可以发放';
$_LANG['use_startdate_notic'] = '只有当前时间介于起始日期和截止日期之间时，此类型的优惠券才可以使用';
$_LANG['type_name_exist'] = '此类型的名称已经存在!';
$_LANG['type_money_beyond'] = '优惠券金额不得大于最小订单金额!';
$_LANG['type_money_error'] = '金额必须是数字并且不能小于 0 !';
$_LANG['coupons_sn_notic'] = '提示：优惠券序列号由六位序列号种子加上四位随机数字组成';
$_LANG['creat_coupons'] = '生成了 ';
$_LANG['creat_coupons_num'] = ' 个优惠券序列号';
$_LANG['coupons_sn_error'] = '优惠券序列号必须是数字!';
$_LANG['send_user_notice'] = '给指定的用户发放优惠券时,请在此输入用户名, 多个用户之间请用逗号(,)分隔开<br />如:liry, wjz, zwj';
$_LANG['allow_level_no_select_no_join'] = '允许参与的会员等级,一个不选表示没有任何会员能参与';

/* 优惠券信息字段 */
$_LANG['coupons_id'] = '编号';
$_LANG['coupons_type_id'] = '类型金额';
$_LANG['send_coupons_count'] = '优惠券数量';
$_LANG['start_coupons_sn'] = '起始序列号';
$_LANG['coupons_sn'] = '优惠券编号';
$_LANG['user_name'] = '所属会员';
$_LANG['user_id'] = '使用会员';
$_LANG['used_time'] = '使用时间';
$_LANG['order_id'] = '订单号';
$_LANG['send_mail'] = '发邮件';
$_LANG['emailed'] = '邮件通知';
$_LANG['mail_status'][BONUS_NOT_MAIL] = '未发';
$_LANG['mail_status'][BONUS_MAIL_FAIL] = '已发失败';
$_LANG['mail_status'][BONUS_MAIL_SUCCEED] = '已发成功';

$_LANG['sendtouser'] = '给指定用户发放优惠券';
$_LANG['senduserrank'] = '按用户等级发放优惠券';
$_LANG['userrank'] = '用户等级';
$_LANG['select_rank'] = '选择会员等级...';
$_LANG['keywords'] = '关键字：';
$_LANG['userlist'] = '会员列表：';
$_LANG['send_to_user'] = '给下列用户发放优惠券';
$_LANG['search_users'] = '搜索会员';
$_LANG['confirm_send_coupons'] = '确定发送优惠券';
$_LANG['coupons_not_exist'] = '该优惠券不存在';
$_LANG['success_send_mail'] = '%d 封邮件已被加入邮件列表';
$_LANG['send_continue'] = '继续发放优惠券';
$_LANG['please_input_coupons_name'] = '请输入优惠券名称';
$_LANG['allow_user_rank'] = '允许参与的会员等级,一个不选表示没有任何会员能参与';
$_LANG['use_threshold_money'] = '使用门槛(元)';
$_LANG['face_value_money'] = '面值(元)';
$_LANG['total_send_num'] = '总发行数(张)';
$_LANG['expiry_date'] = '有效期';
$_LANG['expired'] = '已过期';

/*大商创1.5新增 sunle*/
$_LANG['start_enddate'] = '发放起止日期';
$_LANG['use_start_enddate'] = '有效时间';
$_LANG['send_continue'] = '继续发放优惠券';
$_LANG['bind_password'] = '绑定密码';

$_LANG['coupons_default_title'] = '选择创建一张优惠券';
$_LANG['coupons_default_tit'] = '通过发放优惠券提高用户参与度';
$_LANG['coupons_add_title'] = "请选择需要添加的优惠券";
$_LANG['coupons_type_01'] = '注册赠券';
$_LANG['coupons_type_02'] = '购物赠券';
$_LANG['coupons_type_03'] = '全场赠券';
$_LANG['coupons_type_04'] = '会员赠券';
$_LANG['coupons_type_05'] = '免邮券';
$_LANG['coupons_type_06'] = '领取关注店铺券';
$_LANG['not_free_shipping'] = '不包邮地区';
$_LANG['set_free_shipping'] = '设置不包邮地区';
$_LANG['cou_man_desc'] = '，无门槛请设为0';

$_LANG['coupons_desc_title_01'] = '新注册用户可获得指定优惠券';
$_LANG['coupons_desc_span_01'] = '通过注册赠券提高用户注册积极性';
$_LANG['coupons_desc_title_02'] = '购物满金额可获得指定优惠券';
$_LANG['coupons_desc_span_02'] = '通过购物赠券鼓励用户提高订单金额';
$_LANG['coupons_desc_title_03'] = '可在商品详情页领取优惠券';
$_LANG['coupons_desc_span_03'] = '通过全场赠券，吸引更多会员在店铺下单';
$_LANG['coupons_desc_title_04'] = '指定会员可获得优惠券';
$_LANG['coupons_desc_span_04'] = '通过指定会员赠券为不同等级会员提供个性促销';

$_LANG['coupons_name'] = "优惠劵名称";
$_LANG['activity_rule'] = "活动规则";
$_LANG['worth'] = "价值(元)";
$_LANG['receive_limit'] = "领取限制";
$_LANG['activity_state'] = "活动状态";
$_LANG['already_receive'] = "已领取";
$_LANG['already_used'] = "已使用";
$_LANG['handle'] = "操作";
$_LANG['coupons_number'] = "优惠劵总张数";
$_LANG['coupons_man'] = "使用门槛";//bylu
$_LANG['coupons_money'] = "面值";//bylu
$_LANG['coupons_intro'] = "备注";//bylu
$_LANG['cou_get_man'] = "获取门槛";//bylu
$_LANG['cou_list'] = "优惠券列表";//bylu
$_LANG['cou_edit'] = "优惠劵编辑";//bylu
$_LANG['full_shopping'] = "购物满";
$_LANG['yuan'] = "元";
$_LANG['deductible'] = "可抵扣";
$_LANG['each_purchase'] = "每人限领";
$_LANG['use_goods'] = "可使用商品";
$_LANG['buy_goods_deduction'] = "购买以下商品可使用优惠券抵扣金额";
$_LANG['goods_all'] = "全部商品";
$_LANG['goods_appoint'] = "指定商品";
$_LANG['spec_cat'] = '指定分类';
$_LANG['label_search_and_add'] = "搜索并加入优惠范围";
$_LANG['full_delivery'] = "满送金额";
$_LANG['desc_yuan'] = "元可获得优惠券";
$_LANG['give_quan_goods'] = "可赠券商品";
$_LANG['buy_has_coupon'] = "购买以下商品并满一定金额可获得优惠券";
$_LANG['come_user'] = "参加会员";
$_LANG['all_checkbox'] = "全选";

$_LANG['label_coupons_title'] = "优惠券标题：";
$_LANG['coupons_title_example'] = "如:仅可购买手机类指定商品或全品类通用";
$_LANG['coupons_title_tip'] = '需与"可使用商品"设置的商品相对应,做到名副其实';
$_LANG['coupons_total_send_num'] = '优惠券总发行数量';

/* 补充优惠券语言包 */
$_LANG['name_notice'] = "优惠券的名称";
$_LANG['coupons_title'] = "优惠券标题";
$_LANG['title_notice'] = "需与'可使用商品'设置的商品相对应,做到名副其实";
$_LANG['total_notice'] = "优惠券总发行数量 单位：张";
$_LANG['money_notice'] = "单位：元";
$_LANG['one'] = "1 张";
$_LANG['add_goods'] = "添加商品";
$_LANG['add_cat'] = '添加分类';

$_LANG['title_exist'] = '优惠券 %s 已经存在';
$_LANG['coupon_store_exist'] = '关注店铺优惠券 %s 已经存在';
$_LANG['copy_url'] = '复制链接';

/* 教程名称 */
$_LANG['tutorials_bonus_list_one'] = '商城优惠券功能说明';

/* 页面顶部操作提示 */
$_LANG['operation_prompt_content']['view'][0] = '查看优惠券发放或领取记录。';
$_LANG['operation_prompt_content']['view'][1] = '可进行删除操作。';

$_LANG['operation_prompt_content']['list'][0] = '展示了优惠券的相关信息列表。';
$_LANG['operation_prompt_content']['list'][1] = '通过优惠券名称关键字、筛选使用类型搜索出具体优惠券信息。';

$_LANG['operation_prompt_content']['info'][0] = '注册赠券:新用户注册成功即发放到用户账户中；';
$_LANG['operation_prompt_content']['info'][1] = '购物赠券:购物满一定金额即发放到用户账户中(后台发货后)；';
$_LANG['operation_prompt_content']['info'][2] = '全场赠券:用户在优惠券首页或列表页或商品详情页或侧边栏点击领取；';
$_LANG['operation_prompt_content']['info'][3] = '会员赠券:同全场赠券(可限定领取的会员等级)。';

return $_LANG;