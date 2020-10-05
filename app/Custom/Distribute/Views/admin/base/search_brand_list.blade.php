@if(isset($filter_brand_list) && !empty($filter_brand_list))
<ul>
	<li data-id="0" data-name="{{$lang['select_barnd']}}" class="blue">{{$lang['cancel_select']}}</li>
	@foreach($filter_brand_list as $key=>$brands)
	<li data-id="{{$brands['brand_id']}}" data-name="{{$brands['brand_name']}}"><em>{{$brands['letter']}}</em>{{$brands['brand_name']}}</li>
	@endforeach
</ul>
@endif