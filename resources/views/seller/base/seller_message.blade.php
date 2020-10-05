@include('seller.base.seller_pageheader')

@include('seller.base.seller_nave_header')

<div class="list-div" style="text-align:center;">
    <div class="fh_message">
        <div class="fr_content">
            <div class="img">

                @if($data['type'] == 1)

                    <i class="fh_icon information"></i>

                @elseif($data['type'] == 2)

                    <i class="fh_icon warning"></i>

                @else

                    <i class="fh_icon confirm"></i>

                @endif

            </div>
            <h3 class="
                    @if($data['type'] == 1)
                    information
                    @elseif($data['type'] == 2)
                    warning
                    @else
                    confirm
                    @endif
                    ">{{ $data['message'] }}</h3>
            <span class="ts" id="redirectionMsg">页面将在 {{ $data['second'] }} 秒后跳转</span>
            <ul class="msg-link">

                @if(isset($data['url']) && $data['url'])

                    <li><a href="{{ $data['url'] }}">{{ $lang['back'] }}</a></li>

                @endif

            </ul>
        </div>
    </div>
</div>

@include('seller.base.seller_pagefooter')
<script type="text/javascript">
    // 自动跳转
    $(function () {
        var time = '{{ $data['second'] }}';
        var href = '{!! $data['url'] !!}';
        var interval = setInterval(function () {
            --time;
            document.getElementById('redirectionMsg').innerHTML = "页面将在 " + time + " 秒后跳转";
            if (time <= 0) {
                window.location.href = href;
                clearInterval(interval);
            }
        }, 1000);
    });


    var height = $(window).height();
    var header = $("header").height();
    var footer = $(".footer").outerHeight();
    //var fr_content = $(".fr_content").outerHeight();
    $(".fh_message").height(height - header - footer - 50);
    //$(".fr_content").css("margin-top",(height-header-footer-fr_content)/2);

    // layer.msg('{{ $data['message'] }}', {icon: {{ $data['type'] }}, offset: '150px', time: 1000, title: '提示'});
    // (function(){
    // 	var time = 1;
    // 	var href = '{{ $data['url'] }}';
    // 	var interval = setInterval(function(){
    // 		--time;
    // 		if(time <= 0) {
    // 			window.location.href = href;
    // 			clearInterval(interval);
    // 		};
    // 	}, 1000);
    // })();
</script>

</body>
</html>
