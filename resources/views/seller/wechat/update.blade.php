@include('seller.base.seller_pageheader')

@include('seller.base.seller_nave_header')

<div class="ecsc-layout">
    <div class="site wrapper">
        @include('seller.base.seller_menu_left')

        <div class="ecsc-layout-right">
            <div class="main-content" id="mainContent">
                @include('seller.base.seller_nave_header_title')
                <div class="wrapper-right of subscribe_head">
                    <div class="content_tips">
                        <div class="flexilist subscribe_head">
                            <div class="progress">
                                <div id="prog" class="progress-bar" role="progressbar" aria-valuenow="60"
                                     aria-valuemin="0" aria-valuemax="100">
                                    <span class="sr-only">{{ $persent }}% {{ $lang['update_complete'] }}</span>
                                </div>
                            </div>
                            <div class="persent-status">{{ $persent }}%</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
<script type="text/javascript">
    $(function () {

        // 更新
        updateURL(1);

        function updateURL(page) {
            $.ajax({
                type: 'POST',
                url: "{{ $request_url }}",
                async: true,
                cache: false,
                dataType: 'json',
                data: {page: page},
                success: function (res) {
                    if (res.status == 0) {
                        $("#prog").css("width", res.persent + "%");
                        $('.persent-status').text(res.persent + "%");
                        if (res.next_page <= res.totalpage) {
                            updateURL(res.next_page);
                        } else {
                            $('.persent-status').text(res.persent + "%  {{ $lang['update_complete'] }}");
                            setTimeout(function () {
                                window.location.href = "{{ route('seller/wechat/subscribe_list') }}";
                            }, 2000);
                        }
                    }
                    return false;
                }
            });
            return false;
        }

    });

</script>

@include('seller.base.seller_pagefooter')
