<style>
    /* 修正导出时间样式 */
    ul, li {overflow: hidden;}
    .dates_box_top {height: 32px;}
    .dates_bottom {height: auto;}
    .dates_hms {width: auto;}
    .dates_btn {width: auto;}
    .dates_mm_list span {width: auto;}
</style>
<div class="wrapper">
	<div class="title"><a href="{{ route('admin/wechat/market_list', array('type' => $config['keywords'])) }}" class="s-back">返回</a>{{ $lang['wechat_menu'] }} - {{ $config['name'] }} 获奖名单</div>
	<div class="content_tips">
        <div class="tabs_info">
            <ul>
                <li><a href="{{ route('admin/wechat/data_list', array('type' => $config['keywords'], 'function' => 'messages', 'id' => $config['market_id'], 'status' => 'all')) }}">全部消息</a></li>
                <li><a href="{{ route('admin/wechat/data_list', array('type' => $config['keywords'], 'function' => 'messages', 'id' => $config['market_id'], 'status' => '')) }}">未审核消息</a></li>
                <li><a href="{{ route('admin/wechat/data_list', array('type' => $config['keywords'], 'function' => 'users', 'id' => $config['market_id'])) }}">参与会员</a></li>
                <li class="curr"><a href="{{ route('admin/wechat/data_list', array('type' => $config['keywords'], 'function' => 'prizes', 'id' => $config['market_id'])) }}">获奖名单</a></li>
            </ul>
        </div>
	    <div class="explanation" id="explanation">
            <div class="ex_tit"><i class="sc_icon"></i><h4>操作提示</h4><span id="explanationZoom" title="收起提示"></span></div>
            <ul>
                <li>微信墙获奖名单，显示已关注微信公众号并且中奖的记录，未中奖的不显示。</li>
            </ul>
        </div>
		<div class="flexilist of">
            <div class="common-content">
                <div class="col-md-12 col-sm-12 col-lg-12" style="padding:0;">
                    <div class="list-div">
                    <table class="table-hover table-striped">
                    <tr class="text-center">
                        <th class="text-center">微信昵称</th>
                        <th class="text-center">奖品</th>
                        <th class="text-center">是否发放</th>
                        <th class="text-center">中奖时间</th>
                        <th class="text-center" width="30%">操作</th>
                    </tr>

@foreach($list as $val)

                    <tr class="text-center wall-list">
                        <td class="text-center">{{ $val['nickname'] }}</td>
                        <td class="text-center">{{ $val['prize_name'] }}</td>
                        <td class="text-center">{{ $val['issue_status'] }}</td>
                        <!--<td class="text-center">
@if(is_array($val['winner']))
{{ $val['winner']['name'] }}<br />{{ $val['winner']['phone'] }}<br />{{ $val['winner']['address'] }}
@endif
</td>-->
                        <td class="text-center">{{ $val['dateline'] }}</td>
                        <td class="handle">
                        <div class="tDiv a3">
                            {{ $val['handler'] }}

                            <a href="{{ route('admin/wechat/send_custom_message', array('openid' => $val['openid'])) }}" class="btn_inst fancybox fancybox.iframe"><i class="fa fa-bullhorn"></i>通知用户</a>
                            <a href="javascript:;" data-href="{!!  route('admin/wechat/market_action', array('type' => $config['keywords'], 'handler' => 'winner_del', 'id' => $val['id']))  !!}" class="btn_trash winner_del" ><i class="fa fa-trash-o"></i>删除</a>
                        </div>
                        </td>
                    </tr>

@endforeach


@if(empty($list))

                    <tr class="no-records" ><td colspan="5">没有找到任何记录</td></tr>

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
                                <div class="fl">
                                    <form action="{{ route('admin/wechat/data_list', array('type' => $config['keywords'], 'function' => 'export_prizes_log', 'id' => $config['market_id'])) }}" method="post">
                                    <div class="label_value">
                                        <div class="text_time" id="text_time1" style="float:left;">
                                            <input type="text" name="starttime"  class="text" value="{{ date('Y-m-d H:i', mktime(0,0,0,date('m'), date('d')-7, date('Y'))) }}" id="promote_start_date" class="text mr0" readonly>
                                        </div>

                                        <div class="text_time" id="text_time2"  style="float:left;">
                                            <input type="text" name="endtime"  class="text" value="{{ date('Y-m-d H:i') }}" id="promote_end_date" class="text" readonly >
                                        </div>
                                        @csrf
                                        <input type="submit" name="export" value="导出" class="button bg-green" />
                                    </div>
                                    </form>
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
	</div>
</div>
<script type="text/javascript">
$(function(){
    // 发放奖品标记
    $(".winner_issue").click(function(){
        var url = $(this).attr("data-href");
        $.post(url, '', function(data){
            layer.msg(data.msg);
            if(data.error == 0 ){
                if(data.url != ''){
                    window.location.href = data.url;
                }else{
                    window.location.reload();
                }
            }
            return false;
        }, 'json');
    });

    // 删除中奖记录
    $(".winner_del").click(function(){
        var url = $(this).attr("data-href");

        //询问框
        layer.confirm('您确定要删除此中奖记录吗？', {
            btn: ['确定','取消'] //按钮
        }, function(){
            $.post(url, '', function(data){
                layer.msg(data.msg);
                if(data.error == 0 ){
                    if(data.url != ''){
                        window.location.href = data.url;
                    }else{
                        window.location.reload();
                    }
                }
                return false;
            }, 'json');
        });

    });


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

});
</script>
