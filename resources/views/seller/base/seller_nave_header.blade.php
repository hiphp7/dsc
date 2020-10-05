<div class="ecsc-head-layout ">
    <!-- 顶部 -->
    <div class="wrapper">
        <div class="admin-logo">
            <a href="../">
                <div class="t">
                    @if (config('shop.seller_logo'))
                        <img src="{{ asset('assets/'.config('shop.seller_logo')) }}" class="logo"/>
                    @else
                        <img src="{{ asset('assets/seller/images/logo.png') }}" class="logo"/>
                    @endif
                    <h1>{{ lang('seller/common.cp_home') }}</h1>
                </div>
                <div class="en"><img src="{{ asset('assets/seller/images/en.png') }}"/></div>
            </a>
        </div>
        <div class="ecsc-nav">
            <ul class="ecsc-nav-ul">
                <li
                        @if(isset($menu_select['action']) && !$menu_select['action'])
                        class="current"
                        @endif
                ><a href="../">{{ lang('seller/common.00_home') }}</a>
                    <div class="arrow"></div>
                </li>

                @if($seller_menu)

                    @foreach($seller_menu as $menu)

                        @if($menu['url'])

                            <li
                                    @if(isset($menu['action']) && isset($menu_select['action']) && $menu['action'] == $menu_select['action'])
                                    class="current"
                                    @endif
                            ><a href="{{ $menu['url'] }}">{{ $menu['label'] }}</a>
                                <div class="arrow"></div>
                            </li>

                        @endif

                    @endforeach

                @endif

            </ul>
        </div>
        <div class="ecsc-admin">
            <div class="avatar">
                <form action="{{ asset('seller/index.php') }}" method="post" enctype="multipart/form-data"
                      runat="server">
                    @csrf
                    <input type="hidden" name="act" value="upload_store_img">
                    <input type="file" name="img">
                </form>
                <a href="javascript:void(0);"><img src="
                    @if(isset($seller_info) && $seller_info['admin_user_img'])
                    {{ $seller_info['admin_user_img'] }}
                    @else
                    {{ asset('assets/seller/images/tx.png') }}
                    @endif
                            "></a>
            </div>
            <dl>
                <dt>
                    <span>{{ $seller_name }}</span>

                    @if($privilege_seller==1)
                        <a href="{{ asset('seller/privilege.php?act=modif') }}" class="modif"><i
                                    class="icon icon-edit"></i></a>
                    @endif

                </dt>
                <dd>
                    <span><i class="sc_icon sc_icon_seller"></i><a class=""
                                                                   href="{{ asset('merchants_store.php?merchant_id='.$ru_id) }}"
                                                                   target="_blank">{{ lang('seller/common.19_merchants_store') }}</a></span>
                    <span><i class="sc_icon sc_icon_set"></i><a class=""
                                                                href="{{ asset('seller/index.php?act=clear_cache') }}">{{ lang('seller/common.clear_cache') }}</a></span>
                    <span><i class="sc_icon sc_icon_out"></i><a class=""
                                                                href="{{ asset('seller/privilege.php?act=logout') }}">{{ lang('common.logout') }}</a></span>
                </dd>
            </dl>
        </div>
    </div>

</div>