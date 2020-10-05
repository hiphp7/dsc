@include('admin.base.header')

<div class="warpper">
    <div class="title">{{ lang('admin/common.14_sms') }} - {{ lang('admin/sms.sms_list') }}</div>
    <div class="content">
        <div class="tabs_info">
            <ul>
                <li role="presentation" class="curr"><a href="{{ route('admin.sms.index') }}">{{ lang('admin/sms.sms_list') }}</a></li>
                <li role="presentation" ><a href="../admin/sms_setting.php?act=step_up">{{ lang('admin/sms.sms_setting') }}</a></li>
                <li role="presentation" ><a href="../admin/dscsms_configure.php?act=list">{{ lang('admin/sms.sms_template') }}</a></li>
            </ul>
        </div>

        <div class="explanation" id="explanation">
            <div class="ex_tit">
                <i class="sc_icon"></i><h4>{{ lang('admin/common.operating_hints') }}</h4><span id="explanationZoom" title="{{ lang('admin/common.fold_tips') }}"></span>
            </div>
            <ul>

                @foreach(lang('admin/sms.sms_list_tips') as $v)
                    <li>{!! $v !!}</li>
                @endforeach

            </ul>
        </div>

        <div class="flexilist">

            <div class="common-content">
                <div class="list-div">
                    <table cellspacing="0" cellpadding="0" border="0">
                        <tr>
                            <th class="text-center">{{ lang('admin/sms.sms_name') }}</th>
                            <th class="text-center">{{ lang('admin/sms.sms_desc') }}</th>
                            <th class="text-center">{{ lang('admin/sms.sms_website') }}</th>
                            <th class="text-center">{{ lang('admin/sms.is_use') }}</th>
                            <th class="text-center">{{ lang('admin/common.handler') }}</th>
                        </tr>

                        @if (isset($plugins) && !empty($plugins))

                        @foreach($plugins as $key => $val)

                            <tr>
                                <td  class="text-center">{{ $val['name'] }}</td>
                                <td  class="text-center">{{ $val['description']  ?? '' }}</td>
                                <td  class="text-center"><a href="{{ $val['website'] ?? '#' }}" target="_blank">{{ $val['website'] ?? '' }}</a></td>
                                <td  class="text-center">

                                        @if(isset($val['default']) && $val['default'] == 1)

                                            <span><i class="fa fa-toggle-on"></i>{{ lang('admin/sms.is_use_1') }}</span>

                                        @else

                                            <span><i class="fa fa-toggle-off"></i>{{ lang('admin/sms.is_use_0') }}</span>

                                        @endif

                                </td>
                                <td class="handle text-center">
                                    <div class="tDiv a2">

                                        @if(isset($val['default']) && $val['default'] == 1)

                                            <a href="{{ route('admin.sms.edit', ['code' => $val['code'], 'handler' => 'edit']) }}" class="btn_edit"><i class="fa fa-edit"></i>{{ lang('admin/common.setup') }}</a>

                                        @else

                                            @if(isset($val['enable']) && $val['enable'] == 1)

                                            <a href="{{ route('admin.sms.edit', ['code' => $val['code'], 'handler' => 'edit']) }}" class="btn_edit"><i class="fa fa-edit"></i>{{ lang('admin/sms.setup_use') }}</a>

                                            @else

                                            <a href="{{ route('admin.sms.edit',  ['code' => $val['code'], 'enable' => 'install']) }}" class="btn_inst" ><i class="sc_icon sc_icon_inst"></i>{{ lang('admin/common.install') }}</a>

                                            @endif

                                        @endif

                                    </div>

                                </td>
                            </tr>

                        @endforeach

                        @endif

                    </table>
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
            $(this).attr("title", "{{ lang('admin/common.fold_tips') }}");
            explanation.find(".ex_tit").css("margin-bottom", 10);
            explanation.animate({
                width: width - 0
            }, 300, function () {
                $(".explanation").find("ul").show();
            });
        } else {
            $(this).addClass("shopUp");
            explanation.find(".ex_tit").css("margin-bottom", 0);
            explanation.animate({
                width: "115"
            }, 300);
            explanation.find("ul").hide();
        }
    });
</script>

@include('admin.base.footer')