<!DOCTYPE html>
<html>
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>{{ $lang['user_prize_list'] ?? '' }}</title>
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/mobile/vendor/bootstrap/css/bootstrap.min.css') }}"/>
    <script src="{{ asset('assets/mobile/vendor/common/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/mobile/vendor/bootstrap/js/bootstrap.min.js') }}"></script>

    <link href="{{ asset('assets/wechat/ggk/css/activity-style.css') }}" rel="stylesheet" type="text/css">

    <script type="text/javascript">
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
    </script>
</head>
<body class="activity-scratch-card-winning">
<div class="container-fluid">
    <div class="page-header">
        <h4 class="prize-list-title">{{ $lang['user_prize_list'] }}</h4>
    </div>
    <div class="row">
        <div class="col-sm-12 col-md-12">
            <div class="panel panel-default">
                <div class="panel-body">
                    <h4 class="sub-header">{{ $lang['nick_name'] }} - {{ $list['data']['0']['nickname'] ?? '' }}</h4>
                    <div class="table-responsive user-prize-list">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th class="col-md-6" >{{ $lang['get_prize'] }}</th>
                                    <th class="col-md-2" >{{ $lang['winner_dateline'] }}</th>
                                    <th class=""></th>
                                </tr>
                            </thead>
                            <tbody>

                            @if(!empty($list['data']))

                                @foreach($list['data'] as $val)

                                <tr >
                                    <td>{{ $val['prize_name'] }}</td>
                                    <td>{{ $val['dateline_format'] }}</td>
                                    <td>
                                        @if (empty($val['winner']))
                                            <a href="{{ route('wechat/plugin_action', ['name' => $plugin_name, 'id' => $val['id']]) }}">{{ $lang['go_to_fill_info'] }}</a>
                                        @endif
                                    </td>

                                </tr>

                                @endforeach

                            @endif

                            </tbody>
                        </table>
                    </div>

                    <div class="row pull-right pr15">
                        <nav aria-label="Page navigation">
                            <ul class="pagination" role="navigation">
                                <li class="page-item @if(is_null($list['prev_page_url'])) disabled @endif" aria-disabled="true">
                                    <a class="mr5" href="{{ $list['first_page_url'] ?? '' }}" aria-label="first page">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                                <li class="page-item ">
                                    <a href="{{ $list['prev_page_url'] ?? '' }}" aria-label="prev page">
                                        <span class="page-link" aria-hidden="true">&lsaquo;</span>
                                    </a>
                                </li>
                                <li class="page-item active" aria-current="page"><span class="page-link">{{ $list['current_page'] ?? 1 }}</span></li>
                                <li class="page-item ">
                                    <a href="{{ $list['next_page_url'] ?? '' }}" rel="next" aria-label="next page">
                                        <span class="page-link" aria-hidden="true">&rsaquo;</span>
                                    </a>
                                </li>
                                <li class="page-item @if(is_null($list['next_page_url'])) disabled @endif" aria-disabled="true">
                                    <a class="ml5" href="{{ $list['last_page_url'] ?? '' }}" aria-label="last page">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>

                </div>


            </div>
        </div>
    </div>

</div>



<script>
$(function () {



});
</script>
</body>
</html>
