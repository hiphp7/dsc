<?php

namespace App\Modules\Seller\Controllers;

use App\Services\FilterWords\FilterWordsService;

class FilterWordsController extends BaseController
{
    /*
    * 更新记录里最新插入的用户信息
    */
    public function updatelogs()
    {
        $id = request()->get('id', 0); // 搜索条件
        app(FilterWordsService::class)->updateLogs($id);
        return response()->json(['error' => 0]);
    }
}