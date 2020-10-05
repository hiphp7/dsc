@include('admin.wechat.pageheader')
<link rel="stylesheet" type="text/css" href="{{ asset('assets/mobile/vendor/webuploader/webuploader.css') }}">
<script type="text/javascript" src="{{ asset('assets/mobile/vendor/webuploader/webuploader.min.js') }}"></script>
<style>
    #notice {
        float: left;
        color: red;
    }
</style>
<div class="wrapper">
    <div class="title"><a href="{{ route('admin/wechat/article') }}"
                          class="s-back">{{ $lang['back'] }}</a>{{ $lang['wechat_article'] }} - {{ $lang['upload_video'] }}
    </div>
    <div class="content_tips">
        <div class="tabs_info">
            <ul>
                <li role="presentation"><a href="{{ route('admin/wechat/article') }}">{{ $lang['article'] }}</a></li>
                <li role="presentation"><a href="{{ route('admin/wechat/picture') }}">{{ $lang['picture'] }}</a></li>
                <li role="presentation"><a href="{{ route('admin/wechat/voice') }}">{{ $lang['voice'] }}</a></li>
                <li role="presentation" class="curr"><a
                            href="{{ route('admin/wechat/video') }}">{{ $lang['video'] }}</a></li>
            </ul>
        </div>
        <div class="explanation" id="explanation">
            <div class="ex_tit"><i class="sc_icon"></i><h4>{{ $lang['operating_hints'] }}</h4><span id="explanationZoom"
                                                                                                    title="{{ $lang['fold_tips'] }}"></span>
            </div>
            <ul>
                @if(isset($lang['video_tips']) && !empty($lang['video_tips']))

                    @foreach($lang['video_tips'] as $v)
                        <li>{{ $v }}</li>
                    @endforeach

                @endif
            </ul>
        </div>
        <div class="flexilist">
            <div class="main-info">
                <form action="{{ route('admin/wechat/video_edit') }}" method="post" enctype="multipart/form-data"
                      id="picForm">
                    <div class="switch_info">
                        <div class="item">
                            <div class="label-t">{{ $lang['upload_video_file'] }}</div>
                            <div id="uploader" class="label_value">
                                <!--用来存放文件信息-->
                                <div id="thelist" class="uploader-list"></div>
                                <div class="type-file-box">
                                    <div id="picker" style="display:inline-flex;"
                                         class="type-file-button">{{ $lang['select_file'] }}</div>
                                    <span class="text-muted" id="notice"></span>
                                </div>
                            </div>
                        </div>
                        <div class="item">
                            <div class="label-t">{{ $lang['video_title'] }}</div>
                            <div class="label_value">
                                <input type="text" name="data[title]"
                                       placeholder="{{ $lang['video_title'] }}（{{ $lang['selection'] }}）" class="text"
                                       value="{{ $video['title'] ?? '' }}"/>
                            </div>
                        </div>
                        <div class="item">
                            <div class="label-t">{{ $lang['video_desc'] }}</div>
                            <div class="label_value">
                                <textarea class="textarea" name="data[content]"
                                          placeholder="{{ $lang['video_desc'] }}（{{ $lang['selection'] }}）"
                                          rows="5">{{ $video['content'] ?? '' }}</textarea>
                            </div>
                        </div>
                        <div class="item">
                            <div class="label-t">&nbsp;</div>
                            <div class="label_value info_btn">
                                @csrf
                                <input type="hidden" name="data[file]" id="file" value="{{ $video['file'] ?? '' }}"/>
                                <input type="hidden" name="data[file_name]" id="file_name"
                                       value="{{ $video['file_name'] ?? '' }}"/>
                                <input type="hidden" name="data[size]" id="size" value="{{ $video['size'] ?? '' }}"/>
                                <input type="hidden" name="id" value="{{ $video['id'] ?? '' }}"/>
                                <input type="submit" name="submit" value="{{ $lang['button_save'] }}"
                                       class="button btn-danger bg-red"/>
                                <input type="reset" name="reset" value="{{ $lang['button_reset'] }}"
                                       class="button button_reset"/>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
    $(function () {
        var $ = jQuery,
            $list = $('#thelist'),
            state = 'pending',
            uploader;
        var video_id = "{{ $video['id'] ?? 0 }}";
        uploader = WebUploader.create({
            formData: {
                vid: video_id,
                // 这里的token是外部生成的长期有效的，如果把token写死，是可以上传的。
                _token: '{{ csrf_token() }}'
            },
            // 不压缩image
            resize: false,
            // swf文件路径
            swf: "{{ asset('assets/mobile/vendor/webuploader/Uploader.swf') }}",
            // 文件接收服务端。
            server: '{{ route("admin/wechat/video_upload") }}',
            // 选择文件的按钮。可选。
            // 内部根据当前运行是创建，可能是input元素，也可能是flash.
            pick: '#picker',
            auto: true,
            accept: {title: 'Video', extensions: 'mp4', mimeTypes: 'video/*'},
            fileNumLimit: 1,
            fileSingleSizeLimit: 2 * 1024 * 1024,   //设定单个文件大小 2M
        });
        // 当有文件添加进来的时候
        uploader.on('fileQueued', function (file) {
            $list['append']('<div id="' + file.id + '" class="item">' +
                '<h4 class="info">' + file.name + '</h4>' +
                '<p class="state">等待上传...</p>' +
                '</div>');
        });
        // 文件上传过程中创建进度条实时显示。
        uploader.on('uploadProgress', function (file, percentage) {
            var $li = $('#' + file.id), $percent = $li.find('.progress .progress-bar');
            // 避免重复创建
            if (!$percent['length']) {
                $percent = $('<div class="progress progress-striped active">' +
                    '<div class="progress-bar" role="progressbar" style="width: 0%">' +
                    '</div>' +
                    '</div>').appendTo($li).find('.progress-bar');
            }

            $li.find('p.state').text('上传中');
            $percent.css('width', percentage * 100 + '%');
        });
        // 文件上传成功，给item添加成功class, 用样式标记上传成功。
        uploader.on('uploadSuccess', function (file) {
            $('#' + file.id).find('p.state').text('上传成功');
        });

        uploader.on('uploadAccept', function (object, ret) {
            if (ret.file_name) {
                $("#file").val(ret.file);
                $("#file_name").val(ret.file_name);
                $("#size").val(ret.size);
            }
        });
        // 文件上传失败，显示上传出错。
        uploader.on('uploadError', function (file) {
            $('#' + file.id).find('p.state').text('上传出错');
        });

        // 进度条完成并隐藏
        // 完成上传完了，成功或者失败，先删除进度条。
        uploader.on('uploadComplete', function (file) {
            //$( '#'+file.id ).find('.progress').fadeOut();
            $('#' + file.id).find('.progress').remove();
        });

        /**
         * 验证文件格式以及文件大小
         */
        uploader.on('error', function (type) {
            if (type == "Q_TYPE_DENIED") {
                $('#notice').html('文件上传失败！格式不正确，请上传标准MP4格式文件');
            } else if (type == "F_EXCEED_SIZE") {
                $('#notice').html('文件上传失败！大小超出最大限制，文件大小不能超过2M');
            }
        });

        //验证表单
        $('input[type="submit"]').click(function () {
            var file = $('input[name="data[file]"]').val();
            var title = $('input[type="text"]').val();
            if (!file) {
                layer.msg('请上传文件');
                return false;
            }
            if (!title) {
                layer.msg('标题不能为空');
                return false;
            }
        });

    })
</script>
@include('admin.wechat.pagefooter')
