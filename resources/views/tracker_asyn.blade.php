<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ trans('user.logistics_tracking') }}</title>
    <link rel="stylesheet" href="{{ asset('vendor/tracker/css/mbase_v6.css') }}"/>
    <link rel="stylesheet" href="{{ asset('vendor/tracker/css/query_v6.css') }}"/>
    <style>
        .company ul {
            padding: 0.3rem;
        }

        .company ul li {
            line-height: 2rem;
            color: #5a5a5a;
        }

        .data-img {
            width: 4rem;
            height: 4rem;
            display: inline-block
        }

        .data-img img {
            width: 4rem;
            height: auto;
        }

        .kd-content {
            margin-bottom: 10px;
            padding: .3rem .3rem 0 .3rem;
            background: #fff;
        }

        .kd-content:last-child {
            margin-bottom: 0;
        }

        .more {
            text-align: center;
            font-size: 14px;
            color: #999;
            padding: 10px 0;
        }

        .result-list {
            overflow: hidden;
            transition: all 0.3s;
        }

        .result-list li.other {
            display: none;
        }
    </style>
</head>
<body>


<div class="container" id="main">
    <div class="main">
        @foreach ($info['data'] as $row)
            <div class="kd-content">
                <section class="result-box">
                    <div class="company">
                        <ul>
                            <li>{{ trans('common.shipping_method') }}：{{$row['shipping_name']}}</li>
                            <li>{{ trans('common.shipping_number') }}：{{$row['invoice_no']}}</li>
                        </ul>
                    </div>
                    @foreach ($row['img'] as $img)
                        <div class="data-img">
                            <img src="{{$img['goods_img']}}">
                        </div>
                    @endforeach

                    <div class="result-success" id="success">
                        <div class="result-top" id="resultTop">
                            <span id="sortSpan" class="col1-up">{{ lang('order.time') }}</span>
                            <span class="col2">{{ lang('order.location_tracking_progress') }}</span>
                        </div>
                        <ul id="result" class="result-list">
                            @foreach ($row['shipping_info'] as $key => $v)
                                <li @if ($key == 0) class="last" @else class="other" @endif>
                                    <div class="col1">
                                        <dl>
                                            <dt>{{$v['time']}}</dt>
                                        </dl>
                                    </div>
                                    <div class="col2"><span></span></div>
                                    <div class="col3">{{$v['context']}}</div>
                                </li>
                            @endforeach
                        </ul>

                        @if (count($row['shipping_info']) > 1)
                            <div class="more">{{lang('common.see_more')}}</div>
                        @endif
                    </div>
                </section>
            </div>
        @endforeach
    </div>
    <script type="text/javascript" src="{{ asset('js/jquery-1.12.4.min.js') }}"></script>
    <script>

        $(".more").click(function () {
            if ($(this).hasClass('up')) {
                $(this).html('{{lang('common.see_more')}}');
                $(this).removeClass("up");
                $(this).siblings(".result-list").find(".other").css("display", "none")
            } else {
                $(this).html('{{lang('common.cilck_retract')}}');
                $(this).addClass("up");
                $(this).siblings(".result-list").find(".other").css("display", "-webkit-box")
            }
        })
    </script>
</div>
</body>
</html>
