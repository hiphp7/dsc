
@if ($type == 1)

    @include('seller.base.seller_pageheader')

    @include('seller.base.seller_nave_header')

    <div class="ecsc-layout">
        <div class="site wrapper">
            @include('seller.base.seller_menu_left')

            <div class="ecsc-layout-right">
                <div class="main-content" id="mainContent">
                    @include('seller.base.seller_nave_header_title')

                    {!! $template_content !!}

                </div>
            </div>

        </div>
    </div>

@include('seller.base.seller_pagefooter')

@else

{!! $template_content !!}

@endif
