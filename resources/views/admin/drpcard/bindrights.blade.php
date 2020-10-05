@include('admin.drpcard.pageheader')

<div class="wrapper">
    <div class="title"><a href="{{ route('admin/drp_card/edit', ['id' => $membership_card_id ]) }}" class="s-back">{{ lang('common.back') }}</a> {{ lang('admin/drp.drp_manage') }} - {{ lang('admin/drpcard.bind_rights') }}</div>

    <div class="content_tips">

        <div class="explanation" id="explanation">
            <div class="ex_tit">
                <i class="sc_icon"></i><h4>{{ lang('admin/common.operating_hints') }}</h4><span id="explanationZoom" title="{{ lang('admin/common.fold_tips') }}"></span>
            </div>
            <ul>
                @foreach(lang('admin/drpcard.bind_rights_tips') as $v)
                    <li>{!! $v !!}</li>
                @endforeach
            </ul>
        </div>

        <div class="flexilist">

            <div class="common-content ">
                <form method="post" action="{{ route('admin/drp_card/bind_rights') }}" class="form-horizontal" role="form">
                <div class="list-div bind-rights">
                    <table cellpadding="0" cellspacing="0" border="0">
                        <thead>
                        <tr>
                            <th width="10%">
                                <div class="tDiv">{{ lang('admin/drpcard.rights_type') }}</div>
                            </th>
                            <th width="10%">
                                <div class="tDiv">{{ lang('admin/drpcard.rights_choice')  }}</div>
                            </th>
                            <th width="20%">
                                <div class="tDiv">{{ lang('admin/drpcard.drp_card_name')  }}</div>
                            </th>
                            <th >
                                <div class="tDiv">{{ lang('admin/drpcard.rights_desc')  }}</div>
                            </th>
                        </tr>
                        </thead>
                        <tbody>

                        @if(isset($plugins) && !empty($plugins))

                        @foreach($plugins as $key=> $group)

                            <tr>
                                <td>
                                    <div class="tDiv">{{ lang('admin/users.rights_group_name_' . $key) }}</div>
                                </td>

                                <td class="sign">
                                    <div class="tDiv">
                                        <ul>

                                            @foreach($group as $k => $vo)
                                                <li>
                                                    <input type="checkbox" name="rights_id[]"  value="{{ $vo['id'] }}" class="checkbox" id="checkbox_{{ $vo['id'] }}"  @if(isset($vo['is_checked']) && $vo['is_checked'] == 1) checked="checked" disabled @endif />
                                                    <label for="checkbox_{{ $vo['id'] }}" class="checkbox_stars @if(isset($vo['is_checked']) && $vo['is_checked'] == 1) active disabled @endif "></label>
                                                </li>
                                            @endforeach

                                        </ul>
                                    </div>
                                </td>
                                <td>
                                    <div class="tDiv">
                                        <ul>

                                            @foreach($group as $k => $vo)
                                                <li>{{ $vo['name'] }}</li>
                                            @endforeach

                                        </ul>
                                    </div>
                                </td>
                                <td>
                                    <div class="tDiv">
                                        <ul>

                                            @foreach($group as $k => $vo)
                                                <li>{{ $vo['description'] }}</li>
                                            @endforeach

                                        </ul>
                                    </div>
                                </td>
                            </tr>

                        @endforeach

                        @endif

                        <tr>
                            <td colspan="4">
                                <div class="info_btn text-center flex p10" >
                                    @csrf
                                    <input type="hidden" name="membership_card_id" value="{{ $membership_card_id }}" />
                                    <input type="submit" value="{{ lang('admin/common.button_submit') }}" class="button btn-danger bg-red" style="margin:0 auto;"/>
                                </div>
                            </td>
                        </tr>

                        </tbody>
                    </table>
                </div>

                </form>
            </div>

        </div>

    </div>

</div>

<script type="text/javascript">
    $(function () {




    });
</script>

@include('admin.drpcard.pagefooter')
