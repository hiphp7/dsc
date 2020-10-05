<div class="list-page">
    <div id="turn-page">
        <span class="page page_1">{{ $lang['total_records'] }}<em
                    id="totalRecords">{{ $page['count'] ?? 0 }}</em>{{ $lang['total_pages'] }}</span>
        <span class="page page_2">{{ $lang['page_feiwei'] }}<em
                    id="totalPages">{{ $page['page_count'] ?? 1 }}</em>{{ $lang['page_ye'] }}</span>
        <!--<span>页当前第<em id="pageCurrent">1</em></span>-->
        <span class="page page_3"><i>{{ $lang['page_size'] }}</i><input type="text" size="3" id="pageSize"
                                                                        value="{{ $page_num }}"
                                                                        onkeypress="return changePageSize(event);"></span>
        <span id="page-link">
	        <a href="
@if(isset($page['page_first']) && $page['page_first'])
            {{ $page['page_first'] }}
            @else
                    javascript:;
                    @endif
                    " class="first" title="{{ $lang['page_first'] }}"></a>
	        <a href="
@if(isset($page['page_prev']) && $page['page_prev'])
            {{ $page['page_prev'] }}
            @else
                    javascript:;
                    @endif
                    " class="prev" title="{{ $lang['page_prev'] }}"></a>
	        <select id="gotoPage" onchange="location.href=this.value">
	            
@if(isset($page['page_number']) && $page['page_number'])

                    @foreach($page['page_number'] as $key=>$vo)

                        <option
                                @if($page['page'] == $key)
                                selected
                                @endif
                                value="{{ $vo }}">{{ $key }}</option>

                    @endforeach

                @else

                    <option selected value="1">1</option>

                @endif

            </select>
	        <a href="
@if(isset($page['page_next']) && $page['page_next'])
            {{ $page['page_next'] }}
            @else
                    javascript:;
                    @endif
                    " class="next" title="{{ $lang['page_next'] }}"></a>
	        <a href="
@if(isset($page['page_last']) && $page['page_last'])
            {{ $page['page_last'] }}
            @else
                    javascript:;
                    @endif
                    " class="last" title="{{ $lang['page_last'] }}"></a>
	    </span>
    </div>
</div>

<script type="text/javascript">
    // 修改分页数量
    function changePageSize(e) {
        var keynum = window.event ? e.keyCode : e.which;
        if (keynum == 13) {
            var page_num = $("#pageSize").val();
            $.post("{{ route('admin/drp/index') }}", {page_num: page_num}, function (data) {
                if (data.status > 0) {
                    window.location.reload();
                }
            }, 'json');
            return false;
        }
    }
</script>
