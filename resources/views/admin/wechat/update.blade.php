@include('admin.wechat.pageheader')
<style>
    #footer {
        position: static;
        bottom: 0px;
    }
</style>
<div class="wrapper">
    <div class="title">{{ $lang['wechat_menu'] }} - {{ $lang['sub_title'] }}</div>
    <div class="content_tips">
        <div class="flexilist subscribe_head">
            <div class="progress">
                <div id="prog" class="progress-bar" role="progressbar" aria-valuenow="60"
                     aria-valuemin="0" aria-valuemax="100">
                    <span class="sr-only">{{ $persent }}% {{ $lang['update_complete'] }}</span>
                </div>
            </div>
            <div class="persent-status">{{ $persent }}%</div>

            <!--             <div class="form-group">
                            <div class="col-sm-offset-4 col-sm-6">
                                <button id="pause" class="btn btn-primary" value="pause">暂停</button>
                                <button id="stop" class="btn btn-primary" value="stop">停止</button>
                                <button id="goon" class="btn btn-primary">继续</button>
                            </div>
                        </div> -->
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
                            $('.persent-status').text(res.persent + "%  {{ $page_name }} {{ $lang['update_complete'] }}");
                            setTimeout(function () {
                                window.location.href = "{{ route('admin/wechat/subscribe_list') }}";
                            }, 1500);
                        }
                    }
                    return false;
                }
            });
            return false;
        }


        // //进度条停止与重新开始
        // $("#stop").click(function () {
        //    if ("stop" == $("#stop").val()) {
        //        //$("#prog").stop();
        //        clearTimeout(st);
        //        $("#prog").css("width","0%").text("等待启动");
        //        $("#stop").val("start").text("重新开始");
        //    } else if ("start" == $("#stop").val()) {
        //        increment();
        //        $("#stop").val("stop").text("停止");
        //   }
        // });
        // //进度条暂停与继续
        // $("#pause").click(function() {
        //    if ("pause" == $("#pause").val()) {
        //        //$("#prog").stop();
        //        clearTimeout(st);
        //        $("#pause").val("goon").text("继续");
        //    } else if ("goon" == $("#pause").val()) {
        //        increment();
        //        $("#pause").val("stop").text("暂停");
        //    }
        // });


    });

</script>

@include('admin.wechat.pagefooter')
