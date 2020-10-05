<form method="post" action="{{ route('distribute.admin.activity_list') }}" name="commonTabsForm">
    {{csrf_field()}}

    <div class="tabs_info">
        <ul>
            <li @if(isset($seller_list) && !$seller_list ) class="curr" @endif>
                <a href="javascript:;" data-val="0" ectype="tabs_info">{{ $lang['self_run'] }}</a>
            </li>

            <li @if(isset($seller_list) && $seller_list == 1 ) class="curr"@endif>
                <a href="javascript:;" data-val="1" ectype="tabs_info">{{ $lang['19_merchants_store'] }}</a>
            </li>
        </ul>
    </div>
    <input type="hidden" name="seller_list" value="{{ $seller_list ?? 0 }}"/>
</form>

<script type="text/javascript">
    $(document).on('click', '*[ectype="tabs_info"]', function () {
        var val = $(this).data('val');
        $(":input[name='seller_list']").val(val);
        $("form[name='commonTabsForm']").submit();
    });
</script>
