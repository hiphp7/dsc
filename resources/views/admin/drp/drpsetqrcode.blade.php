@include('admin.drp.pageheader')

<div class="wrapper">
    <div class="title">{{ $lang['drp_manage'] }} - {{ $lang['drp_qrcode_config'] }}</div>
    <div class="content_tips qrcode-set">
        <div class="tabs_info">
            <ul>
                <li><a href="{{ route('admin/drp/config') }}">{{ $lang['drp_config'] }}</a></li>
                <li><a href="{{ route('admin/drp/config', ['group' => 'show']) }}">{{ lang('admin/drp.drp_show_config') }}</a></li>
                <li><a href="{{ route('admin/drp/drp_scale_config') }}">{{ $lang['drp_scale_config'] }}</a></li>
                <li class="curr"><a href="{{ route('admin/drp/drp_set_qrcode') }}">{{ $lang['drp_qrcode_config'] }}</a></li>
                <li><a href="{{ route('admin/drp/config', ['group' => 'message']) }}">{{ lang('admin/drp.drp_message_config') }}</a></li>
            </ul>
        </div>
        <div class="explanation" id="explanation">
            <div class="ex_tit">
                <i class="sc_icon"></i><h4>{{ lang('admin/common.operating_hints') }}</h4><span id="explanationZoom" title="{{ lang('admin/common.fold_tips') }}"></span>
            </div>
            <ul>
                <li>{{ $lang['drp_qrcode_config_tips']['0'] }}</li>
                <li>{{ $lang['drp_qrcode_config_tips']['1'] }}</li>
                <li>{{ $lang['drp_qrcode_config_tips']['2'] }}</li>

                @if(config('shop.open_oss') == 1)

                    <li>{{ $lang['drp_qrcode_config_tips']['3'] }}</li>
                    {{--<li>{{ $lang['drp_qrcode_config_tips']['4'] }}</li>--}}

                @endif

            </ul>
        </div>

        <div class="common-head mt15">
            <div class="fl">
                <a href="javascript:;" class="reset_qrconfig" data-href="{{ route('admin/drp/reset_qrconfig') }}">
                <div class="fbutton">
                    <div class="csv" title="{{ $lang['reset_qrconfig'] }}"><span><i class="fa fa-refresh"></i>{{ $lang['reset_qrconfig'] }}</span></div>
                    </div>
                </a>
            </div>
            <div class="fl pl5">
                <a href="javascript:;" class="delete_user_qrcode" data-href="{{ route('admin/drp/delete_user_qrcode') }}">
                <div class="fbutton">
                    <div class="csv" title="{{ $lang['delete_user_qrcode'] }}"><span><i class="fa fa-trash-o"></i>{{ $lang['delete_user_qrcode'] }}</span></div>
                </div>
                </a>
            </div>

        </div>

        <div class="common-head item_title drp_config_title mt15">
            <div class="vertical"></div>
            <div class="f15">{{ lang('admin/drp.drp_qrcode_config') }}</div>
        </div>

        <div class="flexilist">
            <div class="main-info">
                <div class="switch_info">
                <form action="{{ route('admin/drp/drp_set_qrcode') }}" method="post" class="form-horizontal" enctype="multipart/form-data" id="picForm" onsubmit="return false;">

                    <ul class="advocacy-warp">
                            <li class="left">
                                <div class="bg-iphone"><img src="{{ asset('img/iphone.jpg') }}" class="bg-img"></div>
                                <div class="advocacy-cont">
                                    <!-- 头像与文字 -->
                                    <div class="avater_text-box">
                                        <div class="avater_text" id="avater_text" style="left:{{ $info['av_left'] }}px; top:{{ $info['av_top'] }}px;">
                                            <div class="avater
