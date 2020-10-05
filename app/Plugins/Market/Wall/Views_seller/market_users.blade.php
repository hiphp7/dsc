
<div class="wrapper-right of">

    <div class="tabmenu">
        <ul class="tab">
            <li><a href="{{ route('seller/wechat/market_list', array('type' => $config['keywords'])) }}" class="s-back">返回</a></li>
            <li><a href="#home" >{{ $config['name'] }}参与会员</a></li>
        </ul>
    </div>

    <div class="tabmenu">
        <ul class="tab">
            <li><a href="{{ route('seller/wechat/data_list', array('type' => $config['keywords'], 'function' => 'messages', 'id' => $config['market_id'], 'status' => 0)) }}">未审核消息</a></li>
            <li><a href="{{ route('seller/wechat/data_list', array('type' => $config['keywords'], 'function' => 'messages', 'id' => $config['market_id'], 'status' => 'all')) }}">全部消息</a></li>
            <li class="active"><a href="{{ route('seller/wechat/data_list', array('type' => $config['keywords'], 'function' => 'users', 'id' => $config['market_id'])) }}">参与会员</a></li>
            <li><a href="{{ route('seller/wechat/data_list', array('type' => $config['keywords'], 'function' => 'prizes', 'id' => $config['market_id'])) }}">获奖名单</a></li>
        </ul>
    </div>

    <div class="explanation" id="explanation">
        <div class="ex_tit"><i class="sc_icon"></i><h4>操作提示</h4></div>
        <ul>
            <li>显示微信墙当前活动所有参与会员，未审核消息数量，会员审核状态。</li>
            <li>会员审核通过后，方可显示在微信大屏幕当中，且可参与抽奖。</li>
        </ul>
    </div>

	<div class="wrapper-list mt20" >

        <div class="list-div" id="listDiv">
            <table id="list-table" class="ecsc-default-table" style="">
                <thead>
                <tr class="text-center">
                    <th class="text-center">昵称</th>
                    <th class="text-center">性别</th>
                    <th class="text-center">加入时间</th>
                    <th class="text-center">未审核消息</th>
                    <th class="text-center">会员状态</th>
                    <th class="text-center">操作</th>
                </tr>
                </thead>

@foreach($list as $val)

                <tr class="text-center wall-list">
                    <td>{{ $val['nickname'] }}</td>
                    <td>{{ $val['sex'] }}</td>
                    <td>{{ $val['addtime'] }}</td>
                    <td>{{ $val['nocheck'] }}</td>
                    <td>{{ $val['status'] }}</td>
                    <td class="info_btn">
                        {{ $val['handler'] }}
                        <a class="button btn-primary" href="{{ route('seller/wechat/data_list', array('type' => $config['keywords'], 'function' => 'users', 'id' => $config['market_id'], 'user_id' => $val['id'])) }}" title="查看消息">查看消息</a>
                        <a class="button btn-danger bg-red data_delete" data-href="{!!  route('seller/wechat/market_action', array('type' => $config['keywords'], 'handler' => 'data_delete', 'market_id' => $config['market_id'], 'user_id' => $val['id']))  !!}" href="javascript:;">删除</a>
                    </td>
                </tr>

@endforeach


@if(empty($list))

                <tr class="no-records" ><td colspan="6">没有找到任何记录</td></tr>

@endif

            </table>
        </div>

        @include('seller.base.seller_pageview')

    </div>

</div>
<script type="text/javascript">
$(function(){
    // 审核会员
    $(".check").click(function(){
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

    // 删除会员以及消息记录
    $(".data_delete").click(function(){
        var url = $(this).attr("data-href");

        //询问框
        layer.confirm('您确定要删除此会员以及记录吗？', {
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