<div class="footer user-footer">
    <div class="dsc-copyright">
        <div class="w w1200">
            @if(isset($navigator_list['bottom']))
                <p class="footer-ecscinfo pb10">
                    @foreach($navigator_list['bottom'] as $key => $nav)
                        <a href="{{ url('/') . '/' . $nav['url'] }}" @if ($nav['opennew'] == 1) target="_blank" @endif >{{ $nav['name'] }}</a>

                        @if (!$loop->last)
                            |
                        @endif

                    @endforeach
                </p>
            @endif

            @if (isset($img_links)  || isset($txt_links))
                <p class="footer-otherlink">
                    {{--开始图片类型的友情链接--}}
                    @foreach($img_links as $key => $link)
                        <a href="{{ $link['url'] }}" target="_blank" title="{{ $link['name'] }}"><img src="{{ $link['logo'] }}" alt="{{ $link['name'] }}" border="0"/></a>

                        @if (!$loop->last)
                            |
                        @endif

                    @endforeach
                    {{--结束图片类型的友情链接--}}

                    {{--开始文字类型的友情链接--}}
                    @if($txt_links)

                        @foreach($txt_links as $key => $link)
                            <a href="{{ $link['url'] }}" target="_blank" title="{{ $link['name'] }}">{{ $link['name'] }}</a>

                            @if (!$loop->last)
                                |
                            @endif

                        @endforeach

                    @endif
                    {{--结束文字类型的友情链接--}}
                </p>
            @endif
            {{--ICP 证书--}}
            @if (isset($icp_number))
                <p class="copyright_info">{{ $lang['icp_number'] }}:<a href="http://www.miibeian.gov.cn/" target="_blank">{{ $icp_number ?? '' }}</a>
                    POWERED BY<a href="https://www.dscmall.cn/" target="_blank">大商创</a></p>
            @endif
            {{--结束ICP 证书--}}
        </div>
    </div>

    <!--隐藏域-->
    <div class="hidden">
        <input type="hidden" name="seller_kf_IM" value="{{ $shop_information['is_IM'] ?? '' }}" rev="{{ url('/') }}" ru_id="{{ $smarty['get']['merchant_id'] ?? 0 }}"/>
        <input type="hidden" name="seller_kf_qq" value="{{ $basic_info['kf_qq'] ?? '' }}"/>
        <input type="hidden" name="seller_kf_tel" value="{{ $basic_info['kf_tel'] ?? '' }}"/>
        <input type="hidden" name="user_id" value="{{ $user_id ?? 0 }}"/>
    </div>
</div>

<script type="text/jscript" src="{{ asset('js/scroll_city.js') }}"></script>