@if($info['avatar'] == 0)
                                                    hidden
                                                    @endif
                                                    " id="avater_text-cont">
                                                <img src="{{ asset('img/get_avatar.png') }}"/>
                                            </div>
                                            <div class="text-desc" id="text-desc" style="color:{{ $info['color'] }}">{!! $show_text_desc !!}</div>
                                        </div>
                                    </div>
                                    <!--bg-->
                                    <div class="advocacy-bg tabcon">
                                        <!--切换背景-->
                                        <ul>
                                            <li class="on">
                                                <img src="{{ $info['backbround'] }}" id="bg-img"/>
                                            </li>
                                        </ul>
                                    </div>
                                    <!--二维码-->
                                    <div class="qr_code-box">
                                        <div class="qr_code" id="qr_code" style="left:{{ $info['qr_left'] }}px; top:{{ $info['qr_top'] }}px;">
                                            <div id="qr_code-cont">
                                                <img src="{{ asset('img/ewm_296.png') }}"/>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                            <li class="right">
                                <div class="right-cont">
                                    <h4>{{ $lang['select_backgroud_img'] }}：</h4>
                                    <div class="tabnav picScroll">
                                        <div class="bd">
                                            <ul>

                                                @foreach($imglist as $val)

                                                    <li
                                                            @if($val == $info['backbround'])
                                                            class="on"
                                                            @endif
                                                    >
                                                        <img src="{{ $val }}?v={{ $time }}" title="{{ $val }}"/>

                                                        @if (stripos($val, 'drp_bg.png') == false)
                                                        <div class="determine-remove fa fa-remove" data-src="{{ $val }}" title="{{ $lang['delete'] }}"></div>
                                                        @endif

                                                        <div class="determine-icon"><img src="{{ asset('img/determine-icon.png') }}"/></div>
                                                        <div class="determine-cont">
                                                            <span>{{ $lang['preview'] }}</span><label>{{ $lang['current_select'] }}</label>
                                                        </div>
                                                    </li>

                                                @endforeach

                                            </ul>
                                        </div>
                                        <a href="javascript:void(0);" class="prev"></a>
                                        <a href="javascript:void(0);" class="next"></a>
                                    </div>

                                    @if(config('shop.open_oss') == 1)

                                        <div class="common-head">
                                            <div class="fl">
                                                <a href="javascript:;" class="upload_to_oss" data-href="{{ route('admin/drp/synchro_images', array('type' => 0)) }}">
                                                    <div class="fbutton">
                                                        {{--同步背景图至OSS--}}
                                                        <div class="add"><span><i class="fa fa-refresh"></i>{{ $lang['local_mirror_oss'] }}</span>
                                                        </div>
                                                    </div>
                                                </a>
                                            </div>
                                            {{--<div class="fl ml5">--}}
                                            {{--<a href="javascript:;" class="download_to_local"--}}
                                            {{--data-href="{{ route('admin/drp/synchro_images', array('type' => 1)) }}">--}}
                                            {{--<div class="fbutton">--}}
                                            {{--<div class="add"><span><i--}}
                                            {{--class="fa fa-refresh"></i>{{ $lang['oss_mirror_local'] }}</span>--}}
                                            {{--</div>--}}
                                            {{--</div>--}}
                                            {{--</a>--}}
                                            {{--</div>--}}
                                        </div>

                                    @endif

                                    <div class="item">
                                        <h4>{{ $lang['user_avatar'] }}：</h4>
                                        <div class="checkbox_items">
                                            <div class="checkbox_item ">
                                                <input type="radio" class="ui-radio clicktype" name="data[avatar]" id="value_1" value="1"
                                                       @if($info['avatar'] == 1)
                                                       checked
                                                        @endif
                                                >
                                                <label for="value_1" class="ui-radio-label
                                                        @if($info['avatar'] == 1)
                                                        active
                                                        @endif
                                                        ">{{ $lang['show'] }}</label>
                                            </div>
                                            <div class="checkbox_item">
                                                <input type="radio" class="ui-radio clicktype" name="data[avatar]" id="value_0" value="0"
                                                       @if($info['avatar'] == 0)
                                                       checked
                                                        @endif
                                                >
                                                <label for="value_0" class="ui-radio-label
                                                        @if($info['avatar'] == 0)
                                                        active
                                                        @endif
                                                        ">{{ $lang['hide'] }}</label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="item drp-section-box">
                                        <h4>{{ $lang['content_text'] }}：</h4>
                                        <div class="">
                                            <textarea name="data[description]" class="textarea" rows="3" style="height:auto; color:{{ $info['color'] }}" placeholder="{{ $lang['content_text_notice'] }}">{{ $info['description'] ?? '' }}</textarea>
                                            <!-- 选择颜色 -->
                                            <div id="font_color" class="font_color mr10">
                                                <input type='text' id="full" value="{{ $info['color'] }}" style="display: none;"/>
                                            </div>
                                            <input type="hidden" id="text_color" name="data[color]" value="{{ $info['color'] }}">
                                            <div class="notic" style="height:auto;">
                                                {!! $lang['content_text_exp'] !!}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="item">
                                        <h4>{{ $lang['button_upload_bg'] }}：</h4>
                                        <div class="custom-bg">
                                            <div class="type-file-box">
                                                <div class="preview-img">

                                                </div>
                                                <div class="determine-cont js-delete">
                                                    <span>{{ $lang['delete'] }}</span>
                                                </div>
                                                <a href="javascript:;" class="type-file-button updata_pic" style="margin-right:20px;">
                                                    <img src="{{ asset('img/custom-bg.png') }}"/>
                                                </a>
                                                <input type="file" name="pic" style="display: none;"/>
                                                <input type="text" name="file_path" value="" style="display:none">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="label_value info_btn">
                                        @csrf
                                        <input type="hidden" name="data[av_left]" value="{{ $info['av_left'] }}">
                                        <input type="hidden" name="data[av_top]" value="{{ $info['av_top'] }}">
                                        <input type="hidden" name="data[qr_left]" value="{{ $info['qr_left'] }}">
                                        <input type="hidden" name="data[qr_top]" value="{{ $info['qr_top'] }}">
                                        <input type="submit"  class="button btn-danger bg-red" value="{{ $lang['button_save'] }}"/>
                                    </div>
                                </div>
                            </li>
                        </ul>

                </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
    $(function () {
        // 背景图滚动
        $(".picScroll").slide({
            mainCell: "ul",
            effect: "leftLoop",
            interTime: 5000,
            delayTime: 500,
            vis: 5,
            scroll: 1,
            trigger: "click"
        });

        // 上传图片
        $(".updata_pic").click(function () {
            $("input[name=pic]").click();
        });
        var filesList = new Array();
        // 上传图片预览
        $("input[name=pic]").change(function (event) {
            // 根据这个 <input> 获取文件的 HTML5 js 对象
            var files = event.target.files, file;
            if (files && files.length > 0) {
                // 获取目前上传的文件
                file = files[0];

                // 那么我们可以做一下诸如文件大小校验的动作
                if (file.size > 1024 * 1024 * 5) {
                    layer.msg('{{ $lang['upload_file_limit_5'] }}');
                    return false;
                }

                // 预览图片
                var reader = new FileReader();
                // 将文件以Data URL形式进行读入页面
                reader.readAsDataURL(file);
                filesList.push(file);
                reader.onload = function (e) {
                    // console.log(this.result);
                    $('.preview-img').html('<img src="' + this.result + '" />');
                    $('.js-delete').show();
                };
            }
        });
        // 上传图片删除
        $('.js-delete').bind('click', function () {
            $('.preview-img').html('');
            $(this).hide();

            //$(this).parent().remove();
            filesList.splice(0, 1);
        });

        // 切换显示头像
        $(".clicktype").click(function () {
            // var val = $(this).find("input[type=radio]").val();
            var val = $(this).val();
            if ('1' == val && $(".avater").hasClass("hidden")) {
                $(".avater").show().removeClass("hidden");
            }
            if ('0' == val && !$(".avater").hasClass("hidden")) {
                $(".avater").hide().addClass("hidden");
            }
        });

        // 文字颜色设置
        $("#font_color input").spectrum({
            showInitial: true,
            showPalette: true,
            showSelectionPalette: true,
            showInput: true,
            maxPaletteSize: 10,
            preferredFormat: "hex",
            cancelText: "{{ lang('admin/common.cancel') }}",//取消按钮,按钮文字
            chooseText: "{{ lang('admin/common.btn_select') }}",//选择按钮,按钮文字
            palette: [
                ["rgb(0, 0, 0)", "rgb(67, 67, 67)", "rgb(102, 102, 102)", "rgb(204, 204, 204)", "rgb(217, 217, 217)", "rgb(255, 255, 255)"],
                ["rgb(152, 0, 0)", "rgb(255, 0, 0)", "rgb(255, 153, 0)", "rgb(255, 255, 0)", "rgb(0, 255, 0)", "rgb(0, 255, 255)", "rgb(74, 134, 232)", "rgb(0, 0, 255)", "rgb(153, 0, 255)", "rgb(255, 0, 255)"],
                ["rgb(230, 184, 175)", "rgb(244, 204, 204)", "rgb(252, 229, 205)", "rgb(255, 242, 204)", "rgb(217, 234, 211)",
                    "rgb(208, 224, 227)", "rgb(201, 218, 248)", "rgb(207, 226, 243)", "rgb(217, 210, 233)", "rgb(234, 209, 220)",
                    "rgb(221, 126, 107)", "rgb(234, 153, 153)", "rgb(249, 203, 156)", "rgb(255, 229, 153)", "rgb(182, 215, 168)",
                    "rgb(162, 196, 201)", "rgb(164, 194, 244)", "rgb(159, 197, 232)", "rgb(180, 167, 214)", "rgb(213, 166, 189)",
                    "rgb(204, 65, 37)", "rgb(224, 102, 102)", "rgb(246, 178, 107)", "rgb(255, 217, 102)", "rgb(147, 196, 125)",
                    "rgb(118, 165, 175)", "rgb(109, 158, 235)", "rgb(111, 168, 220)", "rgb(142, 124, 195)", "rgb(194, 123, 160)",
                    "rgb(166, 28, 0)", "rgb(204, 0, 0)", "rgb(230, 145, 56)", "rgb(241, 194, 50)", "rgb(106, 168, 79)",
                    "rgb(69, 129, 142)", "rgb(60, 120, 216)", "rgb(61, 133, 198)", "rgb(103, 78, 167)", "rgb(166, 77, 121)",
                    "rgb(91, 15, 0)", "rgb(102, 0, 0)", "rgb(120, 63, 4)", "rgb(127, 96, 0)", "rgb(39, 78, 19)",
                    "rgb(12, 52, 61)", "rgb(28, 69, 135)", "rgb(7, 55, 99)", "rgb(32, 18, 77)", "rgb(76, 17, 48)"]
            ]
        });
        $('.sp-choose').click(function () {
            var sp_color = $('.sp-input').val();

            $('.textarea').css("color", sp_color);
            $('input[name="data[color]"]').val(sp_color);
            // 预览
            $('.text-desc').css("color", sp_color);
        });

        // 实时文字预览
        $(".textarea").bind("input propertychange",function(event){
            var text = $(".textarea").val();
            $('.text-desc').html(text);
        });

        // 背景切换选择
        tab();

        function tab() {
            // 获得选中背景图 src
            let img = $('.advocacy-warp .right .tabnav ul li.on').children("img").attr("src");
            if (img) {
                let data = img.substring(img.indexOf("?"), img.length);
                img = img.replace(data, '');
                $("input[name='file_path']").val(img);
            }

            $('.advocacy-warp .right .tabnav ul li').on('click', function () {
                let index = $(this).index();
                $(this).addClass('on').siblings().removeClass('on');
                $(".advocacy-warp .left .tabcon ul li").eq(index).addClass('on').siblings().removeClass('on');

                // 获得选中背景图 src
                let img = $(this).attr("class", "on").children("img").attr("src");

                // 预览选中背景图
                $('.advocacy-bg ul li').attr("class", "on").children("img").attr("src", img);

                if (img) {
                    // 替换?后面的值
                    let data = img.substring(img.indexOf("?"), img.length);
                    img = img.replace(data, '');

                    // 保存选中背景图
                    $("input[name='file_path']").val(img);
                } else {
                    $("input[name='file_path']").val('');
                }

            });
        }

        // 删除背景
        $(".determine-remove").click(function(e){
            var e = e || window.event;  
            if(e.stopPropagation) { //W3C阻止冒泡方法  
                e.stopPropagation();  
            } else {  
                e.cancelBubble = true; //IE阻止冒泡方法  
            }

            var li = $(this).parent("li");
            var src = $(this).attr("data-src");

            if (src) {
                var url = '{{ route('admin/drp/remove_bg') }}';
                $.post(url, {path:src}, function (data) {
                    layer.msg(data.msg);
                    if (data.error == 0) {
                        // 移除li
                        li.remove();

                        if (data.url) {
                            window.location.href = data.url;
                        } else {
                            window.location.reload();
                        }
                    }
                    return false;
                }, 'json');
            }
        });

        // 提交
        $(".form-horizontal").submit(function () {
            var form = document.getElementById("picForm");
            var ajax_data = new FormData(form);

            var img_src = $('.preview-img').children("img").attr("src");
            if (img_src) {
                imgNatural = getImgNaturalStyle(img_src);
                if (imgNatural[0] != 640 || imgNatural[1] != 1136) {
                    layer.msg('{{ $lang['upload_file_not_allow'] }}');
                    return false;
                }
            }

            var zip_pic = zipImage(img_src);
            if (zip_pic) {
                // 合并压缩文件file
                ajax_data.append('zip_pic', zip_pic);
            }

            $.ajax({
                url: "{{ route('admin/drp/drp_set_qrcode') }}",
                type: "POST",
                dataType: "json",
                data: ajax_data,
                processData: false,  // 告诉jQuery不要去处理发送的数据
                contentType: false,   // 告诉jQuery不要去设置Content-Type请求头
                success: function (data) {
                    layer.msg(data.msg);
                    if (data.error == 0) {
                        window.location.href = "{{ route('admin/drp/drp_set_qrcode') }}";
                    }
                    return false;
                }
            });
            return false;
        });

        function getImgNaturalStyle(img_src) {
            var img = new Image();
            img.src = img_src;
            return [img.naturalWidth, img.naturalHeight];
        }

        /**
         * 压缩图片函数
         * @param  file 图片路径
         * @param  只对 base64位图片进行压缩
         * @return bool
         */
        function zipImage(file) {
            if (file) {
                var hasScale, fileIsBase64 = /^data:/.test(file);
                if (!fileIsBase64) {
                    return false;
                }
                // 1. 创建一个图片和一个canvas对象
                var image = new Image(), canvas = document.createElement("canvas"), ctx = canvas.getContext('2d');
                image.src = file;

                var maxWidth = '1024'; // 小于1024 不压缩
                if (image.complete) {
                    var w = image.naturalWidth, h = image.naturalHeight;
                    hasScale = maxWidth && w > maxWidth;
                    if (!hasScale) {
                        return false;
                    }
                    canvas.width = w;
                    canvas.height = h;
                    ctx.drawImage(image, 0, 0, w, h, 0, 0, w, h);
                } else {
                    image.onload = function () {
                        var w = image.naturalWidth, h = image.naturalHeight;
                        canvas.width = w;
                        canvas.height = h;
                        ctx.drawImage(image, 0, 0, w, h, 0, 0, w, h);
                    };
                }
                ;

                var dataURI = canvas.toDataURL("image/jpeg", 0.7); // quality : 0.7 图片压缩质量，取值 0 - 1 数值越大 越清晰

                var byteString;
                if (dataURI.split(',')[0].indexOf('base64') >= 0) {
                    byteString = atob(dataURI.split(',')[1]);
                } else {
                    byteString = unescape(dataURI.split(',')[1]);
                }
                // write the bytes of the string to a typed array
                var ia = new Uint8Array(byteString.length);
                for (var i = 0; i < byteString.length; i++) {
                    ia[i] = byteString.charCodeAt(i);
                }
                // separate out the mime component
                var mimeString = dataURI.split(',')[0].split(':')[1].split(';')[0];
                return new Blob([ia], {type: mimeString});
            } else {
                return false;
            }
        }


        // 同步背景图至OSS
        $(".upload_to_oss").click(function () {
            var url = $(this).attr("data-href");
            //询问框
            layer.confirm('{{ $lang['confirm_mirror_oss'] }}', {
                btn: ['{{ $lang['ok'] }}', '{{ $lang['cancel'] }}'] //按钮
            }, function () {
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
            });
        });

        // 同步下载OSS背景图到本地
        $(".download_to_local").click(function () {
            var url = $(this).attr("data-href");
            //询问框
            layer.confirm('{{ $lang['confirm_mirror_local'] }}', {
                btn: ['{{ $lang['ok'] }}', '{{ $lang['cancel'] }}'] //按钮
            }, function () {
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
            });
        });


        // 恢复默认配置
        $(".reset_qrconfig").click(function () {
            var url = $(this).attr("data-href");
            //询问框
            layer.confirm('{{ $lang['confirm_reset_qrconfig'] }}', {
                btn: ['{{ $lang['ok'] }}', '{{ $lang['cancel'] }}'] //按钮
            }, function () {
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
            });
        });

        // 删除所有用户生成的名片二维码
        $(".delete_user_qrcode").click(function () {
            var url = $(this).attr("data-href");
            //询问框
            layer.confirm('{{ $lang['confirm_delete_user_qrcode'] }}', {
                btn: ['{{ $lang['ok'] }}', '{{ $lang['cancel'] }}'] //按钮
            }, function () {
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
            });
        });
    });
