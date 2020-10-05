@include('admin.wechat.pageheader')
<style>
    #footer {
        position: static;
        bottom: 0px;
    }
</style>
<div class="wrapper">
    <div class="title"><a href="{{ route('admin/wechat/article') }}"
                          class="s-back">{{ $lang['back'] }}</a>{{ $lang['wechat_article'] }}
        - {{ $lang['article_edit'] }}</div>
    <div class="content_tips">
        <div class="flexilist">
            <div class="common-content">
                <div class="main-info">
                    <form action="{{ route('admin/wechat/article_edit') }}" method="post" enctype="multipart/form-data" role="form" class="form-horizontal">
                        <div class="item">
                            <div class="label-t">{{ $lang['article_title'] }}:</div>
                            <div class="label_value">
                                <input type="text" name="data[title]" value="{{ $article['title'] ?? '' }}"
                                       class="text"/>
                            </div>
                        </div>
                        <div class="item">
                            <div class="label-t">{{ $lang['article_author'] }}（{{ $lang['selection'] }}）:</div>
                            <div class="label_value">
                                <input type="text" name="data[author]" value="{{ $article['author'] ?? '' }}"
                                       class="text"/>
                            </div>
                        </div>
                        <div class="item">
                            <div class="label-t">{{ $lang['article_file'] }}:</div>
                            <div class="label_value">
                                <div class="type-file-box">
                                    <input type="button" id="button" class="type-file-button">
                                    <input type="file" class="type-file-file" name="pic" size="30" data-state="imgfile"
                                           hidefocus="true">
                                    <span class="show">
						                <a href="#inline" class="nyroModal fancybox" title="{{ $lang['preview'] }}">
						                	<i class="fa fa-picture-o"></i>
						                </a>
						            </span>
                                    <input type="text" name="file_path" class="type-file-text"
                                           value="{{ $article['file'] ?? '' }}" id="textfield" style="display:none">
                                </div>
                                <div class="notic">{{ $lang['article_file_notice'] }}</div>
                            </div>
                        </div>
                        <div class="item">
                            <div class="label-t">&nbsp;</div>
                            <div class="label_value"><a class="btn button btn-info fancybox fancybox.iframe"
                                                        href="{{ route('admin/wechat/gallery_album') }}">{{ $lang['select_gallery_album'] }}</a>
                            </div>
                        </div>
                        <div class="item">
                            <div class="label-t">{{ $lang['article_file_show_whether'] }}:</div>
                            <div class="label_value">
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
                            </div>
                        </div>
                        <div class="item">
                            <div class="label-t">{{ $lang['article_digest'] }}:</div>
                            <div class="label_value">
                                <textarea class="textarea" name="data[digest]">{{ $article['digest'] ?? '' }}</textarea>
                            </div>
                        </div>

                        <div class="item">
                            <div class="label-t">{{ $lang['article_content'] }}:</div>
                            <div class="label_value"
                                 style="line-height:0px;">{!! create_editor('content', $article['content']) !!}</div>
                        </div>

                        <div class="item" style="padding-top:10px;">
                            <div class="label-t">{{ $lang['article_link'] }}:</div>
                            <div class="label_value">
                                <input type='text' name='data[link]' value="{{ $article['link'] ?? '' }}"
                                       class="text" style="width:500px;"/>
                                <div class="notic">{{ $lang['article_link_notice'] }}</div>
                            </div>
                        </div>
                        <div class="item">
                            <div class="label-t">{{ $lang['article_sort'] }}:</div>
                            <div class="label_value">
                                <input type='text' name='data[sort]' value="{{ $article['sort'] ?? '0' }}"
                                       class="text"/>
                            </div>
                        </div>
                        <div class="item">
                            <div class="label-t">&nbsp;</div>
                            <div class="label_value info_btn">
                                @csrf
                                <input type="hidden" name="id" value="{{ $article['id'] ?? '0' }}"/>
                                <input type="submit" value="{{ $lang['button_submit'] }}"
                                       class="button btn-danger bg-red"/>
                                <input type="reset" value="{{ $lang['button_reset'] }}"
                                       class="button button_reset"/>
                            </div>
                        </div>
                    </form>
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

    // 提交关闭 fancybox 弹窗
    $(".form-horizontal").submit(function(){

        if (window.parent.$.fancybox) {
            setTimeout(function () {
                window.parent.$.fancybox.close();
            }, 1500);
        }
    });

</script>
@include('admin.wechat.pagefooter')
