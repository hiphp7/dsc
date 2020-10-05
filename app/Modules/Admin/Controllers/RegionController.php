<?php

namespace App\Modules\Admin\Controllers;

use App\Models\Region;

/**
 * 地区切换程序
 */
class RegionController extends InitController
{
    public function index()
    {
        header('Content-type: text/html; charset=' . EC_CHARSET);
        $data = ['content' => '', 'region_name' => ''];
        $type = !empty($_REQUEST['type']) ? intval($_REQUEST['type']) : 0;
        $parent = !empty($_REQUEST['parent']) ? intval($_REQUEST['parent']) : 0;
        $shipping = !empty($_REQUEST['shipping']) ? intval($_REQUEST['shipping']) : 0;
        $region = get_regions($type, $parent);
        $value = '';
        $type = $type + 1;
        foreach ($region as $k => $v) {
            if ($v['region_id'] > 0) {
                if ($shipping == 1) {
                    $value .= '<div class="region_item"><input type="checkbox" name="region_name" data-region="' . $v['region_name'] . '" value="' . $v['region_id'] . '" class="ui-checkbox" id="region_' . $v['region_id'] . '" /><label for="region_' . $v['region_id'] . '" class="ui-label">' . $v['region_name'] . '</label></div>';
                } else {
                    $value .= '<span class="liv" data-text="' . $v['region_name'] . '" data-type="' . $type . '"  data-value="' . $v['region_id'] . '">' . $v['region_name'] . '</span>';
                }
            }
        }
        if ($parent > 0) {
            $data['region_name'] = Region::where('region_id', $parent)->value('region_name');
            $data['region_name'] = $data['region_name'] ? $data['region_name'] : '';
        }
        $data['content'] = $value;

        return response()->json($data);
    }
}
