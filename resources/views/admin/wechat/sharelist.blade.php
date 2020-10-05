@include('admin.wechat.pageheader')
<div class="wrapper">
	<div class="title">{{ $lang['wechat_menu'] }} - {{ $lang['share'] }}</div>
	<div class="content_tips">
        <div class="tabs_info">
            <ul>
                <li><a href="{{ route('admin/wechat/qrcode_list') }}">{{ $lang['qrcode'] }}</a></li>
                <li class="curr"><a href="{{ route('admin/wechat/share_list') }}">{{ $lang['share_qrcode'] }}</a></li>
            </ul>
        </div>
	    <div class="explanation" id="explanation">
            <div class="ex_tit"><i class="sc_icon"></i><h4>{{ $lang['operating_hints'] }}</h4><span id="explanationZoom" title="{{ $lang['fold_tips'] }}"></span></div>
            <ul>
				@if(isset($lang['share_tips']) && !empty($lang['share_tips']))

					@foreach($lang['share_tips'] as $v)
						<li>{!! $v !!}</li>
					@endforeach

				@endif
            </ul>
        </div>
		<div class="flexilist of">
			<div class="common-head">
                <div class="fl">
                	<a href="{{ route('admin/wechat/share_edit') }}" class="fancybox fancybox.iframe"><div class="fbutton"><div class="add " title="添加二维码"><span><i class="fa fa-plus"></i>{{ $lang['add'].$lang['share_qrcode'] }}</span></div></div></a>
                </div>
            </div>
			<div class="common-content">
				<div class="list-div">
	                <table cellspacing="0" cellpadding="0" border="0">
						<tr>
							<th width="15%"><div class="tDiv">{{ $lang['share_name'] }}</div></th>
							<th width="15%"><div class="tDiv">{{ $lang['share_account'] }}</div></th>
							<th width="10%"><div class="tDiv">{{ $lang['scan_num'] }}</div></th>
							<th width="5%"><div class="tDiv">{{ $lang['expire_seconds'] }}</div></th>
							<th width="15%"><div class="tDiv">{{ $lang['qrcode_function'] }}</div></th>
							<th width="5%"><div class="tDiv">{{ $lang['wechat_status'] }}</div></th>
                            <th width="5%"><div class="tDiv">{{ $lang['sort_order'] }}</div></th>
							<th ><div class="tDiv">{{ $lang['handler'] }}</div></th>
						</tr>
                        
@if($list)

	                    
@foreach($list as $key=>$val)

						<tr>
							<td><div class="tDiv">{{ $val['username'] }}</div></td>
							<td><div class="tDiv">
@if($val['share_account'])
{{ $val['share_account'] }}
@else
0
@endif
</div></td>
							<td><div class="tDiv">{{ $val['scan_num'] }}</div></td>
							<td><div class="tDiv">
@if($val['expire_seconds'])
{{ $val['expire_seconds'] }}
@else
{{ $lang['no_limit'] }}
@endif
</div></td>
							<td><div class="tDiv">{{ $val['function'] }}</div></td>
                            <td><div class="tDiv">
@if($val['status'] == 1)
 正常 
@else
 失效 
@endif
</div></td>
							<td><div class="tDiv">{{ $val['sort'] }}</div></td>
							<td class="handle">
								<div class="tDiv a3">
                                    
@if($val['status'] == 1)

                                        <a href="{{ route('admin/wechat/qrcode_get', array('id' => $val['id'])) }}" class="btn_region fancybox fancybox.iframe getqr"><i class="fa fa-qrcode"></i>{{ $lang['qrcode_get'] }}</a>
                                        <a href="{{ route('admin/wechat/share_edit', array('id' => $val['id'])) }}" class="btn_edit fancybox fancybox.iframe"><i class="fa fa-edit"></i>{{ $lang['wechat_editor'] }}</a>
                                    
@endif

									<a href="javascript:if(confirm('{{ $lang['confirm_delete'] }}')){window.location.href='{{ route('admin/wechat/qrcode_del', array('id'=>$val['id'])) }}'};" class="btn_trash"><i class="fa fa-trash-o"></i>{{ $lang['drop'] }}</a>
							    </div>
							</td>
						</tr>
						
@endforeach

                        
@else

                          <tbody>
                                 <tr><td class="no-records" colspan="8">{{ $lang['no_records'] }}</td></tr>
                          </tbody>
                        
@endif

						<tfoot>
                        	<tr>
                                <td colspan="8">
                                	@include('admin.wechat.pageview')
                                </td>
                            </tr>
                        </tfoot>
					</table>
			    </div>
			</div>
		</div>
    </div>
</div>
<script type="text/javascript">
$(function(){
	$(".getqr").click(function(){
		var url = $(this).attr("href");
	    $.get(url, '', function(data){
	        if(data.status <= 0 ){
                layer.msg(data.msg);
	        	$.fancybox.close();
	            return false;
		    }
		}, 'json');
	});
})
</script>

@include('admin.wechat.pagefooter')
