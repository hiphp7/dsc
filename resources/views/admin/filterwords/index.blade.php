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
	<div class="title">{{ lang('admin/filter_words.filter_words') }} - {{ lang('admin/filter_words.words_config') }}</div>
	<div class="content_tips">
    <div class="tabs_info">
        <ul>
            <li class="curr"><a href="{{ route('admin/filter/index') }}">{{ lang('admin/filter_words.words_config') }}</a></li>
            <li><a href="{{ route('admin/filter/words') }}">{{ lang('admin/filter_words.words_store') }}</a></li>
            <li><a href="{{ route('admin/filter/logs') }}">{{ lang('admin/filter_words.words_logs') }}</a></li>
            <li><a href="{{ route('admin/filter/stats') }}">{{ lang('admin/filter_words.words_stats') }}</a></li>
        </ul>
    </div>
	<div class="explanation" id="explanation">
    	<div class="ex_tit">
    		<i class="sc_icon"></i>
    			<h4>{{ lang('admin/common.operating_hints') }}</h4>
    			<span id="explanationZoom" title="{{ lang('admin/common.fold_tips') }}"></span>
    	</div>
        <ul>
        	<li>{{ lang('admin/filter_words.config_notice') }}</li>
        </ul>
    </div>
    <div class="flexilist">
    	  <div class="main-info">
            <form action="{{ route('admin/filter/update') }}" method="get" name="theForm" enctype="multipart/form-data" id="" >
                @csrf
                <div class="switch_info" id="conent_area">
                    <div class="item">
                        <div class="label-t">{{ lang('admin/filter_words.is_open') }}：</div>
                        <div class="label_value">
                            <div class="checkbox_items">
                                <div class="checkbox_item">
                                    <input type="radio" name="data[filter_words_control]" class="ui-radio event_zhuangtai" id="status_0" value="1" @if($filter_config['filter_words_control'] == 1) checked @endif>
                                    <label for="status_0" class="ui-radio-label @if($filter_config['filter_words_control'] == 1) active @endif">{{ lang('admin/common.yes') }}</label>
                                </div>
                                <div class="checkbox_item">
                                    <input type="radio" name="data[filter_words_control]" class="ui-radio event_zhuangtai" id="status_1" value="0" @if($filter_config['filter_words_control'] == 0) checked @endif>
                                    <label for="status_1" class="ui-radio-label @if($filter_config['filter_words_control'] == 0) active @endif">{{ lang('admin/common.no') }}</label>
                                </div>
                            </div>
                        </div>
                    </div>
			        <div class="item">
			          <div class="label-t">&nbsp;</div>
			          <div class="label_value info_btn">
			                  <input type="submit" value="{{ lang('admin/common.button_submit') }}" class="button btn-danger bg-red" />
			                  <input type="reset" value="{{ lang('admin/common.button_reset') }}" class="button button_reset" />
			          </div>
			        </div>
                </div>
            </form>
        </div>
    </div>
  </div>
</div>

@include('admin.filterwords.pagefooter')
