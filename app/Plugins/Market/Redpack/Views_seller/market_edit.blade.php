
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
<div class="wrapper-right of" >
    <div class="tabmenu">
        <ul class="tab">
            <li><a href="{{ route('seller/wechat/market_list', array('type' => $config['keywords'])) }}" class="s-back">返回</a></li>
            <li class="active"><a href="#">{{ $config['name'] }} -
@if(isset($info['id']) && $info['id'])
编辑
@else
添加
@endif
</a></li>
        </ul>
    </div>

    <div class="explanation" id="explanation">
        <div class="ex_tit"><i class="sc_icon"></i><h4>操作提示</h4></div>
        <ul>
            <li>关于设置红包活动概率计算说明：</li>
            <li>1、原理：在随机数最小值与随机数最大值之间，产生一个随机数，与设置的红包发放数字进行匹配。
            <br>2、举例：随机最小值设置为1，最大值设置为100，红包发放数字设置为：1,3,5（注意需要在1,100的数字之间）
            <br>如果用户参与活动时，随机出一个数65， 65不在红包发放数字之间，则没中； 如果随机出一个数在1,3,5 之间，则中奖。
            <br>所以可以看出，中奖概率与随机数最小值与最大值的差值 有一定的相关性。差值越大，中奖的概率越低。相反，中奖的概率越高。
            </li>
        </ul>
    </div>

    <div class="wrapper-list mt20" >

            <form action="{{ route('seller/wechat/market_edit', array('type' => $config['keywords'])) }}" method="post" class="form-horizontal" role="form" enctype="multipart/form-data" onsubmit="return false;">
                <div class="account-setting ecsc-form-goods">

                    <dl>
                        <dt>活动名称：</dt>
                        <dd>
                            <div class="col-sm-3">
                                <input type="text" name="data[name]" class="form-control" value="{{ $info['name'] ?? '' }}" />
                            </div>
                            <div class="notic" style="width:50%" > * 必填。且必须少于32个字符</div>
                        </dd>
                    </dl>
                    <dl>
                        <dt>活动背景：</dt>
                        <dd>
                            <div class="col-md-3">
                                <div class="type-file-box">
                                    <input type="button" class="type-file-button" value="上传...">
                                    <input type="file" class="type-file-file" name="background" data-state="imgfile" hidefocus="dlue" >
                                    <span class="show">
                                        <a href="#inline_background" class="nyroModal fancybox" title="预览">
                                            <i class="fa fa-picture-o" ></i>
                                        </a>
                                    </span>
                                    <input type="text" name="background_path" class="type-file-text" value="{{ $info['background'] ?? ''}}"  style="display:none">
                                </div>
                            </div>
                            <div class="notic" style="width:60%"> - 选填。背景图片建议大于尺寸：320×568 px (参考iphone5手机分辨率)， 支持格式：jpeg,png</div>
                        </dd>
                    </dl>
                    <dl>
                        <dt>起止时间：</dt>
                        <dd>
                            <div class="col-sm-6">
                                <div class="text_time" id="text_time1">
                                    <input type="text" name="data[starttime]" class="form-control text" id="promote_start_date" value="{{ $info['starttime'] }}" />
                                </div>
                                <span class="bolang">~&nbsp;&nbsp;</span>
                                <div class="text_time" id="text_time2">
                                    <input type="text" name="data[endtime]" class="form-control text" id="promote_end_date" value="{{ $info['endtime'] }}" />
                                </div>
                            </div>
                        </dd>
                    </dl>
                    <dl>
                        <dt>活动说明：</dt>
                        <dd>
                            <div class="col-md-5 col-sm-5">
                                <textarea name="data[description]" class="form-control bd1" rows="3">{{ $info['description'] ?? ''}}</textarea>
                            </div>
                        </dd>
                    </dl>
                    <dl>
                        <dt>赞助支持：</dt>
                        <dd>
                            <div class="col-md-5 col-sm-5">
                                <input type="text" name="data[support]" class="form-control"  value="{{ $info['support'] ?? '' }}" />
                            </div>
                            <div class="notic" style="width:50%"> - 选填。 本次活动由XX公司赞助支持</div>
                        </dd>
                    </dl>
                    <dl>
                        <dt>红包类型</dt>
                        <dd>
                            <div class="col-sm-2">
                                <div class="input-group">
                                <select name="config[hb_type]" class="form-control">
                                    <option value='0'
