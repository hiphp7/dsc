<!DOCTYPE html>
<html>
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>DSC Message</title>
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/mobile/vendor/bootstrap/css/bootstrap.min.css') }}"/>
    <script src="{{ asset('assets/mobile/vendor/common/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/mobile/vendor/bootstrap/js/bootstrap.min.js') }}"></script>

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
    <div class="row text-center">
        <div class="page-header"><h2>{{ $data['title'] ?? '' }}</h2></div>

        <div class=" alert alert-danger">
            <p>{{ $data['message'] ?? '' }}</p>
            <p><a href="{!!  $data['url'] ?? '' !!}"> {{ lang('common.back') }}</a> </p>
        </div>
    </div>

</div>


<script>
$(function () {

    var time = '{{ $data['second'] ?? '1' }}';
    var href = '{!! $data['url'] !!}';
    var interval = setInterval(function(){
        --time;
        if(time <= 0) {
            window.location.href = href;
            clearInterval(interval);
        };
    }, 1000);

});
</script>
</body>
</html>
