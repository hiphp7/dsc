<div class="wrapper">
	<div class="title"><a href="{{ route('admin/wechat/extend_index') }}" class="s-back">{{ $lang['back'] }}</a>{{ $lang['wechat_extend'] }} - {{ $config['name'] }}</div>
	<div class="content_tips">
		<div class="flexilist">
			<div class="main-info">
				<form action="{{ route('admin/wechat/extend_edit', ['ks'=>'sign']) }}" method="post" class="form-horizontal" role="form">
				<div class="switch_info">
				    <div class="item">
					        <div class="label-t">{{ $lang['extend_name'] }}：</div>
					        <div class="label_value" >
								<input type="text" name="data[name]" class="text" value="{{ $config['name'] }}" />
								<div class="notic"> {{ $lang['name_notice_sign'] }} </div>
							</div>
					    </div>
					    <div class="item">
					        <div class="label-t">{{ $lang['extend_command'] }}：</div>
					        <div class="label_value">{{ $config['command'] }}</div>
					    </div>
					    <div class="item">
					        <div class="label-t">{{ $lang['extend_keywords'] }}：</div>
				            <div class="label_value">
				                <input type="text" name="data[keywords]" class="text" value="{{ $config['keywords'] }}" />
				                <div class="notic">{{ $lang['extend_keywords_notice'] }}：</div>
				            </div>
					    </div>
					    <div class="item">
					        <div class="label-t">{{ $lang['point_status'] }}：</div>
					            <div class="label_value">
					            	<div class="checkbox_items">
			                            <div class="checkbox_item">
						                    <input type="radio" name="cfg_value[point_status]" class="ui-radio event_zhuangtai" id="value_121_0" value="1" checked="true"
@if(isset($config['config']['point_status']) && $config['config']['point_status'])
checked
@endif
>
						                    <label for="value_121_0" class="ui-radio-label
@if(isset($config['config']['point_status']) && $config['config']['point_status'])
active
@endif
">{{ $lang['wechat_open'] }}</label>
						                </div>
						                <div class="checkbox_item">
						                    <input type="radio" name="cfg_value[point_status]" class="ui-radio event_zhuangtai" id="value_121_1" value="0"
@if(isset($config['config']['point_status']) && empty($config['config']['point_status']))
checked
@endif
>
						                    <label for="value_121_1" class="ui-radio-label
@if(isset($config['config']['point_status']) && empty($config['config']['point_status']))
active
@endif
">{{ $lang['wechat_close'] }}</label>
						                </div>
	                               </div>
					           </div>
					    </div>

					    <div class="item">
					        <div class="label-t">{{ $lang['rank_point_value'] }}：</div>
					            <div class="label_value">
					                <input type="number" min="0" step="0.01"  name="cfg_value[rank_point_value]" class="text" value="{{ $config['config']['rank_point_value'] ?? '0' }}" />
					           </div>
					    </div>

					    <div class="item">
					        <div class="label-t">{{ $lang['pay_point_value'] }}：</div>
					            <div class="label_value">
					                <input type="number" min="0" step="0.01" name="cfg_value[pay_point_value]" class="text" value="{{ $config['config']['pay_point_value'] ?? '0' }}" />
					           </div>
					    </div>

						<div class="item">
							<div class="label-t">{{ $lang['continue_day'] }}：</div>
							<div class="label_value">
								<div class="input-group w150">
									<input type="number" min="0" max="30" step="1" name="cfg_value[continue_day]" class="form-control" value="{{ $config['config']['continue_day'] ?? '5' }}" />
									<span class="input-group-addon">{{ $lang['day'] }}</span>
								</div>
								<div class="notic">{{ $lang['continue_day_notice'] }}</div>
							</div>
						</div>

						<div class="item">
							<div class="label-t">{{ $lang['select_coupons'] }}：</div>
							<div class="label_value">
								<div class="select_w320 fl" style="margin-right:10px;">
									<select name="cfg_value[coupons_id]" class="form-control">
										<option value="0">{{ $lang['please_select'] }}...</option>
										@if (isset($config['coupons_list']) && !empty($config['coupons_list']))

										@foreach($config['coupons_list'] as $v)

											<option value="{{ $v['cou_id'] }}"
													@if(isset($config['config']['coupons_id']) && ($config['config']['coupons_id'] == $v['cou_id']))
													selected
													@endif
											>{{ $v['cou_name'] }} => {{ $v['cou_man_format'] }}</option>

										@endforeach

										@endif

									</select>
								</div>
								<div class="notic">{!! $lang['select_coupons_notice'] !!}</div>
							</div>
						</div>

					@if (isset($config['config']['media_id']))
						<div class="item">
							<div class="label-t">{{ $lang['media_info'] }}：</div>
							<div class="label_value">
								<input type="hidden" name="cfg_value[media_id]" value="{{ $config['config']['media_id'] ?? '' }}" />
								<div class="fl" style="margin-right:20px;">
									<a class="btn button btn-info fancybox fancybox.iframe" href="{{ route('admin/wechat/article_edit', ['id' => $config['config']['media_id']]) }}">{{ $lang['edit_media'] }}</a>
								</div>
								<span class="notic">{{ $lang['media_info_notice'] }}</span>
							</div>
						</div>
					@endif

					    <div class="item">
					        <div class="label-t">&nbsp;</div>
					        <div class="label_value info_btn">
                                @csrf
                                <input type="hidden" name="data[command]" value="{{ $config['command'] }}" />

                                <input type="hidden" name="data[author]" value="{{ $config['author'] }}">
                                <input type="hidden" name="data[website]" value="{{ $config['website'] }}">
                                <input type="hidden" name="handler" value="{{ $config['handler'] ?? '' }}">
                                <input type="submit" name="submit" class="button btn-danger bg-red" value="{{ $lang['button_submit'] }}" />
                                <input type="reset" name="reset" class="button button_reset" value="{{ $lang['button_revoke'] }}" />
					        </div>
					    </div>
                </div>
				</form>
            </div>
		</div>
	</div>
</div>

<script type="text/javascript">

// 验证提交
$(".form-horizontal").submit(function(){

	var continue_day = $("input[name='cfg_value[continue_day]']").val();
	if (continue_day >= 30) {
		layer.msg('{{ $lang['continue_day_js_notice'] }}');
		return false;
	}
});

</script>