
<style>
/*.dates_box {width: 300px;}*/
.dates_box_top {height: 32px;}
.dates_bottom {height: auto;}
.dates_hms {width: auto;}
.dates_btn {width: auto;}
.dates_mm_list span {width: auto;}
</style>
<div class="wrapper">
	<div class="title"><a href="{{ route('admin/wechat/extend_index') }}" class="s-back">{{ $lang['back'] }}</a>{{ $lang['wechat_extend'] }} - {{ $config['name'] }}</div>
	<div class="content_tips">
		<div class="flexilist">
			<div class="main-info">
				<form action="{{ route('admin/wechat/extend_edit', array('ks'=>$config['command'])) }}" method="post" class="form-horizontal" role="form" >
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
				                <input type="text" name="data[keywords]" class="form-control text" readonly value="{{ $config['keywords'] }}" />
			                	<div class="notic"></div>
				            </div>
					    </div>
					    <div style="display:none">
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
					    </div>
					    <div class="item">
					        <div class="label-t">{{ $lang['people_num'] }}：</div>
				            <div class="label_value">
				                <input type="text" name="cfg_value[people_num]" value="{{ $config['config']['people_num'] ?? 0 }}" class="form-control text" readonly />
				                <div class="notic">{{ $lang['people_num_notice'] }}：</div>
				           </div>
					    </div>

						<div class="item">
					        <div class="label-t">{{ $lang['period_time'] }}：</div>
				            <div class="label_value">
				            	<div class="text_time" id="text_time1">
				                <input type="text" name="cfg_value[starttime]" class="text" id="promote_start_date" value="{{ $config['config']['starttime'] }}" />
				                </div>
				                <span class="bolang">~&nbsp;&nbsp;</span>
				                <div class="text_time" id="text_time2">
				                <input type="text" name="cfg_value[endtime]" class="text" id="promote_end_date" value="{{ $config['config']['endtime'] }}" />
				                </div>
				          </div>
					    </div>

					    <div class="item">
					        <div class="label-t">{{ $lang['prize_num'] }}：</div>
					        <div class="label_value">
					            <input type="text" name="cfg_value[prize_num]" class="text" value="{{ $config['config']['prize_num'] ?? '' }}" />
					            <div class="notic">{{ $lang['prize_num_notice'] }}</div>
					        </div>
					    </div>
					    <div class="item">
					        <div class="label-t">{{ $lang['prize_list'] }}：</div>
				            <div class="label_value">
					                <table class="table ectouch-table prize_list">
					                    <tr>
					                        <th class="text-center" width="10%"><a href="javascript:;" class="glyphicon glyphicon-plus" onClick="addprize(this)"></a></th>
					                        <th class="text-center"  width="20%">{{ $lang['prize_level'] }}</th>
					                        <th class="text-center" width="20%">{{ $lang['prize_name'] }}</th>
					                        <th class="text-center" width="20%">{{ $lang['prize_count'] }}</th>
					                        <th class="text-center" width="20%">{{ $lang['prize_prob'] }}</th>
					                    </tr>
@if (isset($config['config']['prize']))
@foreach($config['config']['prize'] as $v)

					                    <tr>
					                        <td class="text-center"><a href="javascript:;" class="glyphicon glyphicon-minus" onClick="delprize(this)"></a></td>
					                        <td class="text-center"><input type="text" name="cfg_value[prize_level][]" class="form-control" placeholder="{{ $lang['for_example'] }}：{{ $lang['prize_level_1'] }}" value="{{ $v['prize_level'] }}"></td>
					                        <td class="text-center"><input type="text" name="cfg_value[prize_name][]" class="form-control" placeholder="{{ $lang['for_example'] }}：IPhone" value="{{ $v['prize_name'] }}"></td>
					                        <td class="text-center"><input type="number" min="0" name="cfg_value[prize_count][]" class="form-control" placeholder="{{ $lang['for_example'] }}：3" value="{{ $v['prize_count'] }}"></td>
					                        <td class="text-center">
					                            <div class="input-group">
					                                <input type="number" min="0" max="100" name="cfg_value[prize_prob][]"  class="form-control" placeholder="{{ $lang['for_example'] }}：1%" value="{{ $v['prize_prob'] }}">
					                                <span class="input-group-addon">%</span>
					                            </div>
					                        </td>
					                   </tr>

