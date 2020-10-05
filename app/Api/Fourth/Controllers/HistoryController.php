<?php

namespace App\Api\Fourth\Controllers;

use App\Api\Foundation\Controllers\Controller;
use Illuminate\Cache\CacheManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Class HistoryController
 * @package App\Api\Fourth\Controllers
 */
class HistoryController extends Controller
{
    /**
     * 生成缓存------浏览记录
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function store(Request $request)
    {
        //数据验证
        $this->validate($request, [
            'id' => 'required|integer',
            'name' => 'required|string',
            'img' => 'required|string',
        ]);
        $info = $request->all();

        $user_id = $this->authorization();

        if ($user_id == 0) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }
        $cache_id = 'history_list' . '_' . $user_id;

        $result = cache($cache_id);

        if (!is_null($result)) {
            array_push($result, $info);
        } else {
            $result = [];
            array_push($result, $info);
        }
        // 数组排序
        $result = $this->my_sort($result, 'addtime', SORT_DESC);

        if(count($result) > 100){
            array_pop($result);
        }

        cache()->forever($cache_id, $result);

        return $this->succeed(['code' => '200']);
    }

    /**
     * 获得浏览记录
     * @param Request $request
     * @return array|bool|CacheManager|JsonResponse|mixed
     * @throws ValidationException
     */
    public function index(Request $request)
    {
        $this->validate($request, [
        ]);

        $user_id = $this->authorization();

        if ($user_id == 0) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }
        $cache_id = 'history_list' . '_' . $user_id;

        $result = cache($cache_id);

        cache()->forget($cache_id);

        cache()->forever($cache_id, $result);

        return $result;
    }

    /**
     * 删除浏览记录
     * @param Request $request
     * @return array|JsonResponse
     * @throws ValidationException
     */
    public function destroy(Request $request)
    {
        $this->validate($request, [

        ]);
        $info = $request->all();
        $key = $info['id'] ?? 0;
        $user_id = $this->authorization();
        if (empty($user_id)) {
            return $this->setStatusCode(12)->failed(lang('user.not_login'));
        }
        $cache_id = 'history_list' . '_' . $user_id;
        $result = cache($cache_id);

        if ($key > 0) {
            foreach ($result as $k => $value) {
                //查看有没有重复项
                if ($value['id'] == $key) {
                    unset($result[$k]);
                    continue;
                }
            }
            cache()->forget($cache_id);
            cache()->forever($cache_id, $result);
        } else {
            cache()->forget($cache_id);
        }
        if ($key > 0) {
            return $code = ['code' => 300, 'msg' => lang('common.delete_success')];
        } else {
            return $code = ['code' => 200, 'msg' => lang('common.delete_success')];
        }
    }

    /**
     * 数组排序
     * @param $arrays
     * @param $sort_key
     * @param int $sort_order
     * @param int $sort_type
     * @return array|bool
     */
    protected function my_sort($arrays, $sort_key, $sort_order = SORT_ASC, $sort_type = SORT_NUMERIC)
    {
        if (is_array($arrays)) {
            foreach ($arrays as $array) {
                if (is_array($array)) {
                    $key_arrays[] = $array[$sort_key];
                } else {
                    return false;
                }
            }
        } else {
            return false;
        }
        array_multisort($key_arrays, $sort_order, $sort_type, $arrays);
        $arr = [];
        $temp = [];
        foreach ($arrays as $k => $v) {
            $res['type'] = $v['type'] ?? 0;
            $res['id'] = $v['id'];
            $temp[$k] = implode(',', $res);
        }
        $temp = array_unique($temp);
        foreach ($temp as $k => $v) {
            $arr[] = $arrays[$k];
        }
        return $arr;
    }
}
