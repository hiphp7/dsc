@include('admin.drp.pageheader')

<style>

</style>
<div class="wrapper">
	<div class="title"><a href="{{ route('admin/drp/shop') }}" class="s-back">{{ lang('common.back') }}</a> {{ lang('admin/drp.edit_shop') }} - {{ $info['user_name'] ?? '' }} </div>
	<div class="content_tips">

		<div class="explanation" id="explanation">
			<div class="ex_tit">
				<i class="sc_icon"></i><h4>{{ lang('admin/common.operating_hints') }}</h4>
			</div>
			<ul>

				@foreach(lang('admin/drp.edit_shop_tips') as $v)
					<li>{!! $v !!}</li>
				@endforeach

			</ul>
		</div>

		<div class="flexilist">
			<div class="main-info add-shop">
				<form action="{{ route('admin/drp/edit_shop') }}" method="post" role="form" class="form-horizontal" >
					<div class="switch_info">

						{{--基本信息--}}
						<div class="item_title">
							<div class="vertical"></div>
							<div class="f15">{{ lang('admin/users.base_title') }}</div>
						</div>

                        <div class="item js-user-info">
                            <div class="label-t">{{ lang('admin/drp.user_info') }}：</div>
                            <div class="label_value">
                                <div class="drp-user-info w300">
                                    <div class="drp-img fl">
                                        <img class="img-rounded" src="{{ $info['user_picture'] ?? '' }}" width="50" height="50"/>
                                    </div>
                                    <div class="drp-name fl">
                                        {{ $info['nick_name'] ?? '' }} <br>  {{ $info['mobile_phone'] ?? '' }}
                                    </div>
                                    <div class="drp-rank fl">
                                        {{ $info['rank_name'] ?? '' }}
                                    </div>
                                </div>
                                <div class=""><a href="../users.php?act=edit&id={{ $info['user_id'] ?? 0 }}">{{ lang('user.profile') }}</a> </div>
                            </div>
                        </div>

                        <div class="item">
                            <div class="label-t"><em class="color-red"> * </em>{{ lang('admin/drp.choose_membership_card') }}</div>

                            <div class="label_value">
                                <select name="data[membership_card_id]" class="form-control w300 " id="membership_card_id">

                                    <option value="">{{ lang('admin/common.please_select') }}</option>
                                    @if(isset($card_list))
                                        @foreach($card_list as $options)

                                            <option value="{{ $options['id'] }}" @if(isset($info['membership_card_id']) && $info['membership_card_id'] == $options['id']) selected  @endif>{{ $options['name'] }}</option>

                                        @endforeach
                                    @endif

                                </select>
                                <div class="notice">{!! lang('admin/drp.choose_membership_card_notice') !!}</div>
                            </div>

                        </div>

                        <div class="item">
                            <div class="label-t">{{ lang('admin/drp.drp_cfg_name.register') }}：</div>
                            <div class="label_value">
                                <div class="checkbox_items">
                                    <div class="checkbox_item">
                                        <input type="radio" name="data[status]" class="ui-radio event_zhuangtai" id="value_121_1" value="1" checked="true"
                                               @if(isset($info['status']) && $info['status'] == 1)
                                               checked
                                                @endif
                                        >
                                        <label for="value_121_1" class="ui-radio-label
												@if(isset($info['status']) && $info['status'] == 1)
                                                active
                                                @endif
                                                ">{{ lang('admin/common.open') }}</label>
                                    </div>
                                    <div class="checkbox_item">
                                        <input type="radio" name="data[status]" class="ui-radio event_zhuangtai" id="value_121_0" value="0"
                                               @if(isset($info['status']) && empty($info['status']))
                                               checked
                                                @endif
                                        >
                                        <label for="value_121_0" class="ui-radio-label
												@if(isset($info['status']) && empty($info['status']))
                                                active
                                                @endif
                                                ">{{ lang('admin/common.close') }}</label>
                                    </div>
                                </div>
                                <div class="notice">{{ lang('admin/drp.drp_cfg_notice.register') }}</div>
                            </div>
                        </div>

					    <div class="item">
					        <div class="label-t">&nbsp;</div>
					        <div class="label_value info_btn">
                                @csrf
                                <input type="hidden" name="user_id" value="{{ $info['user_id'] ?? 0 }}" />
                                <input type="submit" class="button btn-danger bg-red" value="{{ lang('admin/common.button_submit') }}" />
					        </div>
					    </div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>


<script type="text/javascript">
    //弹出框
    $(".fancybox").fancybox({
        width: '60%',
        height: '60%',
        closeBtn: true,
        title: ''
    });


	// 验证提交
	$(".form-horizontal").submit(function(){

        var user_id = $('input[name="user_id"]').val();

        var membership_card_id = $('#membership_card_id').val();

        if (!user_id) {
            layer.msg('{{ lang('admin/drp.search_user_name_not') }}');
            return false;
        }

        if (!membership_card_id) {
            layer.msg('{{ lang('admin/drp.membership_card_empty') }}');
            return false;
        }

	});
</script>

@include('admin.drp.pagefooter')