@if($info['config']['hb_type'] == 0)
 selected
@endif
>普通红包</option>
                                    <option value='1'
@if($info['config']['hb_type'] == 1)
 selected
@endif
>裂变红包</option>
                                </select>
                                </div>
                            </div>
                        </dd>
                    </dl>
                    <dl>
                        <dt>MCHID</dt>
                        <dd>
                            <div class="col-sm-3">
                                <input type="password" name="config[mchid]" autocomplete="off" class="form-control" value="{{ $info['config']['mchid'] }}" placeholder="微信支付商户号"/>
                            </div>
                            <div class="notic" style="width:50%"> * 必填。微信支付商户号ID</div>
                        </dd>
                    </dl>
                    <dl>
                        <dt>PARTNERKEY</dt>
                        <dd>
                            <div class="col-sm-6">
                                <input type="password" name="config[partner]" autocomplete="off" class="form-control" value="{{ $info['config']['partner'] }}" placeholder="支付API密钥key" />
                            </div>
                            <div class="notic" style="width:50%"> * 必填。微信支付API密钥key（32位）</div>
                        </dd>
                    </dl>
                    <dl>
                        <dt>基础红包金额</dt>
                        <dd>
                            <div class="col-sm-2">
                                <div class="input-group">
                                <input type="number" name="config[base_money]" class="form-control" value="{{ $info['config']['base_money'] }}" />
                                <span class="input-group-addon">元</span>
                                </div>
                            </div>
                            <div class="notic"> * 必填。红包最小发放金额，普通红包默认最小1元，裂变红包最小3元，最大200元(含加成金额)</div>
                        </dd>
                    </dl>
                    <dl>
                        <dt>加成红包金额</dt>
                        <dd>
                            <div class="col-sm-2">
                                <div class="input-group">
                                <input type="number" name="config[money_extra]" class="form-control" value="{{ $info['config']['money_extra'] }}" />
                                <span class="input-group-addon">元</span>
                                </div>
                            </div>
                            <div class="notic"> - 选填。无加成红包则设为0，设置后红包总金额=基础红包金额+随机加成红包金额</div>
                        </dd>
                    </dl>
                    <dl>
                        <dt>随机数最小值</dt>
                        <dd>
                            <div class="col-sm-2">
                                <input type="number" name="config[randmin]" class="form-control" value="{{ $info['config']['randmin'] }}" />
                            </div>
                            <span class="bolang" >~&nbsp;&nbsp; 随机数最大值</span>
                            <div class="col-sm-2">
                                <input type="number" name="config[randmax]" class="form-control" value="{{ $info['config']['randmax'] }}" />
                            </div>
                            <div class="notic" style="width:50%"> * 必填。随机数必须为整数，且最小值必须大于1</div>
                        </dd>
                    </dl>
                    <dl>
                        <dt>红包发放数字</dt>
                        <dd>
                            <div class="col-sm-6">
                                <input type="text" name="config[sendnum]" class="form-control" value="{{ $info['config']['sendnum'] }}" placeholder="例如：1,3,5,7,9 "/>
                            </div>
                            <span class="notic"> * 必填。格式：1,3,5,7,9 数字间用英文逗号分隔。且必须为位于设置的随机数最小值和最大值之间，当产生的随机数与此处填写的其中一个值相符即发放红包 </span>
                        </dd>
                    </dl>
                    <dl>
                        <dt>红包发放总人数</dt>
                        <dd>
                            <div class="col-sm-2">
                                <div class="input-group">
                                <input type="number" name="config[total_num]" class="form-control" value="{{ $info['config']['total_num'] }}" placeholder=""/>
                                <span class="input-group-addon">人</span>
                                </div>
                            </div>
                            <span class="notic">* 必填。红包发放总人数，即总共有多少人可以领到该组红包（包括分享者）。普通红包发放总人数默认固定为1，裂变红包发放总人数则必须至少为3</span>
                        </dd>
                    </dl>
                    <dl>
                        <dt>使用场景</dt>
                        <dd>
                            <div class="col-sm-2">
                                <div class="input-group">
                                <select name="config[scene_id]" class="form-control">
                                    <option value='0'
