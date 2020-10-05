<?php

namespace App\Services\FilterWords;


use App\Models\AdminUser;
use App\Models\FilterWords;
use App\Models\FilterWordsLogs;
use App\Models\ShopConfig;
use App\Models\Users;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use Firebase\JWT\JWT;

/**
 * 过滤词
 * Class User
 * @package App\Services
 */
class FilterWordsService
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
    * 过滤词开关
    */
    public function getControl()
    {
        $value = ShopConfig::where('code', 'filter_words_control')->value('value');

        return !empty($value) ? $value : 0;
    }

    /*
    * 更新过滤词开关
    */
    public function updateConfig($data = [])
    {
        if (empty($data)) {
            return false;
        }

        $parent_id = ShopConfig::where('code', 'extend_basic')->value('id');

        foreach ($data as $key => $val) {
            $arr = ['value' => $val, 'type' => 'hidden', 'shop_group' => 'filter_words', 'parent_id' => $parent_id];
            ShopConfig::UpdateOrCreate(['code' => $key], $arr);
        }
    }

    /*
    * 过滤词列表
    */
    public function getWordsList($where = [])
    {
        $list = FilterWords::whereRaw('1');
        if (!empty($where['keywords'])) {
            $list = $list->where('words', 'like', "%" . $where['keywords'] . "%");
        }

        if (!empty($where['start'])) {
            $list = $list->offset($where['start']);
        }

        if (!empty($where['limit'])) {
            $list = $list->limit($where['limit']);
        }

        $list = $list->orderBy('id', 'DESC');

        $list = $this->baseRepository->getToArrayGet($list);

        if (!empty($list)) {
            foreach ($list as $k => $words) {
                $user_name = AdminUser::where('user_id', $words['admin_id'])->value('user_name');
                $user_name = empty($user_name) ? '' : $user_name;
                $create_time = $this->timeRepository->getLocalDate($this->config['time_format'], $words['created_at']);

                $list[$k]['user_name'] = $user_name;
                $list[$k]['create_time'] = $create_time;
                $list[$k]['rank_name'] = $words['rank'] == 1 ? lang('filter_words.banned_word') : lang('filter_words.sensitive_word');
            }
        }

        return $list;
    }

    /*
    * 过滤词总数
    */
    public function getWordsCount($where = [])
    {
        $list = FilterWords::whereRaw('1');
        if (!empty($where['keywords'])) {
            $list = $list->where('words', 'like', "%" . $where['keywords'] . "%");
        }
        return $list->count();
    }

    /*
    * 单条过滤词信息
    */
    public function getWordsInfo($id = 0)
    {
        $id = !empty($id) ? intval($id) : 0;

        $row = [];

        if ($id > 0) {
            $row = FilterWords::where('id', $id);
            $row = $this->baseRepository->getToArrayFirst($row);
        }

        return $row;
    }

    /*
    * 过滤词插入更新
    */
    public function wordsUpdate($data = [])
    {
        $words = [
            'id' => $data['id'],
            'words' => $data['words'],
            'rank' => isset($data['rank']) ? intval($data['rank']) : 1, // 1 违禁词 2 敏感词
            'admin_id' => $data['admin_id'],
            'status' => $data['status'] ?? 0
        ];

        if ($words['id'] == 0) {
            $words['created_at'] = $this->timeRepository->getGmTime();
            FilterWords::create($words);
        } else {
            FilterWords::where('id', $words['id'])->update($words);
        }

        $this->updateCache(); // 更新缓存
    }

    /*
    * 检测过滤词是否存在
    */
    public function wordsExists($words, $id)
    {
        $count = FilterWords::where('words', $words);

        if ($id > 0) {
            $count = $count->where('id', '<>', $id);
        }

        $count = $count->count();

        return ($count > 0) ? true : false;
    }

    /*
    * 删除关键词
    */
    public function wordsDrop($id)
    {
        if ($id > 0) {
            $words = FilterWords::where('id', $id)->value('words');
            FilterWords::where('id', $id)->delete(); // 违禁词
            FilterWordsLogs::where('filter_words', $words)->delete(); // 日志
        } else {
            return false;
        }

        $this->updateCache(); // 更新缓存

        return true;
    }

    /*
    * 更新缓存
    */
    private function updateCache()
    {
        $list = FilterWords::pluck('words');
        $list = $list ? $list->toArray() : [];

        if (!empty($list)) {
            /* 存储缓存 */
            $this->baseRepository->setDiskForever('filterWords', $list);
        }
    }

    /*
    * 记录列表
    */
    public function getLogsList($where = [])
    {
        $list = FilterWordsLogs::whereRaw('1');
        if (!empty($where['keywords'])) // 搜索会员名称待完善
        {
            $list = $list->where('filter_words', 'like', "%" . $where['keywords'] . "%");
        }

        if (!empty($where['start'])) {
            $list = $list->offset($where['start']);
        }

        if (!empty($where['limit'])) {
            $list = $list->limit($where['limit']);
        }

        $list = $list->orderBy('id', 'DESC');

        $list = $this->baseRepository->getToArrayGet($list);

        if (!empty($list)) {
            foreach ($list as $k => $logs) {
                $create_time = $this->timeRepository->getLocalDate($this->config['time_format'], $logs['created_at']);
                $list[$k]['create_time'] = $create_time;
            }
        }

        return $list;
    }

    /*
    * 日志记录总数
    */
    public function getLogsCount($where = [])
    {
        $list = FilterWordsLogs::whereRaw('1');
        if (!empty($where['keywords'])) {
            $list = $list->where('filter_words', 'like', "%" . $where['keywords'] . "%");
        }
        return $list->count();
    }

    /*
    * 删除日志记录
    */
    public function logsDrop($id)
    {
        if ($id > 0) {
            $words = FilterWordsLogs::where('id', $id)->value('filter_words'); // 检查过滤词
            FilterWordsLogs::where('id', $id)->delete(); // 删除日志记录
            $logsCount = FilterWordsLogs::where('filter_words', $words)->count(); // 删除日志后更新过滤词日志记录总数
            FilterWords::where('words', $words)->update(['click_count' => $logsCount]);
        } else {
            return false;
        }

        return true;
    }

    /*
    * 统计列表
    */
    public function getStatsList($where = [], $stats = 0)
    {
        $list = FilterWords::whereRaw('1');
        if (!empty($where['keywords'])) // 搜索会员名称待完善
        {
            $list = $list->where('words', 'like', "%" . $where['keywords'] . "%");
        }

        if (!empty($where['start'])) {
            $list = $list->offset($where['start']);
        }

        if (!empty($where['limit'])) {
            $list = $list->limit($where['limit']);
        }

        $list = $list->orderBy('id', 'DESC');

        $list = $this->baseRepository->getToArrayGet($list);

        if (!empty($list)) {
            foreach ($list as $k => $words) {
                $list[$k]['today'] = $this->getDaysCount($words['words']);
                $list[$k]['seven_days'] = $this->getDaysCount($words['words'], 7);
                $list[$k]['thirty_days'] = $this->getDaysCount($words['words'], 30);
            }
        }

        return $list;
    }

    /*
    * 统计总数
    */
    public function getStatsCount($where = [])
    {
        $list = FilterWords::whereRaw('1');
        if (!empty($where['keywords'])) {
            $list = $list->where('words', 'like', "%" . $where['keywords'] . "%");
        }
        return $list->count();
    }

    /*
    * 按天统计点击记录量
    */
    private function getDaysCount($words, $days = 0)
    {
        $timestamp = $days * 24 * 3600; // 天数转时间戳
        $time = $this->timeRepository->getGmTime(); // 当前时间戳
        $date = $this->timeRepository->getLocalDate('Y-m-d', $time - $timestamp); // 当天日期
        $format_date = $this->timeRepository->getLocalStrtoTime($date);

        return FilterWordsLogs::where('created_at', '>', $format_date)->where('filter_words', $words)->count();
    }


    /*
    * 更新日志操作人信息
    */
    public function updateLogs($id = 0, $source = '')
    {
        switch ($source) {
            case 'http':
                $user_id = session('user_id', 0);
                $user_name = Users::where('user_id', $user_id)->value('user_name');
                $user_name = !empty($user_name) ? $user_name : '';
                break;

            case 'api':
                // 获取token值user_id
                if (request()->hasHeader('token')) {
                    $token = request()->header('token');
                } elseif (request()->has('token')) {
                    $token = request()->get('token');
                } else {
                    $token = '';
                }

                $key = config('app.key');

                try {
                    $data = JWT::decode($token, $key, ['HS256']);
                    $user_id = collect($data)->get('user_id');
                } catch (\Exception $e) {
                    $user_id = 0;
                }

                $user_name = Users::where('user_id', $user_id)->value('user_name');
                $user_name = !empty($user_name) ? $user_name : '';

                break;

            default:
                $adminru = get_admin_ru_id();
                $user_id = $adminru['user_id'];
                $user_name = $adminru['user_name'];
                break;
        }

        FilterWordsLogs::where('id', $id)->update(['user_id' => $user_id, 'user_name' => $user_name]);
    }
}
