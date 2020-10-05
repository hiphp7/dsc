@include('admin.app.pageheader')

<div class="wrapper">
    <div class="title">{{ lang('admin/app.menu') }} - {{ lang('admin/app.ad_info') }}</div>
    <div class="content_tips">
        <div class="explanation" id="explanation">
            <div class="ex_tit"><i class="sc_icon"></i><h4>{{ lang('admin/common.operating_hints') }}</h4><span
                        id="explanationZoom" title="{{ lang('admin/common.fold_tips') }}"></span></div>
            <ul>
                <li>{{ lang('admin/app.ad_info_tips.0') }}</li>
            </ul>
        </div>

        <div class="flexilist">
            <div class="common-content">
                <div class="main-info">

                    <form action="{{ route('admin/app/update_ads') }}" method="post" enctype="multipart/form-data"
                          role="form">
                        <div class="switch_info">

                            <div class="item">
                                <div class="label-t"><em class="color-red">*</em> {{ lang('admin/app.ad_name') }}：</div>
                                <div class="label_value">
                                    <input type="text" name="ad_name" class="text"
                                           value="{{ $ads_info['ad_name'] ?? '' }}" autocomplete="off"/>
                                    <div class="notic m20">{{ lang('admin/app.ad_name_notic') }}</div>
                                    <div class="form_prompt"></div>
                                </div>
                            </div>

                            <div class="item @if( !empty($ads_info['ad_id']) ) hide @endif">
                                <div class="label-t">{{ lang('admin/app.media_type') }}：</div>
                                <div class="label_value">
                                    <div class="date-item">
                                        <select name="media_type" class="text media_type_select">
                                            <option value="">{{ lang('admin/app.select_please') }}</option>

                                            <option value="0"
                                                    @if(isset($ads_info['media_type']) && $ads_info['media_type'] == '0')
                                                    selected
                                                    @endif
                                            >{{ lang('admin/app.ad_img') }}</option>

                                            <option value="3"
                                                    @if(isset($ads_info['media_type']) && $ads_info['media_type'] == '3')
                                                    selected
                                                    @endif
                                            >{{ lang('admin/app.ad_text') }}</option>

                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="item">
                                <div class="label-t">{{ lang('admin/app.position_name') }}：</div>
                                <div class="label_value">
                                    <div class="date-item ">

                                        <select name="position_id" class="text">
                                            <option value="">{{ lang('admin/app.select_please') }}</option>

                                            @if($position_list)

                                                @foreach($position_list as $key => $list)
                                                    <option value="{{ $list['position_id'] }}"
                                                            @if((isset($ads_info['position_id']) && $ads_info['position_id'] == $list['position_id']) || $position_id == $list['position_id'])
                                                            selected
                                                            @endif
                                                    >{{ $list['position_name_format'] }}</option>
                                                @endforeach

                                            @endif

                                        </select>

                                    </div>
                                </div>
                            </div>

                            @if( (isset($ads_info['media_type']) && $ads_info['media_type'] == '0') || empty($ads_info['ad_id']) )

                                <div id="0">
                                    <div class="item">
                                        <div class="label-t">{{ lang('admin/app.ad_link') }}：</div>
                                        <div class="label_value">
                                            <input type="text" name="ad_link" class="text"
                                                   value="{{ $ads_info['ad_link'] ?? '' }}" autocomplete="off"/>
                                        </div>
                                    </div>
                                    <div class="item">
                                        <div class="label-t">{{ lang('admin/app.upfile_img') }}：</div>
                                        <div class="label_value">
                                            <div class="type-file-box">
                                                <input type="button" name="button" id="button" class="type-file-button"
                                                       value=""/>
                                                <input type="file" class="type-file-file" name="pic"
                                                       data-state="imgfile" size="30" hidefocus="true"/>
                                                <span class="show">
                                                <a href="#inline" class="nyroModal fancybox"
                                                   title="{{ $lang['preview'] }}">
                                                    <i class="fa fa-picture-o"></i>
                                                </a>
                                            </span>
                                                <input type="text" name="file_path" class="type-file-text hide"
                                                       value="{{ $ads_info['ad_code'] ?? '' }}" id="textfield"
                                                       autocomplete="off" readonly/>
                                            </div>
                                            <div class="notic m20">{{ lang('admin/app.upfile_img_notice') }}</div>
                                        </div>
                                    </div>
                                    <div class="item">
                                        <div class="label-t">{{ lang('admin/app.img_url') }}：</div>
                                        <div class="label_value">
                                            <input type='text' name='img_url' value="{{ $url_src ?? '' }}" class="text"
                                                   autocomplete="off"/>
                                        </div>
                                    </div>
                                </div>

                            @endif

                            @if( (isset($ads_info['media_type']) && $ads_info['media_type'] == '3') || empty($ads_info['ad_id']))

                                <div id="3" style="
                                @if( (isset($ads_info['media_type']) && $ads_info['media_type'] != '3') || empty($ads_info['ad_id']))
                                        display:none
                                @endif ">
                                    <div class="item">
                                        <div class="label-t">{{ lang('admin/app.ad_link') }}：</div>
                                        <div class="label_value">
                                            <input type="text" name="ad_link" class="text"
                                                   value="{{ $ads_info['ad_link'] ?? '' }}" autocomplete="off"/>
                                        </div>
                                    </div>
                                    <div class="item">
                                        <div class="label-t">{{ lang('admin/app.ad_content') }}：</div>
                                        <div class="label_value">
                                            <textarea name="ad_code" cols="60" rows="4"
                                                      class="textarea">{{ $ads_info['ad_code'] ?? '' }}</textarea>
                                        </div>
                                    </div>
                                </div>

                            @endif

                            <div class="item">
                                <div class="label-t">{{ lang('admin/app.sort_order') }}：</div>
                                <div class="label_value">
                                    <input type="text" name="sort_order" class="text"
                                           value="{{ $ads_info['sort_order'] ?? '0' }}" autocomplete="off"/>
                                    <div class="form_prompt"></div>
                                </div>
                            </div>

                            <div class="item">
                                <div class="label-t">{{ lang('admin/app.is_show') }}：</div>
                                <div class="label_value">
                                    <div class="checkbox_items">
                                        <div class="checkbox_item">
                                            <input type="radio" class="ui-radio" name="enabled" id="sex_1" value="1"
                                                   @if(isset($ads_info['enabled']) && $ads_info['enabled'] == 1 || empty($ads_info['ad_id']))
                                                   checked="true"
                                                    @endif
                                            />
                                            <label for="sex_1"
                                                   class="ui-radio-label">{{ lang('admin/app.yes') }}</label>
                                        </div>
                                        <div class="checkbox_item">
                                            <input type="radio" class="ui-radio" name="enabled" id="sex_0" value="0"
                                                   @if(isset($ads_info['enabled']) && $ads_info['enabled'] == 0)
                                                   checked="true"
                                                    @endif
                                            />
                                            <label for="sex_0" class="ui-radio-label">{{ lang('admin/app.no') }}</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="item">
                                <div class="label-t">&nbsp;</div>
                                <div class="label_value info_btn">
                                    @csrf
                                    <input type="hidden" name="ad_id" value="{{ $ads_info['ad_id'] ?? '' }}"/>
                                    <input type="submit" value="{{ lang('admin/app.button_submit') }}"
                                           class="button btn-danger bg-red"/>
                                </div>
                            </div>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>

</div>

@if(isset($ads_info['media_type']) && $ads_info['media_type'] == '0')
    <div class="panel panel-default" style="display: none;" id="inline">
        <div class="panel-body">
            <img src="{{ $ads_info['ad_code'] ?? '' }}" class="img-responsive"/>
        </div>
    </div>
@endif
<script type="text/javascript">

    $(function () {

        // 切换选择类型
        $('.media_type_select').change(function () {
            var val = $(this).children('option:selected').val(); //是selected的值

            showMedia(val);
        })
    });


    var MediaList = new Array('0', '1', '2', '3');
    function showMedia(val) {
        for (I = 0; I < MediaList.length; I++) {
            if (MediaList[I] == val) {
                $("#" + val).css("display", "block");
            } else {
                $("#" + MediaList[I]).css("display", "none")
            }
        }
    }

</script>
@include('admin.app.pagefooter')
