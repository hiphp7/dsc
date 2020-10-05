@include('admin.drp.pageheader')

<div class="wrapper">
    <div class="title">{{ $lang['drp_manage'] }} - {{ $lang['set_drp_menu'] }}</div>

    <div class="content_tips">
        <div class="tabs_info">
            <ul>
                <li class="curr"><a href="{{ route('admin/drp/shop') }}">{{ $lang['drp_shop_list'] }}</a></li>
                <li><a href="{{ route('admin/drp/drp_user_credit') }}">{{ $lang['drp_credit'] }}</a></li>
            </ul>
        </div>
        <div class="explanation" id="explanation">
            <div class="ex_tit"><i class="sc_icon"></i><h4>{{ $lang['operating_hints'] }}</h4><span id="explanationZoom"
                                                                                                    title="{{ $lang['fold_tips'] }}"></span>
            </div>
            <ul>
                @if(isset($lang['drp_shop_list_tips']) && !empty($lang['drp_shop_list_tips']))

                    @foreach($lang['drp_shop_list_tips'] as $v)
                        <li>{!! $v !!}</li>
                    @endforeach

                @endif
            </ul>
        </div>

        <div class="flexilist">
            <div class="main-info">
                <form method="post" action="{{ route('distribute.admin.set_drp') }}" class="form-horizontal"
                      role="form">
                    <div class="switch_info">

                        <div class="item">
                            <div class="label-t">{{ $lang['shop_name'] }}</div>
                            <div class="label_value">
                                <input type="text" name="data[shop_name]" class="text"
                                       value="">
                                <div class="notic ">{{ $lang['shop_name_notice'] }}</div>
                            </div>
                        </div>

                        <div class="item">
                            <div class="label-t">{{ $lang['rely_name'] }}</div>
                            <div class="label_value">
                                <input type="text" name="data[real_name]" class="text"
                                       value="">
                                <div class="notic ">{{ $lang['real_name_notice'] }}</div>
                            </div>
                        </div>

                        <div class="item">
                            <div class="label-t">{{ $lang['mobile'] }}</div>
                            <div class="label_value">
                                <input type="text" name="data[mobile]" class="text"
                                       value="">
                                <div class="notic ">{{ $lang['mobile_notice'] }}</div>
                            </div>
                        </div>

                        <div class="item">
                            <div class="label-t">{{ $lang['drp_qq'] }}</div>
                            <div class="label_value">
                                <input type="text" name="data[qq]" class="text"
                                       value="">
                                <div class="notic ">{{ $lang['drp_qq_notice'] }}</div>
                            </div>
                        </div>

                        <div class="item">
                            <div class="label-t">&nbsp;</div>
                            <div class="lable_value info_btn">
                                @csrf
                                <input type="hidden" name="data[user_id]" value="{{ $user_id ?? 0 }}">
                                <input type="submit" name="submit" value="{{ $lang['button_submit'] }}"
                                       class="button btn-danger bg-red" style="margin:0 auto;"/>
                            </div>
                        </div>

                    </div>
                </form>
            </div>
        </div>

    </div>
</div>
@include('admin.drp.pagefooter')