</script>

<script type="text/javascript">
    /**
     * 纯js实现多div拖拽
     * @param bar, 拖拽触柄
     * @param target, 可拖动窗口
     * @param inWindow, 为true时只能在屏幕范围内拖拽
     * @param BG, 为指定范围内拖拽 例如是一张背景图片
     * @param callback, 拖拽时执行的回调函数。包含两个参数，target的left和top
     * @returns {*}
     * @private
    */
    var startDrag = function (bar, target, inWindow, BG, callback) {
        (function (bar, target, inWindow, BG, callback) {
            var D = document,
                DB = document.body,
                params = {
                    left: 0,
                    top: 0,
                    currentX: 0,
                    currentY: 0
                };
            if (typeof bar == "string") {
                bar = D.getElementById(bar);
            }
            if (typeof target == "string") {
                target = D.getElementById(target);
            }
            bar.style.cursor = "move";
            bindHandler(bar, "mousedown", function (e) {
                if (e.preventDefault) {
                    e.preventDefault();
                } else {
                    e.returnValue = false;
                }

                params.left = target.offsetLeft;
                params.top = target.offsetTop;
                if (!e) {
                    e = window.event;
                    bar.onselectstart = function () {
                        return false;
                    }
                }
                params.currentX = e.clientX;
                params.currentY = e.clientY;

                var stopDrag = function () {
                    removeHandler(DB, "mousemove", beginDrag);
                    removeHandler(DB, "mouseup", stopDrag);
                }, beginDrag = function (e) {
                    var evt = e ? e : window.event,
                        nowX = evt.clientX, nowY = evt.clientY,
                        disX = nowX - params.currentX, disY = nowY - params.currentY,
                        left = parseInt(params.left) + disX,
                        top = parseInt(params.top) + disY;

                    if (inWindow) {
                        // 限制拖动范围 不能超出背景图的最大宽度与最大高度
                        var maxTop = BG.offsetHeight - target.offsetHeight,
                            maxLeft = BG.offsetWidth - target.offsetWidth;
                        if (top < 0) {
                            top = 0;
                            return false;
                        }
                        if (top > maxTop) {
                            top = maxTop;
                            return false;
                        }
                        if (left < 0) {
                            left = 0;
                            return false;
                        }
                        if (left > maxLeft) {
                            left = maxLeft;
                            return false;
                        }
                    }
                    target.style.left = left + "px";
                    target.style.top = top + "px";

                    if (typeof callback == "function") {
                        callback(left, top);
                    }
                };

                bindHandler(DB, "mouseup", stopDrag);
                bindHandler(DB, "mousemove", beginDrag);
            });

            function bindHandler(elem, type, handler) {
                if (window.addEventListener) {
                    //false表示在冒泡阶段调用事件处理程序
                    elem.addEventListener(type, handler, false);
                } else if (window.attachEvent) {
                    // IE浏览器
                    elem.attachEvent("on" + type, handler);
                }
            }

            function removeHandler(elem, type, handler) {
                // 标准浏览器
                if (window.removeEventListener) {
                    elem.removeEventListener(type, handler, false);
                } else if (window.detachEvent) {
                    // IE浏览器
                    elem.detachEvent("on" + type, handler);
                }
            }

        })(bar, target, inWindow, BG, callback);
    };

    // 背景图
    var BG = document.getElementById('bg-img');

    // 头像与文字框
    var aBox = document.getElementById("avater_text");
    var aBar = document.getElementById("avater_text-cont");
    var avater_position = startDrag(aBar, aBox, true, BG, function (left, top) {
        $("input[name='data[av_left]']").val(left);
        $("input[name='data[av_top]']").val(top);
    });

    // 二维码框
    var qBox = document.getElementById("qr_code");
    var qBar = document.getElementById("qr_code-cont");
    var qr_position = startDrag(qBar, qBox, true, BG, function (left, top) {
        $("input[name='data[qr_left]']").val(left);
        $("input[name='data[qr_top]']").val(top);
    });
</script>


@include('admin.drp.pagefooter')
