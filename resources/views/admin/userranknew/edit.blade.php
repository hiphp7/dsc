@include('admin.base.header')
<style>
    .checkbox {display: inline-block;}
</style>
	<div class="warpper">
    	<div class="title"><a href="{{ $action_link['href'] }}" class="s-back">{{ lang('common.back') }}</a>{{ lang('admin/user_rank.user') }} - {{ lang('admin/user_rank.edit_user_rank') }}</div>
        <div class="content">
        	<div class="explanation" id="explanation">
                <div class="ex_tit"><i class="sc_icon"></i><h4>{{ lang('admin/common.operating_hints') }}</h4><span id="explanationZoom" title="{$lang.fold_tips}"></span></div>
                <ul>
                    <li>{!! lang('admin/common.operation_prompt_content_common') !!}</li>
                </ul>
            </div>
            <div class="flexilist">
                <div class="common-content">
                    <div class="main-info">
                        <form action="{{ route('admin/user_rank/edit') }}" method="post" name="theForm" id="user_rank_form">
                        	<div class="switch_info">
                                <div class="item">
                                    <div class="label-t"><em class="color-red">*</em>&nbsp;{{ lang('admin/user_rank.rank_name') }}：</div>
                                    <div class="label_value">
                                        <input type="text" name="rank_name" value="{{ $rank['rank_name'] }}" class="text" id="rank_name" autocomplete="off" />
                                        <div class="form_prompt"></div>
                                    </div>
                                </div>
                                <div class="item">
                                    <div class="label-t"><em class="color-red">*</em>&nbsp;{{ lang('admin/user_rank.integral_min') }}：</div>
                                    <div class="label_value">
                                        <input type="text" name="min_points" value="{{ $rank['min_points'] }}" class="text" id="min_points" autocomplete="off" />
                                        <p class="notic">{{ lang('admin/user_rank.integral_min_notice') }}</p>
                                    </div>
                                </div>
                                <div class="item">
                                    <div class="label-t">{{ lang('admin/user_rank.set_show') }}：</div>
                                    <div class="label_value">
                                        <div class="checkbox_items">
                                            <div class="checkbox_item">
                                                <input type="checkbox" class="checkbox" value='1' name="show_price"
                                                       @if(isset($rank['show_price']) && $rank['show_price'] == 1)checked @endif
                                                id="checkbox_001"/>
                                                <label for="checkbox_001" class="ui-label">{{ lang('admin/user_rank.show_price') }}</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="item">
                                    <div class="label-t">{{ lang(('admin/user_rank.special_rank')) }}：</div>
                                    <div class="label_value">
                                        <div class="checkbox_items">
                                            <div class="checkbox_item">
                                                <input type="checkbox" class="checkbox" value='1' name="special_rank"
                                                       @if(isset($rank['special_rank']) && $rank['special_rank'] == 1)checked @endif
                                                       id="checkbox_002"/>
                                                <label for="checkbox_002" class="ui-label">{{ lang('admin/user_rank.special_rank') }}</label>
                                            </div>
                                        </div>
                                        <p class="notic">{{ lang('admin/user_rank.notice_special') }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class=" bind-rights pt20">

                                {{--添加会员权益--}}
                                <a href="{{ route('admin/user_rank/bind_rights', ['rank_id' => $rank['rank_id']]) }}" class="">
                                    <div class="fbutton"><div class="add "><span><i class="fa fa-plus"></i>{{ lang('admin/drpcard.bind_rights') }}</span></div></div>
                                </a>

                                <div class="list-div pt20" id="listDiv">
                                    <table cellspacing="0" cellpadding="0" border="0" class="table table-striped">
                                        <tr class="active">
                                            <th>
                                                <div class="tDiv">{{ lang('admin/users.rights_icon') }}</div>
                                            </th>
                                            <th>
                                                <div class="tDiv">{{ lang('admin/users.rights_name') }}</div>
                                            </th>
                                            <th width="30%">
                                                <div class="tDiv">{{ lang('admin/users.trigger_content') }}</div>
                                            </th>
                                            <th >
                                                <div class="tDiv">{{ lang('admin/users.trigger_point') }}</div>
                                            </th>
                                            <th width="15%">
                                                <div class="tDiv">{{ lang('admin/common.handler') }}</div>
                                            </th>
                                        </tr>

                                        @if(isset($rights_list) && $rights_list)

                                            @foreach($rights_list as $value)

                                                <tr>
                                                    <td>
                                                        <div class="tDiv"><img class="img-rounded" src="{{ $value['icon'] ?? '' }}" width="50" height="50" ></div>
                                                    </td>
                                                    <td>
                                                        <div class="tDiv">{{ $value['name'] ?? '' }}</div>
                                                    </td>
                                                    <td>
                                                        <div class="tDiv @if($value['enable'] == 0)li_color @endif">{{ $value['rights_configure_format'] ?? '' }}</div>
                                                    </td>

                                                    <td>
                                                        <div class="tDiv">{{ $value['trigger_point_format'] ?? '' }}</div>
                                                    </td>
                                                    <td class="handle">
                                                        <div class="tDiv">
                                                            @if($value['enable'] == 1)
                                                                <a href="{{ route('admin/user_rank/edit_rights', ['id' => $value['id']]) }}" class="btn_edit"><i class="fa fa-edit"></i>{{ lang('admin/common.edit') }}</a>
                                                            @else
                                                                <a href="javascript:;" title="{{ $value['rights_configure_format'] ?? '' }}" class="btn_edit"><i class="fa fa-edit"></i>{{ lang('admin/common.edit') }}</a>
                                                            @endif
                                                            <a href="javascript:;" data-href="{{ route('admin/user_rank/unbind_rights', ['id' => $value['id']]) }}" class="btn_trash js-unbind-rights"><i class="fa fa-trash-o"></i>{{ lang('admin/common.drop') }}</a>
                                                        </div>
                                                    </td>
                                                </tr>

                                            @endforeach

                                        @else

                                            <tbody>
                                            <tr>
                                                <td class="no-records" colspan="5">{{ lang('admin/common.no_records') }}</td>
                                            </tr>
                                            </tbody>

                                        @endif

                                    </table>
                                </div>
                            </div>

                            <div class="item">
                                <div class="label-t">&nbsp;</div>
                                <div class="label_value info_btn">
                                    @csrf
                                    <input type="hidden" name="id" value="{{ $rank['rank_id'] ?? '' }}"/>
                                    <input type="submit" value="{{ lang('admin/common.button_submit_alt') }}" class="button btn-danger bg-red"/>
                                    <input type="button" value="{{ lang('admin/common.drop') }}" class="button btn-info js-drop-rank"/>
                                </div>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
		</div>
    </div>
<script src="{{ asset('/assets/admin/js/jquery.validation.min.js') }}"></script>
    <script type="text/javascript">

		$(function(){
			$("#submitBtn").click(function(){
				var minval = Number($.trim($("#min_points").val()));

				if($("#user_rank_form").valid()){
					//防止表单重复提交
                    if(checkSubmit() == true){
                        $("#user_rank_form").submit();
                    }
                    return false;
				}
			});
		
			$('#user_rank_form').validate({
				errorPlacement:function(error, element){
					var error_div = element.parents('div.label_value').find('div.form_prompt');
					element.parents('div.label_value').find(".notic").hide();
					error_div.append(error);
				},
				rules : {
					rank_name : {
						required : true
					},
					min_points : {
						digits : true
					},

				},
				messages : {
					rank_name : {
						required : '<i class="icon icon-exclamation-sign"></i>'+ '{{ lang('admin/user_rank.js_languages.rank_name_empty') }}'
					},
					min_points : {
						digits : '<i class="icon icon-exclamation-sign"></i>'+ '{{ lang('admin/user_rank.js_languages.integral_min_invalid') }}'
					},
				}
			});

            // 删除权益
            $(".js-unbind-rights").click(function () {
                var url = $(this).attr("data-href");
                //询问框
                layer.confirm('{{ lang('admin/drpcard.confirm_unbind_rights')}}', {
                    btn: ['{{ lang('admin/common.ok') }}', '{{ lang('admin/common.cancel')}}'] //按钮
                }, function(){
                    $.post(url, '', function(data){
                        layer.msg(data.msg);
                        if (data.error == 0 ) {
                            if (data.url) {
                                window.location.href = data.url;
                            } else {
                                window.location.reload();
                            }
                        }
                        return false;
                    }, 'json');
                });
            });

            // 删除
            $(".js-drop-rank").click(function () {
                var url = "{{ route('admin/user_rank/delete') }}";

                var id = $('input[name="id"]').val();
                //询问框
                layer.confirm('{{ lang('admin/user_rank.drop_confirm_rank')}}', {
                    btn: ['{{ lang('admin/common.ok') }}', '{{ lang('admin/common.cancel')}}'] //按钮
                }, function(){
                    $.post(url, {
                        id:id
                    }, function(data){
                        layer.msg(data.msg);
                        if (data.error == 0 ) {
                            if (data.url) {
                                window.location.href = data.url;
                            } else {
                                window.location.reload();
                            }
                        }
                        return false;
                    }, 'json');
                });
            });
		});
	</script>
@include('admin.base.footer')
