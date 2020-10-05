@include('admin.drpcard.pageheader')

<style>
    .card-background {width: 130px; height: 60px; }
</style>
<div class="wrapper">
	<div class="title">{{ lang('admin/drp.drp_manage') }} - {{ lang('admin/drpcard.drp_card') }}</div>
	<div class="content_tips">
        <div class="explanation" id="explanation">
            <div class="ex_tit">
                <i class="sc_icon"></i>
                    <h4>{{ lang('admin/common.operating_hints') }}</h4>
                    <span id="explanationZoom" title="{{ lang('admin/common.fold_tips') }}"></span>
            </div>
            <ul>

                @foreach(lang('admin/drpcard.drp_card_tips') as $v)
                    <li>{!! $v !!}</li>
                @endforeach

            </ul>
        </div>

        <div class="tabs_info mt20">
            <ul>
                <li @if(isset($enable) && $enable == 1) class="curr" @endif >
                    <a href="{{ route('admin/drp_card/index', ['enable' => 1]) }}">{{ lang('admin/drpcard.card_enable_1') }} ({{ $count['card_enable_1'] ?? 0 }})</a>
                </li>
                <li @if(isset($enable) && $enable == 0) class="curr" @endif>
                    <a href="{{ route('admin/drp_card/index', ['enable' => 0]) }}">{{ lang('admin/drpcard.card_enable_0') }} ({{ $count['card_enable_0'] ?? 0 }})</a>
                </li>
            </ul>
        </div>

        <div class="flexilist">
            <div class="common-head">

                @if(empty($total_all))
                    <div class="fl pr10">
                        <a href="javascript:;" data-href="{{ route('admin/drp_card/sync') }}" class="btn_trash js-sync">
                            <div class="fbutton"><div class="add "><span>{{ lang('admin/drpcard.sync') }}</span></div></div>
                        </a>
                    </div>
                @endif

                @if(isset($enable) && $enable == 1)
                <div class="fl">
                    <a href="{{ route('admin/drp_card/add') }}" class="">
                        <div class="fbutton"><div class="add "><span><i class="fa fa-plus"></i>{{ lang('admin/drpcard.add_drp_card') }}</span></div></div>
                    </a>
                </div>
                @endif

            </div>
            <div class="list-div" id="listDiv">
                <table cellspacing="0" cellpadding="0" border="0" class="table table-striped">
                    <thead>
                        <tr>
                            <th width="15%">
                                <div class="tDiv">{{ lang('admin/drpcard.drp_card_image') }}</div>
                            </th>
                            <th width="10%">
                                <div class="tDiv">{{ lang('admin/drpcard.drp_card_name') }}</div>
                            </th>
                            <th width="20%">
                                <div class="tDiv">{{ lang('admin/drpcard.drp_card_condition') }}</div>
                            </th>
                            <th width="15%">
                                <div class="tDiv">{{ lang('admin/drpcard.drp_card_expiry') }}</div>
                            </th>
                            <th >
                                <div class="tDiv">{{ lang('admin/drpcard.bind_rights_list') }}</div>
                            </th>
                            <th width="20%">
                                <div class="tDiv">{{ lang('admin/common.handler') }}</div>
                            </th>
                        </tr>
                    </thead>

                    @if(isset($list) && $list)

                        @foreach($list as $value)

                            <tr>
                                <td>
                                    @if(isset($value['background']) && $value['background'] == 1)
                                        <div class="tDiv"><img class="img-rounded" src="{{ $value['background_img'] ?? '' }}" width="130" height="60" /></div>
                                    @else
                                        <div class="tDiv">
                                            <p class="card-background img-rounded" style="background-color: {{ $value['background_color'] }}"></p>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <div class="tDiv">{{ $value['name'] }}</div>
                                </td>
                                <td>
                                    <div class="tDiv">{!! $value['receive_value_format'] ?? '' !!}</div>
                                </td>

                                <td>
                                    <div class="tDiv">{!! $value['expiry_type_format'] ?? '' !!}</div>
                                </td>

                                <td>
                                    <div class="tDiv">{!! $value['bind_rights_name_format'] ?? '' !!}</div>
                                </td>
                                <td class="handle">
                                    <div class="tDiv a2">
                                        {{--发放状态可编辑--}}
                                        @if(isset($value['enable']) && $value['enable'] == 1)
                                            <a href="{{ route('admin/drp_card/edit', ['id' => $value['id']]) }}" class="btn_edit"><i class="fa fa-edit"></i>{{ lang('admin/common.edit') }}</a>
                                        @else

                                            <a href="{{ route('admin/drp_card/edit', ['id' => $value['id'], 'enable' => 0]) }}" class="btn_see"><i class="fa fa-address-card"></i>{{ lang('admin/common.view') }}</a>

                                        @endif

                                        <a href="{{ route('admin/drp/shop', ['card_id' => $value['id']]) }}" class="btn_see"><i class="fa fa-user-circle"></i>{{ lang('admin/drpcard.member_view') }}</a>

                                    </div>
                                </td>
                            </tr>

                        @endforeach

                    @else

                        <tbody>
                        <tr>
                            <td class="no-records" colspan="6">{{ lang('admin/common.no_records') }}</td>
                        </tr>
                        </tbody>

                    @endif

                </table>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
$(function () {

    // 同步历史数据
    $('.js-sync').bind('click', function () {

        var url = $(this).attr("data-href");

        sync_data(url);
    });

    function sync_data(url) {

        $.post(url, '', function (data) {
            layer.msg(data.msg);
            if (data.error == 0) {
                if (data.url) {
                    window.location.href = data.url;
                } else {
                    window.location.reload();
                }
            }

            return false;
        }, 'json');
    }


    @if(empty($total_all) && VERSION <= 'v1.4.1')
        // 页面加载执行
        var url = $('.js-sync').attr("data-href");

        sync_data(url)
    @endif


});
</script>

@include('admin.drpcard.pagefooter')
