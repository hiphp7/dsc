<?php

namespace App\Services\FilterWords;

use App\Models\FilterWords;
use App\Models\FilterWordsLogs;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;

/**
 * 检查输入内容
 * Class User
 * @package App\Services
 */
class WordsCheckedService
{
    protected $baseRepository;
    protected $timeRepository;
    protected $dscRepository;
    protected $config;

    public function __construct(
        BaseRepository $baseRepository,
        TimeRepository $timeRepository,
        DscRepository $dscRepository
    )
    {
        $this->baseRepository = $baseRepository;
        $this->timeRepository = $timeRepository;
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
    }

    /*
    * 接收、检查路由中包含的过滤词
    */
    public function wordsFilter($request)
    {
        $all = $request->all(); // 取得传参

        $server = $request->server(); // 获取SERVER信息

        $is_filter = true; // 初始化filter参数

        $is_ajax = false; // 初始化ajax参数

        $param = $this->getParam($request);
        $user_name = $this->getUserName($param); // 返回用户名

        $url = isset($server['REDIRECT_QUERY_STRING']) && !empty($server['REDIRECT_QUERY_STRING']) ? $request->url() . '?' . $server['REDIRECT_QUERY_STRING'] : $request->url();  // 拼接 URL

        if (!empty($all)) {

            $filter_list = $this->loadCache(); // 读取过滤词列表

            array_walk_recursive($all, function ($value, $key) use ($filter_list, $url, &$is_ajax, &$is_filter) { // 处理多维数组过滤

                if (!empty($key) && $key == 'is_ajax') {
                    $is_ajax = true;
                }

                $content = [
                    'words' => $value,
                    'user_name' => '',
                    'url' => $url
                ];

                $filter = $this->checked($content, $filter_list); // 检查

                if ($filter === false) {
                    $is_filter = false;

                    return false;
                }

                if (!is_bool($filter)) // 警告不返回 继续执行
                {
                    $is_filter = 'warning';
                }
            });
        }

        return ['is_filter' => $is_filter, 'is_ajax' => $is_ajax];
    }

    /*
    * 检查输入内容
    */
    private function checked($content = [], $filter_list = [])
    {
        if (empty($content['words'])) {
            return true; // is null --pass
        }

        $warning = 0; // 警告初始化

        if (!empty($filter_list) && is_array($filter_list)) {
            foreach ($filter_list as $filter) {
                if (strpos($content['words'], $filter) !== false) {
                    $logs = [
                        'user_id' => '',
                        'filter_words' => $filter,
                        'note' => $content['words'],
                        'url' => $content['url']
                    ];
                    $this->updateClick($filter); // 更新过滤词使用量
                    $this->writeLogs($logs); // 记录日志

                    $rank = FilterWords::where('words', $filter)->value('rank'); // 1 违禁 2 敏感

                    if ($rank == 1) {
                        return false; // error
                    } else {
                        $warning = 1; // 警告初始化 可继续执行
                    }
                }
            }

            if ($warning == 1) {
                return 'warning';
            }
        }

        return true;  // pass
    }

    /*
    * 读取缓存
    */
    private function loadCache()
    {
        /* 读取缓存 */
        $filterWords = $this->baseRepository->getDiskForeverData('filterWords');

        if (is_null($filterWords)) {
            $filterWords = FilterWords::pluck('words');
            $filterWords = $filterWords ? $filterWords->toArray() : [];

            if (!empty($filterWords)) {
                /* 存储缓存 */
                $this->baseRepository->setDiskForever('filterWords', $filterWords);
            }
        }

        return $filterWords;
    }

    /*
    * 写入日志
    */
    private function writeLogs($logs = [])
    {
        if (!empty($logs)) {
            $logs['created_at'] = $this->timeRepository->getGmTime();
            $id = FilterWordsLogs::insertGetId($logs);
            session(['filterLogId' => $id]); // 设置插入的
        }
    }

    /*
    * 更新过滤词使用次数
    */
    private function updateClick($words)
    {
        FilterWords::where('words', $words)->increment('click_count', 1);
    }


    /*
    * 判断路由来源 前后台、哪个后台
    */
    public function getParam($request)
    {
        $manage = ['admin', 'seller', 'suppliers', 'stores', 'api'];

        $param = collect($manage)->first(function ($val) use ($request) {
            if ($request->is($val . '/*')) {
                return $val;
            }
        });
        return empty($param) ? '' : $param;
    }

    /*
    * 返回用户名
    */
    private function getUserName($param)
    {

    }
}
