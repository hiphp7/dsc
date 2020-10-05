<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ trans('user.logistics_tracking') }}</title>
    <link rel="stylesheet" href="{{ asset('vendor/tracker/css/mbase_v6.css') }}"/>
    <link rel="stylesheet" href="{{ asset('vendor/tracker/css/query_v6.css') }}"/>
    <style>
        .company ul {
            padding: 0.3rem;
        }

        .company ul li {
            line-height: 2rem;
            color: #5a5a5a;
        }

        .data-img {
            width: 4rem;
            height: 4rem;
            display: inline-block
        }

        .data-img img {
            width: 4rem;
            height: auto;
        }

        .kd-content {
            margin-bottom: 10px;
            padding: .3rem .3rem 0 .3rem;
            background: #fff;
        }

        .kd-content:last-child {
            margin-bottom: 0;
        }

        .more {
            text-align: center;
            font-size: 14px;
            color: #999;
            padding: 10px 0;
        }

        .result-list {
            overflow: hidden;
            transition: all 0.3s;
        }

        .result-list li.other {
            display: none;
        }
    </style>
</head>
<body>


<div class="container" id="main">
    <div class="main">
            <div class="kd-content" v-for="(trackerItem,trackerIndex) in trackerList" :key="trackerIndex">
                <section class="result-box">
                    <div class="company">
                        <ul>
                            <li>{{ trans('common.shipping_method') }}：@{{ trackerItem.shipping_name }}</li>
                            <li>{{ trans('common.shipping_number') }}：@{{ trackerItem.invoice_no }}</li>
                        </ul>
                    </div>
                    <div class="data-img" v-for="(itemImg, listImg) in trackerItem.img" :key="listImg">
                        <img :src="itemImg.goods_img" class="img" v-if="itemImg.goods_img">
                    </div>
                    <div class="result-fail" v-show="error[trackerIndex]">
                        <p>{{ lang('order.tracking_tips.0') }}</p>
                        <p>{{ lang('order.tracking_tips.1') }}</p>
                    </div>

                    <div class="result-success" id="success">
                        <div class="result-top" id="resultTop" v-if="list[trackerIndex] && list[trackerIndex].length > 0">
                            <span id="sortSpan" class="col1-up">{{ lang('order.time') }}</span>
                            <span class="col2">{{ lang('order.location_tracking_progress') }}</span>
                        </div>
                        <ul id="result" class="result-list sortup" v-if="list[trackerIndex] && list[trackerIndex].length > 0">
                            <li v-for="(item, index) in list[trackerIndex]"
                                :class="{ last: index == 0}" :key="index" v-show="index < number[trackerIndex]">
                                <div class="col1">
                                    <dl>
                                        <dt>@{{ item.time }}</dt>
                                    </dl>
                                </div>
                                <div class="col2"><span></span></div>
                                <div class="col3">@{{ item.context }}</div>
                            </li>
                        </ul>
                        <div class="more" @click="seeMore(trackerItem.shipping_code,trackerItem.invoice_no,trackerIndex)" v-if="!error[trackerIndex] && (!list[trackerIndex] || list[trackerIndex].length == 0)">{{ lang('order.track_shipping_info_one') }}</div>
                        <div class="more" @click="upMore(trackerIndex)" v-if="list[trackerIndex] && list[trackerIndex].length > 0">
                            <span v-if="!status[trackerIndex]">{{ lang('order.track_shipping_info_two') }}</span>
                            <span v-else>{{ lang('order.track_shipping_info_three') }}</span>
                        </div>
                    </div>
                </section>
            </div>
    </div>
    <script type="text/javascript" src="{{ asset('js/jquery-1.9.1.min.js') }}"></script>
    <script src="{{ asset('js/vue.min.js') }}"></script>
    <script>
        new Vue({
            el: '#main',
            data: {
                trackerList:[],
                error: [],
                list: [],
                number: [],
                status: []
            },
            created: function () {
                this.loader()
            },
            methods: {
                loader: function () {
                    var that = this;
                    var url = '/api/v4/order/tracker_order?order_sn={{ $order_sn }}';
                    $.get(url, function (res) {
                        if (res.status === 'success') {
                            that.trackerList = res.data
                        }
                    }, 'JSON');
                },
                seeMore: function (shipping_code,invoice_no,index) {
                    var that = this;
                    var url = '/api/v4/order/tracker?type='+ shipping_code + '&postid=' + invoice_no;

                    $.get(url, function (res) {
                        var error = res.status === 'success' ? false : true;

                        that.error.splice(index,1,error);

                        if (res.status === 'success') {
                            that.list.splice(index,1,res.data)
                            that.number.splice(index,1,res.data.length);
                            that.status.splice(index,1,false);
                        }
                    }, 'JSON');
                },
                upMore: function(index){
                    var that = this;
                    var length = that.list[index].length;
                    if(that.status[index] == false){
                        that.number.splice(index,1,3);
                    }else{
                        that.number.splice(index,1,length);
                    }

                    that.status[index] = !that.status[index]
                }
            },
            filters: {
                dater: function (value) {
                    var date = new Date();
                    date.setTime(value * 1000);
                    return date.toLocaleDateString();
                },
                timer: function (value) {
                    var date = new Date();
                    date.setTime(value * 1000);
                    return date.toLocaleTimeString().substr(2);
                }
            },
            watch:{
                trackerList(){
                    let that = this;
                    that.trackerList.forEach((v,i)=>{
                        that.list.push([]);
                        that.number.push([]);
                        that.status.push([]);
                        that.error.push(false);
                    })
                },
            }
        });
    </script>
</div>
</body>
</html>