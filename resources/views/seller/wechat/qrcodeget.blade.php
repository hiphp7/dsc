@include('admin.wechat.pageheader')
<div class="panel panel-default" style="margin:0;">
    <div class="panel-heading">{{ $lang['qrcode'] }}</div>
    <div class="panel-body text-center">
        <img src="{{ $qrcode_url }}" alt="{{ $lang['qrcode'] }}"/>
        <br>{{ $short_url }}<br>{{ $lang['qrcode_get_notice'] }}
    </div>
</div>
</body>
</html>
