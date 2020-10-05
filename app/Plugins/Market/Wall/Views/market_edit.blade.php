
<style>
/*.dates_box {width: 300px;}*/
.dates_box_top {height: 32px;}
.dates_bottom {height: auto;}
.dates_hms {width: auto;}
.dates_btn {width: auto;}
.dates_mm_list span {width: auto;}

.form-control {font-size: 12px;}

#footer {position: static;bottom:0px;}
</style>
<div class="wrapper">
  <div class="title"><a href="{{ route('admin/wechat/market_list', array('type' => $config['keywords'])) }}" class="s-back">返回</a>{{ $config['name'] }} -
@if(isset($info['id']) && $info['id'])
编辑
@else
添加
@endif
活动</div>
  <div class="content_tips">
      <div class="flexilist">
        <div class="common-content">
            <div class="main-info">
            <form action="{{ route('admin/wechat/market_edit', array('type' => $config['keywords'])) }}" method="post" class="form-horizontal" role="form" enctype="multipart/form-data" onsubmit="return false;" >
                <div class="switch_info">
                <table class="table table-hover ectouch-table">
                    <tr>
                        <td class="text-align-r" width="200">活动名称：</td>
                        <td>
                            <div class="col-md-2">
                                <input type="text" name="data[name]" class="form-control" value="{{ $info['name'] ?? '' }}" />
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-align-r" width="200">公司LOGO：</td>
                        <td>
                            <div class="col-md-2">
                                <div class="type-file-box">
                                    <input type="button"  class="type-file-button">
                                    <input type="file" class="type-file-file" name="logo" data-state="imgfile" hidefocus="true" >
                                    <span class="show">
                                        <a href="#inline_logo" class="nyroModal fancybox" title="预览">
                                            <i class="fa fa-picture-o" ></i>
                                        </a>
                                    </span>
                                    <input type="text" name="logo_path" class="type-file-text" value="{{ $info['logo'] ?? '' }}" style="display:none">
                                </div>
                            </div>
                            <div class="notic">logo图片建议尺寸：335×55 px ，支持格式：jpeg,png</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-align-r" width="200">活动背景：</td>
                        <td>
                            <div class="col-md-2">
                                <div class="type-file-box">
                                    <input type="button" class="type-file-button">
                                    <input type="file" class="type-file-file" name="background" data-state="imgfile" hidefocus="true" >
                                    <span class="show">
                                        <a href="#inline_background" class="nyroModal fancybox" title="预览">
                                            <i class="fa fa-picture-o" ></i>
                                        </a>
                                    </span>
                                    <input type="text" name="background_path" class="type-file-text" value="{{ $info['background'] ?? '' }}"  style="display:none">
                                </div>
                            </div>
                            <div class="notic">背景图片建议尺寸：1920×1080 px (普通宽屏电脑分辨率)， 支持格式：jpeg,png</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-align-r" width="200">开始时间：</td>
                        <td>
                            <div class="col-md-4 col-sm-4">
                                <div class="text_time" id="text_time1">
                                <input type="text" name="data[starttime]" class="form-control text" id="promote_start_date" value="{{ $info['starttime'] }}" />
                                </div>
                          </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-align-r" width="200">结束时间：</td>
                        <td>
                            <div class="col-md-4 col-sm-4">
                                <div class="text_time" id="text_time2">
                                <input type="text" name="data[endtime]" class="form-control text" id="promote_end_date" value="{{ $info['endtime'] }}" />
                                </div>
                           </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-align-r" width="200">奖品列表：</td>
                        <td>
                            <div class="col-md-6 col-sm-6">
                                <div class="form-group">
                                <table class="table ectouch-table prize_list">
                                    <tr>
                                        <th class="text-center" width="10%"><a href="javascript:;" class="glyphicon glyphicon-plus" onClick="addprize(this)"></a></th>
                                        <th class="text-center" >奖项</th>
                                        <th class="text-center" >奖品</th>
                                        <th class="text-center" >数量</th>
                                    </tr>
