@include('admin.team.admin_pageheader')

<div class="wrapper">
    <div class="title">{{ $lang['activity_list'] }} - {{ $lang['activity_info'] }}</div>
    <div class="content_tips">
        <div class="tabs_info">
            <ul>
                <li><a href="{{ route('distribute.admin.activity_info',array('id'=>$activity_detail['id'] ?? 0)) }}">{{$lang['self_info']}}</a></li>
                <li class="curr"><a href="{{ route('distribute.admin.activity_info_dsc',array('id'=>$activity_detail['id'] ?? 0))  }}">{{$lang['act_dsc_info']}}</a></li>
            </ul>
        </div>
        <div class="explanation" id="explanation">
            <div class="ex_tit"><i class="sc_icon"></i><h4>{{ $lang['operating_hints'] }}</h4><span id="explanationZoom" title="{{ $lang['activity_info_menu'] }}"></span>
            </div>
            <ul>
                @if(isset($lang['activity_details_tips']) && !empty($lang['activity_details_tips']))

                    @foreach($lang['activity_details_tips'] as $v)
                        <li>{!! $v !!}</li>
                    @endforeach
                @endif
            </ul>
        </div>
        <div>{{$activity_dsc}}</div>
    </div>
</div>
<script>
    var ue = UE.getEditor('act_dsc');
</script>
@include('admin.base.footer')
