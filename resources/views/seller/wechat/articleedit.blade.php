@include('seller.base.seller_pageheader')

@include('seller.base.seller_nave_header')
<style>
    .album_btn {
        margin: 0;
        border-radius: 0;
        padding: 4px 6px;
    }
</style>
<div class="ecsc-layout">
    <div class="site wrapper">
        @include('seller.base.seller_menu_left')

        <div class="ecsc-layout-right">
            <div class="main-content" id="mainContent">
                @include('seller.base.seller_nave_header_title')
                <div class="wrapper-right of">
                    <div class="tabmenu">
                        <ul class="tab">
                            <li class="active"><a href="#">{{ $lang['article_edit'] }}</a></li>
                        </ul>
                    </div>

                    <div class="wrapper-list mt20">
                        <form action="{{ route('seller/wechat/article_edit') }}" method="post"
                              enctype="multipart/form-data" role="form">
                            <div class="account-setting ecsc-form-goods">
                                <dl>
                                    <dt>{{ $lang['article_title'] }}：</dt>
                                    <dd>
                                        <input type='text' name='data[title]' value="{{ $article['title'] ?? '' }}"
                                               class="text"/>
                                    </dd>
                                </dl>
                                <dl>
                                    <dt>{{ $lang['article_author'] }} ({{ $lang['selection'] }})：</dt>
                                    <dd>
                                        <input type='text' name='data[author]' value="{{ $article['author'] ?? '' }}"
                                               class="text"/>
                                    </dd>
                                </dl>
                                <dl>
                                    <dt>{{ $lang['article_file'] }}：</dt>
                                    <dd>
                                        <div class="type-file-box ">
                                            <div class="input">
                                                <input type="button" id="button" class="type-file-button" value="{{ $lang['button_upload'] }}...">
                                                <input type="file" class="type-file-file" name="pic" size="30"
                                                       data-state="imgfile" hidefocus="true">
                                                <span class="show">
                                                <a href="#inline" class="nyroModal fancybox"
                                                   title="{{ $lang['preview'] }}">
                                                    <i class="fa fa-picture-o"></i>
                                                </a>
                                            </span>
                                                <input type="text" name="file_path" class="type-file-text"
                                                       value="{{ $article['file'] ?? '' }}" id="textfield"
                                                       style="display:none">
                                            </div>
                                            <div class="label_value"><a
                                                        class="btn button btn-info album_btn fancybox fancybox.iframe"
                                                        href="{{ route('seller/wechat/gallery_album') }}">{{ $lang['select_gallery_album'] }}</a>
                                            </div>
                                            <div class="form_prompt"></div>
                                            <div class="notic">{{ $lang['article_file_notice'] }}</div>
                                        </div>
                                    </dd>
                                </dl>
                                <dl>
                                    <dt>{{ $lang['article_file_show_whether'] }}：</dt>
                                    <dd>
                                        <div class="checkbox_items">
                                            <div class="checkbox_item">
                                                <input type="radio" name="data[is_show]" class="ui-radio evnet_show"
                                                       id="value_119_0" value="1" checked="true"
                                                       @if(isset($article['is_show']) && $article['is_show'] == 1)
                                                       checked
                                                        @endif
                                                >
                                                <label for="value_119_0" class="ui-radio-label
@if(isset($article['is_show']) && $article['is_show'] == 1)
                                                        active
                                                        @endif
                                                        ">{{ $lang['yes'] }}</label>
                                            </div>
                                            <div class="checkbox_item">
                                                <input type="radio" name="data[is_show]" class="ui-radio evnet_show"
                                                       id="value_119_1" value="0"
                                                       @if(isset($article['is_show']) && $article['is_show'] == 0)
                                                       checked
                                                        @endif
                                                >
                                                <label for="value_119_1" class="ui-radio-label
@if(isset($article['is_show']) && $article['is_show'] == 0)
                                                        active
                                                        @endif
                                                        ">{{ $lang['no'] }}</label>
                                            </div>
                                        </div>
                                    </dd>
                                </dl>
                                <dl>
                                    <dt>{{ $lang['article_digest'] }}：</dt>
                                    <dd>
                                        <textarea class="textarea"
                                                  name="data[digest]">{{ $article['digest'] ?? '' }}</textarea>
                                    </dd>
                                </dl>
                                <dl>
                                    <dt>{{ $lang['article_content'] }}：</dt>
                                    <dd>
                                        <div class="label_value"
                                             style="float:left;line-height:0px;">{!! create_editor('content', $article['content']) !!} </div>
                                    </dd>
                                </dl>
                                <dl>
                                    <dt>{{ $lang['article_link'] }}：</dt>
                                    <dd>
                                        <input type='text' name='data[link]' placeholder="http://"
                                               value="{{ $article['link'] ?? '' }}" class="text"/>
                                        <div class="form_prompt"></div>
                                        <div class="notic">{{ $lang['article_link_notice'] }}</div>
                                    </dd>
                                </dl>
                                <dl>
                                    <dt>{{ $lang['article_sort'] }}：</dt>
                                    <dd>
                                        <input type='text' name='data[sort]' value="{{ $article['sort'] ?? '' }}"
                                               class="text"/>
                                    </dd>
                                </dl>
                                <dl>
                                    <dt>&nbsp;</dt>
                                    <dd class="button_info">
                                        @csrf
                                        <input type="hidden" name="id" value="{{ $article['id'] ?? '' }}"/>
                                        <input type="submit" value="{{ $lang['button_submit'] }}"
                                               class="sc-btn sc-blueBg-btn btn35"/>
                                        <input type="reset" value="{{ $lang['button_reset'] }}"
                                               class="sc-btn sc-blue-btn btn35"/>
                                    </dd>
                                </dl>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>

    </div>
</div>
<div class="panel panel-default" style="display: none;" id="inline">
    <div class="panel-body">
        <img src="{{ $article['file'] ?? '' }}" class="img-responsive"/>
    </div>
</div>
<script>
    //file移动上去的js
    $(".type-file-box").hover(function () {
        $(this).addClass("hover");
    }, function () {
        $(this).removeClass("hover");
    });

    // 上传图片预览
    $("input[name=pic]").change(function (event) {
        // 根据这个 <input> 获取文件的 HTML5 js 对象
        var files = event.target.files, file;
        if (files && files.length > 0) {
            // 获取目前上传的文件
            file = files[0];

            // 那么我们可以做一下诸如文件大小校验的动作
            if (file.size > 1024 * 1024 * 5) {
                alert('{{ $lang['file_size_limit_5'] }}');
                return false;
            }

            // 预览图片
            var reader = new FileReader();
            // 将文件以Data URL形式进行读入页面
            reader.readAsDataURL(file);
            reader.onload = function (e) {
                $(".img-responsive").attr("src", this.result);
            };
        }
    });

</script>

@include('seller.base.seller_pagefooter')
