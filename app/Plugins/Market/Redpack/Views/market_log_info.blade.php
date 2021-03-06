
<style>
/*.dates_box {width: 300px;}*/
.dates_box_top {height: 32px;}
.dates_bottom {height: auto;}
.dates_hms {width: auto;}
.dates_btn {width: auto;}
.dates_mm_list span {width: auto;}

.form-control {font-size: 12px; }
.read-info {border: none;text-shadow:none;-webkit-box-shadow:none;box-shadow:none; line-height:37px;}

#footer {position: static;bottom:0px; display: none;}
</style>
<div class="wrapper">
	<div class="title">{{ $lang['wechat_menu'] }} - {{ $config['name'] }} 活动记录详情</div>

		<div class="flexilist of">
            <div class="common-content">
                <form action="#" method="post" class="form-horizontal form" id="form" onsubmit="return false;">
                    <div class="panel panel-default">
                        <div class="panel-body">
                            <div class="form-group">
                                <label class="col-xs-12 col-sm-3 col-md-2 col-lg-2 control-label">微信昵称：</label>
                                <div class="col-xs-12 col-sm-5">
                                    <span class="text read-info">{{ $info['nickname'] }}</span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-12 col-sm-3 col-md-2 col-lg-2 control-label">红包类型：</label>
                                <div class="col-xs-12 col-sm-5">
                                    <span class="text read-info">{{ $info['hb_type'] }}</span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-12 col-sm-3 col-md-2 col-lg-2 control-label">是否领取：</label>
                                <div class="col-xs-12 col-sm-5">
                                    <span class="text read-info">{{ $info['hassub'] }}</span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-12 col-sm-3 col-md-2 col-lg-2 control-label">领取金额：</label>
                                <div class="col-xs-12 col-sm-2">
                                    <span class="text read-info">{{ $info['money'] }} 元</span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-12 col-sm-3 col-md-2 col-lg-2 control-label">领取时间：</label>
                                <div class="col-xs-12 col-sm-5">
                                    <span class="text read-info">{{ $info['time'] }}</span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-xs-12 col-sm-3 col-md-2 col-lg-2 control-label">商户订单号：</label>
                                <div class="col-xs-12 col-sm-5">
                                    <span class="text read-info">{{ $info['mch_billno'] }}</span>
                                </div>
                            </div>
<!--                            <div class="form-group">
                                <label class="col-xs-12 col-sm-3 col-md-2 col-lg-2 control-label">微信支付商户号：</label>
                                <div class="col-xs-12 col-sm-5">
                                    <span class="text read-info">{{ $info['mch_id'] }}</span>
                                </div>
                            </div>
                             <div class="form-group">
                                <label class="col-xs-12 col-sm-3 col-md-2 col-lg-2 control-label">公众账号appid：</label>
                                <div class="col-xs-12 col-sm-5">
                                    <span class="text read-info">{{ $info['wxappid'] }}</span>
                                </div>
                            </div> -->
                            <div class="form-group">
                                <label class="col-xs-12 col-sm-3 col-md-2 col-lg-2 control-label">订单类型：</label>
                                <div class="col-xs-12 col-sm-5">
                                    <span class="text read-info">{{ $info['bill_type'] }}</span>
                                </div>
                            </div>
<!--                             <div class="form-group info_btn">
                                <div class="col-xs-12 col-sm-9 col-md-10 col-lg-10 col-sm-offset-3 col-md-offset-2 col-lg-offset-2">
                                    <input type="hidden" name="market_id" value="{{ $info['market_id'] }}" />
                                    <input type="hidden" name="id" value="{{ $info['id'] ?? ''}}" />
                                    <input type="submit" name="submit" class="button btn-primary" value="确认" />
                                </div>
                            </div> -->
                        </div>
                    </div>
                </form>
		    </div>
	    </div>
	</div>
</div>

<script type="text/javascript">
$(function(){

});
</script>
