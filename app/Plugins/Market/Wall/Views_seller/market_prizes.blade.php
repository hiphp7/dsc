
<div class="wrapper-right of">
	<div class="tabmenu">
        <ul class="tab">
            <li><a href="{{ route('seller/wechat/market_list', array('type' => $config['keywords'])) }}" class="s-back">返回</a></li>
            <li><a href="#home" >{{ $config['name'] }} 获奖名单</a></li>
        </ul>
    </div>

    <div class="tabmenu">
        <ul class="tab">
            <li><a href="{{ route('seller/wechat/data_list', array('type' => $config['keywords'], 'function' => 'messages', 'id' => $config['market_id'], 'status' => 0)) }}">未审核消息</a></li>
            <li><a href="{{ route('seller/wechat/data_list', array('type' => $config['keywords'], 'function' => 'messages', 'id' => $config['market_id'], 'status' => 'all')) }}">全部消息</a></li>
            <li><a href="{{ route('seller/wechat/data_list', array('type' => $config['keywords'], 'function' => 'users', 'id' => $config['market_id'])) }}">参与会员</a></li>
            <li class="active"><a href="{{ route('seller/wechat/data_list', array('type' => $config['keywords'], 'function' => 'prizes', 'id' => $config['market_id'])) }}">获奖名单</a></li>
        </ul>
    </div>
    <div class="explanation" id="explanation">
        <div class="ex_tit"><i class="sc_icon"></i><h4>操作提示</h4></div>
        <ul>
            <li>微信墙获奖名单，显示已关注微信公众号并且中奖的记录，未中奖的不显示。</li>
        </ul>
    </div>
	<div class="wrapper-list mt20" >

        <div class="list-div" id="listDiv">
            <table id="list-table" class="ecsc-default-table" style="">
                <thead>
                <tr class="text-center">
                    <th class="text-center">微信昵称</th>
                    <th class="text-center">奖品</th>
                    <th class="text-center">是否发放</th>
                    <th class="text-center">中奖时间</th>
                    <th class="text-center">操作</th>
                </tr>
                </thead>

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

                        <a href="{{ route('seller/wechat/send_custom_message', array('openid' => $val['openid'])) }}" class="btn_inst fancybox fancybox.iframe"><i class="fa fa-bullhorn"></i>通知用户</a>
                        <a href="javascript:;" data-href="{!!  route('seller/wechat/market_action', array('type' => $config['keywords'], 'handler' => 'winner_del', 'id' => $val['id']))  !!}" class="btn_trash winner_del" ><i class="fa fa-trash-o"></i>删除</a>
                    </div>
                    </td>
                </tr>

@endforeach


@if(empty($list))

                <tr class="no-records" ><td colspan="5">没有找到任何记录</td></tr>

@endif

            </table>
        </div>

        @include('seller.base.seller_pageview')

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

});
</script>