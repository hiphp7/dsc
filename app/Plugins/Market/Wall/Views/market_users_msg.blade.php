
<div class="wrapper">
	<div class="title"><a href="{{ route('admin/wechat/data_list', array('type' => $config['keywords'], 'function' => 'users', 'id' => $config['market_id'])) }}" class="s-back">返回</a>{{ $lang['wechat_menu'] }} - {{ $config['name'] }}参与会员消息记录</div>
	<div class="content_tips">
        <div class="tabs_info">
            <ul>
                <li><a href="{{ route('admin/wechat/data_list', array('type' => $config['keywords'], 'function' => 'messages', 'id' => $config['market_id'], 'status' => 'all')) }}">全部消息</a></li>
                <li><a href="{{ route('admin/wechat/data_list', array('type' => $config['keywords'], 'function' => 'messages', 'id' => $config['market_id'], 'status' => '')) }}">未审核消息</a></li>
                <li class="curr"><a href="{{ route('admin/wechat/data_list', array('type' => $config['keywords'], 'function' => 'users', 'id' => $config['market_id'])) }}">参与会员</a></li>
                <li><a href="{{ route('admin/wechat/data_list', array('type' => $config['keywords'], 'function' => 'prizes', 'id' => $config['market_id'])) }}">获奖名单</a>/li>
            </ul>
        </div>
	    <div class="explanation" id="explanation">
            <div class="ex_tit"><i class="sc_icon"></i><h4>操作提示</h4><span id="explanationZoom" title="收起提示"></span></div>
            <ul>
                <li>微信墙参与会员消息</li>
            </ul>
        </div>
        <form action="{{ route('admin/wechat/market_action', array('type' => $config['keywords'], 'function' => 'users', 'handler' => 'batch_checking','market_id' => $config['market_id'], 'user_id' => $config['user_id'])) }}" method="post" class="form-inline" role="form">
		<div class="flexilist of">
            <div class="common-content">
                <div class="col-md-12 col-sm-12 col-lg-12" style="padding:0;">
                    <div class="list-div">
                    <table class="table-hover table-striped" style="table-layout:fixed">
                        <tr class="text-center">
                            <th class="text-center" width="5%"><input type="checkbox" id="check_box" /></th>
                            <th class="text-center">内容</th>
                            <th class="text-center">留言时间</th>
                            <th class="text-center">审核时间</th>
                            <th class="text-center" width="5%">状态</th>
                            <th class="text-center" width="30%">操作</th>
                        </tr>

@if($list)


@foreach($list as $val)

                        <tr class="text-center wall-list">
                            <td class="sign"><input type="checkbox" name="user_msg_id[]" value="{{ $val['id'] }}" class="checks"></td>
                            <td><a class="atip" href="#" data-toggle="tooltip" data-placement="top" title="{{ $val['content'] }}" data-original-title="{{ $val['content'] }}">{{ $val['content'] }}</a></td>
                            <td>{{ $val['addtime'] }}</td>
                            <td>{{ $val['checktime'] }}</td>
                            <td>
@if($val['status'] == '已审核')
 <span class="color-289">{{ $val['status'] }}</span>
@else
 <span class="color-red">{{ $val['status'] }}</span>
@endif
</td>
                            <td class="info_btn">
                                <a class="button btn-danger bg-red data_delete" data-href="{!!  route('admin/wechat/market_action', array('type' => $config['keywords'], 'handler' => 'data_delete', 'market_id' => $config['market_id'], 'msg_id' => $val['id']))  !!}" href="javascript:;">删除</a>
                            </td>
                        </tr>

@endforeach


@else

                        <tr class="no-records" ><td colspan="6">没有找到任何记录</td></tr>

@endif

                    </table>
                    </div>
                </div>
		    </div>
	    </div>
        <div class="list-div of">
            <table cellspacing="0" cellpadding="0" border="0">
                <tfoot>
                    <tr>
                        <td colspan="2">
                            <div class="tDiv of">
                                <div class="tfoot_btninfo">
                                    <span class="fl" style="line-height:30px;margin-right:20px;">批量</span>
                                    <select name="check_id" style="padding:5px;height:30px;" class="imitate_select select_w120 fl">
                                        <option value="1">审核</option>
                                        <option value="0">移除</option>
                                    </select>
                                    <input type="submit" class="btn button btn_disabled" value="确定" disabled="disabled" ectype='btnSubmit' >
                                </div>
                            </div>
                        </td>
                        <td colspan="3">
                            @include('admin.wechat.pageview')
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
        </form>
	</div>
</div>
<script type="text/javascript">
$(function(){
    // 提示
    $(".atip").tooltip();

    // 选择全中复选框
    $('#check_box').bind('click', function(){
        $('.checks').prop("checked", $(this).prop("checked"));
    });

    // 选择单个复选框
    $("input[type='checkbox']").bind("click",function(){
        var length = $("input[type='checkbox']:checked").length;
        if(length > 0){
            if($("*[ectype='btnSubmit']").length > 0){
                $("*[ectype='btnSubmit']").removeClass("btn_disabled");
                $("*[ectype='btnSubmit']").attr("disabled",false);
            }
        }else{
            if($("*[ectype='btnSubmit']").length > 0){
                $("*[ectype='btnSubmit']").addClass("btn_disabled");
                $("*[ectype='btnSubmit']").attr("disabled",true);
            }
        }
    });

    // 批量审核验证
    $("input[ectype='btnSubmit']").bind("click",function(){
        var item = $("select[name=check_id]").val();
        if(!item){
            layer.msg('请选择');
            return false;
        };
        var num = $("input[name='user_msg_id[]']:checked").length;
        if (num > 50) {
            layer.msg('批量审核数量一次不能超过50');
            return false;
        }
    });

    // 审核会员
    $(".check").click(function(){
        var url = $(this).attr("data-href");
        $.post(url, '', function(data){
            layer.msg(data.msg);
            if(data.error == 0 ){
                if(data.url != ''){
                    // layer.msg(data.url);
                    window.location.href = data.url;
                }else{
                    window.location.reload();
                }
            }
            return false;
        }, 'json');
    });

    // 删除会员的消息
    $(".data_delete").click(function(){
        var url = $(this).attr("data-href");

        //询问框
        layer.confirm('您确定要删除此会员的消息吗？', {
            btn: ['确定','取消'] //按钮
        }, function(){
            $.post(url, '', function(data){
                layer.msg(data.msg);
                if(data.error == 0 ){
                    if(data.url != ''){
                        // layer.msg(data.url);
                        window.location.href = data.url;
                    }else{
                        window.location.reload();
                    }
                }
                return false;
            }, 'json');
        });

    });

});
</script>
