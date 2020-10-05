<?php

namespace App\Http\Controllers;

use App\Services\Common\AreaService;

/**
 * 地区切换程序
 */
class RegionGoodsController extends InitController
{
    protected $areaService;

    public function __construct(
        AreaService $areaService
    )
    {
        $this->areaService = $areaService;
    }

    public function index()
    {
        header('Content-type: text/html; charset=' . EC_CHARSET);
        $user_id = session('user_id', 0);

        $type = (int)request()->input('type', 0);
        $parent = (int)request()->input('parent', 0);
        $ru_id = (int)request()->input('ru_id', 0);

        $arr['regions'] = get_regions($type, $parent);
        $arr['type'] = $type;
        $arr['target'] = !empty($_REQUEST['target']) ? stripslashes(trim($_REQUEST['target'])) : '';
        $arr['target'] = htmlspecialchars($arr['target']);
        $arr['user_id'] = $user_id;
        $arr['ru_id'] = $ru_id;

        $user_address = get_user_address_region($user_id);
        $user_address = $user_address && $user_address['region_address'] ? explode(",", $user_address['region_address']) : [];

        if (in_array($parent, $user_address)) {
            $arr['isRegion'] = 1;
        } else {
            $arr['isRegion'] = 88; //原为0
            $arr['message'] = $GLOBALS['_LANG']['region_message'];

            $area_cookie = $this->areaService->areaCookie();

            $arr['province'] = $area_cookie['province'];
            $arr['city'] = $area_cookie['city'];
            $arr['district'] = $area_cookie['district'];
        }

        if (empty($arr['regions'])) {
            $arr['empty_type'] = 1;
        } else {
            /* 支持配送地区 */
            foreach ($arr['regions'] as $k => $v) {
                $arr['regions'][$k]['choosable'] = true;
            }
        }

        return json_encode($arr);
    }
}
