@include('admin.cloudapi.pageheader')
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
	<div class="title">{{ $lang['27_interface'] }} - {{ $ur_here }}</div>
	<div class="content_tips">
	<div class="explanation" id="explanation">
    	<div class="ex_tit">
    		<i class="sc_icon"></i>
    			<h4>{{ $lang['operating_hints'] }}</h4>
    			<span id="explanationZoom" title="{{ $lang['fold_tips'] }}"></span>
    	</div>
        <ul>
        	<li>{{ $lang['cloudapi_notice_content'] }}</li>
        </ul>
    </div>
    <div class="flexilist">
    	  <div class="main-info">
            <form action="{{ route('cloudapi.update') }}" method="get" name="theForm" enctype="multipart/form-data" id="" >
                @csrf
                <div class="switch_info" id="conent_area">
                    <div class="item">
                        <div class="label-t">{{ $lang['client_id'] }}</div>
                        <div class="label_value">
                            <input type="text" name="data[cloud_client_id]" class="text" value="{{ $api_config['client_id'] }}" autocomplete="off" /><div class="notic m20"></div>
                        </div>
                    </div>
                    <div class="item">
                        <div class="label-t">{{ $lang['appkey'] }}</div>

                        <div class="label_value">
                            <input type="text" name="data[cloud_appkey]" class="text" value="{{ $api_config['appkey'] }}" autocomplete="off" /><div class="notic m20"></div>
                        </div>
                    </div>
					<div class="item">
                        <div class="label-t">{{ $lang['cloud_dsc_appkey'] }}</div>
                        <div class="label_value">
                            <input type="text" name="data[cloud_dsc_appkey]" class="text" value="{{ $api_config['cloud_dsc_appkey'] }}" autocomplete="off" /><div class="notic m20">{{ $lang['cloud_dsc_appkey_notice'] }}</div>
                        </div>
                    </div>
                    <div class="item">
                        <div class="label-t">{{ $lang['status'] }}</div>
                        <div class="label_value">
                            <div class="checkbox_items">
                                <div class="checkbox_item">
                                    <input type="radio" name="data[cloud_is_open]" class="ui-radio event_zhuangtai" id="status_0" value="1" @if($api_config['cloud_is_open'] == 1) checked @endif>
                                    <label for="status_0" class="ui-radio-label @if($api_config['cloud_is_open'] == 1) active @endif">
                                    {{ $lang['yes'] }}</label>
                                </div>
                                <div class="checkbox_item">
                                    <input type="radio" name="data[cloud_is_open]" class="ui-radio event_zhuangtai" id="status_1" value="0" @if($api_config['cloud_is_open'] == 0) checked @endif>
                                    <label for="status_1" class="ui-radio-label @if($api_config['cloud_is_open'] == 0) active @endif">
                                    {{ $lang['no'] }}</label>
                                </div>
                            </div>
                        </div>
                    </div>
			        <div class="item">
			          <div class="label-t">&nbsp;</div>
			          <div class="label_value info_btn">
			                  <input type="submit" value="{{ $lang['button_submit'] }}" class="button btn-danger bg-red" />
			                  <input type="reset" value="{{ $lang['button_reset'] }}" class="button button_reset" />
			          </div>
			        </div>
                </div>
            </form>
        </div>
    </div>
  </div>
</div>

@include('admin.cloudapi.pagefooter')
