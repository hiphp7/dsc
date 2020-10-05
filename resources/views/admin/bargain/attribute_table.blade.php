@if($attr_group)

    <table class="table_head" width="100%">
        <thead>
        <tr>
            <th width="10%">
                @foreach($attribute_array as $attribute)
                    {{ $attribute['attr_name'] }}
                @endforeach
            </th>
            <th width="5%"
                @if($model_name == '')
                class="hide"
                    @endif
            >{{ $model_name }}</th>
            <th width="8%"
                @if($goods_attr_price == 0)
                class="hide"
                    @endif
            ><em class="require-field">*</em>{{ $lang['bargain_shop_price'] }}<i
                        class="sc_icon sc_icon_edit pointer pro_shop"></i></th>
            <th width="8%"><em class="require-field">*</em>{{ $lang['goods_stock'] }}<i
                        class="sc_icon sc_icon_edit pointer pro_number"></i>
            </th>
            <th width="8%"><em class="require-field">*</em>{{ $lang['warning_value'] }}<i
                        class="sc_icon sc_icon_edit pointer pro_warning"></i>
            </th>
            <th width="10%">{{ $lang['goods_product_sn'] }}</th>
            <th width="10%">{{ $lang['bargain_target_price'] }}</th>
        </tr>
        </thead>
    </table>

    <div id="listDiv">
        <div class="step_item_table2">
            <table class="table_attr" width="100%">
                <tbody>

                @foreach($attr_group as $group)

                    @if(isset($group['attr_info']) && $group['attr_info'])

                        <tr>
                            <td class="td_bg_blue" width="10%">
                                @foreach($group['attr_info'] as $one)
                                    {{ $one['attr_value'] }}<input type="hidden" value="{{ $one['attr_value'] }}"/>
                                @endforeach
                            </td>
                            <td width="5%"
                                @if($region_name == '')
                                class="hide"
                                    @endif
                            >{{ $region_name }}</td>
                            <td width="8%"
                                @if($goods_attr_price == 0)
                                class="hide"
                                    @endif
                            ><input type="text" name="product_price[]" class="text w60" autocomplete="off" readonly
                                    value="{{ $group['product_price'] ?? '0.00' }}"/></td>
                            <td width="8%"><input type="text" class="text w60" autocomplete="off" readonly
                                                  value="{{ $group['product_number'] ?? '0' }}"/></td>
                            <td width="8%"><input type="text" class="text w60" autocomplete="off" readonly
                                                  value="{{ $group['product_warn_number'] ?? '1' }}"/></td>
                            <td width="10%"><input type="text" class="text w120" autocomplete="off" readonly
                                                   value="{{ $group['product_sn'] ?? '' }}"/></td>
                            <td width="10%"><input type="text" name="target_price[]" class="text w120"
                                                   autocomplete="off" value="{{ $group['target_price'] ?? '' }}"/></td>
                            <td class="hide" width="10%">
                                <input type="hidden" name="product_id[]" value="{{ $group['product_id'] ?? '' }}"/>
                                <input type="hidden" name="bargain_id[]" value="{{ $group['goods_attr_id'] ?? '' }}"/>
                            </td>

                        </tr>

                    @endif

                @endforeach

                </tbody>
            </table>

        </div>
    </div>


@endif
