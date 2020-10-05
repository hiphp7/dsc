@if ($type == 1)

@include('admin.wechat.pageheader')

@endif

{!! $template_content !!}

@if ($type == 1)

@include('admin.wechat.pagefooter')

@endif