@if($info['config']['scene_id'] == '0')
 selected
@endif
>无</option>
                                    <option value='PRODUCT_1'
@if($info['config']['scene_id'] == 'PRODUCT_1')
 selected
@endif
>商品促销</option>
                                    <option value='PRODUCT_2'
@if($info['config']['scene_id'] == 'PRODUCT_2')
 selected
@endif
>抽奖</option>
                                    <option value='PRODUCT_3'
@if($info['config']['scene_id'] == 'PRODUCT_3')
 selected
@endif
>虚拟物品兑奖 </option>
                                    <option value='PRODUCT_4'
@if($info['config']['scene_id'] == 'PRODUCT_4')
 selected
@endif
>企业内部福利</option>
                                    <option value='PRODUCT_5'
@if($info['config']['scene_id'] == 'PRODUCT_5')
 selected
@endif
>渠道分润</option>
                                    <option value='PRODUCT_6'
@if($info['config']['scene_id'] == 'PRODUCT_6')
 selected
@endif
>保险回馈</option>
                                    <option value='PRODUCT_7'
@if($info['config']['scene_id'] == 'PRODUCT_7')
 selected
@endif
>彩票派奖</option>
                                    <option value='PRODUCT_8'
@if($info['config']['scene_id'] == 'PRODUCT_8')
 selected
@endif
>税务刮奖</option>
                                </select>
                                </div>
                            </div>
                            <span class="notic" style="width:50%"> - 选填。发放红包使用场景，默认 无。红包金额大于200元时则必填</span>
                        </dd>
                    </dl>

                    <dl>
                        <dt>提供方名称</dt>
                        <dd>
                            <div class="col-sm-2">
                                <input type="text" name="config[nick_name]" class="form-control" value="{{ $info['config']['nick_name'] }}" />
                            </div>
                            <div class="notic" style="width:50%"> * 必填。可写公司名称，如商创网络。并且须少于16个字符</div>
                        </dd>
                    </dl>
                    <dl>
                        <dt>红包发送方名称</dt>
                        <dd>
                            <div class="col-sm-3">
                                <input type="text" name="config[send_name]" class="form-control" value="{{ $info['config']['send_name'] }}" />
                            </div>
                            <div class="notic" style="width:50%"> * 必填。即商户名称，如天虹百货。并且须少于32个字符</div>
                        </dd>
                    </dl>
                    <dl>
                        <dt>祝福语</dt>
                        <dd>
                            <div class="col-sm-4">
                                <input type="text" name="config[wishing]" class="form-control" value="{{ $info['config']['wishing'] }}" />
                            </div>
                            <div class="notic" style="width:50%"> * 必填。如：感谢您参加红包活动！</div>
                        </dd>
                    </dl>
                    <dl>
                        <dt>备注信息</dt>
                        <dd>
                            <div class="col-sm-4">
                                <input type="text" name="config[remark]" class="form-control" value="{{ $info['config']['remark'] }}" />
                            </div>
                            <div class="notic" style="width:50%"> * 必填。如：红包福利来咯，手慢无，快来快来！</div>
                        </dd>
                    </dl>
                    <dl>
                        <dt>活动链接</dt>
                        <dd>
                            <div class="col-sm-12 step_value" >
                                <span class="text weixin_url">{{ $info['url'] ?? ''}}</span>
                                <input type="hidden" name="data[url]" value="{{ $info['url'] ?? ''}}" />
                            </div>
                            <div class="notic">自动生成推送微信素材消息时所需的活动链接</div>
                        </dd>
                    </dl>
                    <dl>
                        <dt>&nbsp;</dt>
                        <dd class="button_info">
                            <input type="hidden" name="id" value="{{ $info['id'] ?? ''}}">
                            <input type="hidden" name="handler" value="{{ $config['handler'] ?? '' }}">
                            <input type="hidden" name="marketing_type" value="redpack">
                            <input type="hidden" name="data[command]" value="
