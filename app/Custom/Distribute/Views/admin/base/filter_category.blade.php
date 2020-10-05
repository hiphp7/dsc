<div class="select-top">
	<a href="javascript:;" class="categoryTop" data-cid="0" data-cname="" data-show='{{$cat_type_show ?? 0}}' data-seller='{{$user_id??0}}' data-table='{$table|default:0}'>{{$lang['reelection']}}</a>
	@if(!empty($filter_category_navigation))
		@foreach($filter_category_navigation as $key=>$navigation)
		&gt <a href="javascript:;" class="categoryOne" data-cid="{{$navigation['cat_id']}}" data-cname="{{$navigation['cat_name']}}" data-url='{{$navigation['url']}}' data-show='{{$cat_type_show ?? 0}}' data-seller='{{$user_id ?? 0}}' data-table='{{$table ?? 0}}'>{{$navigation['cat_name']}}</a>
		@endforeach
	@else
	&gt <span>{{$lang['select_cat']}}</span>
	@endif
</div>
<div class="select-list">
	<ul>
		@if(isset($filter_category_navigation))
			@foreach($filter_category_list as $key=>$category)
			<li data-cid="{{$category['cat_id']}}" data-cname="{{$category['cat_name']}}" @if(isset($category['is_selected']))class="blue"@endif data-url='{{$category['url']}}' data-show='{{$cat_type_show ?? 0}}' data-seller='{{$user_id ?? 0}}}' data-table='{{$table ?? 0}}}'>
				<em>
					@if($filter_category_level == 1)
					Ⅰ
					@elseif($filter_category_level == 2)
					Ⅱ
					@elseif($filter_category_level == 3)
					Ⅲ
					@else
					Ⅰ
					@endif</em>
				{{$category['cat_name']}}
			</li>
			@endforeach
		@endif
	</ul>
</div>