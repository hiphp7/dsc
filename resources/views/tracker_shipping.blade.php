<link rel="stylesheet" href="{{ asset('vendor/tracker/css/query_v6.css') }}"/>
<div class="container" id="main" v-cloak>
    <div class="main">
        <article>
            <div class="kd-content">
                <section class="result-box">
                    <div class="result-fail" v-show="error">
                        <p>{{ lang('order.tracking_tips.0') }}</p>
                        <p>{{ lang('order.tracking_tips.1') }}</p>
                    </div>

                    <div class="result-success" id="success" v-show="!error">
                        <div class="result-top" id="resultTop">
                            <span id="sortSpan" class="col1-up">{{ lang('order.time') }}</span>
                            <span class="col2">{{ lang('order.location_tracking_progress') }}</span>
                        </div>
                        <ul id="result" class="result-list sortup">
                            <li v-for="(item, index) in list" :class="{ last: index==0,  finish: !status}">
                                <div class="col1">
                                    <dl>
                                        <dt>@{{ item.time }}</dt>
                                    </dl>
                                </div>
                                <div class="col2"><span></span></div>
                                <div class="col3">@{{ item.context }}</div>
                            </li>
                        </ul>
                    </div>

                </section>
            </div>
        </article>
    </div>
    <script src="{{ asset('js/jquery-1.12.4.min.js') }}"></script>
    <script src="{{ asset('js/vue.min.js') }}"></script>
    <script>
        new Vue({
            el: '#main',
            data: {
                error: false,
                list: [],
                status: 0
            },
            created: function () {
                this.loader();
            },
            methods: {
                loader: function () {
                    var that = this;

                    var url = '/api/v4/order/tracker?type={{ $type }}&postid={{ $post_id }}';
                    $.get(url, function (res) {
                        if (res.status === 'success') {
                            that.error = false;
                            that.list = res.data;
                            that.status = res.status;
                        } else {
                            that.error = true;
                        }
                    }, 'JSON');
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
            }
        });
    </script>
