@include('admin.base.header')

<div class="warpper">
    <div class="title"><a href="{{ route('admin/user_rights/index') }}" class="s-back">{{ lang('common.back') }}</a> {{ lang('admin/users.manage_rights') }}</div>
    <div class="content">
        <div class="explanation" id="explanation">
            <div class="ex_tit">
                <i class="sc_icon"></i><h4>{{ lang('admin/common.operating_hints') }}</h4><span id="explanationZoom" title="{{ lang('admin/common.fold_tips') }}"></span>
            </div>
            <ul>

                @foreach(lang('admin/users.user_rights_tips') as $v)
                    <li>{!! $v !!}</li>
                @endforeach

            </ul>
        </div>

        <div class="flexilist">

            @if (isset($plugins) && !empty($plugins))

            <div class="switch_info">
                <div class="mkc_content rights-list">

                        @foreach($plugins as $key => $group)

                            <div class="mkc_dl">
                                <div class="mkc_dt">{{ lang('admin/users.rights_group_name_' . $key) }}</div>
                                <div class="">
                                    <ul class="items-box">

                                        @if (isset($group) && !empty($group))

                                            @foreach($group as $key => $vo)

                                                <li class="item_wrap">
                                                    <div class="plugin_item" style="clear:both">
                                                        <div class="plugin_icon">
                                                            <img class="img-rounded" src="{{ $vo['icon'] ?? '' }}" alt="">
                                                        </div>
                                                        <div class="plugin_status">
                                                            <span class="status_txt">
                                                                <div class="list-div">
                                                                    <div class="handle">
                                                                        <div class="tDiv">

                                                                            @if(isset($vo['install']) && $vo['install'] == 1)
                                                                                <a href="{{ route('admin/user_rights/edit', array('code' => $vo['code'], 'handler' => 'edit')) }}" class="btn_edit bg-blue btn_edit_rights" ><i class="fa fa-edit"></i>{{ lang('admin/common.edit') }}</a>
                                                                                <a href="javascript:;" data-href="{{ route('admin/user_rights/uninstall', array('code' => $vo['code'])) }}" class="btn_trash bg-red btn_uninstall_rights js-uninstall"><i class="fa fa-trash-o"></i>{{ lang('admin/common.uninstall') }}</a>

                                                                            @else

                                                                                <a href="{{ route('admin/user_rights/edit', array('code' => $vo['code'], 'handler' => 'install')) }}" class="btn_inst bg-blue btn_install_rights"><i class="fa fa-cog"></i>{{ lang('admin/common.install') }}</a>

                                                                            @endif

                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </span>
                                                        </div>
                                                        <div class="plugin_content">
                                                            <h3 class="title">{{ $vo['name'] }}</h3>
                                                            <p class="desc">{{ $vo['description'] }}</p>
                                                        </div>
                                                    </div>
                                                </li>

                                            @endforeach

                                        @endif

                                    </ul>
                                </div>
                            </div>

                        @endforeach

                </div>
            </div>

            @endif

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

    // 卸载
    $(".js-uninstall").click(function(){
        var url = $(this).attr("data-href");
        //询问框
        layer.confirm('{{ lang('admin/users.confirm_uninstall')}}', {
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


    $("#explanationZoom").on("click", function () {
        var explanation = $(this).parents(".explanation");
        var width = $(".content").width();
        if ($(this).hasClass("shopUp")) {
            $(this).removeClass("shopUp");
            $(this).attr("title", "{{ lang('admin/common.fold_tips') }}");
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