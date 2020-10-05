<div id="turn-page">
    <div class="pagination">
        <ul>
            <li class="first-li"><span><a href="
@if(isset($page['page_first']) && $page['page_first'])
                    {{ $page['page_first'] }}
                    @else
                            javascript:;
                            @endif
                            " class="first" title="{{ $lang['page_first'] }}">{{ $lang['page_first'] }}</a></span></li>
            <li><span><a href="
@if(isset($page['page_prev']) && $page['page_prev'])
                    {{ $page['page_prev'] }}
                    @else
                            javascript:;
                            @endif
                            " class="prev" title="{{ $lang['page_prev'] }}">{{ $lang['page_prev'] }}</a></span></li>
            <li class="select-page">
                    <span id="page-link">
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
                    </span>
            </li>
            <li><span><a href="
@if(isset($page['page_next']) && $page['page_next'])
                    {{ $page['page_next'] }}
                    @else
                            javascript:;
                            @endif
                            " class="next" title="{{ $lang['page_next'] }}">{{ $lang['page_next'] }}</a></span></li>
            <li class="last-li"><span><a href="
@if(isset($page['page_last']) && $page['page_last'])
                    {{ $page['page_last'] }}
                    @else
                            javascript:;
                            @endif
                            " class="last" title="{{ $lang['page_last'] }}">{{ $lang['page_last'] }}</a></span></li>
            <li class="totalRecords"><span>{{ $lang['total_records'] }} <em
                            id="totalRecords">{{ $page['count'] ?? 0 }}</em>{{ $lang['total_pages'] }}</span></li>
        </ul>
    </div>
</div>
