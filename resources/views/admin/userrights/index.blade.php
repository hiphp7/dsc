@include('admin.base.header')

<div class="warpper">
    <div class="title">{{ lang('admin/users.user_rights') }}</div>
    <div class="content">
        <div class="explanation" id="explanation">
            <div class="ex_tit">
                <i class="sc_icon"></i><h4>{{ lang('admin/common.operating_hints') }}</h4><span id="explanationZoom" title="{{ lang('admin/common.fold_tips') }}"></span>
            </div>
            <ul>

                @foreach(lang('admin/users.user_rights_index_tips') as $v)
                    <li>{!! $v !!}</li>
                @endforeach

            </ul>
        </div>

        <div class="flexilist">
            <div class="common-head mt10">
                <div class="fl">
                    <a href="{{ route('admin/user_rights/list') }}">
                        <button type="button" class="btn btn-info">{{ lang('admin/users.manage_rights') }}</button>
                    </a>
                </div>
            </div>

            @if (isset($plugins) && !empty($plugins))
            <div class="switch_info">
                <div class="common-content">
                    <div class="mkc_content">

                            @foreach($plugins as $key => $group)

                            <div class="mkc_dl">
                                <div class="mkc_dt">{{ lang('admin/users.rights_group_name_' . $key) }}</div>
                                <div class="mkc_dd">
                                    <ul>

                                        @foreach($group as $k => $vo)
                                            <li>
                                                <a href="{{ route('admin/user_rights/edit', array('code' => $vo['code'], 'handler' => 'edit')) }}" >
                                                    <em><img class="img-rounded" src="{{ $vo['icon'] ?? '' }}" /></em>
                                                    <div class="info">
                                                        <h2>{{ $vo['name'] }}</h2>
                                                        <span>{{ $vo['description'] }}</span>
                                                    </div>
                                                </a>
                                            </li>
                                        @endforeach

                                    </ul>
                                </div>
                            </div>

                            @endforeach

                    </div>
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