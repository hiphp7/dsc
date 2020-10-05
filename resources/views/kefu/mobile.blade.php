<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0"/>
    <meta name="apple-mobile-web-app-capable" content="yes"/>
    <meta name="apple-mobile-web-app-status-bar-style" content="black"/>
    <meta name="format-detection" content="telephone=no"/>
    <title>{{ $title }}</title>
    <link rel="stylesheet" href="{{ asset('assets/chat/css/font-awesome.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/chat/css/index.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/layui/css/layui.css') }}">
    <script>window.ROOT_URL = '{{ url('/') }}';</script>
</head>
<body style="position:relative;">
<section id="tank">
    <a id="jw-get-more" class="get-more">点击查看更多</a>
    <div class="tank-con">
        <ul class="padding-lr user-consult" id="jw-come-msg">
            <li class="cons-list dis-box">
                <div class="cons-head-img-box text-center"><img alt=""></div>
                <div class="box-flex1 m-top02">
                    <div class="cons-admin">
                        <b></b><span></span>
                    </div>
                    <div class="fl">
                        <div class="cons-cont m-top04 text"></div>
                    </div>
                </div>
            </li>
            <li class="cons-list dis-box">
                <div class="box-flex1 m-top02">
                    <div class="cons-admin text-right"><b></b><span></span></div>
                    <div class="fr m-top04">
                        <div class="cons-cont-1 text"></div>
                    </div>
                </div>
                <div class="cons-head-img-box cons-head-img-boxr"><img /></div>
            </li>
        </ul>
    </div>
</section>
<section class="filter-btn dis-box comment" id="jw-editor">
    <div class="speak-contcom_form">
        <span class="fa fa-smile-o btn emotion"></span>
        <span class="fa fa-image jw-send-image"></span>
    </div>

    <div class="text-input">
        <!--<textarea  rows="10" class="write-input" id="saytext" name="saytext"></textarea>-->
        <div contenteditable="true" class="write-input" id="saytext" name="saytext"></div>

    </div>
    <div class="send-btn">
        <button class="send jw-send-msg">发送</button>
        <input name="file" type="file" style="display:none;" onchange="upload.send_image(this)" />
    </div>
</section>

