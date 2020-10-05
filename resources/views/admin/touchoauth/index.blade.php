@include('admin.base.header')

<div class="warpper">
    <div class="title">{{ $lang['touch_list'] }}</div>
    <div class="content">
        <div class="explanation" id="explanation">
            <div class="ex_tit">
                <i class="sc_icon"></i><h4>{{ $lang['operating_hints'] }}</h4><span id="explanationZoom" title="{{ $lang['fold_tips'] }}"></span>
            </div>
            <ul>
                @if(isset($lang['touch_list_tips']) && !empty($lang['touch_list_tips']))

                    @foreach($lang['touch_list_tips'] as $v)
                        <li>{!! $v !!}</li>
                    @endforeach

                @endif
            </ul>
        </div>

        <div class="flexilist">
        <div class="switch_info">
            <div class="wrapper-content" style="margin-top:20px;">
                <ul class="items-box">

                @foreach($modules as $key => $vo)

                    <li class="item_wrap">
                        <div class="plugin_item" style="clear:both">
                            <div class="plugin_icon">
                                <img src="{{ asset('assets/mobile/img/oauth/sns_' . $vo['type'] . '.png') }}" alt="">
                            </div>
                            <div class="plugin_status">
                        	<span class="status_txt">
	                        	<div class="list-div">
	                        		<div class="handle">
	                        			<div class="tDiv">

                                            @if(isset($vo['install']) && $vo['install'] == 1)

                                                <a href="{{ route('admin/touch_oauth/edit', array('type'=>$vo['type'])) }}" class="btn_edit"><i class="fa fa-edit"></i>{{ $lang['edit'] }}</a>
                                                <a href="{{ route('admin/touch_oauth/uninstall', array('type'=>$vo['type'])) }}" class="btn_trash"><i class="fa fa-trash-o"></i>{{ $lang['uninstall'] }}</a>

                                            @else

                                                <a href="{{ route('admin/touch_oauth/install', array('type'=>$vo['type'])) }}" class="btn_inst"><i class="sc_icon sc_icon_inst"></i>{{ $lang['install'] }}</a>

                                            @endif

	                        			</div>
	                        		</div>
	                        	</div>
                        	</span>
                            </div>
                            <div class="plugin_content"><h3 class="title">{{ $vo['name'] }}</h3>
                                <p class="desc">{{ $lang['version'] }}:{{ $vo['version'] }}</p></div>
                        </div>
                    </li>

                @endforeach

                </ul>
            </div>
        </div>
        </div>
    </div>
</div>
<script>
    $(document).on("mouseenter", ".list-div tbody td", function () {
        $(this).parents("tr").addClass("tr_bg_blue");
    });

    $(document).on("mouseleave", ".list-div tbody td", function () {
        $(this).parents("tr").removeClass("tr_bg_blue");
    });


    $("#explanationZoom").on("click", function () {
        var explanation = $(this).parents(".explanation");
        var width = $(".content").width();
        if ($(this).hasClass("shopUp")) {
            $(this).removeClass("shopUp");
            $(this).attr("title", "{{ $lang['fold_tips'] }}");
            explanation.find(".ex_tit").css("margin-bottom", 10);
            explanation.animate({
                width: width - 0
            }, 300, function () {
                $(".explanation").find("ul").show();
            });
        } else {
            $(this).addClass("shopUp");
            $(this).attr("title", "提示相关设置操作时应注意的要点");
            explanation.find(".ex_tit").css("margin-bottom", 0);
            explanation.animate({
                width: "115"
            }, 300);
            explanation.find("ul").hide();
        }
    });
</script>
@include('admin.base.footer')