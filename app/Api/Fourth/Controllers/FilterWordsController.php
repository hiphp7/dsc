<?php

namespace App\Api\Fourth\Controllers;

use App\Api\Foundation\Controllers\Controller;
use App\Services\FilterWords\FilterWordsService;
use Illuminate\Http\JsonResponse;

/**
 * Class FilterWordsController
 * @package App\Api\Fourth\Controllers
 */
class FilterWordsController extends Controller
{
    /**
     * 更新记录里最新插入的用户信息
     * @return JsonResponse
     */
    public function updatelogs()
    {
        $id = request()->get('id', 0); // 搜索条件
        app(FilterWordsService::class)->updateLogs($id);
        return response()->json(['error' => 0]);
    }
}
