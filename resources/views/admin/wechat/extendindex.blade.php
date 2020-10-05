@include('admin.wechat.pageheader')
<style>
    #footer {
        position: static;
        bottom: 0px;
    }
</style>
<div class="wrapper">
    <div class="title">{{ $lang['wechat_menu'] }} - {{ $lang['wechat_extend'] }}</div>
    <div class="content_tips">
        <div class="explanation" id="explanation">
            <div class="ex_tit"><i class="sc_icon"></i><h4>{{ $lang['operating_hints'] }}</h4><span id="explanationZoom" title="{{ $lang['fold_tips'] }}"></span>
            </div>
            <ul>
                @if(isset($lang['wechat_extend_tips']) && !empty($lang['wechat_extend_tips']))

                    @foreach($lang['wechat_extend_tips'] as $v)
                        <li>{{ $v }}</li>
                    @endforeach

                @endif
            </ul>
        </div>
        <div class="flexilist">
            <div class="common-content">
                <ul class="items-box seller-extend">

                    @foreach($plugins as $val)

                        <li class="item_wrap">
                            <div class="plugin_item">
                                <div class="plugin_icon">
                                    <i class="icon iconfont icon-{{ $val['command'] }} bg-{{ $val['command'] }}"></i>
                                </div>
                                <div class="plugin_status">
                                <span class="status_txt">
                                   <div class="list-div">
                                        <div class="handle">
                                            <div class="tDiv">
                                                
@if(isset($val['enable']) && !empty($val['enable']))

                                                    <a href="{{ route('admin/wechat/extend_edit', array('ks'=>$val['command'], 'handler'=>'edit')) }}" class="btn_edit"><i class="fa fa-edit"></i>{{ $lang['edit'] }}</a>
                                                    <a href="javascript:if(confirm('{{ $lang['confirm_uninstall'] }}')){window.location.href='{{ route('admin/wechat/extend_uninstall', array('ks'=>$val['command'])) }}'};" class="btn_trash"><i class="fa fa-trash-o"></i>{{ $lang['uninstall'] }}</a>

                                                @else

                                                    <a href="{{ route('admin/wechat/extend_edit', array('ks'=>$val['command'])) }}" class="btn_inst"><i class="sc_icon sc_icon_inst"></i>{{ $lang['install'] }}</a>

                                                @endif

                                                @if($val['enable'] == 1 && isset($val['config']['haslist']) && $val['config']['haslist'] == 1)
                                                    <a href="{{ route('admin/wechat/winner_list', array('ks'=> $val['command'])) }}" class="btn_see"><i class="sc_icon sc_icon_see"></i>{{ $lang['view_record'] }}</a>
                                                @elseif(isset($val['command']) && $val['command'] == 'sign')
                                                    <a href="{{ route('admin/wechat/sign_list', array('ks'=> $val['command'])) }}" class="btn_see"><i class="sc_icon sc_icon_see"></i>{{ $lang['view_record'] }}</a>
                                                @endif

                                            </div>
                                        </div>
                                    </div>
                                </span>
                                </div>
                                <div class="plugin_content">

                                    @if(config('shop.lang') == 'en')
                                        <h3 class="title">{{ ucfirst($val['command']) }}</h3>
                                    @else
                                        <h3 class="title">{{ $val['name'] }}</h3>
                                    @endif

                                    <p class="desc">{{ ucfirst($val['command']) }}</p>
                                </div>
                            </div>
                        </li>

                    @endforeach

                </ul>
            </div>
        </div>
    </div>
</div>

@include('admin.wechat.pagefooter')