@endforeach
@endif
					                </table>
				                <div class="notic">{{ $lang['prize_list_notice_ggk'] }}</div>
				            </div>
					    </div>
					    <div class="item">
					        <div class="label-t">{{ $lang['activity_rules'] }}：</div>
					            <div class="label_value">
					                <textarea name="cfg_value[description]" class="textarea" rows="3">{{ $config['config']['description'] ?? '' }}</textarea>
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
                                <input type="hidden" name="cfg_value[haslist]" value="1">
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
    //添加奖项
    var num = $('.prize_list tr').length > 0 ? $('.prize_list tr').length : 1;

    function addprize(obj){
        switch(num)
        {
            case 1:
                prize_level = "{{ $lang['prize_level_1'] }}";
                break;
            case 2:
                prize_level = "{{ $lang['prize_level_2'] }}";
                break;
            case 3:
                prize_level = "{{ $lang['prize_level_3'] }}";
                break;
            case 4:
                prize_level = "{{ $lang['prize_level_4'] }}";
                break;
            case 5:
                prize_level = "{{ $lang['prize_level_5'] }}";
                break;
            case 6:
                prize_level = "{{ $lang['prize_level_6'] }}";
                break;
            default:
                prize_level = "";
        }

    	var html = '<tr><td class="text-center"><a href="javascript:;" class="glyphicon glyphicon-minus" onClick="delprize(this)"></a></td><td class="text-center"><input type="text" name="cfg_value[prize_level][]" class="form-control" placeholder="{{ $lang['for_example'] }}：{{ $lang['prize_level_1'] }}" value="'+prize_level+'" ></td><td class="text-center"><input type="text" name="cfg_value[prize_name][]" class="form-control" placeholder="{{ $lang['for_example'] }}：IPhone"></td><td class="text-center"><input type="number" min="0" name="cfg_value[prize_count][]" class="form-control" placeholder="{{ $lang['for_example'] }}：3"></td><td class="text-center"><div class="input-group"><input type="number" min="0" max="100" name="cfg_value[prize_prob][]"  class="form-control" placeholder="{{ $lang['for_example'] }}：1"><span class="input-group-addon">%</span></div></td></tr>';
        $(obj).parent().parent().parent().append(html);
    }
    //删除奖项
    function delprize(obj){
        $(obj).parent().parent().remove();
    }

    // 大商创PC日历插件
	var opts1 = {
		'targetId':'promote_start_date',
		'triggerId':['promote_start_date'],
		'alignId':'text_time1',
		'format':'-',
		'hms':'off'
	},opts2 = {
		'targetId':'promote_end_date',
		'triggerId':['promote_end_date'],
		'alignId':'text_time2',
		'format':'-',
		'hms':'off'
	}

	xvDate(opts1);
	xvDate(opts2);

	// 验证提交
	$(".form-horizontal").submit(function(){

		if ($('.prize_list tr').length > 10 ) {
			layer.msg('{{ $lang['prize_level_limited_9'] }}');
			return false;
		}
		var prize_count = $("input[name='cfg_value[prize_count][]']").val();
		if (prize_count < 0) {
			layer.msg('{{ $lang['wechat_js_languages']['prize_count_not_minus'] }}');
			return false;
		}
		var prize_prob = $("input[name='cfg_value[prize_prob][]']").val();
		if (prize_prob < 0) {
			layer.msg('{{ $lang['wechat_js_languages']['prize_prob_not_minus'] }}');
			return false;
		}
		if (prize_prob > 100) {
			layer.msg('{{ $lang['wechat_js_languages']['prize_prob_max_limited_100'] }}');
			return false;
		}
		// 概率总和
		var prize_prob_sum = 0;
		$("input[name='cfg_value[prize_prob][]']").each(function(){
			prize_prob_sum += parseInt($(this).val());
		});
		if (prize_prob_sum > 100) {
			layer.msg('{{ $lang['wechat_js_languages']['prize_prob_sum_max_limited_100'] }}');
			return false;
		}
	});
</script>
