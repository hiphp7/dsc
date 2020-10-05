    <link rel="stylesheet" type="text/css" href="{{ __TPL__ . '/css/base.css' }}"/>
    <link rel="stylesheet" type="text/css" href="{{ __TPL__ . '/css/style.css' }}"/>
    <link rel="stylesheet" type="text/css" href="{{ __TPL__. '/css/iconfont.css' }}"/>
    <link rel="stylesheet" type="text/css" href="{{ __TPL__. '/css/purebox.css' }}"/>
    <link rel="stylesheet" type="text/css" href="{{ __TPL__. '/css/quickLinks.css' }}"/>

    <script type="text/jscript" src="{{ asset('js/jquery-1.12.4.min.js') }}"></script>
    <script type="text/jscript" src="{{ asset('js/jquery.json.js') }}"></script>
    <script type="text/jscript" src="{{ asset('js/transport_jquery.js') }}"></script>

    <script type="text/javascript">
        var json_languages = {!! $json_languages ?? '' !!};

        //加载效果
        var load_cart_info = '<img src="{{ __TPL__ . '/images/load/loadGoods.gif' }}" class="load" />';
        var load_icon = '<img src="{{ __TPL__ . '/images/load/load.gif' }}" width="200" height="200" />';
    </script>
