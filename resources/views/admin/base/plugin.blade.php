@if ($type == 'admin')

@include('admin.base.header')

@endif

{!! $content !!}

@if ($type == 'admin')

@include('admin.base.footer')

@endif