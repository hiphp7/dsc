<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Order Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during Order for various
    | messages. You are free to modify
    | these language lines according to your application's requirements.
    |
    */

    'no_comment' => '尚未评论',

    'update_shipping' => '将【%s】配送方式修改为【%s】',
    'self_motion_goods' => '自动确认收货',

    'order_ship_delivery' => '发货单流水号：【%s】',
    'order_ship_invoice' => '发货单号：【%s】',
    'edit_order_invoice' => '将发货单号【%s】修改为：【%s】',

    'add_order_info' => '【%s】手工添加订单',
    'shipping_refund' => '编辑订单导致退款：%s',

    '01_stores_pick_goods' => '订单未付款，无法提货',
    '02_stores_pick_goods' => '操作成功，提货完成',
    '03_stores_pick_goods' => '验证码不正确，请重新输入',
    '04_stores_pick_goods' => '请输入6位提货码',

    'order_label' => '信息标签',
    'amount_label' => '金额标签',

    'bar_code' => '条形码',
    'label_bar_code' => '条形码：',

    'region' => '配送区域',
    'order_shipped_sms' => '您的订单%s已于%s发货', //wang

    'setorder_nopay' => '设置为未付款',
    'setorder_cancel' => '设置为未付款',

//退换货 start
    'rf' => [
        RF_APPLICATION => '由用户寄回',
        RF_RECEIVE => '收到退换货',
        RF_SWAPPED_OUT_SINGLE => '换出商品寄出【分单】',
        RF_SWAPPED_OUT => '换出商品寄出',
        RF_COMPLETE => '完成',
        RF_AGREE_APPLY => '同意申请',
        REFUSE_APPLY => '申请被拒',
        FF_NOMAINTENANCE => '未维修',
        FF_MAINTENANCE => '已维修',
        FF_REFOUND => '已退款',
        FF_NOREFOUND => '未退款',
        FF_NOEXCHANGE => '未换货',
        FF_EXCHANGE => '已换货',
    ],

    'refund_money' => '退款金额',
    'money' => '金额',
    'shipping_money' => '元&nbsp;&nbsp;运费',
    'is_shipping_money' => '退运费',
    'no_shipping_money' => '不退运费',
    'return_user_line' => '线下退款',
    'return_reason' => '退换货原因',
    'whether_display' => '是否显示',
    'return_baitiao' => '退回白条余额(如果是余额与白条混合支付,余额部分退回余额)',
    'return_online' => '在线原路退款',
    //退换货 end

    //ecmoban模板堂 --zhuo start
    'auto_delivery_time' => '自动确认收货时间：',
    'dateType' => [
        '0' => '天',
    ],
    //ecmoban模板堂 --zhuo end

    /* 订单搜索 */
    'order_sn' => '订单号',
    'consignee' => '收货人',
    'all_status' => '订单状态',

    'cs' => [
        OS_UNCONFIRMED => '待确认',
        CS_AWAIT_PAY => '待付款',
        CS_AWAIT_SHIP => '待发货',
        CS_FINISHED => '已完成',
        PS_PAYING => '付款中',
        OS_CANCELED => '取消',
        OS_INVALID => '无效',
        OS_RETURNED => '退货',
        OS_SHIPPED_PART => '待收货',
    ],


    /* 订单状态 */
    'os' => [
        OS_UNCONFIRMED => '未确认',
        OS_CONFIRMED => '已确认',
        OS_CANCELED => '取消',
        OS_INVALID => '无效',
        OS_RETURNED => '退货',
        OS_SPLITED => '已分单',
        OS_SPLITING_PART => '部分分单',
        OS_RETURNED_PART => '部分已退货',
        OS_ONLY_REFOUND => '仅退款',
    ],

    'ss' => [
        SS_UNSHIPPED => '未发货',
        SS_PREPARING => '配货中',
        SS_SHIPPED => '已发货',
        SS_RECEIVED => '收货确认',
        SS_SHIPPED_PART => '已发货(部分商品)',
        SS_SHIPPED_ING => '发货中',
    ],

    'ps' => [
        PS_UNPAYED => '未付款',
        PS_PAYING => '付款中',
        PS_PAYED => '已付款',
        PS_PAYED_PART => '部分付款(定金)',
        PS_REFOUND => '已退款',
        PS_REFOUND_PART => '部分退款',
    ],

    'ss_admin' => [
        SS_SHIPPED_ING => '发货中（前台状态：未发货）',
    ],

    /* 订单操作 */
    'label_operable_act' => '当前可执行操作：',
    'label_action_note' => '操作备注：',
    'label_invoice_note' => '发货备注：',
    'label_invoice_no' => '发货单号：',
    'label_cancel_note' => '取消原因：',
    'notice_cancel_note' => '（会记录在商家给客户的留言中）',
    'op_confirm' => '确认',
    'refuse' => '拒绝',
    'refuse_apply' => '拒绝申请',
    'is_refuse_apply' => '已拒绝申请',
    'op_pay' => '付款',
    'op_prepare' => '配货',
    'op_ship' => '发货',
    'op_cancel' => '取消',
    'op_invalid' => '无效',
    'op_return' => '退货',
    'op_unpay' => '设为未付款',
    'op_unship' => '未发货',
    'op_cancel_ship' => '取消发货',
    'op_receive' => '已收货',
    'op_assign' => '指派给',
    'op_after_service' => '售后',
    'act_ok' => '操作成功',
    'act_false' => '操作失败',
    'act_ship_num' => '此单发货数量不能超出订单商品数量',
    'act_good_vacancy' => '商品已缺货',
    'act_good_delivery' => '货已发完',
    'notice_gb_ship' => '备注：团购活动未处理为成功前，不能发货',
    'back_list' => '返回列表页',
    'op_remove' => '删除',
    'op_you_can' => '您可进行的操作',
    'op_split' => '生成发货单',
    'op_to_delivery' => '去发货',
    'op_swapped_out_single' => '换出商品寄出【分单】',
    'op_swapped_out' => '换出商品寄出',
    'op_complete' => '完成退换货',
    'op_refuse_apply' => '拒绝申请',
    'baitiao_by_stages' => '白条分期',
    'expired' => '已到期',
    'unable_to_click' => '无法点击',

    /* 订单列表 */
    'order_amount' => '应付金额',
    'total_fee' => '总金额',
    'shipping_name' => '配送方式',
    'pay_name' => '支付方式',
    'address' => '地址',
    'order_time' => '下单时间',
    'trade_snapshot' => '交易快照',
    'detail' => '查看',
    'phone' => '电话',
    'group_buy' => '（团购）',
    'error_get_goods_info' => '获取订单商品信息错误',
    'exchange_goods' => '（积分兑换）',
    'auction' => '（拍卖活动）',
    'snatch' => '（夺宝奇兵）',
    'presale' => '（预售）',


    /* 订单搜索 */
    'label_buyer' => '购货人：',
    'label_order_sn' => '订单号：',
    'label_all_status' => '订单状态：',
    'label_user_name' => '购货人：',
    'label_consignee' => '收货人：',
    'label_email' => '电子邮件：',
    'label_address' => '收货地址：',
    'label_zipcode' => '邮政编码：',
    'label_tel' => '电话号码：',
    'label_mobile' => '手机号码：',
    'label_shipping' => '配送方式：',
    'label_payment' => '支付方式：',
    'label_order_status' => '订单状态：',
    'label_pay_status' => '付款状态：',
    'label_shipping_status' => '发货状态：',
    'label_area' => '所在地区：',
    'label_time' => '下单时间：',

    /* 订单详情 */
    'prev' => '前一个订单',
    'next' => '后一个订单',
    'print_order' => '打印订单',
    'print_shipping' => '打印快递单',
    'print_order_sn' => '订单编号：',
    'print_buy_name' => '购 货 人：',
    'label_consignee_address' => '收货地址：',
    'no_print_shipping' => '很抱歉,目前您还没有设置打印快递单模板.不能进行打印',
    'suppliers_no' => '不指定供货商本店自行处理',
    'restaurant' => '本店',

    'order_info' => '订单信息',
    'base_info' => '基本信息',
    'other_info' => '其他信息',
    'consignee_info' => '收货人信息',
    'fee_info' => '费用信息',
    'action_info' => '操作信息',
    'shipping_info' => '配送信息',

    'label_how_oos' => '缺货处理：',
    'label_how_surplus' => '余额处理：',
    'label_pack' => '包装：',
    'label_card' => '贺卡：',
    'label_card_message' => '贺卡祝福语：',
    'label_order_time' => '下单时间：',
    'label_pay_time' => '付款时间：',
    'label_shipping_time' => '发货时间：',
    'label_sign_building' => '地址别名：',
    'label_best_time' => '送货时间：',
    'label_inv_type' => '发票类型：',
    'label_inv_payee' => '发票抬头：',
    'label_inv_content' => '发票内容：',
    'label_postscript' => '买家留言：',
    'label_region' => '所在地区：',

    'label_shop_url' => '网址：',
    'label_shop_address' => '地址：',
    'label_service_phone' => '电话：',
    'label_print_time' => '打印时间：',

    'label_suppliers' => '选择供货商：',
    'label_agency' => '办事处：',
    'suppliers_name' => '供货商',

    'product_sn' => '货品号',
    'goods_info' => '商品信息',
    'goods_name' => '商品名称',
    'goods_name_brand' => '商品名称 [ 品牌 ]',
    'goods_sn' => '货号',
    'goods_price' => '价格',
    'give_integral' => '商品赠送积分',
    'goods_number' => '数量',
    'goods_attr' => '属性',
    'goods_delivery' => '已发货数量',
    'goods_delivery_curr' => '此单发货数量',
    'storage' => '库存',
    'subtotal' => '小计',
    'amount_return' => '商品退款',
    'label_total' => '合计：',
    'label_total_weight' => '商品总重量：',
    'label_total_cost' => '合计商品成本：',
    'measure_unit' => '单位',
    'contain_content' => '包含内容',
    'application_refund' => '申请退款',

    'return_discount' => '享受折扣',

    'label_goods_amount' => '商品总金额：',
    'label_discount' => '折扣：',
    'label_tax' => '发票税额：',
    'label_shipping_fee' => '配送费用：',
    'label_insure_fee' => '保价费用：',
    'label_insure_yn' => '是否保价：',
    'label_pay_fee' => '支付费用：',
    'label_pack_fee' => '包装费用：',
    'label_card_fee' => '贺卡费用：',
    'label_money_paid' => '已付款金额：',
    'label_surplus' => '使用余额：',
    'label_integral' => '使用积分：',
    'label_bonus' => '使用红包：',
    'label_value_card' => '使用储值卡：',
    'label_coupons' => '使用优惠券：',
    'label_order_amount' => '订单总金额：',
    'label_money_dues' => '应付款金额：',
    'label_money_refund' => '应退款金额：',
    'label_to_buyer' => '商家给客户的留言：',
    'save_order' => '保存订单',
    'notice_gb_order_amount' => '（备注：团购如果有保证金，第一次只需支付保证金和相应的支付费用）',
    'formated_order_amount' => '订单总金额',
    'stores_info' => '门店信息',

    'action_user' => '操作者',
    'action_time' => '操作时间',
    'return_status' => '操作',
    'refound_status' => '状态',
    'order_status' => '订单状态',
    'pay_status' => '付款状态',
    'shipping_status' => '发货状态',
    'action_note' => '备注',
    'pay_note' => '支付备注：',
    'action_jilu' => '操作记录',
    'not_action_jilu' => '当前还没有操作记录',

    'sms_time_format' => 'm月j日G时',
    'order_splited_sms' => '您的订单%s,%s正在%s [%s]',
    'order_removed' => '订单删除成功。',
    'return_list' => '返回订单列表',
    'order_remove_failure' => '%s 存在单商品退换货订单，订单删除失败。',

    /* 订单处理提示 */
    'surplus_not_enough' => '该订单使用 %s 余额支付，现在用户余额不足',
    'integral_not_enough' => '该订单使用 %s 积分支付，现在用户积分不足',
    'bonus_not_available' => '该订单使用红包支付，现在红包不可用',

    /* 购货人信息 */
    'display_buyer' => '显示购货人信息',
    'buyer_info' => '购货人信息',
    'pay_points' => '消费积分',
    'rank_points' => '成长值',
    'user_money' => '账户余额',
    'email' => '电子邮件',
    'rank_name' => '会员等级',
    'bonus_count' => '红包数量',
    'zipcode' => '邮编',
    'tel' => '电话',
    'mobile' => '手机号码',
    'leaving_message' => '留言',

    /*增值税发票信息*/
    'vat_info' => '增值税发票信息',
    'vat_name' => '单位名称',
    'vat_taxid' => '纳税人识别码',
    'vat_company_address' => '注册地址',
    'vat_company_telephone' => '注册电话',
    'vat_bank_of_deposit' => '开户银行',
    'vat_bank_account' => '银行账户',

    /* 合并订单 */
    'seller_order_sn_same' => '要合并的两个订单所属商家必须相同',
    'merge_order_main_count' => '要合并的订单不能为主订单',
    'order_sn_not_null' => '请填写要合并的订单号',
    'two_order_sn_same' => '要合并的两个订单号不能相同',
    'order_not_exist' => '定单 %s 不存在',
    'os_not_unconfirmed_or_confirmed' => '%s 的订单状态不是“未确认”或“已确认”',
    'ps_not_unpayed' => '订单 %s 的付款状态不是“未付款”',
    'ss_not_unshipped' => '订单 %s 的发货状态不是“未发货”',
    'order_user_not_same' => '要合并的两个订单不是同一个用户下的',
    'merge_invalid_order' => '对不起，您选择合并的订单不允许进行合并的操作。',

    'merge_order' => '合并订单',
    'from_order_sn' => '从订单：',
    'to_order_sn' => '主订单：',
    'merge' => '合并',
    'notice_order_sn' => '当两个订单不一致时，合并后的订单信息（如：支付方式、配送方式、包装、贺卡、红包等）以主订单为准。',


    /* 批处理 */
    'pls_select_order' => '请选择您要操作的订单',
    'no_fulfilled_order' => '没有满足操作条件的订单。',
    'updated_order' => '更新的订单：',
    'order' => '订单：',
    'confirm_order' => '以下订单无法设置为确认状态',
    'invalid_order' => '以下订单无法设置为无效',
    'cancel_order' => '以下订单无法取消',
    'remove_order' => '以下订单无法被移除',

    /* 编辑订单打印模板 */
    'edit_order_templates' => '编辑订单打印模板',
    'template_resetore' => '还原模板',
    'edit_template_success' => '编辑订单打印模板操作成功!',
    'remark_fittings' => '（配件）',
    'remark_gift' => '（赠品）',
    'remark_favourable' => '（特惠品）',
    'remark_package' => '（礼包）',
    'remark_package_goods' => '（礼包内产品）',

    /* 订单来源统计 */
    'from_order' => '订单来源',
    'referer' => '来源',
    'from_ad_js' => '广告：',
    'from_goods_js' => '商品站外JS投放',
    'from_self_site' => '来自本站',
    'from' => '来自站点：',

    /* 添加、编辑订单 */
    'add_order' => '添加订单',
    'edit_order' => '编辑订单',
    'step' => [
        'user' => '请选择您要为哪个会员下订单',
        'goods' => '选择商品',
        'consignee' => '设置收货人信息',
        'shipping' => '选择配送方式',
        'payment' => '选择支付方式',
        'other' => '设置其他信息',
        'money' => '设置费用',
    ],

    'anonymous' => '匿名用户',
    'by_useridname' => '按会员编号或会员名搜索',
    'button_prev' => '上一步',
    'button_next' => '下一步',
    'button_finish' => '完成',
    'button_cancel' => '取消',
    'name' => '名称',
    'desc' => '描述',
    'shipping_fee' => '配送费',
    'free_money' => '免费额度',
    'insure' => '保价费',
    'pay_fee' => '手续费',
    'pack_fee' => '包装费',
    'card_fee' => '贺卡费',
    'no_pack' => '不要包装',
    'no_card' => '不要贺卡',
    'add_to_order' => '加入订单',
    'calc_order_amount' => '计算订单金额',
    'available_surplus' => '可用余额：',
    'available_integral' => '可用积分：',
    'available_bonus' => '可用红包：',
    'admin' => '管理员添加',
    'search_goods' => '按商品编号或商品名称或商品货号搜索',
    'category' => '分类',
    'order_category' => '订单分类',
    'brand' => '品牌',
    'user_money_not_enough' => '用户余额不足',
    'pay_points_not_enough' => '用户积分不足',
    'money_paid_enough' => '已付款金额比商品总金额和各种费用之和还多，请先退款',
    'price_note' => '备注：商品价格中已包含属性加价',
    'select_pack' => '选择包装',
    'select_card' => '选择贺卡',
    'select_shipping' => '请先选择配送方式',
    'want_insure' => '我要保价',
    'update_goods' => '更新商品',
    'notice_user' => '<strong>注意：</strong>搜索结果只显示前20条记录，如果没有找到相' .
        '应会员，请更精确地查找。另外，如果该会员是从论坛注册的且没有在商城登录过，' .
        '也无法找到，需要先在商城登录。',
    'amount_increase' => '由于您修改了订单，导致订单总金额增加，需要再次付款',
    'amount_decrease' => '由于您修改了订单，导致订单总金额减少，需要退款',
    'continue_shipping' => '由于您修改了收货人所在地区，导致原来的配送方式不再可用，请重新选择配送方式',
    'continue_payment' => '由于您修改了配送方式，导致原来的支付方式不再可用，请重新选择配送方式',
    'refund' => '退款',
    'cannot_edit_order_shipped' => '您不能修改已发货的订单',
    'cannot_edit_order_payed' => '您不能修改已付款的订单',
    'address_list' => '从已有收货地址中选择：',
    'order_amount_change' => '订单总金额由 %s 变为 %s',
    'shipping_note' => '说明：因为订单已发货，修改配送方式将不会改变配送费和保价费。',
    'change_use_surplus' => '编辑订单 %s ，改变使用预付款支付的金额',
    'change_use_integral' => '编辑订单 %s ，改变使用积分支付的数量',
    'return_order_surplus' => '由于取消、无效或退货操作，退回支付订单 %s 时使用的预付款',
    'return_order_integral' => '由于取消、无效或退货操作，退回支付订单 %s 时使用的积分',
    'order_gift_integral' => '订单 %s 赠送的积分',
    'return_order_gift_integral' => '由于退货或未发货操作，退回订单 %s 赠送的积分',
    'invoice_no_mall' => '多个发货单号，请用英文逗号（“,”）隔开。',

    /* js 验证提示 */
    'js_languages' => [
        'confirm_merge' => '您确实要合并这两个订单吗？',
        'remove_confirm' => '删除订单将清除该订单的所有信息。您确定要这么做吗？',

        'input_price' => '自定义价格',
        'pls_search_user' => '请搜索并选择会员',
        'confirm_drop' => '确认要删除该商品吗？',
        'invalid_goods_number' => '商品数量不正确',
        'pls_search_goods' => '请搜索并选择商品',
        'pls_select_area' => '请完整选择所在地区',
        'pls_select_shipping' => '请选择配送方式',
        'pls_select_payment' => '请选择支付方式',
        'pls_select_pack' => '请选择包装',
        'pls_select_card' => '请选择贺卡',
        'pls_input_note' => '请您填写备注！',
        'pls_input_cancel' => '请您填写取消原因！',
        'pls_select_refund' => '请选择退款方式！',
        'pls_select_agency' => '请选择办事处！',
        'pls_select_other_agency' => '该订单现在就属于这个办事处，请选择其他办事处！',
        'loading' => '加载中...',

        'not_back_cause' => '请输出退换货原因',
        'no_confirmation_delivery_info' => '暂无确认发货信息',

        'jl_merge_order' => '合并订单',
        'jl_merge' => '合并',
        'jl_sure_merge_order' => '您确定合并这两个订单么？',
        'jl_order_step_js_notic_11' => '您修改的费用信息折扣不能大于实际订单总金额',
        'jl_order_step_js_notic_12' => '，会产生退款，一旦退款则将扣除您账户里面的资金',
        'jl_order_step_js_notic_13' => '您修改的费用信息产生了负数（',
        'jl_order_step_js_notic_14' => '，是否继续？',
        'jl_order_export_dialog' => '订单导出弹窗',
        'jl_set_rob_order' => '设置抢单',
        'jl_vat_info' => '增值税发票信息',
        'jl_search_logistics_info' => '正在查询物流信息，请稍后...',
        'jl_goods_delivery' => '商品发货',
    ],


    /* 订单操作 */
    'order_operate' => '订单操作：',
    'label_refund_amount' => '退款金额：',
    'label_handle_refund' => '退款方式：',
    'label_refund_note' => '退款说明：',
    'return_user_money' => '退回用户余额',
    'create_user_account' => '生成退款申请',
    'not_handle' => '不处理，误操作时选择此项',

    'order_refund' => '订单退款：%s',
    'order_pay' => '订单支付：%s',

    'send_mail_fail' => '发送邮件失败',

    'send_message' => '发送/查看留言',

    /* 发货单操作 */
    'delivery_operate' => '发货单操作：',
    'delivery_sn_number' => '流水号：',
    'invoice_no_sms' => '请填写发货单号！',

    /* 发货单搜索 */
    'delivery_sn' => '发货单',

    /* 发货单状态 */
    'delivery_status_dt' => '发货单状态',
    'delivery_status' => [
        '0' => '已发货',
        '1' => '退货',
        '2' => '正常',
    ],


    /* 发货单标签 */
    'label_delivery_status' => '状态',
    'label_suppliers_name' => '供货商',
    'label_delivery_time' => '生成时间',
    'label_delivery_sn' => '发货单流水号',
    'label_add_time' => '下单时间',
    'label_update_time' => '发货时间',
    'label_send_number' => '发货数量',
    'batch_delivery' => '批量发货',

    /* 发货单提示 */
    'tips_delivery_del' => '发货单删除成功！',

    /* 退货单操作 */
    'back_operate' => '退货单操作：',

    /* 退货单标签 */
    'return_time' => '退货时间：',
    'label_return_time' => '退货时间',
    'label_apply_time' => '退换货申请时间',
    'label_back_shipping' => '退换货配送方式',
    'label_back_invoice_no' => '退换货发货单号',
    'back_order_info' => '退货单信息',

    /* 退货单提示 */
    'tips_back_del' => '退货单删除成功！',

    'goods_num_err' => '库存不足，请重新选择！',

    /*大商创1.5后台新增*/
    /*退换货列表*/
    'problem_desc' => '问题描述',
    'product_repair' => '商品维修',
    'product_return' => '商品退货',
    'product_change' => '商品换货',
    'product_price' => '商品价格',

    'return_sn' => '流水号',
    'return_change_sn' => '流水号',
    'repair' => '维修',
    'return_goods' => '退货',
    'change' => '换货',
    'only_return_money' => '仅退款',
    'already_repair' => '已维修',
    'refunded' => '已退款',
    'already_change' => '已换货',
    'return_change_type' => '类型',
    'apply_time' => '申请时间',
    'y_amount' => '应退金额',
    's_amount' => '实退金额',
    'actual_return' => '合计已退款',
    'return_change_num' => '退换数量',
    'receipt_time' => '签收时间',
    'applicant' => '申请人',
    'to_order_sn2' => '主订单',
    'to_order_sn3' => '主订单',
    'sub_order_sn' => '子订单',
    'sub_order_sn2' => '子订单',

    'return_reason' => '退换原因',
    'reason_cate' => '原因分类',
    'top_cate' => '顶级分类',

    'since_some_info' => '自提点信息',
    'since_some_name' => '自提点名称',
    'contacts' => '联系人',
    'tpnd_time' => '提货时间',
    'warehouse_name' => '仓库名称',
    'ciscount' => '优惠',
    'notice_delete_order' => '(用户会员中心：已删除该订单)',
    'notice_trash_order' => '用户已处理进入回收站',
    'order_not_operable' => '（订单不可操作）',

    'region' => '地区',
    'seller_mail' => '卖家寄出',
    'courier_sz' => '快递单号',
    'select_courier' => '请选择快递公司',
    'fillin_courier_number' => '请填写快递单号',
    'edit_return_reason' => '编辑退换货原因',
    'buyers_return_reason' => '买家退换货原因',
    'user_file_image' => '用户上传图片凭证',
    'operation_notes' => '操作备注',
    'agree_apply' => '同意申请',
    'receive_goods' => '收到退回商品',
    'current_executable_operation' => '当前可执行操作',
    'refound' => '去退款',
    'swapped_out_single' => '换出商品寄出【分单】',
    'swapped_out' => '换出商品寄出',
    'complete' => '完成退换货',
    'after_service' => '售后',
    'complete' => '完成退换货',
    'wu' => '无',
    'not_filled' => '未填写',
    'seller_message' => '卖家留言',
    'buyer_message' => '买家留言',
    'total_stage' => '总分期数',
    'stage' => '期',
    'by_stage' => '分期金额',
    'yuan_stage' => '元/期',
    'submit_order' => '提交订单',
    'payment_order' => '支付订单',
    'seller_shipping' => '商家发货',
    'confirm_shipping' => '确认收货',
    'evaluate' => '评价',
    'logistics_tracking' => '物流跟踪',
    'cashdesk' => '收银台',
    'wxapp' => '小程序',
    'info' => '信息',
    'general_invoice' => '普通发票',
    'personal_general_invoice' => '个人普通发票',
    'enterprise_general_invoice' => '企业普通发票',
    'VAT_invoice' => '增值税发票',
    'id_code' => '识别码',
    'has_been_issued' => '已发',
    'invoice_generated' => '发货单已生成',
    'has_benn_refund' => '已退款',
    'net_profit' => '净利润约',
    'one_key_delivery' => '一键发货',
    'goods_delivery' => '商品发货',
    'search_logistics_info' => '正在查询物流信息，请稍后...',
    'consignee_address' => '收货地址',
    'view_order' => '查看订单',
    'set_baitiao' => '设置白条',
    'account_details' => '账目明细',
    'goods_sku' => '商品编号',
    'all_order' => '全部订单',
    'order_status_01' => '待确认',
    'order_status_02' => '待付款',
    'order_status_03' => '待发货',
    'order_status_04' => '已完成',
    'order_status_05' => '待收货',
    'order_status_06' => '付款中',
    'search_keywords_placeholder' => '订单编号/商品编号/商品关键字',
    'search_keywords_placeholder2' => '商品编号/商品关键字',
    'is_reality' => '正品保证',
    'is_return' => '包退服务',
    'is_fast' => '闪速配送',

    'order_category' => '订单分类',
    'baitiao_order' => '白条订单',
    'zc_order' => '众筹订单',
    'so_order' => '门店订单',
    'fx_order' => '分销订单',
    'team_order' => '拼团',
    'bargain_order' => '砍价',
    'wholesale_order' => '批发',
    'package_order' => '超值礼包',
    'xn_order' => '虚拟商品订单',
    'pt_order' => '普通订单',
    'return_order' => '退换货订单',
    'other_order' => '促销订单',
    'db_order' => '夺宝订单',
    'ms_order' => '秒杀订单',
    'tg_order' => '团购订单',
    'pm_order' => '拍卖订单',
    'jf_order' => '积分订单',
    'ys_order' => '预售订单',

    'have_commission_bill' => '已出佣金账单',
    'knot_commission_bill' => '已结佣金账单',
    'view_commission_bill' => '查看账单',

    'order_export_dialog' => '订单导出弹窗',
    'operation_error' => '操作错误',

    'confirmation_receipt_time' => '确认收货时间',

    'fill_user' => '填写用户',
    'batch_add_order' => '批量添加订单',
    'search_user_placeholder' => '会员名称/编号',
    'username' => '会员名称',
    'search_user_name_not' => '请先搜索会员名称',
    'search_user_name_notic' => '<strong>注意：</strong>搜索结果只显示前20条记录，如果没有找到相应会员，请更精确地查找。<br>另外，如果该会员是从论坛注册的且没有在商城登录过，也无法找到，需要先在商城登录。',

    'search_number_placeholder' => '编号/名称/货号',
    'select_warehouse' => '请先选择仓库',
    'receipt_info' => '请填写收货信息',
    'add_distribution_mode' => '添加配送方式',
    'select_payment_method' => '选择支付方式',
    'join_order' => '加入订单',
    'add_invoice' => '添加发票',
    'search_goods_first' => '请先搜索商品',

    'order_step_notic_01' => '请查看地区信息是否输入正确',
    /*大商创1.5后台新增end*/

    /*众筹相关 by wu*/
    'zc_goods_info' => '众筹方案信息',
    'zc_project_name' => '众筹项目名称',
    'zc_project_raise_money' => '众筹金额',
    'zc_goods_price' => '方案价格',
    'zc_shipping_fee' => '配送费用',
    'zc_return_time' => '预计回报时间',
    'zc_return_content' => '回报内容',
    'zc_return_detail' => '项目成功结束后%s天内',

    'set_grab_order' => '设为抢单',
    'set_success' => '设置完成',

    //by wu
    'cannot_delete' => '存在子退换原因，无法删除',
    'invoice_no_null' => '快递单号不能为空',

    'seckill' => '秒杀订单',

    //分单操作
    'split_action_note' => '【商品货号：%s，发货：%s件】',

    /* 退换货原因 */
    'add_return_cause' => '添加退换货原因',
    'return_cause_list' => '退换货原因列表',
    'back_return_cause_list' => '返回退换货原因列表',
    'return_order_info' => '返回订单信息',
    'back_return_delivery_handle' => '返回退货单操作',

    'detection_list_notic' => '只能操作-订单应收货时间-小于当前系统时间的订单',

    'refund_type_notic_one' => '退款失败，退款金额大于实际退款金额',
    'refund_type_notic_two' => '退款失败，您的账户资金负金额大于信用额度，请您进行账户资金充值或者联系客服',
    'refund_type_notic_three' => '该订单存在退换货商品，不能退货',
    'refund_type_notic_six' => '在线退款申请失败，请检查支付方式是否配置，或在线账户资金充足或者联系客服',

    'batch_delivery_success' => '批量发货成功',
    'batch_delivery_failed' => '批量发货失败，发货单号不能为空',
    'inspect_order_type' => '请检查订单状态',

    'order_return_prompt' => '订单退款，退回订单',
    'buy_integral' => '购买的积分',


    /* order_step js*/
    'order_step_js_notic_01' => '请填写收货人名称',
    'order_step_js_notic_02' => '请选择国家',
    'order_step_js_notic_03' => '请选择省/直辖市',
    'order_step_js_notic_04' => '请选择城市',
    'order_step_js_notic_05' => '请选择区/县',
    'order_step_js_notic_06' => '请填写收货地址',
    'order_step_js_notic_07' => '请填写手机号码',
    'order_step_js_notic_08' => '手机号码不正确',
    'order_step_js_notic_09' => '商品库存不足',
    'order_step_js_notic_10' => '请先添加商品',
    'order_step_js_notic_11' => '您修改的费用信息折扣不能大于实际订单总金额',
    'order_step_js_notic_12' => '，会产生退款，一旦退款则将扣除您账户里面的资金',
    'order_step_js_notic_13' => '您修改的费用信息产生了负数（',
    'order_step_js_notic_14' => '，会是否继续？',

    'lab_bar_shop_price' => '本店售价',
    'lab_bar_market_price' => '市场价',

    'refund_way' => '退款方式',
    'return_balance' => '退回到余额',

    /* 页面顶部操作提示 */
    'operation_prompt_content' => [
        'return_cause_list' => [
            '0' => '商城退货原因信息列表管理。',
            '1' => '可进行删除或修改退换货原因。',
        ],
        'return_cause_info' => [
            '0' => '可选择已有的顶级原因分类。',
        ],
        'back_list' => [
            '0' => '商城所有退货订单列表管理。',
            '1' => '可通过订单号进行查询，侧边栏进行高级搜索。',
        ],
        'delivery_list' => [
            '0' => '商城和平台所有已发货的订单列表管理。',
            '1' => '可通过订单号进行查询，侧边栏进行高级搜索。',
            '2' => '可进入查看取消发货。',
        ],
        'detection_list' => [
            '0' => '检测商城所有已发货订单。',
            '1' => '可通过订单号进行查询，侧边栏进行高级搜索。',
            '2' => '一键确认收货功能说明：使用一键确认收货功能，只能针对当前页面的订单条数进行操作处理，如需一次操作更多的订单条数，可修改每页显示的条数进行操作处理。',
        ],
        'list' => [
            '0' => '商城所有的订单列表，包括平台自营和入驻商家的订单。',
            '1' => '点击订单号即可进入详情页面对订单进行操作。',
            '2' => 'Tab切换不同状态下的订单，便于分类订单。',
        ],
        'search' => [
            '0' => '订单查询提供多个条件进行精准查询。',
        ],
        'step' => [
            '0' => '添加订单流程为：选择商城已有会员-选择商品加入订单-确认订单金额-填写收货信息-添加配送方式-选择支付方式-添加发票-查看费用信息-完成。',
        ],
        'return_list' => [
            '0' => '买家提交的退换货申请信息管理。',
            '1' => '可通过订单号进行查询，侧边栏进行高级搜索。',
        ],
        'templates' => [
            '0' => '下单成功后，可打印订单详情。',
            '1' => '请不要轻易修改模板变量，否则会导致数据无法正常显示。',
            '2' => '变量说明：
                            <p>$lang.***：语言项，修改路径：根目录/languages/zh_cn/admin/order.php</p>
                            <p>$order.***：订单数据，请参考dsc_order数据表</p>
                            <p>$goods.***：商品数据，请参考dsc_goods数据表</p>',
        ],
    ],

    'stores_name' => '门店名称：',
    'stores_address' => '门店地址：',
    'stores_tel' => '联系方式：',
    'stores_opening_hours' => '营业时间：',
    'stores_traffic_line' => '交通线路：',
    'stores_img' => '实景图片：',
    'pick_code' => '提货码：',


    // 商家后台
    'label_sure_collect_goods_time' => '确认收货时间：',
    'label_order_cate' => '订单分类：',
    'label_id_code' => '识别码：',
    'label_get_post_address' => '收票地址：',


    'add_order_step' => [
        '0' => '选择下单用户',
        '1' => '选择订单商品',
        '2' => '填写收货地址',
        '3' => '选择配送方式',
        '4' => '选择支付方式',
        '5' => '填写发票信息',
        '6' => '填写费用信息',
    ],


    'print_shipping_form' => '打印发货单',
    'current' => '当前',
    'store_info' => '门店信息',

    'this_order_return_no_continue' => '此订单已确认为退换货订单，无法继续订单操作！',
    'no_info_fill_express_number' => '暂无信息,请填写快递单号',

];