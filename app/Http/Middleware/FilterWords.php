<?php

namespace App\Http\Middleware;

use App\Services\FilterWords\FilterWordsService;
use App\Services\FilterWords\WordsCheckedService;
use Closure;

class FilterWords
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($this->routeFilter() === true) // 过滤路由
        {
            return $next($request);
        }

        $config = cache('shop_config');
        if (is_null($config)) {
            $config = app(\App\Services\Common\ConfigService::class)->getConfig();
        }

        $config['filter_words_control'] = $config['filter_words_control'] ?? 0;

        if ($config['filter_words_control'] == 0) // 配置项 是否启用过滤词
        {
            return $next($request);
        }

        $wordChecked = app(WordsCheckedService::class);// 实例化
        $filterWords = app(FilterWordsService::class);// 实例化

        $param = $wordChecked->getParam($request); // 返回前后台参数
        $filter = $wordChecked->wordsFilter($request); // 过滤

        if ($filter['is_filter'] === true || !is_bool($filter['is_filter'])) // 验证通过 警告也可通过 会记录日志
        {
            return $next($request);
        } else {
            if ($request->is('api/*')) // api 通用返回格式
            {
                $log_id = session()->get('filterLogId');
                $filterWords->updateLogs($log_id, 'api'); // 更新api过滤词日志

                return response()->json(['status' => 'failed', 'errors' => ['code' => 506, 'message' => lang('admin/filter_words.exists')], 'time' => time()])
                    ->withHeaders([
                        'X-Client-Hash' => request()->header('X-Client-Hash')
                    ]);
            }

            if ($filter['is_ajax'] === true) // pc 通用ajax返回
            {
                return response()->json(['message' => lang('admin/filter_words.exists'), 'error' => 2]);
            } else // pc 通用返回
            {
                if ($param == 'stores') {
                    return response()->json(['message' => lang('admin/filter_words.exists'), 'error' => 2]);
                }

                return response()->view('errors.filterwords', ['msg' => lang('admin/filter_words.exists'), 'param' => $param, 'log_id' => session()->get('filterLogId')], 506);
            }
        }
    }

    /*
    * 路由过滤
    */
    private function routeFilter()
    {
        $except_arr = ['admin/filter/*'];

        foreach ($except_arr as $except) {
            if ($except !== '/') {
                $except = trim($except, '/');
            }

            if (request()->fullUrlIs($except) || request()->is($except)) {
                return true;
            }
        }

        return false;
    }
}
