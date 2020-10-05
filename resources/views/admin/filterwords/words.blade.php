@include('admin.filterwords.pageheader')
<style>
.article{border:1px solid #ddd;padding:5px 5px 0 5px;}
.cover{height:160px; position:relative;margin-bottom:5px;overflow:hidden;}
.article .cover img{width:100%; height:auto;}
.article span{height:40px; line-height:40px; display:block; z-index:5; position:absolute;width:100%;bottom:0px; color:#FFF; padding:0 10px; background-color:rgba(0,0,0,0.6)}
.article_list{padding:5px;border:1px solid #ddd;border-top:0;overflow:hidden;}
.radio label{width:100%;position:relative;padding:0;}
.radio .news_mask{position:absolute;left:0;top:0;background-color:#000;opacity:0.5;width:100%;height:100%;z-index:10;}
</style>
<div class="wrapper">
	<div class="title">{{ lang('admin/filter_words.filter_words') }} - {{ lang('admin/filter_words.words_list') }}</div>
	<div class="content_tips">
    <div class="tabs_info">
        <ul>
            <li><a href="{{ route('admin/filter/index') }}">{{ lang('admin/filter_words.words_config') }}</a></li>
            <li class="curr"><a href="{{ route('admin/filter/words') }}">{{ lang('admin/filter_words.words_store') }}</a></li>
            <li><a href="{{ route('admin/filter/logs') }}">{{ lang('admin/filter_words.words_logs') }}</a></li>
            <li><a href="{{ route('admin/filter/stats') }}">{{ lang('admin/filter_words.words_stats') }}</a></li>
        </ul>
    </div>
	<div class="explanation" id="explanation">
    	<div class="ex_tit">
    		<i class="sc_icon"></i>
    			<h4>{{ lang('admin/common.operating_hints') }}</h4>
    			<span id="explanationZoom" title="{{ lang('admin/common.fold_tips') }}"></span>
    	</div>
        <ul>
        	<li>{{ lang('admin/filter_words.words_notice.0') }}</li>
            <li>{{ lang('admin/filter_words.words_notice.1') }}</li>
        </ul>
    </div>
        <div class="flexilist of">
            <div class="common-head">
                <div class="fl">
                    <a href="{{ route('admin/filter/wordsupdate') }}"><div class="fbutton"><div class="add " title="{{ lang('admin/filter_words.add') }}"><span><i class="fa fa-plus">{{ lang('admin/filter_words.add') }}</i></span></div></div></a>
                    <a href="{{ route('admin/filter/batch') }}"><div class="fbutton"><div class="add"><span><i class="fa fa-plus">{{ lang('admin/filter_words.batch_add') }}</i></span></div></div></a>
                </div>
                <div class="search">
                    <form action="{{ route('admin/filter/words') }}" name="searchForm" method="post" role="search">
                    <div class="input">
                        @csrf
                        <input type="text" name="keywords" class="text" placeholder="{{ lang('admin/filter_words.words_name') }}" autocomplete="off">
                        <input type="submit" value="" class="btn search_button">
                    </div>
                    </form>
                </div>
            </div>
            <div class="common-content">
                <div class="list-div" id="listDiv">
                <form action="{{ route('admin/filter/batchdrop') }}" method="post" class="form-inline" role="form" onSubmit="return confirm('{{ lang('admin/filter_words.js_notice') }}')">
                    <table cellspacing="0" cellpadding="0" border="0">
                        <tr>
                            <th width="3%" class="sign">
                                <div class="tDiv">
                                    <input type="checkbox" class="checkbox" name="all_list" id="all_list"/>
                                    <label for="all_list" class="checkbox_stars"></label>
                                </div>
                            </th>
                            <th width="7%"><div class="tDiv">{{ lang('admin/filter_words.record') }}</div></th>
                            <th width="30%"><div class="tDiv">{{ lang('admin/filter_words.words') }}</div></th>
                            <th width="10%"><div class="tDiv">{{ lang('admin/filter_words.user_name') }}</div></th>
                            <th width="20%"><div class="tDiv">{{ lang('admin/filter_words.create_time') }}</div></th>
                            <th width="10%"><div class="tDiv">{{ lang('admin/filter_words.rank_name') }}</div></th>
                            <th width="10%"><div class="tDiv">{{ lang('admin/filter_words.is_open') }}</div></th>
                            <th width="10%"><div class="tDiv">{{ lang('admin/common.handler') }}</div></th>
                        </tr>
                        @forelse ($words_list as $key=>$words)
                        <tr>
                            <td class="sign">
                                <div class="tDiv">
                                    <input type="checkbox" class="checkbox" id="checkbox_{{ $words['id'] }}" name="id[]" value="{{ $words['id'] }}" >
                                    <label for="checkbox_{{ $words['id'] }}" class="checkbox_stars"></label>
                                </div>
                            </td>
                            <td><div class="tDiv">{{ $words['id'] }}</div></td>
                            <td><div class="tDiv">{{ $words['words'] }}</div></td>
                            <td><div class="tDiv">{{ $words['user_name'] }}</div></td>
                            <td><div class="tDiv">{{ $words['create_time'] }}</div></td>
                            <td><div class="tDiv">{{ $words['rank_name'] }}</div></td>
                            <td>
                                <div class="tDiv">
                                    @if($words['status'] == 1)
                                        <span class="green">{{ lang('admin/common.yes') }}</span>
                                    @else 
                                        <span class="red">{{ lang('admin/common.no') }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="handle">
                                <div class="tDiv ht_tdiv" style="padding-bottom:0px;">
                                    <a href="{{ route('admin/filter/wordsupdate', ['id'=>$words['id']]) }}"><i class="fa fa-edit"></i>{{ lang('admin/common.edit') }}</a>
                                    <a href="javascript:;" ectype="drop" class="btn-trash" data-id="{{ $words['id'] }}"><i class="fa fa-trash"></i>{{ lang('admin/common.drop') }}</a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <tr><td class="no-records" colspan="10">{{ lang('admin/common.no_records') }}</td></tr>
                        </tr>
                        @endforelse
                        <tfoot>
                            <tr>
                                <td colspan="1">
                                    <div class="tDiv of">
                                        <div class="tfoot_btninfo">
                                            @csrf
                                            <input type="submit" class="btn button btn_disabled" value="{{ lang('admin/common.drop') }}" disabled="disabled" ectype='btnSubmit'>
                                        </div>
                                    </div>
                                </td>
                                <td colspan="8">
                                @include('admin.filterwords.pageview')
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </form>
                </div>
            </div>
        </div>
  </div>
</div>
<script type="text/javascript">
    $(function(){
        // 全选切换效果
        $(document).on("click", "input[name='all_list']", function () {
            if ($(this).prop("checked") == true) {
                $(".list-div").find("input[type='checkbox']").prop("checked", true);
                $(".list-div").find("input[type='checkbox']").parents("tr").addClass("tr_bg_org");
            } else {
                $(".list-div").find("input[type='checkbox']").prop("checked", false);
                $(".list-div").find("input[type='checkbox']").parents("tr").removeClass("tr_bg_org");
            }

            btnSubmit();
        });

        // 单选切换效果
        $(document).on("click", ".sign .checkbox", function () {
            if ($(this).is(":checked")) {
                $(this).parents("tr").addClass("tr_bg_org");
            } else {
                $(this).parents("tr").removeClass("tr_bg_org");
            }

            btnSubmit();
        });

        // 禁用启用提交按钮
        function btnSubmit() {
            var length = $(".list-div").find("input[name='id[]']:checked").length;

            if ($("#listDiv *[ectype='btnSubmit']").length > 0) {
                if (length > 0) {
                    $("#listDiv *[ectype='btnSubmit']").removeClass("btn_disabled");
                    $("#listDiv *[ectype='btnSubmit']").attr("disabled", false);
                } else {
                    $("#listDiv *[ectype='btnSubmit']").addClass("btn_disabled");
                    $("#listDiv *[ectype='btnSubmit']").attr("disabled", true);
                }
            }
        }
    });

    $(document).on('click', "a[ectype='drop']", function(){
        if(confirm("{{ lang('admin/filter_words.js_notice') }}"))
        {
            var id = $(this).data('id');

            $.post("{{ route('admin/filter/wordsdrop') }}", {id:id}, function(res){
                if(res.error > 0)
                {
                    alert(res.msg);
                    return false;
                }
                else
                {
                    window.location.href = "{{ route('admin/filter/words') }}";
                }
            }, 'json');
        }
        else
        {
            return false;
        }
    });    
</script>

@include('admin.filterwords.pagefooter')
