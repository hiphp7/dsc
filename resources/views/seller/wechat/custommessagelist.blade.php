@include('seller.base.seller_pageheader')

@include('seller.base.seller_nave_header')

<div class="ecsc-layout">
    <div class="site wrapper">
        @include('seller.base.seller_menu_left')

        <div class="ecsc-layout-right">
            <div class="main-content" id="mainContent">
                @include('seller.base.seller_nave_header_title')
                <div class="wrapper-right of" style="background:#fff">
                    <div class="tabmenu">
                        <ul class="tab pngFix">
                            <li role="presentation" class="active"><a href="#home" role="tab"
                                                                      data-toggle="tab">{{ $postion['ur_here'] ?? '' }}</a>
                            </li>
                        </ul>
                    </div>

                    <div class="wrapper-content">
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
                        @include('seller.base.seller_pageview')
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script type="text/javascript">
    $(function () {
        $(".getqr").click(function () {
            var url = $(this).attr("href");
            $.get(url, '', function (data) {
                if (data.status <= 0) {
                    layer.msg(data.msg);
                    $.fancybox.close();
                    return false;
                }
            }, 'json');
        });
    })
</script>

@include('seller.base.seller_pagefooter')