@if(isset($info['prize_arr']) && $info['prize_arr'])
@foreach($info['prize_arr'] as $v)

                                    <tr>
                                        <td class="text-center"><a href="javascript:;" class="glyphicon glyphicon-minus" onClick="delprize(this)"></a></td>
                                        <td class="text-center"><input type="text" name="config[prize_level][]" class="form-control" placeholder="例如：一等奖" value="{{ $v['prize_level'] }}"></td>
                                        <td class="text-center"><input type="text" name="config[prize_name][]" class="form-control" placeholder="例如：法拉利跑车" value="{{ $v['prize_name'] }}"></td>
                                        <td class="text-center"><input type="number" min="0" step="1" name="config[prize_count][]" class="form-control" placeholder="例如：3" value="{{ $v['prize_count'] }}"></td>
                                   </tr>

@endforeach
@endif
                                </table>
                                </div>
                                <p class="notic"></p>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-align-r" width="200">活动说明：</td>
                        <td>
                            <div class="col-md-4 col-sm-4">
                                <textarea name="data[description]" class="form-control" rows="3">{{ $info['description'] ?? '' }}</textarea>
                          </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-align-r" width="200">赞助支持：</td>
                        <td>
                            <div class="col-md-4 col-sm-4">
                                <textarea name="data[support]" class="form-control" placeholder="例如：本次活动由XX公司赞助支持" rows="3">{{ $info['support'] ?? '' }}</textarea>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-align-r" width="200">活动链接：</td>
                        <td>
                            <div class="col-md-6 item" >
                                <span class="text weixin_url">{{ $info['url'] ?? '' }}</span>
                                <input type="hidden" name="data[url]" value="{{ $info['url'] ?? '' }}" />
                                <div class="notic">自动生成推送微信素材消息时所需的活动链接</div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td></td>
                        <td>
                            <div class="col-md-4 info_btn">
                                <input type="hidden" name="id" value="{{ $info['id'] ?? '' }}">
                                <input type="hidden" name="handler" value="{{ $config['handler'] ?? '' }}">
                                <input type="hidden" name="marketing_type" value="wall">
                                <input type="hidden" name="data[command]" value="
@if(isset($info['command']) && $info['command'])
{{ $info['command'] }}
@else
{{ $config['command'] }}
@endif
" />
                                <input type="submit" name="submit" class="button btn-danger bg-red" value="{{ $lang['button_submit'] }}" />
                                <input type="reset" name="reset" class="button button_reset" value="{{ $lang['button_revoke'] }}" />
                            </div>
                        </td>
                    </tr>
                </table>
                </div>
            </form>
            </div>
        </div>
      </div>
   </div>
</div>

<!-- 图片预览 start -->
<div class="panel panel-default" style="display: none;" id="inline_logo">
  <div class="panel-body">
     <img src="{{ $info['logo'] ?? '' }}" class="img-responsive" id="logo-preview" />
  </div>
</div>
<div class="panel panel-default" style="display: none;" id="inline_background">
  <div class="panel-body">
     <img src="{{ $info['background'] ?? '' }}" class="img-responsive" id="background-preview" />
  </div>
