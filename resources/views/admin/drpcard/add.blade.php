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
	<div class="title"><a href="{{ route('admin/drp_card/index') }}" class="s-back">{{ lang('common.back') }}</a> {{ lang('admin/drp.drp_manage') }} - {{ lang('admin/drpcard.add_drp_card') }}</div>
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
                <form method="post" action="{{ route('admin/drp_card/add') }}" class="form-horizontal" enctype="multipart/form-data" role="form" >
                    <div class="switch_info add-rights">

                        {{--基本信息--}}
                        <div class="item_title" style="position: relative;">
                            <div class="vertical"></div>
                            <div class="f15">{{ lang('admin/users.base_title') }}</div>
                            <div class="card-preview">

                            </div>
                            <div class="card-preview-title"></div>
                        </div>

                        <div class="item">
                            <div class="label-t"><em class="color-red">*</em> {{ lang('admin/drpcard.drp_card_name') }}</div>
                            <div class="label_value">
                                <input type="text" name="data[name]" class="form-control w350 card-title" value=""/>
                            </div>
                        </div>
                        
                        {{--背景--}}
                        <div class="item item_background">
                            <div class="label-t">{{ lang('admin/drpcard.card_bg_set') }}</div>
                            <div class="label_value">

                                <div class="checkbox_items">
                                    <div class="checkbox_item">
                                        <input type="radio" name="background" class="ui-radio event_zhuangtai" id="value_background_0" value="0" checked />
                                        <label for="value_background_0" class="ui-radio-label  flex pl30 active" style="width: 200px">
                                            <div id="font_color" class="font_color mr10 w300">
                                                <input type='text' id="full" value="#efccc6" style="display:none"/>
                                            </div>
                                            <input type="hidden" id="text_color" name="background_color" value="#efccc6">
                                        </label>
                                    </div>
                                    <div class="checkbox_item">
                                        <input type="radio" name="background" class="ui-radio event_zhuangtai" id="value_background_1" value="1">
                                        <label for="value_background_1" class="ui-radio-label flex pl30" style="width: 400px;">
                                            <div class="type-file-box w600">
                                                <input type="button" id="button" class="type-file-button">
                                                <input type="file" class="type-file-file" name="background_img" size="30" data-state="imgfile" hidefocus="true">
                                                @if(isset($info['background_img']) && !empty($info['background_img']))
                                                <span class="show">
                                                    <a href="#inline" class="nyroModal fancybox" title="{{ lang('admin/common.preview') }}">
                                                        <i class="fa fa-picture-o"></i>
                                                    </a>
                                                </span>
                                                @endif
                                                
                                                <input type="text" name="file_path" class="type-file-text" value="" id="textfield" style="display:none">
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
                                        <input type="radio" name="data[expiry_type]" class="ui-radio event_zhuangtai" id="value_expiry_type_0" value="forever" checked />
                                        <label for="value_expiry_type_0" class="ui-radio-label active ">
                                            {{ lang('admin/drpcard.expiry_type_forever') }}
                                            <input type="text" class="hide" name="data[expiry_date]" value="">
                                        </label>
                                    </div>
                                    <div class="checkbox_item">
                                        <input type="radio" name="data[expiry_type]" class="ui-radio event_zhuangtai" id="value_expiry_type_1" value="days"/>
                                        <label for="value_expiry_type_1" class="ui-radio-label">
                                            {{ lang('admin/drpcard.expiry_type_days') }}
                                            <input type="number" min="1" step="1" name="data[expiry_date]" value="" class="form-control w100 input-sm">
                                            {{ lang('admin/drpcard.expiry_type_days_part') }}
                                        </label>
                                    </div>
                                    <div class="checkbox_item">
                                        <input type="radio" name="data[expiry_type]" class="ui-radio event_zhuangtai" id="value_expiry_type_2" value="timespan" />
                                        <label for="value_expiry_type_2" class="ui-radio-label fl" >
                                            <div id="text_time1" class="text_time">
                                                <input type="text" class="text expiry_date" name="expiry_date[]" id="expiry_date_start" value="" placeholder="{{ lang('admin/drpcard.expiry_date_start') }}" autocomplete="off" readonly />
                                            </div>
                                            <span class="bolang">{{ lang('admin/drpcard.to') }}</span>
                                            <div id="text_time2" class="text_time">
                                                <input type="text" class="text expiry_date" name="expiry_date[]" id="expiry_date_end" value="" placeholder="{{ lang('admin/drpcard.expiry_date_end') }}" autocomplete="off" readonly />
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
                                                <input type="checkbox" name="receive_type[]" class="checkbox" id="value_receive_type_{{ $value['type'] }}" value="{{ $value['type'] }}" />
                                                <label for="value_receive_type_{{ $value['type'] }}" class="checkbox_stars">{{ lang('admin/drpcard.receive_type_' . $value['type']) }}

                                                    @if(isset($value['type']) && $value['type'] == 'goods')

                                                        <input type="hidden" readonly name="input_value[{{ $value['type'] }}]" class="form-control w250 input-sm readonly" value=""  />

                                                        {{--选择商品--}}
                                                        <a href="{{ route('admin/drp_card/select_goods') }}" class="fancybox fancybox.iframe ml10"><span class="btn btn-info btn-xs ">{{ lang('admin/drpcard.select_goods_menu') }}</span></a>
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

                                                           name="input_value[{{ $value['type'] }}]" class="form-control w250 input-sm"  value="" autocomplete="off" placeholder="{{ lang('admin/drpcard.placeholder_for_' . $value['type']) }}" />

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
                                <textarea name="data[description]" rows="5" class="form-control w350"></textarea>
                            </div>
                        </div>

                        <div class="item">
                            <div class="label-t">{{ lang('admin/common.sort_order') }}</div>
                            <div class="label_value">
                                <input type="number" name="data[sort]" class="form-control w100" value="50">
                            </div>
                        </div>

                        <div class="item hide">
                            <div class="label-t">{{ lang('admin/common.status') }}</div>
                            <div class="label_value">
                                <input type="text" name="data[enable]" class="form-control w100" value="1">
                            </div>
                        </div>

                        <div class="item">
                            <div class="label-t">&nbsp;</div>
                            <div class="label_value info_btn">
                                @csrf
                                <input type="hidden" name="id" value=""/>
                                <input type="hidden" name="data[type]" value="{{ $type ?? '2' }}"/>
                                <input type="submit" value="{{ lang('admin/common.button_save') }}" class="button btn-danger bg-red"/>
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

        // 颜色设置
        $("#font_color input").spectrum({
            color: '#efccc6',//初始化颜色
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
        $("input[name='input_value[goods]']").on('change', function () {
            console.log($(this).val());
            var goods_id = $(this).val();
            if (goods_id) {
                // 请求商品列表
                var url = "{{ route('admin/drp_card/get_goods') }}";

                $.post(url, {
                    goods_id:goods_id
                }, function(data){
                    if (data.error == 0 ) {
                        console.log(data.list);
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

    // 大商创PC日历插件
    var opts1 = {
        'targetId':'expiry_date_start',
        'triggerId':['expiry_date_start'],
        'alignId':'text_time1',
        'format':'-',
        'hms':'on',
        'min':''
    },opts2 = {
        'targetId':'expiry_date_end',
        'triggerId':['expiry_date_end'],
        'alignId':'text_time2',
        'format':'-',
        'hms':'on',
        'min':''
    }

    xvDate(opts1);
    xvDate(opts2);

</script>

@include('admin.drpcard.pagefooter')
