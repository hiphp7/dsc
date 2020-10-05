<?php

namespace App\Api\Fourth\Controllers;

use App\Api\Foundation\Controllers\Controller;
use App\Models\Region;
use Exception;
use Illuminate\Cache\CacheManager;
use Illuminate\Http\Request;

/**
 * Class RegionController
 * @package App\Api\Fourth\Controllers
 */
class RegionController extends Controller
{
    /**
     * 返回地区列表
     * @param Request $request
     * @return array|CacheManager|mixed
     * @throws Exception
     */
    public function index(Request $request)
    {
        $region = $request->get('region', 0);
        $level = $request->get('level', 1);

        $cache_name = 'region_' . $region;
        $result = cache($cache_name);
        if (is_null($result)) {
            $list = Region::where('parent_id', $region)->get();
            $list = $list ? $list->toArray() : [];

            $result = [];
            foreach ($list as $key => $value) {
                $result[$key]['id'] = $value['region_id'];
                $result[$key]['name'] = $value['region_name'];
                $result[$key]['level'] = $level + 1;
            }
            cache()->forever($cache_name, $result);
        }

        return $result;
    }
}