</div>
<!-- 图片预览 end -->
<script type="text/javascript">
    //file移动上去的js
    $(".type-file-box").hover(function(){
        $(this).addClass("hover");
    },function(){
        $(this).removeClass("hover");
    });

    //添加奖项
    var num = $('.prize_list tr').length > 0 ? $('.prize_list tr').length : 1;
    function addprize(obj){

        switch(num)
        {
            case 1:
              prize_level = "一等奖";
              break;
            case 2:
              prize_level = "二等奖";
              break;
            case 3:
              prize_level = "三等奖";
              break;
            case 4:
              prize_level = "四等奖";
              break;
            case 5:
              prize_level = "五等奖";
              break;
            case 6:
              prize_level = "六等奖";
              break;
            default:
              prize_level = "";
        }
        var html = '<tr><td class="text-center"><a href="javascript:;" class="glyphicon glyphicon-minus" onClick="delprize(this)"></a></td><td class="text-center"><input type="text" name="config[prize_level][]" class="form-control" placeholder="例如：一等奖" value="'+prize_level+'"></td><td class="text-center"><input type="text" name="config[prize_name][]" class="form-control" placeholder="例如：法拉利跑车"></td><td class="text-center"><input type="number" min="0" step="1" name="config[prize_count][]" class="form-control" placeholder="例如：3"></td></tr>';
        if(num <= 6){
            $(obj).parent().parent().parent().append(html);
        }else{
            layer.msg('奖项不能超过6项');
            return false;
        }
        num++;
    }
    //删除奖项
    function delprize(obj){
        $(obj).parent().parent().remove();
    }

    // 大商创PC日历插件
    var opts1 = {
        'targetId':'promote_start_date',
        'triggerId':['promote_start_date'],
        'alignId':'text_time1',
        'format':'-',
        'hms':'on',
        'min':'{{ $info['starttime'] }}' //最小时间
    },opts2 = {
        'targetId':'promote_end_date',
        'triggerId':['promote_end_date'],
        'alignId':'text_time2',
        'format':'-',
        'hms':'on',
        'min':'{{ $info['endtime'] }}' //最小时间
    }

    xvDate(opts1);
    xvDate(opts2);


$(function(){
    // 上传图片预览
    $("input[name=logo]").change(function(event) {
        // 根据这个 <input> 获取文件的 HTML5 js 对象
        var files = event.target.files, file;
        if (files && files.length > 0) {
          // 获取目前上传的文件
          file = files[0];

          // 那么我们可以做一下诸如文件大小校验的动作
          if(file.size > 1024 * 1024 * 5) {
            alert('图片大小不能超过 5MB!');
            return false;
          }

          // 预览图片
          var reader = new FileReader();
          // 将文件以Data URL形式进行读入页面
          reader.readAsDataURL(file);
          reader.onload = function(e){

              $("#logo-preview").attr("src", this.result);
          };
        }
    });

    // 上传图片预览
    $("input[name=background]").change(function(event) {
        // 根据这个 <input> 获取文件的 HTML5 js 对象
        var files = event.target.files, file;
        if (files && files.length > 0) {
          // 获取目前上传的文件
          file = files[0];

          // 那么我们可以做一下诸如文件大小校验的动作
          if(file.size > 1024 * 1024 * 5) {
            alert('图片大小不能超过 5MB!');
            return false;
          }

          // 预览图片
          var reader = new FileReader();
          // 将文件以Data URL形式进行读入页面
          reader.readAsDataURL(file);
          reader.onload = function(e){

              $("#background-preview").attr("src", this.result);
          };
        }
    });


    // 提交
    $('input[type="submit"]').click(function(){

        if($('.prize_list tr').length > 7 ){
            layer.msg('奖项不能超过6项');
            return false;
        }
        var prize_count = $("input[name='config[prize_prob][]']").val();
        if (prize_count < 0) {
            layer.msg('数量不能为负数');
            return false;
        }

        var ajax_data = $(".form-horizontal").serialize();
        $(".form-horizontal").ajaxSubmit({
            type: "POST",
            dataType: "json",
            url: "{{ route('admin/wechat/market_edit', array('type' => $config['keywords'])) }}",
            data: {
                ajax_data
            },
            contentType: false,
            cache: false,
            processData:false,
            success: function(data, textStatus) {
                layer.msg(data.msg);
                if(data.error == 0){
                    if(data.url != ''){
                        window.location.href = data.url;
                    }else{
                        window.location.reload();
                    }
                }else{
                    return false;
                }
            },
        });

    });
})

</script>