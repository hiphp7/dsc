@include('admin.filterwords.pageheader')
<style>
.article{border:1px solid #ddd;padding:5px 5px 0 5px;}
.cover{height:160px; position:relative;margin-bottom:5px;overflow:hidden;}
.article .cover img{width:100%; height:auto;}
.article span{height:40px; line-height:40px; display:block; z-index:5; position:absolute;width:100%;bottom:0px; color:#FFF; padding:0 10px; background-color:rgba(0,0,0,0.6)}
.article_list{padding:5px;border:1px solid #ddd;border-top:0;overflow:hidden;}
.radio label{width:100%;position:relative;padding:0;}
.radio .news_mask{position:absolute;left:0;top:0;background-color:#000;opacity:0.5;width:100%;height:100%;z-index:10;}
</style>
<div class="wrapper">
	<div class="title"><a href="{{ route('admin/filter/words') }}" class="s-back">{{ lang('admin/common.back') }}</a>{{ lang('admin/filter_words.filter_words') }} - {{ lang('admin/filter_words.words_list') }}</div>
	<div class="content_tips">
	<div class="explanation" id="explanation">
    	<div class="ex_tit">
    		<i class="sc_icon"></i>
    			<h4>{{ lang('admin/common.operating_hints') }}</h4>
    			<span id="explanationZoom" title="{{ lang('admin/common.fold_tips') }}"></span>
    	</div>
        <ul>
        	<li>{{ lang('admin/filter_words.words_batch_add_notice.0') }}</li>
            <li>{{ lang('admin/filter_words.words_batch_add_notice.1') }}</li>
        </ul>
    </div>
    <div class="flexilist">
        <div class="common-content">
    	    <div class="main-info of">
                <form action="{{ route('admin/filter/batchlist') }}" method="post" enctype="multipart/form-data" name="theForm" id="batch_form">
                    <div class="switch_info of">
                        <div class="items" style="width: 800px; margin: 0 auto">
                    <div class="item">
                        <div class="label">{{ lang('admin/filter_words.upload_file') }}：</div>
                        <div class="label_value">
                            <div class="type-file-box">
                                <input type="button" name="button" id="button" class="type-file-button" value="">
                                <input type="file" class="type-file-file" id="file" name="file" size="30" data-state="csvfile" hidefocus="true" value="">
                                <input type="text" name="textfile" class="type-file-text" id="textfield" readonly>
                            </div>
                            <div class="form_prompt"></div>
                            <div class="notic">{{ lang('admin/filter_words.upload_notice') }}</div>
                        </div>
                    </div>
                    <div class="item">
                        <div class="label">&nbsp;</div>
                        <div class="label_value">
                            <a href="{{ route('admin/filter/download') }}" class="mr10">{{ lang('admin/filter_words.download_notice') }}</a>
                        </div>
                    </div>                          
                    <div class="item">
                        <div class="label">&nbsp;</div>
                        <div class="label_value info_btn">
                            @csrf
                            <input type="submit" value="{{ lang('admin/common.button_submit') }}" class="button btn-danger bg-red" id="submitBtn">
                        </div>
                    </div>
                    </div>
                </div>
                </form>
            </div>
        </div>
    </div>
  </div>
</div>

@include('admin.filterwords.pagefooter')
