@include('admin.app.pageheader')

<div class="wrapper">
    <div class="title">{{ lang('admin/app.menu') }} - {{ lang('admin/app.ad_position_info') }}</div>
    <div class="content_tips">
        <div class="explanation" id="explanation">
            <div class="ex_tit"><i class="sc_icon"></i><h4>{{ lang('admin/common.operating_hints') }}</h4><span
                        id="explanationZoom" title="{{ lang('admin/common.fold_tips') }}"></span></div>
            <ul>
                <li>{{ lang('admin/app.ad_position_tips.0') }}</li>
            </ul>
        </div>

        <div class="flexilist">
            <div class="common-content">
                <div class="main-info">
                    <form action="{{ route('admin/app/update_position') }}" method="post" enctype="multipart/form-data"
                          role="form">
                        <div class="switch_info">
                            <div class="item">
                                <div class="label-t"><em class="color-red">*</em> {{ lang('admin/app.position_name') }}：
                                </div>
                                <div class="label_value">
                                    <input type="text" name="position_name" class="text"
                                           value="{{ $position_info['position_name'] ?? '' }}" id="position_name"
                                           autocomplete="off"/>
                                    <div class="form_prompt"></div>
                                </div>
                            </div>

                            <div class="item">
                                <div class="label-t">{{ lang('admin/app.position_type') }}：</div>
                                <div class="label_value">
                                    <div>
                                        <select name="position_type" class="text">
                                            <option value="">{{ lang('admin/app.menu_select_ap') }}</option>

                                            <option value="loading_screen"
                                                    @if(isset($position_info['position_type']) && $position_info['position_type'] == 'loading_screen')
                                                    selected
                                                    @endif
                                            >{{ lang('admin/app.loading_screen') }}</option>

                                        </select>
                                    </div>
                                    <div class="notic">广告位类型 如 APP首屏启动页</div>
                                    <div class="form_prompt"></div>
                                </div>
                            </div>

                            <div class="item">
                                <div class="label-t">{{ lang('admin/app.posit_width') }}：</div>
                                <div class="label_value">
                                    <input type="text" name="ad_width" class="text"
                                           value="{{ $position_info['ad_width'] ?? '360' }}" id="ad_width"
                                           placeholder="360" autocomplete="off"/>
                                    <div class="notic m20">{{ lang('admin/app.unit_px') }}</div>
                                    <div class="form_prompt"></div>
                                </div>
                            </div>
                            <div class="item">
                                <div class="label-t">{{ lang('admin/app.posit_height') }}：</div>
                                <div class="label_value">
                                    <input type="text" name="ad_height" class="text"
                                           value="{{ $position_info['ad_height'] ?? '180' }}" placeholder="180"
                                           id="ad_height" autocomplete="off"/>
                                    <div class="notic m20">{{ lang('admin/app.unit_px') }}</div>
                                    <div class="form_prompt"></div>
                                </div>
                            </div>
                            <div class="item">
                                <div class="label-t">{{ lang('admin/app.position_desc') }}：</div>
                                <div class="label_value">
                                    <input type="text" name="position_desc" class="text"
                                           value="{{ $position_info['position_desc'] ?? '' }}" autocomplete="off"/>
                                </div>
                            </div>

                            <div class="item">
                                <div class="label-t">&nbsp;</div>
                                <div class="label_value info_btn">
                                    @csrf
                                    <input type="hidden" name="position_id"
                                           value="{{ $position_info['position_id'] ?? '' }}"/>
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

<script type="text/javascript">


</script>

@include('admin.app.pagefooter')