<ul>
    <li data-id="0" data-name="{{ $lang['choose_brand'] }}" class="blue">{{ $lang['choose_brand'] }}</li>

    @foreach($filter_brand_list as $brand)

        <li data-id="{{ $brand['brand_id'] }}" data-name="{{ $brand['brand_name'] }}">
            <em>{{ $brand['letter'] }}</em>{{ $brand['brand_name'] }}</li>

    @endforeach

</ul>