@if($info['command'])
{{ $info['command'] }}
@else
{{ $config['command'] }}
@endif
" />
                            <input type="submit" name="submit" class="sc-btn sc-blueBg-btn btn35" value="{{ $lang['button_submit'] }}" />
                            <input type="reset" name="reset" class="sc-btn sc-blue-btn btn35" value="{{ $lang['button_revoke'] }}" />
                        </dd>
                    </dl>
                </table>
                </div>
            </form>

    </div>

</div>

<!-- 图片预览 start -->
<div class="panel panel-default" style="display: none;" id="inline_background">
  <div class="panel-body">
     <img src="{{ $info['background'] ?? ''}}" class="img-responsive" id="background-preview" />
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
              prize_level = "未中奖";
              break;
            default:
              prize_level = "";
        }
        var html = '<tr><td class="text-center"><a href="javascript:;" class="glyphicon glyphicon-minus" onClick="delprize(this)"></a></td><td class="text-center"><input type="text" name="config[prize_level][]" class="form-control" placeholder="例如：一等奖" value="'+prize_level+'"></td><td class="text-center"><input type="text" name="config[prize_name][]" class="form-control" placeholder="例如：法拉利跑车"></td><td class="text-center"><input type="text" name="config[prize_count][]" class="form-control" placeholder="例如：3"></td></tr>';
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

        if($('input[name="data[name]"]').val() == ''){
            layer.msg('未填写活动名称.');
            return false;
        }
        if($('input[name="config[base_money]"]').val() == ''){
            layer.msg('未填写基础红包金额.');
            return false;
        }

        if($('input[name="config[appid]"]').val() == ''){
            layer.msg('未填写发放总人数.');
            return false;
        }

        if($('input[name="config[mchid]"]').val() == ''){
            layer.msg('未填写MCHID.');
            return false;
        }

        if($('input[name="config[partner]"]').val() == ''){
            layer.msg('未填写PARTNERKEY.');
            return false;
        }

        if($('input[name="config[wishing]"]').val() == ''){
            layer.msg('未填写祝福语.');
            return false;
        }

        if($('input[name="config[remark]"]').val() == ''){
            layer.msg('未填写备注信息.');
            return false;
        }

        if($('input[name="config[nick_name]"]').val() == ''){
            layer.msg('未填写提供方名称.');
            return false;
        }

        if($('input[name="config[send_name]"]').val() == ''){
            layer.msg('未填写红包发送方名称.');
            return false;
        }

        if($('input[name="data[endtime]"]').val() == ''){
            layer.msg('结束时间不能为空.');
            return false;
        }
        if($('input[name="data[starttime]"]').val() == ''){
            layer.msg('开始时间不能为空.');
            return false;
        }
        if($('input[name="config[randmin]"]').val() == ''){
            layer.msg('未填写随机数下界.');
            return false;
        }
        if($('input[name="config[randmax]"]').val() == ''){
            layer.msg('未填写随机数上界.');
            return false;
        }

        if($('input[name="config[sendnum]"]').val() == ''){
            layer.msg('未填写红包发放数字.');
            return false;
        }

        if(isNaN($('input[name="config[base_money]"]').val())){
            layer.msg("基础红包金额必须为数字！");
            return false;
        }

        if(isNaN($('input[name="config[money_extra]"]').val())){
            layer.msg("加成红包金额必须为数字！");
            return false;
        }

        var min = parseInt($('input[name="config[randmin]"]').val());
        var max = parseInt($('input[name="config[randmax]"]').val());

        var sendnum = $('input[name="config[sendnum]"]').val();
        var sendArr = sendnum.split(",");
        for(var i = 0;i < sendArr.length; i++){
            var temp = parseInt(sendArr[i]);
            if(isNaN(temp)){
                layer.msg("输入必须为数字，且以英文逗号分隔！");
                return false;
                break;
            }
            if(temp < min || temp > max){
                layer.msg("红包发放数字必须在上下界之间，且随机数下界必须小于上界！");
                return false;
                break;
            }
        }


        if($('.prize_list tr').length > 7 ){
            layer.msg('奖项不能超过6项');
            return false;
        }

        var ajax_data = $(".form-horizontal").serialize();
        $(".form-horizontal").ajaxSubmit({
            type: "POST",
            dataType: "json",
            url: "{{ route('seller/wechat/market_edit', array('type' => $config['keywords'])) }}",
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