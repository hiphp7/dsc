<style type="text/css">
#footer {display: none;}
</style>
<div class="panel panel-default" style="margin:0;">
    <div class="panel-body text-center">
    	<img src="{{ $config['qrcode_url'] }}" alt="{{ $lang['qrcode'] }}" />
        <br>{{-- {{ $short_url }} --}} <br>微信扫描二维码，或关注微信公众号回复关键词：{{ $config['command'] }} ，可进入微信墙
    </div>
</div>
