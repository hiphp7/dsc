@include('seller.base.seller_pageheader')

@include('seller.base.seller_nave_header')

<div class="ecsc-layout">
    <div class="site wrapper">
        @include('seller.base.seller_menu_left')

        <div class="ecsc-layout-right">
            <div class="main-content" id="mainContent">
                @include('seller.base.seller_nave_header_title')
                <div class="wrapper-right of">
                    <div class="tabmenu">
                        <ul class="tab ">
                            <li><a href="{{ route('seller/wechat/article') }}">{{ $lang['article'] }}</a></li>
                            <li><a href="{{ route('seller/wechat/picture') }}">{{ $lang['picture'] }}</a></li>
                            <li class="active"><a href="{{ route('seller/wechat/voice') }}">{{ $lang['voice'] }}</a>
                            </li>
                            <li><a href="{{ route('seller/wechat/video') }}">{{ $lang['video'] }}</a></li>
                        </ul>
                    </div>
                    <div class="explanation" id="explanation">
                        <div class="ex_tit"><i class="sc_icon"></i><h4>{{ $lang['operating_hints'] }}</h4></div>
                        <ul>
                            @if(isset($lang['voice_tips']) && !empty($lang['voice_tips']))

                                @foreach($lang['voice_tips'] as $v)
                                    <li>{{ $v }}</li>
                                @endforeach

                            @endif
                        </ul>
                    </div>
                    <div class="tab-content">
                        <div class="tab-pane active">
                            <div class="common-head mt20">
                                <form action="{{ route('seller/wechat/voice') }}" method="post"
                                      enctype="multipart/form-data" id="voiceForm">
                                    <div class="fl mb20">
                                        <div class="type-file-box">
                                            <div class="input">
                                                @csrf
                                                <input type="button" id="button" class="type-file-button updata_pic"
                                                       value="{{ $lang['button_upload'] }}"
                                                       onclick="$('input[name=voice]').click();">

                                                <input type="file" name="voice" style="display: none;"
                                                       onchange="$('#voiceForm').submit();">
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <div class="clear"></div>
                            <div class="wrapper-content" style="overflow:hidden">
                                <ul class="picture_ul">

                                    @foreach($list as $v)

                                        <li class="picture-add" style="">
                                            <div class="picture-add-top">
                                                <img alt="{{ $v['file_name'] }}" src="{{ asset('img/voice.png') }}"
                                                     class="img-rounded" style="height:220px"/>
                                                <p class="text-muted seller_pic">{{ $v['file_name'] }}</p>
                                                <p class="text-muted">{{ $v['size'] }}</p>
                                            </div>
                                            <div class="operate threebtn">
                                                <a href="{{ route('seller/wechat/download', array('id'=>$v['id'])) }}"
                                                   title="{{ $lang['button_download'] }}" class="ectouch-fs18"><span
                                                            class="glyphicon glyphicon-download-alt"></span></a>
                                                <a href="{{ route('seller/wechat/media_edit', array('id'=>$v['id'])) }}"
                                                   title="{{ $lang['edit'] }}"
                                                   class="ectouch-fs18 fancybox fancybox.iframe"><span
                                                            class="glyphicon glyphicon-pencil"></span></a>
                                                <a href="javascript:;" title="{{ $lang['drop'] }}"
                                                   data-href="{{ route('seller/wechat/media_del', array('id'=>$v['id'])) }}'}"
                                                   class="ectouch-fs18 delete_confirm"><span
                                                            class="glyphicon glyphicon-trash"></span></a>
                                            </div>
                                        </li>

                                    @endforeach

                                </ul>
                            </div>
                            @include('seller.base.seller_pageview')

                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>
</div>

@include('seller.base.seller_pagefooter')
