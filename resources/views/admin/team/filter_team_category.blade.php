<div class="select-top">
    <a href="javascript:;" class="categoryTop" data-cid="0" data-cname="">{{ $lang['choose_again'] }}</a>

    @if($filter_category_navigation)


        @foreach($filter_category_navigation as $navigation)

            &gt;<a href="javascript:;" class="categoryTop" data-cid="{{ $navigation['cat_id'] }}"
                   data-cname="{{ $navigation['cat_name'] }}">{{ $navigation['cat_name'] }}</a>

        @endforeach


    @else

        &gt; <span>{{ $lang['please_category'] }}</span>

    @endif

</div>
<div class="select-list">
    <ul>

        @foreach($filter_category_list as $category)

            <li data-cid="{{ $category['cat_id'] }}" data-cname="{{ $category['cat_name'] }}"><em>
                    @if($filter_category_level == 1)
                        Ⅰ
                    @elseif($filter_category_level == 2)
                        Ⅱ
                    @elseif($filter_category_level == 3)
                        Ⅲ
                    @else
                        Ⅰ
                    @endif
                </em>{{ $category['cat_name'] }}</li>

        @endforeach


    </ul>
</div>