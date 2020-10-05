@include('admin.wechat.pageheader')
<div class="wrapper">
    <div class="title">{{ $lang['wechat_menu'] }} - {{ $lang['templates'] }}</div>
	<div class="content_tips">
        <div class="explanation" id="explanation">
        	<div class="ex_tit"><i class="sc_icon"></i><h4>{{ $lang['operating_hints'] }}</h4><span id="explanationZoom" title="{{ $lang['fold_tips'] }}"></span></div>
            <ul>
                @if(isset($lang['templates_tips']) && !empty($lang['templates_tips']))

                    @foreach($lang['templates_tips'] as $v)
                        <li>{{ $v }}</li>
                    @endforeach

                @endif

            </ul>
        </div>
        <div class="flexilist">
            <div class="common-head industry">
                
@if($industry)

                <div class="h34">{{ $lang['industry'] }}：{{ $lang['main_industry'] }} <span>{{ $industry['primary_industry']['first_class'] }}</span> / <span>{{ $industry['primary_industry']['second_class'] }}</span>， {{ $lang['deputy_industry'] }} <span>{{ $industry['secondary_industry']['first_class'] }}</span> / <span>{{ $industry['secondary_industry']['second_class'] }}</span> 。 <a href="#inline" class="btn_edit fancybox "><i class="fa fa-edit"></i>{{ $lang['edit_industry'] }}</a></div>
                
@else

                <div class="h34">{{ $error }}</div>
                
@endif

            </div>
            <div class="common-content">
                <div class="list-div">
                <table cellspacing="0" cellpadding="0" border="0">
                    <tr>
                        <th class="text-center">{{ $lang['template_title'] }}</th>
                        <th class="text-center">{{ $lang['template_code'] }}</th>
                        <th class="text-center">{{ $lang['wechat_add_time'] }}</th>
                        <th class="text-center">{{ $lang['handler'] }}</th>
                    </tr>
                    
@foreach($list as $key=>$val)

                    <tr>
                        <td  class="text-center">{{ $val['title'] }}</td>
                        <td  class="text-center">{{ $val['code'] }}</td>
                        <td  class="text-center">{{ $val['add_time'] }}</td>
                        <td class="handle text-center">
                            <div class="tDiv a3">
                                
@if($val['status'] == 1)

                                <a href="{{ route('admin/wechat/switch_template', array('id'=>$val['id'], 'status'=>0)) }}" class="btn_trash" title="{{ $lang['to_disabled'] }}"><i class="fa fa-toggle-on"></i>{{ $lang['already_enabled'] }}</a>
                                
@else

                                <a href="{{ route('admin/wechat/switch_template', array('id'=>$val['id'], 'status'=>1)) }}" class="btn_trash" title="{{ $lang['to_enabled'] }}"><i class="fa fa-toggle-off"></i>{{ $lang['already_disabled'] }}</a>
                                
@endif


                                <a href="{{ route('admin/wechat/edit_template', array('id' => $val['id'])) }}" class="btn_edit fancybox fancybox.iframe"><i class="fa fa-edit"></i>{{ $lang['wechat_editor'] }}</a>

                                <a class="btn_trash reset-template"  href="javascript:;" data-href="{{ route('admin/wechat/reset_template', array('id' => $val['id'])) }}"  title="{{ $lang['reset'] }}"><i class="fa fa-repeat"></i>{{ $lang['reset'] }}</a>
                           </div>

                        </td>
                    </tr>
                    
@endforeach

                </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div style="display:none">
     <div id="inline" class="industry" style="width:500px; height: auto; overflow:hidden">
        <div class="label_value h34 mr20">
            <form action="{{ route('admin/wechat/template') }}" method="post">
            <div class="fl" >
                <select name="data[primary_industry]" class="select">
                    <option value='0' >{{ $lang['choose_main_industry'] }}</option>
                    <option value="1" >{{ $lang['main_industry_01'] }}</option>

                </select>
            </div>
            <div class="fl pl10 mr10" >
                <select name="data[secondary_industry]" class="select">
                    <option value='0' >{{ $lang['choose_deputy_industry'] }}</option>
                    <option value="4" >{{ $lang['deputy_industry_01'] }}</option>
                </select>
            </div>
            @csrf
            <input type="submit" class="button bg-green" value="{{ $lang['button_submit'] }}" />
            </form>
        </div>
     </div>
</div>

<script type="text/javascript">
$(function(){
    // 重置模板消息
    $(".reset-template").click(function(){
        var url = $(this).attr("data-href");
        //询问框
        layer.confirm('{{ $lang['confirm_reset'] }}', {
            btn: ['{{ $lang['ok'] }}', '{{ $lang['cancel'] }}'] //按钮
        }, function(){
            $.post(url, '', function(data){
                layer.msg(data.msg);
                if(data.error == 0 ){
                    if(data.url){
                        window.location.href = data.url;
                    }else{
                        window.location.reload();
                    }
                }
                return false;
            }, 'json');
        });
    });
})
</script>

@include('admin.wechat.pagefooter')
