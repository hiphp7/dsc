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
        	<li>{{ lang('admin/filter_words.words_add_notice.0') }}</li>
            <li>{{ lang('admin/filter_words.words_add_notice.1') }}</li>
            <li>{{ lang('admin/filter_words.words_add_notice.2') }}</li>
        </ul>
    </div>
	<div class="flexilist">
		<div class="main-info of">
			<form action="{{ route('admin/filter/wordsupdate') }}" method="post" class="form-horizontal" role="form">
		        <div class="switch_info of">
			        <div class="item">
			          <div class="label-t">{{ lang('admin/filter_words.filter_one') }}:</div>
			          <div class="label_value">
			              <input type="text" id="words" name="data[words]" value="{{ $words_info['words'] }}" class="text" placeholder="{{ lang('admin/filter_words.words_name') }}" />
			              <div class="notic">{{ lang('admin/filter_words.add_notice') }}</div>
			            </div>
			        </div>		        	
			        <div class="item">
			          <div class="label-t">{{ lang('admin/filter_words.rank') }}:</div>
			          <div class="label_value">
			          	<div class="select_w320">
			              <select class="input-sm select_type" name="data[rank]">
			              	<option value="1" @if( $words_info['rank'] == 1 ) selected @endif>{{ lang('admin/filter_words.filter_one') }}</option>
			              	<option value="2" @if( $words_info['rank'] == 2 ) selected @endif >{{ lang('admin/filter_words.filter_two') }}</option>
			              </select>
			             </div>
			          </div>
			        </div>
			        <div class="item">
			          <div class="label-t">{{ lang('admin/filter_words.is_open') }}:</div>
                        <div class="label_value">
                            <div class="checkbox_items">
                                <div class="checkbox_item">
                                    <input type="radio" name="data[status]" class="ui-radio event_zhuangtai" id="status_1" value="1" @if($words_info['status'] == 1) checked @endif>
                                    <label for="status_1" class="ui-radio-label @if($words_info['status'] == 1) active @endif">{{ lang('admin/common.yes') }}</label>
                                </div>
                                <div class="checkbox_item">
                                    <input type="radio" name="data[status]" class="ui-radio event_zhuangtai" id="status_0" value="0" @if($words_info['status'] == 0) checked @endif>
                                    <label for="status_0" class="ui-radio-label @if($words_info['status'] == 0) active @endif">{{ lang('admin/common.no') }}</label>
                                </div>
                            </div>
                        </div>
			        </div>				        
			        <div class="item">
			          <div class="label-t">&nbsp;</div>
			          <div class="label_value info_btn">
			          	@csrf
						<input type="submit" value="{{ lang('admin/common.button_submit') }}" class="button btn-danger bg-red" />
		              	<input type="reset" value="{{ lang('admin/common.button_reset') }}" class="button button_reset" />
		              	<input type="hidden" name="id" value="{{ $id }}"/>
			          </div>
			        </div>
		        </div>
			</form>
		</div>
	</div>
  </div>
</div>
@include('admin.filterwords.pagefooter')