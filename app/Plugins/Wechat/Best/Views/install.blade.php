<div class="wrapper">
	<div class="title"><a href="{{ route('admin/wechat/extend_index') }}" class="s-back">{{ $lang['back'] }}</a>{{ $lang['wechat_extend'] }} - {{ $config['name'] }}</div>
	<div class="content_tips">
		<div class="flexilist">
			<div class="main-info">
				<form action="{{ route('admin/wechat/extend_edit') }}" method="post" class="form-horizontal" role="form">
				<div class="switch_info">
				    <div class="item">
				        <div class="label-t">{{ $lang['extend_name'] }}：</div>
				        <div class="label_value"><input type="text" name="data[name]" class="text" value="{{ $config['name'] }}" /></div>
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
					                    <input type="radio" name="cfg_value[point_status]" class="ui-radio event_zhuangtai" id="value_118_0" value="1" checked="true"
@if(isset($config['config']['point_status']) && $config['config']['point_status'])
checked
@endif
>
					                    <label for="value_118_0" class="ui-radio-label
@if(isset($config['config']['point_status']) && $config['config']['point_status'])
active
@endif
">{{ $lang['wechat_open'] }}</label>
					                </div>
					                <div class="checkbox_item">
					                    <input type="radio" name="cfg_value[point_status]" class="ui-radio event_zhuangtai" id="value_118_1" value="0"
@if(isset($config['config']['point_status']) && empty($config['config']['point_status']))
checked
@endif
>
					                    <label for="value_118_1" class="ui-radio-label
@if(isset($config['config']['point_status']) && empty($config['config']['point_status']))
active
@endif
">{{ $lang['wechat_close'] }}</label>
					                </div>
                               </div>
				           </div>
				    </div>
				    <div class="item">
				        <div class="label-t">{{ $lang['point_value'] }}：</div>

				            <div class="label_value">
				                <input type="text" name="cfg_value[point_value]" class="text" value="{{ $config['config']['point_value'] ?? '' }}" />
				           </div>

				    </div>
				   <div class="item">
				        <div class="label-t">{{ $lang['point_num'] }}：</div>
			            <div class="label_value">
			                <input type="text" name="cfg_value[point_num]" class="text" value="{{ $config['config']['point_num'] ?? '' }}" />
			                <div class="notic">{{ $lang['point_num_notice'] }}</div>
			           </div>
				    </div>
				    <div class="item">
				        <div class="label-t">{{ $lang['point_interval'] }}：</div>
			            <div class="label_value">
			            	<div class="select_w320">
				                <select name="cfg_value[point_interval]" class="form-control">
				                        <option value="86400"
@if(isset($config['config']['point_interval']) && $config['config']['point_interval'] == 86400)
selected
@endif
>24 {{ $lang['hour'] }}</option>
				                        <option value="3600"
@if(isset($config['config']['point_interval']) && $config['config']['point_interval'] == 3600)
selected
@endif
>1 {{ $lang['hour'] }}</option>
				                        <option value="60"
@if(isset($config['config']['point_interval']) && $config['config']['point_interval'] == 60)
selected
@endif
>1 {{ $lang['minute'] }}</option>
				                </select>
			            	</div>
			           </div>
				    </div>
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
