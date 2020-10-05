@include('admin.bargain.admin_pageheader')

<div class="wrapper">
    {{--亲友邦列表--}}
    <div class="title">{{ $lang['bargain_menu'] }} - {{ $lang['bargain_help_list'] }}</div>

    <div class="content_tips">
        <div class="flexilist">
            <div class="common-content">
                <div class="list-div" id="listDiv">
                    <table cellspacing="0" cellpadding="0" border="0">
                        <tr class="active">
                            <th>
                                <div class="tDiv">{{ $lang['record_id'] }}</div>
                            </th>
                            <th>
                                <div class="tDiv">{{ $lang['user_name'] }}</div>
                            </th>
                            <th>
                                {{--帮你砍价--}}
                                <div class="tDiv">{{ $lang['bargain_subtract_price'] }}</div>
                            </th>
                            <th>
                                <div class="tDiv">{{ $lang['bargain_add_time'] }}</div>
                            </th>
                        </tr>

                        @if($list)

                            @foreach($list as $value)

                                <tr>
                                    <td>
                                        <div class="tDiv">{{ $value['id'] }}</div>
                                    </td>
                                    <td>
                                        <div class="tDiv clamp2">{{ $value['user_name'] }}</div>
                                    </td>
                                    <td>
                                        <div class="tDiv">{{ $value['subtract_price'] }}</div>
                                    </td>
                                    <td>
                                        <div class="tDiv">{{ $value['add_time'] }}</div>
                                    </td>
                                </tr>

                            @endforeach

                        @else

                            <tbody>
                            <tr>
                                <td class="no-records" colspan="9">{{ $lang['no_records'] }}</td>
                            </tr>
                            </tbody>

                        @endif

                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

@include('admin.base.footer')
