@include('admin.wechat.pageheader')

<div class="wrapper">
    <div class="title">{{ $lang['wechat_menu'] }} - {{ $lang['custom_message_list'] }}</div>
    <div class="content_tips">

        {{--<div class="tabs_info">--}}
        {{--<ul>--}}
        {{--<li class="curr"><a href="#">{{ $lang['custom_message_list'] }}  -  {{ $nickname }}</a></li>--}}
        {{--</ul>--}}
        {{--</div>--}}

        <div class="main-info">
            <div class="switch_info">
                <div class="row" style="margin:0">
                    <div class="col-md-11 col-sm-11 col-lg-11" style="padding:0;margin: 0;">

                        <div class="panel-heading">{{ $lang['custom_message_list'] }} - {{ $nickname }}</div>
                        <div class="list-div">
                            <table class="table table-hover table-bordered table-striped">
                                <tr>
                                    <th class="text-center">{{ $lang['interactive_user'] }}</th>
                                    <th class="text-center">{{ $lang['message_content'] }}</th>
                                    <th class="text-center" width="20%">{{ $lang['message_time'] }}</th>
                                </tr>

                                @foreach($list as $key=>$val)

                                    <tr>

                                        @if($val['wechat_id'])

                                            <td class="text-center">{{ $lang['official'] }}</td>

                                        @else

                                            <td class="text-center">{{ $nickname }}</td>

                                        @endif

                                        <td class="text-center">{{ $val['msg'] }}</td>
                                        <td class="text-center">{{ $val['send_time'] }}</td>
                                    </tr>

                                @endforeach

                            </table>
                        </div>

                        @include('admin.wechat.pageview')

                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@include('admin.wechat.pagefooter')
