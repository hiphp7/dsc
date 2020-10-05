<link rel="stylesheet" type="text/css" href="{{ asset('js/spectrum-master/spectrum.css') }}" />
<link rel="stylesheet" type="text/css" href="{{ asset('js/perfect-scrollbar/perfect-scrollbar.min.css') }}" />
<link rel="stylesheet" type="text/css" href="{{ asset('assets/seller/css/font-awesome.min.css') }}" />
<link rel="stylesheet" type="text/css" href="{{ asset('assets/seller/css/iconfont.css') }}" />
<div class="brand-top">
	<div class="letter">
		<ul>
			<li><a href="javascript:void(0);" data-letter="">{{$lang['all_brand']}}</a></li>
			@if(isset($letter))
				@foreach($letter as $key=>$val)
				<li><a href="javascript:void(0);" data-letter="{{$val}}">{{$val}}</a></li>
				@endforeach
			@endif
			<li><a href="javascript:void(0);" data-letter="QT">{{$lang['other']}}</a></li>
		</ul>
	</div>
	<div class="b_search">
		<input name="search_brand_keyword" id="search_brand_keyword" type="text" class="b_text" placeholder="{{$lang['brand_name_keywords_search']}}" autocomplete="off" />
		<a href="javascript:void(0);" class="btn-mini"><i class="icon icon-search"></i></a>
	</div>
</div>
<div class="brand-list">
	@if(isset($filter_brand_list) && !empty($filter_brand_list))
	<ul>
		<li data-id="0" data-name="{{$lang['select_barnd']}}" class="blue">{{$lang['cancel_select']}}</li>
		@foreach($filter_brand_list as $key=>$brands)
		<li data-id="{{$brands['brand_id']}}" data-name="{{$brands['brand_name']}}"><em>{{$brands['letter']}}</em>{{$brands['brand_name']}}</li>
		@endforeach
	</ul>
	@endif
</div>
<div class="brand-not" style="display:none;">{{$lang['no_accord_with']}}<strong>{{$lang['other']}}</strong>{{$lang['condition_brand']}}</div>

