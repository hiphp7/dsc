@include('admin.wechat.pageheader')
<style>
    .article {
        border: 1px solid #ddd;
        padding: 5px 5px 0 5px;
    }

    .cover {
        height: 160px;
        position: relative;
        margin-bottom: 5px;
        overflow: hidden;
    }

    .article .cover img {
        width: 100%;
        height: auto;
    }

    .article span {
        height: 40px;
        line-height: 40px;
        display: block;
        z-index: 5;
        position: absolute;
        width: 100%;
        bottom: 0px;
        color: #FFF;
        padding: 0 10px;
        background-color: rgba(0, 0, 0, 0.6)
    }

    .article_list {
        padding: 5px;
        border: 1px solid #ddd;
        border-top: 0;
        overflow: hidden;
    }

    .radio label {
        width: 100%;
        position: relative;
        padding: 0;
    }

    .radio .news_mask {
        position: absolute;
        left: 0;
        top: 0;
        background-color: #000;
        opacity: 0.5;
        width: 100%;
        height: 100%;
        z-index: 10;
    }
</style>
<div class="wrapper">
    <div class="title">{{ $lang['wechat_menu'] }} - {{ $lang['mass_message'] }}</div>
    <div class="content_tips">
        <div class="tabs_info">
            <ul>
                <li class="curr"><a href="{{ route('admin/wechat/mass_message') }}">{{ $lang['mass_message'] }}</a></li>
                <li><a href="{{ route('admin/wechat/mass_list') }}">{{ $lang['mass_history'] }}</a></li>
            </ul>
        </div>
        <div class="explanation" id="explanation">
            <div class="ex_tit">
                <i class="sc_icon"></i><h4>{{ lang('admin/common.operating_hints') }}</h4><span id="explanationZoom"
                                                                                    title="{{ lang('admin/common.fold_tips') }}"></span>
            </div>
            <ul>
                @if(isset($lang['mass_message_tips']) && !empty($lang['mass_message_tips']))

                    @foreach($lang['mass_message_tips'] as $v)
                        <li>{{ $v }}</li>
                    @endforeach

                @endif
            </ul>
        </div>
        <div class="flexilist">
            <div class="main-info">
                <form action="{{ route('admin/wechat/mass_message') }}" method="post" enctype="multipart/form-data"
                      class="form-horizontal" role="form">
                    <table id="general-table" class="table table-hover ectouch-table">
                        <tr>
                            <td width="200" class="text-align-r">{{ $lang['select_tags'] }}：</td>
                            <td>
                                <div class="col-md-2">
                                    <select name="tag_id" class="form-control input-sm">

                                        @foreach($tags as $val)

                                            <option value="{{ $val['tag_id'] }}">{{ $val['name'] }}</option>

                                        @endforeach

                                    </select>
                                </div>
                                <div class="notic">{{ $lang['mass_tags_notice'] }}</div>
                            </td>
                        </tr>
                        <tr>
                            <td width="200" class="text-align-r">{{ $lang['article_news_select'] }}：</td>
                            <td>
                                <div class="col-md-5 label_value">
                                    <div class="fl" style="margin-right:20px;">
                                        <a class="btn button btn-info bg-green fancybox fancybox.iframe"
                                           href="{{ route('admin/wechat/auto_reply', array('type'=>'news')) }}">{{ $lang['article_news_select'] }}</a>
                                    </div>
                                    <div class="notic">{{ $lang['mass_article_news_notice'] }}</div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td width="200" class="text-align-r">{{ $lang['article_news'] }}：</td>
                            <td>
                                <div class="col-md-3 content_0"></div>
                            </td>
                        </tr>
                        <tr>
                            <td width="200"></td>
                            <td>
                                <div class="label_value info_btn">
                                    @csrf
                                    <input type="submit" value="{{ lang('admin/common.button_submit') }}"
                                           class="button btn-danger bg-red"/>
                                    <input type="reset" value="{{ lang('admin/common.button_reset') }}"
                                           class="button button_reset"/>
                                </div>
                            </td>
                        </tr>
                    </table>
                </form>
            </div>
        </div>
    </div>
</div>

@include('admin.wechat.pagefooter')
