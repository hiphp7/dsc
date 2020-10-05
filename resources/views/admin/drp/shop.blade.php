@include('admin.drp.pageheader')

<style>
    ul, li {
        overflow: hidden;
    }
    .tabs_info {margin-top: 20px;}

    .dates_box_top {
        height: 32px;
    }

    .dates_bottom {
        height: auto;
    }

    .dates_hms {
        width: auto;
    }

    .dates_btn {
        width: auto;
    }

    .dates_mm_list span {
        width: auto;
    }

    /*div+js模仿select效果*/
    .imitate_select{ float: left; position:relative;border: 1px solid #dbdbdb;border-radius: 2px;height: 32px;line-height: 30px; margin-right:10px;font-size: 12px;}
    .imitate_select .cite{ background: #fff url({{ asset('assets/admin/images/xjt.png') }}) right 11px no-repeat; padding: 0 10px; cursor:pointer;overflow: hidden;white-space: nowrap;text-overflow: ellipsis; text-align:left;}
    .imitate_select ul{ position:absolute; top:28px; left:-1px; background:#fff; width:100%; border:1px solid #dbdbdb; border-radius:0 0 3px 3px; display:none; z-index:199; max-height:280px; overflow:hidden;}
    .imitate_select ul li{ padding:0 10px; cursor:pointer;}
    .imitate_select ul li:hover{ background:#f5faff;}
    .imitate_select ul li a{ display:block;overflow: hidden;white-space: nowrap;text-overflow: ellipsis; text-align:left; color:#707070;}

    .imitate_select ul li.li_not{ text-align:center;padding: 20px 10px;}
    .imitate_select .upward{ top:inherit; bottom:28px; border-radius:3px 3px 0 0;}
    /*div+js模仿select效果end*/

</style>
<div class="wrapper">
    {{--分销商列表--}}
    <div class="title">{{ $lang['drp_shop_list'] }} @if($shop_name) - {{ $shop_name }}{{ $lang['of_drp_shop_list'] }} @endif
        @if($card_name) - {{ $card_name }} @endif</div>

    <div class="content_tips">
        <div class="explanation" id="explanation">
            <div class="ex_tit"><i class="sc_icon"></i><h4>{{ lang('admin/common.operating_hints') }}</h4><span id="explanationZoom" title="{{ lang('admin/common.fold_tips') }}"></span>
            </div>
            <ul>
                @if(isset($lang['drp_shop_list_tips']) && !empty($lang['drp_shop_list_tips']))

                    @foreach($lang['drp_shop_list_tips'] as $v)
                        <li>{!! $v !!}</li>
                    @endforeach

                @endif
            </ul>
        </div>

        <div class="p10">
            <div class="common-head">
                <div class="fr">
                    <form action="{{ route('admin/drp/export_shop', ['card_id' => $card_id ?? 0]) }}" method="post">
                        <div class="label_value">
                            <div class="text_time" id="text_time1" style="float:left;">
                                <input type="text" name="starttime" class="text mr0" value="{{ date('Y-m-d H:i', mktime(0,0,0,date('m'), date('d')-7, date('Y'))) }}" id="promote_start_date"  readonly>
                            </div>

                            <div class="text_time" id="text_time2" style="float:left;">
                                <input type="text" name="endtime" class="text" value="{{ date('Y-m-d H:i') }}" id="promote_end_date"  readonly>
                            </div>
                            @csrf
                            <input type="hidden" name="user_id" value="{{ $user_id }}">
                            <input type="submit" name="export" value="{{ $lang['export_excel'] }}" class="button bg-green"/>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="tabs_info">
            <ul>
                <li class=" @if($status == 'active' || empty($status)) curr @endif ">
                    <a href="{{ route('admin/drp/shop', 'status=active&card_id='.$card_id . '&user_id='.$user_id) }}">{{ $lang['drp_shop_status']['active'] }}({{ $count['active'] ?? 0 }})</a>
                </li>
                <li class="@if($status == 'wait') curr @endif">
                    <a href="{{ route('admin/drp/shop', 'status=wait&card_id='.$card_id . '&user_id='.$user_id) }}">{{ $lang['drp_shop_status']['wait_audit'] }}({{ $count['wait'] ?? 0 }})</a>
                </li>
                <li class="@if($status == 'expired') curr @endif">
                    <a href="{{ route('admin/drp/shop', 'status=expired&card_id='.$card_id . '&user_id='.$user_id) }}">{{ $lang['drp_shop_status']['expired'] }}({{ $count['expired'] ?? 0 }})</a>
                </li>
            </ul>
        </div>

        <div class="flexilist">
            <div class="common-head">
                <div class="fl">
                    <a href="{{ route('admin/drp/add_shop') }}" class="">
                        <div class="fbutton"><div class="add "><span><i class="fa fa-plus"></i>{{ lang('admin/drp.add_shop') }}</span></div></div>
                    </a>
                </div>

                <div class="search">
                    <form action="{{ route('admin/drp/shop', ['status' => $status]) }}" method="post">
                        <div class="select_w140 imitate_select ">
                            <div class="cite">
                                @if(isset($card_id) && $card_id)
                                    {{$card_name}}
                                    @else
                                    {{ lang('admin/common.please_select') }}{{ $lang['drp_shop_name'] }}
                                @endif</div>
                            <ul>
                                <li><a href="javascript:;" data-value="">{{$lang['all']}}</a></li>
                                @foreach($card_list as $key=>$val)
                                <li><a href="javascript:;" data-value="{{ $val['id'] }}">{{ $val['name'] }}</a></li>
                                @endforeach
                            </ul>
                            <input name="card_id" type="hidden" value="{{ $card_id ?? 0 }}">
                        </div>
                        <div class="input">
                            <input type="text" placeholder="{{ $lang['search_keywords'] }}" @if(isset($keyword) && $keyword) value="{{$keyword}}" @endif name="keyword" class="text nofocus" autocomplete="off">
                            @csrf
                            <input type="hidden" name="user_id" value="{{ $user_id }}">
                            <input type="submit" name="search" value="" class="btn" style="font-style:normal">
                        </div>
                    </form>
                </div>
            </div>
            <div class="common-content">
                <div class="list-div" id="listDiv">
                    <table cellpadding="0" cellspacing="0" border="0">
                        <thead>
                        <tr>
                            <th width="15%">
                                <div class="tDiv">{{ $lang['user_name'] }}</div>
                            </th>
                            <th>{{--会员卡--}}
                                <div class="tDiv"> {{ $lang['drp_shop_name'] }}</div>
                            </th>
                            <th>
                                <div class="tDiv">{{ $lang['shop_name'] }}</div>
                            </th>
                            <th>
                                {{--推荐人--}}
                                <div class="tDiv">{{ $lang['parent_name'] }}</div>
                            </th>
                            <th>{{--申请时间--}}
                                <div class="tDiv">{{ $lang['apply_time'] }}</div>
                            </th>
                            <th>
                                {{--审核状态--}}
                                <div class="tDiv">{{ $lang['check_status'] }}</div>
                            </th>
                            <th>{{--权益开始时间--}}
                                <div class="tDiv">{{ $lang['open_time'] }}</div>
                            </th>
                            <th>
                                {{--店铺状态--}}
                                <div class="tDiv">{{ $lang['shop_status'] }}</div>
                            </th>
                            <th width="20%">
                                <div class="tDiv text-center">{{ lang('admin/common.handler') }}</div>
                            </th>
                        </tr>
                        </thead>

                        @if(isset($list) && $list)

                            @foreach($list as $key => $val)

                                <tr>
                                    <td>
                                        <div class="tDiv">
                                            <div class="drp-img fl">
                                                @if(isset($val['shop_portrait']) && $val['shop_portrait'])
                                                    <img class="img-rounded" src="{{ $val['shop_portrait'] }}" width="50" height="50" alt="{{ $val['user_name'] ?? '' }}"/>
                                                @endif
                                            </div>
                                            <div class="drp-name fl">
                                                {{ $val['user_name'] ?? '' }}
                                                <br>{{ $val['mobile'] }}
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="tDiv">{{ $val['credit_name'] }}</div>
                                    </td>
                                    <td>
                                        <div class="tDiv">{{ $val['shop_name'] }}</div>
                                    </td>
                                    <td>
                                        <div class="tDiv">{{ $val['parent_name'] ?? '' }}</div>
                                    </td>
                                    <td>
                                        <div class="tDiv">{{ $val['apply_time_format'] }}</div>
                                    </td>
                                    <td>
                                        <div class="tDiv">
                                            @if($val['audit'] == 2 || $val['audit'] == 0)
                                                <em class="li_color">{{ $val['audit_format'] }}</em>
                                            @else
                                                <em class="color-289">{{ $val['audit_format'] }}</em>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div class="tDiv">{{ $val['open_time_format'] }}</div>
                                    </td>
                                    <td class="handle">
                                        <div class="tDiv">
                                            {{--店铺开启、关闭--}}
                                            @if(isset($val['status']) && $val['status'] == 1)

                                                <a href="{{ route('admin/drp/set_shop', array('id' => $val['id'], 'status'=> 0)) }}" class="btn_trash" title="{{ $lang['drp_close'] }}"><i class="fa fa-toggle-on"></i>{{ $val['status_format'] }}</a>

                                            @else

                                                @if(isset($val['audit']) && $val['audit'] != 2)
                                                    <a href="{{ route('admin/drp/set_shop', array('id' => $val['id'], 'status'=> 1)) }}" class="btn_trash" title="{{ $lang['drp_open'] }}"><i class="fa fa-toggle-off"></i><em class="li_color">{{ $val['status_format'] }}</em></a>
                                                @endif

                                            @endif

                                        </div>
                                    </td>
                                    <td class="handle text-center">
                                        <div class="tDiv a2">

                                            @if($status != 'expired')
                                            <a href="{{ route('admin/drp/edit_shop', array('id' => $val['id'])) }}" class="btn_trash"><i class="fa fa-edit"></i>{{ lang('admin/drp.edit_shop') }}</a>
                                            @endif

                                            @if(empty($val['audit']) && $status != 'expired')

                                                <a href="javascript:;" data-href="{{ route('admin/drp/set_shop', array('id' => $val['id'])) }}" class="btn_edit check-drp"><i class="fa fa-edit"></i>{{ $lang['goto_audit'] }}
                                                </a>

                                            @endif

                                        </div>

                                        <div class="tDiv a2">
                                            <a href="{{ route('admin/drp/shop', array('user_id' => $val['user_id'])) }}" class="btn_see"><i class="sc_icon sc_icon_see"></i>{{ $lang['next_level_drp'] }}
                                            </a>

                                            <a href="{{ route('admin/drp/drp_aff_list', array('auid' => $val['user_id'])) }}" class="btn_see"><i class="sc_icon sc_icon_see"></i>{{ $lang['next_level_user'] }}
                                            </a>
                                        </div>

                                    </td>
                                </tr>

                            @endforeach


                        @else

                            <tbody>
                            <tr>
                                <td class="no-records" colspan="9">{{ $lang['no_records'] }}</td>
                            </tr>
                            </tbody>

                        @endif

                        <tfoot>
                        <tr>
                            <td colspan="12">
                                <div class="list-page">
                                    @include('admin.drp.pageview')
                                </div>
                            </td>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

{{--选择审核框--}}
<div class="check hide" >

    <div class="item" style="padding: 10px; line-height: 10px">
        <div class="label_value">
            <div class="checkbox_items">
                <div class="checkbox_item">
                    <input id="audit_0" type="radio" name="audit" value="0" >
                    <label for="audit_0" class="active" >{{ $lang['audit_0'] }}</label>
                </div>
                <div class="checkbox_item">
                    <input id="audit_1" type="radio" name="audit" value="1" >
                    <label for="audit_1" class="active">{{ $lang['audit_1'] }}</label>
                </div>
                <div class="checkbox_item">
                    <input id="audit_2" type="radio" name="audit" value="2"  >
                    <label for="audit_2" class="active">{{ $lang['audit_2'] }}</label>
                </div>

            </div>

        </div>
    </div>

</div>
<script type="text/javascript">

    var opts1 = {
        'targetId': 'promote_start_date',
        'triggerId': ['promote_start_date'],
        'alignId': 'text_time1',
        'format': '-',
        'hms': 'off'
    }, opts2 = {
        'targetId': 'promote_end_date',
        'triggerId': ['promote_end_date'],
        'alignId': 'text_time2',
        'format': '-',
        'hms': 'off'
    }

    xvDate(opts1);
    xvDate(opts2);


    // 审核
    $('.check-drp').click(function () {

        var that = $(this);

        layer.open({
            type: 1
            ,title: "{{ $lang['confirm_check_drp'] }}"
            ,closeBtn: false
            ,area: '300px;'
            ,shade: 0.8
            ,id: 'LAY_layuipro' //设定一个id，防止重复弹出
            ,resize: false
            ,btn: ['{{ lang('admin/common.ok') }}', '{{ lang('admin/common.cancel') }}']
            ,btnAlign: 'c'
            ,moveType: 1 //拖拽模式，0或者1
            ,content: $('.check').html()
            ,success: function(layero){
                //var btn = layero.find('.layui-layer-btn');
                //btn.find('.layui-layer-btn0').attr({href: url});
            },yes: function(){

                var check = $("input[name='audit']:checked").val();
                var url = that.attr("data-href");

                url = url + '&audit=' + check;
                //console.log(url)
                window.location.href= url;
            }
        });
    });

    //div仿select下拉选框 start
    $(document).on("click", ".imitate_select .cite", function () {
        $(".imitate_select ul").hide();
        $(this).parents(".imitate_select").find("ul").show();
        //$(this).siblings("ul").perfectScrollbar("destroy");
        //$(this).siblings("ul").perfectScrollbar();
    });

    $(document).on("click", ".imitate_select li  a", function () {
        var _this = $(this);
        var val = _this.data('value');
        var text = _this.html();
        _this.parents(".imitate_select").find(".cite").html(text);
        _this.parents(".imitate_select").find("input[type=hidden]").val(val);
        _this.parents(".imitate_select").find("ul").hide();
    });
    //div仿select下拉选框 end

    //select下拉默认值赋值
    $('.imitate_select').each(function () {
        var sel_this = $(this)
        var val = sel_this.children('input[type=hidden]').val();
        sel_this.find('a').each(function () {
            if ($(this).attr('data-value') == val) {
                sel_this.children('.cite').html($(this).html());
            }
        })
    });

    $(document).click(function(e){
        //仿select
        if(e.target.className !='cite' && !$(e.target).parents("div").is(".imitate_select")){
            $('.imitate_select ul').hide();
        }

        //分类
        if(e.target.id !='category_name' && !$(e.target).parents("div").is(".select-container")){
            $('.categorySelect .select-container').hide();
        }
    });

</script>

@include('admin.drp.pagefooter')

