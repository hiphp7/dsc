
<style>
/*.dates_box {width: 300px;}*/
.dates_box_top {height: 32px;}
.dates_bottom {height: auto;}
.dates_hms {width: auto;}
.dates_btn {width: auto;}
.dates_mm_list span {width: auto;}
#xv_Ipt_year,#xv_Ipt_month {background: none; color: #fff;padding: 0;}
</style>

<div class="wrapper-right of">
	<div class="tabmenu">
        <ul class="tab ">
            <li><a href="{{ route('seller/wechat/extend_index') }}" class="s-back">{{ $lang['back'] }}</a></li>
            <li role="presentation" class="active"><a href="#home" role="tab" data-toggle="tab">{{ $lang['wechat_extend'] }} - {{ $config['name'] }}</a></li>
        </ul>
    </div>
    <div class="wrapper-list mt20">
    	<form action="{{ route('admin/wechat/extend_edit', array('ks'=>$config['command'])) }}" method="post" class="form-horizontal" role="form" >
		<div class="account-setting ecsc-form-goods">
            <dl>
                <dt>{{ $lang['extend_name'] }}：</dt>
                <dd class="txtline">
                    <span><input type="text" name="data[name]" class="text" value="{{ $config['name'] }}" /></span>
                </dd>
            </dl>
            <dl>
                <dt>{{ $lang['extend_command'] }}：</dt>
                <dd class="txtline">
                    <span>{{ $config['command'] }}</span>
                </dd>
            </dl>
            <dl>
                <dt>{{ $lang['extend_keywords'] }}：</dt>
                <dd>
                    <input type="text" name="data[keywords]" class="text" value="{{ $config['keywords'] }}" />
                    <div class="form_prompt"></div>
                    <div class="notic"> {{ $lang['extend_keywords_notice'] }}：</div>
                </dd>
            </dl>
            <dl>
                <dt>{{ $lang['people_num'] }}：</dt>
                <dd>
                    <input type="text" name="cfg_value[people_num]" value="{{ $config['config']['people_num'] ?? 0 }}" class="form-control text" readonly />
                    <div class="form_prompt"></div>
                    <div class="notic">{{ $lang['people_num_notice'] }}：</div>
                </dd>
            </dl>
            <dl>
                <dt>{{ $lang['period_time'] }}：</dt>
                <dd>
                    <div class="text_time" id="text_time1">
                    <input type="text" name="cfg_value[starttime]" class="text" id="promote_start_date" value="{{ $config['config']['starttime'] }}" />
                    </div>
                    <span class="bolang">~&nbsp;&nbsp;</span>
                    <div class="text_time" id="text_time2">
                    <input type="text" name="cfg_value[endtime]" class="text" id="promote_end_date" value="{{ $config['config']['endtime'] }}" />
                    </div>
                </dd>
            </dl>
            <dl>
                <dt>{{ $lang['prize_num'] }}：</dt>
                <dd>
                    <input type="text" name="cfg_value[prize_num]" class="text" value="{{ $config['config']['prize_num'] ?? 0 }}" />
                    <div class="form_prompt"></div>
                    <div class="notic">{{ $lang['prize_num_notice'] }}</div>
                </dd>
            </dl>
            <dl>
                <dt>{{ $lang['prize_list'] }}：</dt>
                <dd>
                    <table class="table ectouch-table prize_list" style="width:80%;">
                    <tr>
                        <th class="text-center" width="10%"><a href="javascript:;" class="glyphicon glyphicon-plus" onClick="addprize(this)"></a></th>
                        <th class="text-center" width="20%">{{ $lang['prize_level'] }}</th>
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
                    <div class="form_prompt"></div>
                    <div class="notic">注意：建议最多添加9项，概率总和小于100%，不能为0</div>
                </dd>
            </dl>
            <dl>
                <dt>{{ $lang['activity_rules'] }}：</dt>
                <dd>
                <textarea name="cfg_value[description]" class="textarea" rows="5">{{ $config['config']['description'] ?? '' }}</textarea>
                </dd>
            </dl>
            <dl>
                <dt>{{ $lang['media_info'] }}：</dt>
                <dd>
                    <input type="text" name="cfg_value[media_id]" class="form-control text" style="width: auto;" value="{{ $config['config']['media_id'] ?? '' }}" readonly />
                    <div class="form_prompt"></div>
                    <div class="notic">{{ $lang['media_info_notice'] }}</div>
                </dd>
            </dl>
            <dl>
                <dt>&nbsp;</dt>
                <dd>
                    @csrf
                    <input type="hidden" name="data[command]" value="{{ $config['command'] }}" />

                    <input type="hidden" name="data[author]" value="{{ $config['author'] }}">
                    <input type="hidden" name="data[website]" value="{{ $config['website'] }}">
                    <input type="hidden" name="cfg_value[haslist]" value="1">
                    <input type="hidden" name="handler" value="{{ $config['handler'] ?? '' }}">
                    <input type="submit" name="submit" class="sc-btn sc-blueBg-btn btn35" value="{{ $lang['button_submit'] }}" />
                    <input type="reset" name="reset" class="sc-btn sc-blue-btn btn35" value="{{ $lang['button_revoke'] }}" />
                </dd>
            </dl>
        </div>
        </form>
    </div>
</div>

<script type="text/javascript">

    //添加奖项
    function addprize(obj){
        var html = '<tr><td class="text-center"><a href="javascript:;" class="glyphicon glyphicon-minus" onClick="delprize(this)"></a></td><td class="text-center"><input type="text" name="cfg_value[prize_level][]" class="form-control" placeholder="{{ $lang['for_example'] }}：{{ $lang['prize_level_1'] }}"></td><td class="text-center"><input type="text" name="cfg_value[prize_name][]" class="form-control" placeholder="{{ $lang['for_example'] }}：IPhone"></td><td class="text-center"><input type="number" min="0" name="cfg_value[prize_count][]" class="form-control" placeholder="{{ $lang['for_example'] }}：3"></td><td class="text-center"><div class="input-group"><input type="number" min="0" max="100" name="cfg_value[prize_prob][]"  class="form-control" placeholder="{{ $lang['for_example'] }}：1"><span class="input-group-addon">%</span></div></td></tr>';
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
            layer.msg('奖项不能超过9项');
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
