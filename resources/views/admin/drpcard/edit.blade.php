@include('admin.drpcard.pageheader')

<style>
    .dates_box_top{ height: 42px;}
    .dates_bottom {height: 42px;}
    .dates_hms {width: 102px;}
    .dates_btn {width: 118px;}
    .card-preview { width: 235px; height: 125px; border-radius: 6px; position: absolute;
        right: 30px;
        top: 70px; overflow: hidden;
        background-color: #efccc6;}
    .card-preview img{ width: 235px; height: 125px; border-radius: 6px;}
    .card-preview-title { position: absolute; font-size: 16px;right: 80px;
        top: 80px; width: 170px; height: 24px; overflow: hidden; text-overflow: ellipsis;}
    .select-goods-wrap {margin: 10px 0;}
    .select-goods-wrap th {padding: 3px 10px;}
    .select-goods-wrap td {padding: 5px 10px;}
    .select-goods-wrap th {font-size: 14px; color: #333; background-color: #EEEEEE;}
    .select-goods-wrap tr td {border-bottom: 1px solid #ddd;}
    .select-goods-wrap tr:last-child td {border-bottom: 2px solid #ddd;}
    .select-goods-wrap img { width: 70px; height: 70px; margin-right:10px; float: left;}
    .select-goods-wrap td p {    height: 70px; overflow: hidden;}
</style>
<div class="wrapper">
	<div class="title"><a href="{{ route('admin/drp_card/index') }}" class="s-back">{{ lang('common.back') }}</a> {{ lang('admin/drp.drp_manage') }} - {{ lang('admin/drpcard.edit_drp_card') }}</div>
	<div class="content_tips">
        <div class="explanation" id="explanation">
            <div class="ex_tit">
                <i class="sc_icon"></i><h4>{{ lang('admin/common.operating_hints') }}</h4><span id="explanationZoom" title="{{ lang('admin/common.fold_tips') }}"></span>
            </div>
            <ul>
                <li>{!! lang('admin/common.operation_prompt_content_common') !!}</li>
            </ul>
        </div>
        <div class="flexilist">
            <div class="main-info">
                <form method="post" action="{{ route('admin/drp_card/edit') }}" class="form-horizontal" enctype="multipart/form-data" role="form" >
                    <div class="switch_info add-rights">

                        {{--基本信息--}}
                        <div class="item_title" style="position: relative;">
                            <div class="vertical"></div>
                            <div class="f15">{{ lang('admin/users.base_title') }}</div>

                            <div class="card-preview" @if(isset($info['background']) && $info['background'] == 0 && $info['background_color']) style="background-color: {{ $info['background_color']}}" @endif>
                                @if(isset($info['background']) && $info['background'] == 1)
                                <img src="{{ $info['background_img'] ?? '' }}" class="img-responsive">
                                @endif
                            </div>
                            <div class="card-preview-title">{{ $info['name'] ?? '' }}</div>
                        </div>

                        <div class="item">
                            <div class="label-t"><em class="color-red">*</em> {{ lang('admin/drpcard.drp_card_name') }}</div>
                            <div class="label_value">
                                <input type="text" name="data[name]" class="form-control w350 card-title" value="{{ $info['name'] ?? '' }}"/>
                            </div>
                        </div>
                        
                        {{--背景--}}
                        <div class="item item_background">
                            <div class="label-t">{{ lang('admin/drpcard.card_bg_set') }}</div>
                            <div class="label_value">

                                <div class="checkbox_items">
                                    <div class="checkbox_item">
                                        <input type="radio" name="background" class="ui-radio event_zhuangtai" id="value_background_0" value="0"
                                               @if((isset($info['background']) && empty($info['background'])) || !isset($info['background']))
                                               checked
                                                @endif
                                        >
                                        <label for="value_background_0" class="ui-radio-label  flex pl30
												@if((isset($info['background']) && empty($info['background'])) || !isset($info['background']))
                                                active
                                                @endif
                                                " style="width: 200px;">
                                            <div id="font_color" class="font_color mr10 w300">
                                                <input type='text' id="full" value="{{ $info['background_color'] ?? ''}}" style="display:none"/>
                                            </div>
                                            <input type="hidden" id="text_color" name="background_color" value="{{ $info['background_color'] ?? '#efccc6' }}">
                                        </label>
                                    </div>
                                    <div class="checkbox_item">
                                        <input type="radio" name="background" class="ui-radio event_zhuangtai" id="value_background_1" value="1"
                                               @if(isset($info['background']) && $info['background'] == 1)
                                               checked
                                                @endif
                                        >
                                        <label for="value_background_1" class="ui-radio-label flex pl30
												@if(isset($info['background']) && $info['background'] == 1)
                                                active
                                                @endif
                                                " style="width: 400px;">
                                            <div class="type-file-box w600">
                                                <input type="button" id="button" class="type-file-button">
                                                <input type="file" class="type-file-file" name="background_img" size="30" data-state="imgfile" hidefocus="true">
                                                @if(isset($info['background_img']) && !empty($info['background_img']))
                                                <span class="show">
                                                    <a href="{{ $info['background_img'] ?? '' }}" class="nyroModal fancybox" title="{{ lang('admin/common.preview') }}">
                                                        <i class="fa fa-picture-o" data-tooltipimg="{{ $info['background_img'] ?? '' }}" ectype="tooltip"></i>
                                                    </a>
                                                </span>
                                                <span class="ml10 js-remove-img"><i class="fa fa-trash-o"></i></span>
                                                @endif
                                                <input type="text" name="file_path" class="type-file-text" value="{{ $info['background_img'] ?? '' }}" id="textfield" style="display:none">
                                                <div class="notic">{{ lang('admin/drpcard.background_img_notice') }}</div>
                                            </div>
                                        </label>
                                    </div>

                                </div>

                            </div>
                        </div>

                        {{--有效期--}}
                        <div class="item">
                            <div class="label-t">{{ lang('admin/drpcard.drp_card_expiry') }}</div>
                            <div class="label_value">
                                <div class="checkbox_items">
                                    <div class="checkbox_item">
                                        <input type="radio" name="data[expiry_type]" class="ui-radio event_zhuangtai" id="value_expiry_type_0" value="forever"
                                               @if((isset($info['expiry_type']) && $info['expiry_type'] == 'forever') || !isset($info['expiry_type']))
                                               checked
                                            @endif
                                        >
                                        <label for="value_expiry_type_0" class="ui-radio-label
                                            @if(isset($info['expiry_type']) && $info['expiry_type'] == 'forever')
                                            active
                                            @endif
                                            ">
                                            {{ lang('admin/drpcard.expiry_type_forever') }}
                                            <input type="text" class="hide" name="data[expiry_date]" value="">
                                        </label>
                                    </div>
                                    <div class="checkbox_item">
                                        <input type="radio" name="data[expiry_type]" class="ui-radio event_zhuangtai" id="value_expiry_type_1" value="days"
                                               @if(isset($info['expiry_type']) && $info['expiry_type'] == 'days')
                                               checked
                                            @endif
                                        >
                                        <label for="value_expiry_type_1" class="ui-radio-label
                                            @if(isset($info['expiry_type']) && $info['expiry_type'] == 'days')
                                            active
                                            @endif
                                            ">
                                            {{ lang('admin/drpcard.expiry_type_days') }}
                                            <input type="number" min="1" step="1" name="data[expiry_date]" value="{{ $info['expiry_date'] ?? '' }}" class="form-control w100 input-sm">
                                            {{ lang('admin/drpcard.expiry_type_days_part') }}
                                        </label>
                                    </div>
                                    <div class="checkbox_item">
                                        <input type="radio" name="data[expiry_type]" class="ui-radio event_zhuangtai" id="value_expiry_type_2" value="timespan"                                                 @if(isset($info['expiry_type']) && $info['expiry_type'] == 'timespan')
                                               checked
                                            @endif
                                        >
                                        <label for="value_expiry_type_2" class="ui-radio-label fl
                                            @if(isset($info['expiry_type']) && $info['expiry_type'] == 'timespan')
                                            active
                                            @endif
                                            " >
                                            <div id="text_time1" class="text_time">
                                                <input type="text" class="text expiry_date" name="expiry_date[]" id="expiry_date_start" value="{{ $info['expiry_date_start'] ?? ''}}" placeholder="{{ lang('admin/drpcard.expiry_date_start') }}" autocomplete="off" readonly />
                                            </div>
                                            <span class="bolang">{{ lang('admin/drpcard.to') }}</span>
                                            <div id="text_time2" class="text_time">
                                                <input type="text" class="text expiry_date" name="expiry_date[]" id="expiry_date_end" value="{{ $info['expiry_date_end'] ?? ''}}" placeholder="{{ lang('admin/drpcard.expiry_date_end') }}" autocomplete="off" readonly />
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{--领取设置--}}
                        <div class="item item_receive_type">
                            <div class="label-t">{{ lang('admin/drpcard.receive_config') }}</div>
                            <div class="label_value ">
                                <div class="checkbox_items">

                                    @if(isset($receive_type_checkbox) && !empty($receive_type_checkbox))

                                        @foreach($receive_type_checkbox as $value)
                                        <div class="checkbox_item">
                                            <input type="checkbox" name="receive_type[]" class="checkbox" id="value_receive_type_{{ $value['type'] }}" value="{{ $value['type'] }}"
                                                   @if(isset($value['is_checked']) && $value['is_checked'] == '1')
                                                   checked
                                                    @endif
                                            >
                                            <label for="value_receive_type_{{ $value['type'] }}" class="checkbox_stars
                                                @if(isset($value['is_checked']) && $value['is_checked'] == '1')
                                                active
                                                @endif
                                                ">{{ lang('admin/drpcard.receive_type_' . $value['type']) }}

                                                @if(isset($value['type']) && $value['type'] == 'goods')

                                                    <input type="hidden" name="input_value_old[{{ $value['type'] }}]" class="form-control w250 input-sm" value="{{ $value['value'] ?? ''}}"  />
                                                    <input type="hidden" readonly name="input_value[{{ $value['type'] }}]" class="form-control w250 input-sm readonly" value="{{ $value['value'] ?? ''}}"  />

                                                    {{--选择商品--}}
                                                    <a href="{{ route('admin/drp_card/select_goods', ['membership_card_id' => $info['id']]) }}" class="fancybox fancybox.iframe ml10"><span class="btn btn-info btn-xs ">{{ lang('admin/drpcard.select_goods_menu') }}</span></a>
                                                    <div class="select-goods-wrap">

                                                    </div>
                                                @else

                                                    @if(isset($value['type']) && $value['type'] == 'free')
                                                        <input type="hidden" name="input_value[{{ $value['type'] }}]"  />
                                                    @else

                                                    <input type="number"
                                                       @if(isset($value['type']) && $value['type'] != 'integral')

                                                       min="0.01" step="0.01"

                                                       @else

                                                       min="1" step="1"

                                                       @endif

                                                       name="input_value[{{ $value['type'] }}]" class="form-control w250 input-sm"  value="{{ $value['value'] ?? ''}}" autocomplete="off" placeholder="{{ lang('admin/drpcard.placeholder_for_' . $value['type']) }}" />

                                                    @endif

                                                @endif

                                            </label>
                                        </div>
                                        @endforeach

                                    @endif

                                </div>
                            </div>
                        </div>

                        <div class="item">
                            <div class="label-t">{{ lang('admin/drpcard.drp_card_desc') }}</div>
                            <div class="label_value">
                                <textarea name="data[description]" rows="5" class="form-control w350">{{ $info['description'] ?? '' }}</textarea>
                            </div>
                        </div>

                        <div class="item">
                            <div class="label-t">{{ lang('admin/common.sort_order') }}</div>
                            <div class="label_value">
                                <input type="number" name="data[sort]" class="form-control w100" value="{{ $info['sort'] ?? '50' }}">
                            </div>
                        </div>

                        <div class="item hide">
                            <div class="label-t">{{ lang('admin/common.status') }}</div>
                            <div class="label_value">
                                <input type="text" name="data[enable]" class="form-control w100" value="1">
                            </div>
                        </div>

                    </div>

                    <div class=" bind-rights pt20">

                        {{--添加会员权益--}}
                        <a href="{{ route('admin/drp_card/bind_rights', ['membership_card_id' => $info['id']]) }}" class="">
                            <div class="fbutton"><div class="add "><span><i class="fa fa-plus"></i>{{ lang('admin/drpcard.bind_rights') }}</span></div></div>
                        </a>

                        <div class="list-div pt20" id="listDiv">
                            <table cellspacing="0" cellpadding="0" border="0" class="table table-striped">
                                <tr class="active">
                                    <th>
                                        <div class="tDiv">{{ lang('admin/users.rights_icon') }}</div>
                                    </th>
                                    <th>
                                        <div class="tDiv">{{ lang('admin/users.rights_name') }}</div>
                                    </th>
                                    <th width="30%">
                                        <div class="tDiv">{{ lang('admin/users.trigger_content') }}</div>
                                    </th>
                                    <th >
                                        <div class="tDiv">{{ lang('admin/users.trigger_point') }}</div>
                                    </th>
                                    <th width="15%">
                                        <div class="tDiv">{{ lang('admin/common.handler') }}</div>
                                    </th>
                                </tr>

                                @if(isset($rights_list) && $rights_list)

                                    @foreach($rights_list as $value)

                                        <tr>
                                            <td>
                                                <div class="tDiv"><img class="img-rounded" src="{{ $value['icon'] ?? '' }}" width="50" height="50" ></div>
                                            </td>
                                            <td>
                                                <div class="tDiv">{{ $value['name'] ?? '' }}</div>
                                            </td>
                                            <td>
                                                <div class="tDiv @if($value['enable'] == 0)li_color @endif">{{ $value['rights_configure_format'] ?? '' }}</div>
                                            </td>

                                            <td>
                                                <div class="tDiv">{{ $value['trigger_point_format'] ?? '' }}</div>
                                            </td>
                                            <td class="handle">
                                                <div class="tDiv">
                                                    @if($value['enable'] == 1)
                                                        <a href="{{ route('admin/drp_card/edit_rights', ['id' => $value['id']]) }}" class="btn_edit"><i class="fa fa-edit"></i>{{ lang('admin/common.edit') }}</a>
                                                    @else
                                                        <a href="javascript:;" title="{{ $value['rights_configure_format'] ?? '' }}" class="btn_edit disabled"><i class="fa fa-edit"></i>{{ lang('admin/common.edit') }}</a>
                                                    @endif
                                                    <a href="javascript:;" data-href="{{ route('admin/drp_card/unbind_rights', ['id' => $value['id']]) }}" class="btn_trash js-unbind-rights"><i class="fa fa-trash-o"></i>{{ lang('admin/common.drop') }}</a>
                                                </div>
                                            </td>
                                        </tr>

                                    @endforeach

                                @else

                                    <tbody>
                                    <tr>
                                        <td class="no-records" colspan="5">{{ lang('admin/common.no_records') }}</td>
                                    </tr>
                                    </tbody>

                                @endif

                            </table>
                        </div>


                        <div class="item">
                            <div class="label-t">&nbsp;</div>
                            <div class="label_value info_btn">
                                @csrf
                                <input type="hidden" name="id" value="{{ $info['id'] ?? '' }}"/>
                                <input type="hidden" name="data[type]" value="{{ $type ?? '2' }}"/>
                                <input type="submit" value="{{ lang('admin/common.button_submit_alt') }}" class="button btn-danger bg-red"/>
                                <input type="button" value="{{ lang('admin/common.drop') }}" class="button btn-info js-drop-card"/>

                                <input type="button" value="{{ lang('admin/drpcard.disabled_card') }}" class="button button_reset js-disabled-card"/>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
  </div>
</div>
<script type="text/javascript">
    $(function () {
        //title
        $(".card-title").on('change', function () {
            $(".card-preview-title").text($(this).val());
        });

        var db_color = "{{ $info['background_color'] ?? '#efccc6'}}";
        // 颜色设置
        $("#font_color input").spectrum({
            color: db_color,//初始化颜色
            showInitial: true,
            showPalette: true,
            showSelectionPalette: true,
            showInput: true,
            maxPaletteSize: 10,
            preferredFormat: "hex",
            containerClassName: "select-color-box",//引用类选择器,可以改变颜色选择器面板的样式
            replacerClassName: "select-color",//引用类选择器,可以改变颜色选择器的样式
            showAlpha: false,//显示透明度选择
            theme: "sp-light",
            palette: [
                ["#efccc6", "#f3eef4", "#f5e7cd", "#f8bfa2", "#44454a","#cccccc"],
            ],
            change: function (color) {
                preview(color);
            },
        });

        $('.sp-choose').click(function(){
            var sp_color = $('.sp-input').val();
            $('input[name="background_color"]').val(sp_color);
        });
        //切换背景
        $("input[name='background']").on('change', function () {
            console.log($(this).val());
            if($(this).val() == 1) {
                $('.card-preview').css('background-color', '#fff');
            } else {
                var color = $("#full").val();
                // console.log(color);
                $('.card-preview').html('');
                $('.card-preview').css('background-color', color);
            }
        });

        // 上传图片预览
        $("input[name=background_img]").change(function (event) {
            // 根据这个 <input> 获取文件的 HTML5 js 对象
            var files = event.target.files, file;
            if (files && files.length > 0) {
                // 获取目前上传的文件
                file = files[0];

                // 那么我们可以做一下诸如文件大小校验的动作
                if (file.size > 1024 * 1024 * 5) {
                    alert('图片大小不能超过 5MB!');
                    return false;
                }

                // 预览图片
                var reader = new FileReader();
                // 将文件以Data URL形式进行读入页面
                reader.readAsDataURL(file);

                reader.onload = function (e) {
                    // console.log(this.result);
                    $('.card-preview').html('');
                    var img = '<img src="'+this.result+'" class="img-responsive">';
                    $('.card-preview').append(img);
                    // $(".img-responsive").attr("src", this.result);
                };
            }
        });

        //选择的商品列表
        var goods_id = $("input[name='input_value[goods]']").val();
        if (goods_id) {
            showGoods(goods_id);
        }

        $("input[name='input_value[goods]']").on('change', function () {
            var goods_id = $(this).val();
            if (goods_id) {
                showGoods(goods_id);
            }
        });


        // 删除图片
        $(".js-remove-img").click(function () {
            var url = "{{ route('admin/drp_card/remove_img') }}";

            var id = $('input[name="id"]').val();
            var file_path = $('input[name="file_path"]').val();

            $.post(url, {
                id:id,
                file_path:file_path
            }, function(data){
                layer.msg(data.msg);
                if (data.error == 0 ) {
                    if (data.url) {
                        window.location.href = data.url;
                    } else {
                        window.location.reload();
                    }
                }
                return false;
            }, 'json');
        });

        // 删除权益
        $(".js-unbind-rights").click(function () {
            var url = $(this).attr("data-href");
            //询问框
            layer.confirm('{{ lang('admin/drpcard.confirm_unbind_rights')}}', {
                btn: ['{{ lang('admin/common.ok') }}', '{{ lang('admin/common.cancel')}}'] //按钮
            }, function(){
                $.post(url, '', function(data){
                    layer.msg(data.msg);
                    if (data.error == 0 ) {
                        if (data.url) {
                            window.location.href = data.url;
                        } else {
                            window.location.reload();
                        }
                    }
                    return false;
                }, 'json');
            });
        });

        // 删除权益卡
        $(".js-drop-card").click(function () {
            var url = "{{ route('admin/drp_card/delete') }}";

            var id = $('input[name="id"]').val();
            //询问框
            layer.confirm('{{ lang('admin/drpcard.confirm_drop_card')}}', {
                btn: ['{{ lang('admin/common.ok') }}', '{{ lang('admin/common.cancel')}}'] //按钮
            }, function(){
                $.post(url, {
                    id:id
                }, function(data){
                    layer.msg(data.msg);
                    if (data.error == 0 ) {
                        if (data.url) {
                            window.location.href = data.url;
                        } else {
                            window.location.reload();
                        }
                    }
                    return false;
                }, 'json');
            });
        });

        // 禁用权益卡 js-disabled-card
        $('.js-disabled-card').on('click', function () {
            var url = "{{ route('admin/drp_card/disabled') }}";

            var id = $('input[name="id"]').val();

            //询问框
            layer.confirm('{{ lang('admin/drpcard.confirm_disabled_card', ['card_name' => $info['name']]) }}', {
                btn: ['{{ lang('admin/common.ok') }}', '{{ lang('admin/common.cancel') }}']
            }, function(){
                $.post(url, {
                    id:id
                }, function(data){
                    layer.msg(data.msg);
                    if (data.error == 0) {
                        if (data.url) {
                            window.location.href = data.url;
                        } else {
                            window.location.reload();
                        }
                    }
                    return false;
                }, 'json');
            });
        });

        // 验证提交
        $(".form-horizontal").submit(function(){

            // 选中 则验证 大于0
            var check_buy = $("input[type='checkbox'][id='value_receive_type_buy']:checked").length;
            if (check_buy > 0) {
                var buy = $('input[name="input_value[buy]"]').val();
                if (buy <= 0) {
                    layer.msg('{{ lang('admin/drpcard.please_input_value') }}');
                    return false;
                }
            }
            var check_order = $("input[type='checkbox'][id='value_receive_type_order']:checked").length;
            if (check_order > 0) {
                var order = $('input[name="input_value[order]"]').val();
                if (order <= 0) {
                    layer.msg('{{ lang('admin/drpcard.please_input_value') }}');
                    return false;
                }
            }

            var check_integral = $("input[type='checkbox'][id='value_receive_type_integral']:checked").length;
            if (check_integral > 0) {
                var integral = $('input[name="input_value[integral]"]').val();
                if (integral <= 0) {
                    layer.msg('{{ lang('admin/drpcard.please_input_value') }}');
                    return false;
                }
            }


        });

    });

    function preview(color) {
        console.log(color.toHexString());
        $('.card-preview').html('');
        $('.card-preview').css('background-color', color.toHexString());
    }

    function showGoods(goods_id) {
        // 请求商品列表
        var url = "{{ route('admin/drp_card/get_goods') }}";

        $.post(url, {
            goods_id:goods_id
        }, function(data){
            if (data.error == 0 ) {
                var temp = '<table><tr><th>{{ lang('admin/drpcard.goods_info') }}</th><th>{{ lang('admin/drpcard.price') }}</th></tr>';

                jQuery.each(data.list, function(i, val) {

                    //text = text + "Key:" + i + ", Value:" + val;
                    temp += '<tr><td><img src="'+ val.goods_thumb +'">';
                    temp += '<p>'+val.goods_name+'</p></td>';
                    temp += '<td>'+val.shop_price_format+'</td></tr>';
                });

                temp += '</table>';
                $(".select-goods-wrap").html(temp);
            }
        }, 'json');
    }

    // 大商创PC日历插件
    var opts1 = {
        'targetId':'expiry_date_start',
        'triggerId':['expiry_date_start'],
        'alignId':'text_time1',
        'format':'-',
        'hms':'on',
        'min':'{{ $info['expiry_date_start'] ?? '' }}'
    },opts2 = {
        'targetId':'expiry_date_end',
        'triggerId':['expiry_date_end'],
        'alignId':'text_time2',
        'format':'-',
        'hms':'on',
        'min':'{{ $info['expiry_date_end'] ?? '' }}'
    }

    xvDate(opts1);
    xvDate(opts2);

</script>

@include('admin.drpcard.pagefooter')
