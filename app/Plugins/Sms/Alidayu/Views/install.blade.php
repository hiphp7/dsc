
<style>

</style>
<div class="warpper">
	<div class="title"><a href="{{ route('admin.sms.index') }}" class="s-back">{{ lang('common.back') }}</a> {{ $cfg['name'] }}</div>
	<div class="content">

		<div class="explanation" id="explanation">
			<div class="ex_tit">
				<i class="sc_icon"></i><h4>{{ lang('admin/common.operating_hints') }}</h4>
			</div>
			<ul>

				@foreach(lang('admin/sms.sms_edit_tips') as $v)
					<li>{!! $v !!}</li>
				@endforeach

			</ul>
		</div>

		<div class="flexilist">
			<div class="main-info">
				<form action="{{ route('admin.sms.edit', ['code' => $cfg['code']]) }}" method="post" role="form" class="form-horizontal" >
					<div class="switch_info">

						<div class="item_title">
							<div class="vertical"></div>
							<div class="f15">{{ lang('admin/sms.base_title') }}</div>
						</div>

					    <div class="item">
					        <div class="label-t">{{ lang('admin/sms.sms_name') }}：</div>
					        <div class="label_value"><input type="text" name="data[name]" class="text" value="{{ $cfg['name'] }}" readonly /></div>
					    </div>

						<div class="item">
							<div class="label-t">{{ lang('admin/sms.sms_desc') }}：</div>
							<div class="label_value">
								<textarea name="data[description]" class="textarea" rows="3">{{ $cfg['description'] ?? '' }}</textarea>
							</div>
						</div>

						@includeIf('admin/sms.sms_configure', ['sms_configure' => $cfg['sms_configure'] ?? ''])

						<div class="item">
							<div class="label-t">{{ lang('admin/sms.sort_order') }}：</div>
							<div class="label_value"><input type="number" min="0" name="data[sort]" class="text w100" value="{{ $cfg['sort'] ?? '50' }}" /></div>
						</div>

					    <div class="item">
					        <div class="label-t">&nbsp;</div>
					        <div class="label_value info_btn">
                                @csrf
                                <input type="hidden" name="code" value="{{ $cfg['code'] }}" />
                                <input type="hidden" name="handler" value="{{ $cfg['handler'] ?? '' }}">
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

    //file移动上去的js
    $(".type-file-box").hover(function () {
        $(this).addClass("hover");
    }, function () {
        $(this).removeClass("hover");
    });

    //弹出框
    $(".fancybox").fancybox({
        width: '60%',
        height: '60%',
        closeBtn: true,
        title: ''
    });


	// 验证提交
	$(".form-horizontal").submit(function(){

        var cfg_value;

        var checkbox = $('input[name="cfg_value[]"]');
        for (var i = 0; i < checkbox.length; i++) {

            cfg_value = $('input[name="cfg_value[]"]:eq('+i+')').val();

            if (!cfg_value) {
                layer.msg('{{ lang('admin/sms.cfg_value_empty') }}');
                return false;
			}

		}

	});
</script>