<script src="{{ asset('js/jquery-1.12.4.min.js') }}" type="text/javascript"></script>
<script src="{{ asset('js/jquery.json.js') }}" type="text/javascript"></script>
<script src="{{ asset('js/transport_jquery.js') }}" type="text/javascript"></script>
<script src="{{ asset('js/jquery.validation.min.js') }}" type="text/javascript"></script>
<script src="{{ asset('js/jquery.nyroModal.js') }}" type="text/javascript"></script>
<script src="{{ asset('js/jquery.form.js') }}" type="text/javascript"></script>
<script src="{{ asset('js/calendar/calendar.min.js') }}" type="text/javascript"></script>
<script src="{{ asset('js/perfect-scrollbar/perfect-scrollbar.min.js') }}" type="text/javascript"></script>
<script src="{{ asset('js/utils.js') }}" type="text/javascript"></script>
<script src="{{ asset('plugins/sms/sms.js') }}" type="text/javascript"></script>
{{--<script src="{{ asset('assets/seller/js/seller.js') }}" type="text/javascript"></script>--}}
<script src="{{ asset('assets/seller/js/listtable_pb.js') }}" type="text/javascript"></script>
<script src="{{ asset('assets/seller/js/listtable.js') }}" type="text/javascript"></script>
<script src="{{ asset('assets/seller/js/common.js') }}" type="text/javascript"></script>
<script src="{{ asset('js/lib_ecmobanFunc.js') }}" type="text/javascript"></script>
<script src="{{ asset('js/jquery.cookie.js') }}" type="text/javascript"></script>
<script src="{{ asset('assets/seller/js/seller.js') }}" type="text/javascript"></script>
<script>
	//这里把JS用到的所有语言都赋值到这里
	var process_request = "正在处理您的请求...";
	var todolist_caption = "记事本";
	var todolist_autosave = "自动保存";
	var todolist_save = "保存";
	var todolist_clear = "清除";
	var js_select = "选择";
	var js_selected = "已选择";
	var todolist_confirm_save = "是否将更改保存到记事本？";
	var todolist_confirm_clear = "是否清空内容？";
	var start_data_notnull = "开始日期不能为空";
	var end_data_notnull = "结束日期不能为空";
	var data_invalid_gt = "输入的结束时间应大于起始日期";
	var file_not_null = "上传文件不能为空";
	var confirm_delete = "确定删除吗?";
	var confirm_delete_fail = "删除失败";
	var file_null = "请选择上传文件";
	var title_name_one = "已完成更新，请关闭该窗口！";
	var title_name_two = "正在更新数据中，请勿关闭该窗口！";
	var print_batch_content = "请选择打印内容";
	var jl_can_setup = "可设置";
	var jl_spand_integral = "消费积分";
	var jl_level_integral = "等级积分";
	var jl_can_setup_integral_buy = "可设置积分购买";
	var jl_money = "金额";
	var jl_sure_delete_template = "确定删除该运费模板吗？";
	var jl_area_name_no_empty = "地区名称不能为空！";
	var jl_no_specialchar = "不能包含特殊字符";
	var jl_upload_video_fail = "视频上传失败";
	var jl_upload_video_success = "视频上传成功";
	var jl_upload_video_format_wrong = "视频上传格式有误";
	var jl_processing_export_number = "正在处理导出第";
	var jl_page_data = "页数据...";
	var jl_completed = "已完成";
	var jl_completed_close = "全部完成，请关闭窗口";
	var jl_reminder = "温馨提示";
	var jl_client_app_no_empty = "客户端应用不可为空";
	var jl_message_template_no_empty = "消息模板不可为空";
	var jl_this_area_existed = "该地区已存在";
	var jl_please_select_album = "请选择相册";
	var jl_module_max_add = "此模块最多可添加";
	var jl_ge_image = "个图片";
	var jl_ge_goods = "个商品";
	var jl_delete = "删除";
	var jl_please_select = "请选择...";
	var jl_cate_no_empty = "分类名称不能为空";
	var jl_navbar_no_exist = "导航不存在";
	var jl_goods_import = "商品库商品导入";
	var rate_empty = "税率不能为空!";
	var empty_cat_rate = "税率不能为空!";
	var jl_do_current_cate_do_sure = "执行此操作时，当前分类所有下级分类也同时转移，确定执行吗？";
	var jl_transfer_goods = "转移商品";
	var jl_start_transfer = "开始转移";
	var jl_reset = "重置";
	var jl_price_area_nosmall_0 = "价格区间不能小于0";
	var jl_price_area_nobig_10 = "价格区间不能大于10";
	var jl_this_cate_must_top = "该分类必须是顶级分类才能使用!";
	var jl_sure_delete_info = "确实要删除该信息吗";
	var jl_delete_fail = "删除失败!";
	var jl_edit_success = "修改成功";
	var jl_region_select = "地区选择";
	var jl_batch_delivery = "批量发货";
	var jl_determine = "确定";
	var jl_cancel = "取消";
	var jl_close = "关闭";
	var jl_please_input_giftcard = "请输入礼品卡名称";
	var jl_please_input_sendnum = "请输入发放数量";
	var jl_please_input_gc_id = "请输入礼品卡序列号";
	var jl_gc_id_must_int = "礼品卡序列号必须为整数";
	var jl_gc_id_must_max_7_bit = "礼品卡序列号必须为大于等于7位数";
	var jl_please_input_gc_pwd = "请输入礼品卡密码";
	var jl_gc_pwd_must_int = "礼品卡密码必须为整数";
	var jl_gc_pwd_must_max_7_bit = "礼品卡密码必须为大于等于6位数";
	var jl_pls_select_file_encode = "请选择文件编码";
	var jl_pls_select_upload_file = "请选择上传文件";
	var jl_pls_select_file_format = "请选择数据格式";
	var jl_pls_select_belong_cate = "请选择所属分类";
	var jl_pls_upload_batch_csv = "请上传批量csv文件";
	var jl_uploaded_complete = "已下载完成！";
	var jl_no_select_bargain_goods = "您没有选砍价商品！";
	var jl_you_no = "您没有";
	var jl_separator_null = "分隔符不可以不填写";
	var jl_shop_guide = "店铺指引";
	var jl_ok_i_know = "好的，我知道了";
	var jl_know_next = "了解了，下一步";
	var jl_print_no_printer = "该打印规格还没有设置指定的打印机";
	var jl_print_express_form = "打印快递单";
	var jl_user_name_not_null = "用户名不能为空";
	var jl_pwd_not_null = "密码不能为空";
	var jl_verify_not_null = "验证码不能为空";
	var jl_verify_wrong = "验证码错误";
	var jl_name_pwd_wrong = "用户名或密码错误";
	var jl_admin_name_not_null = "管理员名称不能为空";
	var jl_email_not_null = "邮箱不能为空";
	var jl_email_format_wrong = "邮箱格式不正确";
	var jl_new_pwd_not_null = "新密码不能为空";
	var jl_pwd_nolessthan_6 = "密码不能小于6位";
	var jl_repwd_not_null = "重复密码不能为空";
	var jl_pwd_not_equal = "密码输入不一致";
	var jl_not_null = "不能为空";
	var jl_format_wrong = "格式不正确";
	var jl_payment_not_null = "支付方式不能为空";
	var jl_pay_year_not_small_1 = "缴纳年限不能小于1";
	var jl_input_content_wrongful = "输入内容不合法";
	var jl_window_name_not_null = "橱窗名称不能为空";
	var jl_order_settle_export_window = "订单结算导出弹窗";
	var jl_no_send_goods_info_yet = "暂无确认发货信息";
	var jl_buy_list = "购物清单";
	var jl_select_print_specification = "请选择打印规格";
	var jl_fill_printer_name = "请填写打印机名称";
	var jl_market_price = "市场价";
	var jl_shop_price = "本店价";
	var jl_select_warehouse = "请选择仓库";
	var jl_select_region = "请选择地区";
	var jl_goods_stock_no_enough = "商品库存不足";
	var jl_upload_img = "上传图片";
	var jl_sure_delete = "确定删除吗？";
	var jl_select_print_content = "请选择打印内容";
	var jl_order_id = "订单号";
	var jl_sure_export_dialog_log = "确认导出聊天记录？";
	var jl_sure_selected_delete = "您确实要把选中的商品放入回收站吗？";
	var jl_sure_selected_outsale = "您确实要将选定的商品下架吗？";
	var jl_please_select_goods_gou = "请勾选商品";
	var jl_please_select_express_company = "请选择快递公司";
	var jl_please_input_express_number = "请填写快递单号";
	var jl_pay_success = "支付成功";
	var jl_select_upload_img = "请选择上传图片";
	var jl_select_deliver = "请选择配送方式";
	var jl_get_lat_lon = "获取经纬度";
	var jl_nationwide = "全国";
	var jl_please_search_goods = "请先搜索商品";
	var jl_please_select_member_level = "请选择适用会员等级";
	var jl_sure_delete_deliver_fee_tpl = "确定删除该运费模板么？";
	var jl_select_region_alt = "选择地区";
	var jl_select_express = "选择快递";
	var jl_edit_deliver_fee_tpl = "编辑运费模板";
	var jl_fast_door_max_8 = "快捷操作最多添加8个";
	var jl_wrong_tip = "错误提示";
	var jl_title_not_null = "标题不为空";
	var jl_edit_freight_tpl = "编辑运费模板";
	var succeed_confirm = "此操作不可逆，您确定要设置该预售活动成功吗？";
	var fail_confirm = "此操作不可逆，您确定要设置该预售活动失败吗？";
	var error_goods_null = "您没有选择预售商品！";
	var error_deposit = "您输入的保证金不是数字！";
	var error_restrict_amount = "您输入的限购数量不是整数！";
	var error_gift_integral = "您输入的赠送积分数不是整数！";
	var search_is_null = "没有搜索到任何商品，请重新搜索";
	var select_cat_null = "请选择活动分类";
	var pay_start_time_null = "支付尾款开始时间不能为空";
	var pay_start_time_cw = "支付尾款开始时间必须大于支付定金结束时间";
	var pay_end_time_null = "支付尾款结束时间不能为空";
	var pay_end_time_cw = "支付尾款结束时间必须大于支付尾款开始时间";
	var batch_drop_confirm = "您确定要删除选定的预售活动吗？";
</script>
