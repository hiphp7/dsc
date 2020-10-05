<!DOCTYPE html>
<html>
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>{{ $lang['prize_user_info'] ?? '' }}</title>
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/mobile/vendor/bootstrap/css/bootstrap.min.css') }}"/>
    <script src="{{ asset('assets/mobile/vendor/common/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/mobile/vendor/bootstrap/js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('assets/mobile/vendor/common/validform.js') }}"></script>

    <script src="{{ asset('assets/mobile/vendor/layer/mobile/layer.js') }}"></script>

    <link href="{{ asset('assets/wechat/dzp/css/activity-style.css') }}" rel="stylesheet" type="text/css">

    <script type="text/javascript">
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
    </script>
</head>
<body>
<div class="container-fluid">
    <div class="page-header">
        <h4 class="prize-list-title">{{ $lang['prize_user_info'] }}</h4>
    </div>
    <div class="row ">
        <div class="col-sm-12 col-md-12">
            <div class="panel panel-default">
                <div class="panel-heading">{{ $lang['please_real_info'] }}</div>
                <div class="panel-body">
                    <form action="{{ route('wechat/plugin_action', ['name'=> $plugin_name]) }}" method="post" class="form-horizontal" role="form" onsubmit="return false;">
                        <div class="form-group">
                            <label class="col-sm-2 control-label">{{ $lang['user_name'] }}</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" placeholder="{{ $lang['please_user_name'] }}" value="{{  $winner_result['name'] ?? '' }}" name="data[name]" datatype="*" nullmsg="{{ $lang['please_user_name'] }}"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label">{{ $lang['mobile_phone'] }}</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" placeholder="{{ $lang['please_mobile_phone'] }}" value="{{  $winner_result['phone'] ?? '' }}" name="data[phone]" datatype="m" nullmsg="{{ $lang['please_mobile_phone'] }}"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label">{{ $lang['user_address'] }}</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" placeholder="{{ $lang['please_user_address'] }}" value="{{  $winner_result['address'] ?? '' }}" name="data[address]" datatype="*" nullmsg="{{ $lang['please_user_address'] }}"/>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-sm-offset-2 col-sm-10">
                                <input type="hidden" name="id" value="{{ $id ?? '' }}"/>
                                <input type="hidden" name="operate" value="address"/>
                                <input type="submit" class="btn btn-primary" value="{{ $lang['button_submit'] }}"/>
                                <input type="reset" class="btn btn-default" value="{{ $lang['button_revoke'] }}"/>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(function () {

    $.Tipmsg.r = null;
    $(".form-horizontal").Validform({
        tiptype: function (msg) {
            layer.open({content: msg, skin: 'msg', time: 2});
        },
        tipSweep: true,
        ajaxPost: true,

        callback: function (data) {
            layer.open({content: data.msg, skin: 'msg', time: 2});
            if (data.url) {
                window.location.href = data.url;
            }
        }
    });

});
</script>
</body>
</html>
