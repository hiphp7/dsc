@include('admin.team.admin_pageheader')

<div class="wrapper">
    <div class="title">{{ $page_title }}</div>
    <div class="content_tips">
        <div class="tabs_info">
            <ul>
                <li><a href="{{ route('admin/team/index') }}">{{ $lang['team_goods'] }}</a></li>
                <li class="curr"><a href="{{ route('admin/team/category') }}">{{ $lang['team_category'] }}</a></li>
                <li><a href="{{ route('admin/team/teaminfo') }}">{{ $lang['team_info'] }}</a></li>
                <li style="display:none"><a href="{{ route('admin/team/teamrecycle') }}">{{ $lang['team_recycle'] }}</a>
                </li>
            </ul>
        </div>
        <div class="explanation" id="explanation">
            <div class="ex_tit"><i class="sc_icon"></i><h4>{{ $lang['operating_hints'] }}</h4><span id="explanationZoom"
                                                                                                    title="{{ $lang['fold_tips'] }}"></span>
            </div>
            <ul>
                <li>{{ $lang['team_category_add_tips']['0'] }}</li>
                <li>{{ $lang['team_category_add_tips']['1'] }}</li>
            </ul>
        </div>
        <div class="flexilist">
            <div class="main-info">
                <form method="post" action="{{ route('admin/team/addcategory') }}" enctype="multipart/form-data"
                      class="form-horizontal" role="form">
                    <div class="switch_info">
                        <div class="item">
                            <div class="label-t">{{ $lang['team_category_name'] }}</div>
                            <div class="label_value">
                                <input type="text" id="name" name="data[name]" class="text"
                                       value="{{ $cat_info['name'] ?? '' }}">
                                <p class="notic">{{ $lang['team_category_name_notice'] }}</p>
                            </div>

                        </div>
                        <div class="item">
                            {{--上级频道--}}
                            <div class="label-t">{{ $lang['team_category_parent'] }}</div>
                            <div class="label_value">
                                <select name="data[parent_id]" class="text">
                                    <option value='0'>{{ $lang['please_select_parent_category'] }}</option>

                                    @foreach($cat_select as $cat)

                                        <option value="{{ $cat['id'] }}"
                                                @if(isset($cat_info['parent_id']) && $cat_info['parent_id'] == $cat['id'] )
                                                selected
                                                @endif
                                        >{{ $cat['name'] }}</option>

                                    @endforeach

                                </select>
                                <p class="notic">{{ $lang['select_parent_category_notice'] }}</p>
                            </div>
                        </div>
						
                        <div class="item">
                            <div class="label-t">{{ $lang['team_category_icon'] }}：</div>
                            <div class="label_value">
                                <input class="text" type="file" value="" name="tc_img" class="form-control input-sm">
								<p class="notic">{{ $lang['team_category_icon_application'] }}</p>
                                @if(isset($cat_info['tc_img']) && $cat_info['tc_img'] && $cat_info['parent_id'] > 0)
                                    <div>
                                        <p class="label_value">
                                            <img style="width: 80px;height: 80px" src="{{ $cat_info['tc_img'] }}">
                                        </p>
                                    </div>
                                @endif								
                            </div>
                        </div>
						
                        <div class="item">
                            <div class="label-t">{{ $lang['team_content'] }}</div>
                            <div class="label_value">
                                <textarea name="data[content]" class="textarea"
                                          rows="5">{{ $cat_info['content'] ?? '' }}</textarea>
                            </div>
                        </div>
                        <div class="item">
                            <div class="label-t">{{ $lang['sort_order'] }}</div>
                            <div class="label_value">
                                <input type="text" name="data[sort_order]" class="text"
                                       value="{{ $cat_info['sort_order'] ?? '' }}">
                            </div>
                        </div>
                        <div class="item">
                            <div class="label-t">{{ $lang['is_show'] }}</div>
                            <div class="label_value">
                                <div class="type-file-box">
                                    <div class="checkbox_items">
                                        <div class="checkbox_item">
                                            <input type="radio" name="data[status]" value="1"
                                                   @if(isset($cat_info['status']) && $cat_info['status']==1)
                                                   checked
                                                    @endif
                                            >
                                            <label class="">{{ $lang['yes'] }}</label>
                                        </div>
                                        <div class="checkbox_item">
                                            <input type="radio" name="data[status]" value="0"
                                                   @if(isset($cat_info['status']) && $cat_info['status']==0)
                                                   checked
                                                    @endif
                                            >
                                            <label class="">{{ $lang['no'] }}</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="item">
                            <div class="label-t">&nbsp;</div>
                            <div class="lable_value info_btn">
                                @csrf
                                <input type="hidden" name="data[id]" value="{{ $cat_info['id'] ?? '' }}">
                                <input type="hidden" name="parent_id1" value="{{ $cat_info['parent_id'] ?? '' }}">
                                <input type="submit" name="submit" value="{{ $lang['button_submit'] }}" class="button"
                                       style="margin:0 auto;">
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
    $("#explanationZoom").on("click", function () {
        var explanation = $(this).parents(".explanation");
        var width = $(".content_tips").width();
        if ($(this).hasClass("shopUp")) {
            $(this).removeClass("shopUp");
            $(this).attr("title", "{{ $lang['fold_tips'] }}");
            explanation.find(".ex_tit").css("margin-bottom", 10);
            explanation.animate({
                width: width
            }, 300, function () {
                $(".explanation").find("ul").show();
            });
        } else {
            $(this).addClass("shopUp");
            $(this).attr("title", "提示相关设置操作时应注意的要点");
            explanation.find(".ex_tit").css("margin-bottom", 0);
            explanation.animate({
                width: "118"
            }, 300);
            explanation.find("ul").hide();
        }
    });

</script>
@include('admin.base.footer')