<script src="{{ asset('js/jquery-1.12.4.min.js') }}"></script>
<script src="{{ asset('assets/chat/js/jquery.qqFace.js') }}"></script>
<script src="{{ asset('assets/chat/js/kefu.js') }}"></script>
<script src="{{ asset('vendor/layui/layui.js') }}"></script>
<script>
    /** 客服 */
    dscmallKefu.user.user_id = "{{ $user['user_id'] }}";
    dscmallKefu.user.user_name = "{{ $user['user_name'] }}";
    dscmallKefu.user.avatar = "{{ $user['avatar'] }}";
    dscmallKefu.user.user_type = "customer";
    dscmallKefu.listen_route = "{{ $listen_route }}";
    dscmallKefu.port = "{{ $port ?? 2347 }}";
    dscmallKefu.user.goods_id = "{{ $goods['goods_id'] ?? 0 }}";
    dscmallKefu.user.store_id = "{{ $shopinfo['ru_id'] ?? 0 }}";

    var transMessage_api = "{{ route('kefu.admin.trans_message') }}";

    $(function () {
        var timer = null;
        $('.emotion').qqFace({
            id: 'facebox',
            assign: 'saytext',
            path: '{{ asset('vendor/layui/images/face') }}/'	//表情存放的路径
        });

        $(".fa.fa-smile-o").click(function () {
            $(".filter-btn").css("bottom", "22rem")
        });

        $(document).click(function () {
            $(".filter-btn").css("bottom", "0")
        });

        function scrollBottom() {
            var bodyHeight = $(document).height();
            // 动画时间小于定时器时间
            $('body,html').animate({scrollTop:bodyHeight}, 200, 'linear', function(){
                clearInterval(timer);
            })
        }

        $('#saytext').focus(function () {
            // 定时器再次重新获取位置，防止第一次失败
            timer = setInterval('scrollBottom', 500)
        });

        $("#saytext").blur(function(){
            scrollBottom()
            //clearInterval(timer);
        });

        setTimeout(function(){
            clearInterval(timer);
        }, 1000);

        /** 客服发送消息 */
        $('.jw-send-msg').click(function () {
            var str = $("#saytext").html();
            $("#saytext").html('');

            setTimeout(function(){
                scrollBottom();
                // clearInterval(timer);
            }, 200);

            dscmallEvent.send_msg(str);
        });

        @if($services_id != '' && $services_id > 0 && $status == '1')
            dscmallEvent.target_service = {
            uid: "{{ $services_id }}"
        };
        @endif

        // 放大显示图片
        layui.use('layer', function () {
            var layer = layui.layer;

            var json = {
                "data": [
                    {
                        "alt": "",
                        "src": "",
                        "thumb": ""
                    }
                ]
            };
            $('ul#jw-come-msg').on('click', '.text img', function () {
                var image = $(this).attr('src');
                json.data[0].src = image;
                layer.photos({
                    photos: json
                });
            });

        });
    });


    /**操作页面*/
    var dscmallEvent = {
        target_service: {uid: null, uname: null},
        friend_list: '',
        page: 1,
        default: 0,
        audio: null,
        element: {
            friend_list_ele: null,
            message_ele_self: null,
            message_ele_others: null
        },
        init: function () {
            dscmallEvent.element.friend_list_ele = $('#friend_list li').clone();
            dscmallEvent.element.message_ele_self = $('#jw-come-msg li:eq(1)').clone();
            dscmallEvent.element.message_ele_others = $('#jw-come-msg li:eq(0)').clone();
            $('#friend_list').empty();
            $('#jw-come-msg').empty();
            $('#come_from').text(dscmallKefu.come_form);
            /** 用户名 */
            $('.jw-user-name').text(dscmallKefu.user.user_name);
            dscmallEvent.get_more_message();
            dscmallEvent.get_default_message();
            dscmallEvent.send_image();

            var u = navigator.userAgent;
            // var isAndroid = u.indexOf('Android') > -1 || u.indexOf('Linux') > -1; //g
            var isIOS = !!u.match(/\(i[^;]+;( U;)? CPU.+Mac OS X/); //ios终端
            if (isIOS) {
                //屏幕滚动
                var height = parseInt($('#jw-editor').css('height')) - parseInt($('#saytext').css('height'));
                $(".write-input").keyup(function(){
                    $('#jw-editor').css('height', (height + parseInt($(this).css('height')))+'px');
                });
            }

            //声音
            dscmallEvent.audio = new Audio("{{ asset('assets/chat/media/ling.mp3') }}");
        },
        //添加单个记录
        add_recode: function (data) {
            $(dscmallEvent.element.friend_list_ele).children('.num').text(data.id + '+');
            $(dscmallEvent.element.friend_list_ele).children('div').children('h4').text(data.id);
            $(dscmallEvent.element.friend_list_ele).children('div').children('span').text(data.message);
            $($('#friend_list')).prepend($(dscmallEvent.element.friend_list_ele).clone());
        },
        come_message: function (data) {
            dscmallEvent.audio.play();
            dscmallEvent.add_message(data, 2);
            dscmallEvent.target_service = {
                uid: data.from_id
            };
            $("#tank").scrollTop(($('.tank-con').outerHeight() + $("#jw-get-more").outerHeight()) - $("#tank").height());
        },
        add_message: function (data, belongs, is_history) {
            // belongs 1为自己消息  2为别人消息
            var content;
            var avatar = '';
            if (belongs == 1) {
                content = dscmallEvent.element.message_ele_self;
                avatar = dscmallKefu.user.avatar;
            } else if (belongs == 2) {
                content = dscmallEvent.element.message_ele_others;
                avatar = data.avatar;
            } else {
                layer.msg('add_message_error');
                return;
            }

            $(content).find('img').attr('src', avatar);
            $(content).find('b').text(data.name);
            $(content).find('.text').html(data.message).text();
            $(content).find('.text-right span').text(data.time || dscmallKefu.SystemDate());

            if (is_history == 1) {
                //查看历史
                $('#jw-come-msg').prepend($(content).clone());
                $("#tank").scrollTop($("#tank").height());
            } else {
                //正常聊天
                $('#jw-come-msg').append($(content).clone());
                if ((parseInt(dscmallEvent.target_service.uid) == 0 || dscmallEvent.target_service.uid == undefined || dscmallEvent.target_service.uid == '') && belongs == 1) {
                    // $('#jw-come-msg').append('<li><div class="wait">正在为您接入，请耐心等待...</div></li>');
                }
                $("#tank").scrollTop(($('.tank-con').outerHeight() + $("#jw-get-more").outerHeight()) - $("#tank").height());
            }
        },
        get_service: function (data) {
            dscmallEvent.target_service = {
                uid: data.service_id
            };
            data.message = data.msg;
            data.time = dscmallEvent.SystemDate;
            if (data.msg != '') {
                dscmallEvent.add_message(data, 2);
            }
        },
        get_more_message: function () {
            /** 点击查看更多 */
            $('#jw-get-more').click(function () {
                $.ajax({
                    url: "{{ route('kefu.index.single_chat_list') }}?user_type=2",
                    type: 'post',
                    data: {page: dscmallEvent.page, default: dscmallEvent.default, store_id: dscmallKefu.user.store_id},
                    dataType: 'json',
                    success: function (data) {
                        // data = dscmallKefu.json_encode(data);
                        if (data.error == 1) {
                            $('#jw-get-more').remove();
                            alert(data.content);
                            return;
                        }
                        for (var i in data) {
                            dscmallEvent.add_message(data[i], (data[i].user_type == 1) ? 2 : 1, 1);
                        }
                        dscmallEvent.page++;
                    }
                });

            });
        },
        get_default_message: function () {
            $.ajax({
                url: "{{ route('kefu.index.single_chat_list') }}?user_type=2",
                type: 'post',
                data: {type: 'default', store_id: dscmallKefu.user.store_id},
                dataType: 'json',
                success: function (data) {
                    dscmallEvent.default = 1;
                    // data = dscmallKefu.json_encode(data);
                    if (data.error == 1) {
                        $('#jw-get-more').remove();
                        alert(data.content);
                        return;
                    }
                    for (var i in data) {
                        dscmallEvent.add_message(data[i], (data[i].user_type == 1) ? 2 : 1, 1);
                    }
                    dscmallEvent.page++;
                }
            });
        },
        close_link: function (data) {
            //客服断开
            if (dscmallEvent.target_service.uid == null) return;
            $('#jw-come-msg').append('<li><div class="success">' + data.msg + '</div></li>');
            dscmallEvent.target_service = {};
            $("#tank").scrollTop(($('.tank-con').outerHeight() + $("#jw-get-more").outerHeight()) - $("#tank").height());
        },
        others_login: function (data) {
            //其他人登录
            dscmallKefu.user = {};
            // $('#jw-come-msg').append('<li><div class="success">您的账号异地登录</div></li>');
            $("#tank").scrollTop(($('.tank-con').outerHeight() + $("#jw-get-more").outerHeight()) - $("#tank").height());
        },
        send_image: function () {
            //发送图片
            $(document).on('keyup keydown', function () {
                dscmallEvent.change_send_btn();
            });
            // 点击表情显示发送按钮
            $('#jw-editor').on('click', 'img', function () {
                dscmallEvent.change_send_btn();
            });
            //
            $('.jw-send-image').click(function () {
                layer.msg('请选择2MB以下的图片');
                $('[name=file]').click();
            });

        },
        change_send_btn: function () {
            //改变发送按钮
        },
        switch_service: function (data) {
            //切换客服
            if (data.store_id == dscmallKefu.user.store_id && dscmallEvent.target_service.uid == data.fid) {
                dscmallEvent.target_service.uid = data.sid;
            }
        },
        send_msg: function (msg) {
            if (msg == '' || msg == null) {
                return false;
            }
            dscmallKefu.message.msg = msg;
            // 处理消息接口
            var regex = /<(?!img|p|\/p).*?>/ig; // 去除所有html标签 且保留img p标签
            dscmallKefu.message.msg = dscmallKefu.message.msg.replace(regex, "");
            $.ajax({
                url: transMessage_api,
                data: {message: dscmallKefu.message.msg},
                async: false,
                type: 'post',
                success: function (res) {
                    dscmallKefu.message.msg = res;
                }
            });
            dscmallKefu.message.type = 'sendmsg';
            dscmallKefu.message.to_id = dscmallEvent.target_service.uid;
            dscmallKefu.message.avatar = dscmallKefu.user.avatar;
            dscmallKefu.message.goods_id = dscmallKefu.user.goods_id;
            dscmallKefu.message.store_id = dscmallKefu.user.store_id;
            dscmallKefu.message.origin = dscmallKefu.come_form;
            dscmallKefu.sendmsg();
            dscmallEvent.change_send_btn();
        }

    };

    /** 上传图片类 */
    var upload = {
        xhr: '',
        send_image: function (obj) {
            var fileObj = obj.files[0];
            if (fileObj == undefined) {
                return false;
            }
            var upadload_path = "{{ route('kefu.index.send_image') }}";
            var fd = new FormData();
            fd.append("myfile", fileObj);

            $.ajax({
                url: upadload_path,
                type: 'post',
                processData: false,
                contentType: false,
                data: fd,
                success: function (res) {
                    if (res.code > 0) {
                        layer.msg('发送失败');
                    } else {
                        var str = '<img src="' + res.data.src + '" />';
                        dscmallEvent.send_msg(str);

                        $(obj).val('');
                    }
                }
            })
        }
    }
</script>
</body>
</html>
