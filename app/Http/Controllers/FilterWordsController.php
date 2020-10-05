<?php

namespace App\Http\Controllers;

use App\Services\FilterWords\FilterWordsService;

class FilterWordsController extends InitController
{
    /**
     * 更新记录里最新插入的用户信息
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatelogs()
    {
        $id = request()->get('id', 0); // 搜索条件
        app(FilterWordsService::class)->updateLogs($id, 'http');
        return response()->json(['error' => 0]);
    }
}