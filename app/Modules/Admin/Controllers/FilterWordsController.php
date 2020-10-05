<?php

namespace App\Modules\Admin\Controllers;

use App\Libraries\Form;
use App\Models\ShopConfig;
use App\Services\Common\OfficeService;
use App\Services\FilterWords\FilterWordsService;

class FilterWordsController extends BaseController
{
    protected $filterwordsService;
    protected $officeService;

    public function __construct(
        FilterWordsService $filterwordsService,
        OfficeService $officeService
    )
    {
        $this->filterwordsService = $filterwordsService;
        $this->officeService = $officeService;
    }

    protected function initialize()
    {
        parent::initialize();

        L(require(resource_path('lang/' . config('shop.lang') . '/admin/filter_words.php')));
        $this->assign('lang', array_change_key_case(L()));

        // 初始化 每页分页数量
        $this->init_params();
    }

    /**
     * 处理公共参数
     */
    private function init_params()
    {
        $page_num = request()->cookie('page_size');
        $this->page_num = is_null($page_num) ? 15 : $page_num;
        $this->assign('page_num', $this->page_num);
    }

    /**
     * 修改分页数量
     */
    public function pages()
    {
        if (request()->isMethod('POST')) {
            //修改每页数量
            $page_num = request()->has('page_num') ? request()->input('page_num') : 0;
            if ($page_num > 0) {
                cookie()->queue('page_size', $page_num, 24 * 60 * 30);
                return response()->json(['status' => 1]);
            }
        }

        return redirect()->route('admin/filter/words');
    }

    /*
    * 配置页
    */
    public function index()
    {
        // 检查权限
        $this->admin_priv('filter_words');

        $filter_config = [
            'filter_words_control' => $this->filterwordsService->getControl()
        ];

        $this->assign('form_act', 'update');
        $this->assign('filter_config', $filter_config);
        return $this->display();
    }

    /*
    * 配置更新
    */
    public function update()
    {
        // 检查权限
        $this->admin_priv('filter_words');

        $data = request()->input('data');

        $update = $this->filterwordsService->updateConfig($data);

        if ($update === false) {
            return sys_msg(lang('admin/filter_words.error_notice'));
        }

        /* 清除缓存 */
        cache()->forget('shop_config');

        return sys_msg(lang('admin/filter_words.success_notice'));
    }

    /*
    * 过滤词
    */
    public function words()
    {
        // 检查权限
        $this->admin_priv('filter_words');

        $keywords = request()->get('keywords', ''); // 搜索条件

        // 分页
        $offset = $this->pageLimit(route('admin/filter/words'), $this->page_num);

        $where = [
            'keywords' => $keywords,
            'start' => $offset['start'],
            'limit' => $offset['limit']
        ];

        $list = $this->filterwordsService->getWordsList($where);
        $total = $this->filterwordsService->getWordsCount($where);

        $this->assign('words_list', $list);
        $this->assign('page', $this->pageShow($total));

        return $this->display();
    }

    /*
    * 过滤词新增更新
    */
    public function wordsupdate($id = 0)
    {
        // 检查权限
        $this->admin_priv('filter_words');

        $id = $id > 0 ? intval($id) : request()->get('id', 0); // 存在则编辑 否则创建

        // 提交处理
        if (request()->isMethod('POST')) {
            $data = request()->input('data');

            // 验证数据
            $form = new Form();
            if (!$form->isEmpty($data['words'], 1)) {
                return sys_msg(lang('admin/filter_words.words_not_null'), 1);
            }

            if ($this->filterwordsService->wordsExists($data['words'], $id) === true) {
                return sys_msg(lang('admin/filter_words.words_exists'), 1);
            }

            $data['id'] = $id;
            $data['admin_id'] = session('admin_id');

            $this->filterwordsService->wordsUpdate($data);

            $link[] = ['href' => route('admin/filter/words'), 'text' => lang('admin/filter_words.words_list')];
            return sys_msg(lang('admin/filter_words.success_notice'), 0, $link);
        }

        $words_info = ['words' => '', 'rank' => 0, 'status' => 1]; // 默认值

        if ($id > 0)  // 编辑时默认传参
        {
            $words_info = $this->filterwordsService->getWordsInfo($id);
        }

        $this->assign('words_info', $words_info);
        $this->assign('id', $id);

        return $this->display();
    }

    /*
    * 批量删除
    */
    public function batchdrop()
    {
        // 检查权限
        $this->admin_priv('filter_words');

        if (request()->isMethod('POST')) {
            $word_list = request()->input('id');

            $is_true = 1;
            $error_count = 0;

            if (is_array($word_list)) {
                foreach ($word_list as $id) {
                    if ($id > 0) {
                        $is_delete = $this->filterwordsService->wordsDrop($id);

                        if (!$is_delete) {
                            $error_count += 1;
                            $is_true = 0;
                        }
                    }
                }

                if ($is_true == 0) {
                    $message = lang('admin/filter_words.success_notice') . "（" . $error_count . lang('admin/filter_words.notice_error_count') . "）";
                }
            } else {
                $is_true = 0;
                $message = lang('admin/filter_words.error_easy_notice');
            }

            if ($is_true == 0) {
                return sys_msg($message, 1);
            }

            $link[] = ['href' => route('admin/filter/words'), 'text' => lang('admin/filter_words.words_list')];
            return sys_msg(lang('admin/filter_words.success_notice'), 0, $link);
        }
    }

    /*
    * 过滤词删除
    */
    public function wordsdrop()
    {
        // 检查权限
        $this->admin_priv('filter_words');

        $id = request()->get('id', 0);

        if ($id > 0) {
            $is_delete = $this->filterwordsService->wordsDrop($id);

            return $is_delete ? response()->json(['error' => 0]) : response()->json(['error' => 1, 'msg' => lang('admin/filter_words.error_easy_notice')]);
        } else {
            return response()->json(['error' => 1, 'msg' => lang('admin/filter_words.error_easy_notice')]);
        }
    }

    /*
    * 批量添加
    */
    public function batch()
    {
        // 检查权限
        $this->admin_priv('filter_words');

        return $this->display();
    }

    /*
    * 批量添加列表
    */
    public function batchlist()
    {
        // 检查权限
        $this->admin_priv('filter_words');

        $file = request()->file('file'); // 接收文件

        $path = '';

        if ($file && $file->isValid()) {
            $path = $file->storeAs('data/filterwords', $file->getClientOriginalName(), 'public'); // 文件存在则拷贝
        } else {
            return sys_msg(lang('admin/filter_words.error_file'), 1);
        }

        $filename = storage_public($path);

        if (file_exists($filename)) {

            $head = [
                'words',
                'rank',
                'status'
            ];

            $result = $this->officeService->importCsv($head, $filename);

            if (!empty($result)) {
                array_shift($result);
            }
        }

        if (empty($result)) {
            return sys_msg(lang('admin/filter_words.empty_file'), 1);
        }

        $duplicate = 0; // 重复过滤词初始化
        $success = 0; // 上传成功初始化
        $id = 0; // 默认ID
        $admin_id = session('admin_id'); // 管理员ID

        foreach ($result as $words) {
            if ($this->filterwordsService->wordsExists($words['words'], 0) === true) {
                $duplicate += 1;
            } else {
                $words['id'] = $id;
                $words['admin_id'] = $admin_id;
                $this->filterwordsService->wordsUpdate($words); // 插入操作
                $success += 1;
            }
        }

        $message = lang('admin/filter_words.success_notice');
        if ($duplicate > 0) {
            $message .= lang('admin/filter_words.combine_notice_one') . $duplicate . lang('admin/filter_words.combine_notice_two');
        }

        $link[] = ['href' => route('admin/filter/words'), 'text' => lang('admin/filter_words.words_list')];
        return sys_msg($message, 0, $link);
    }

    /*
    * 下载示例文件
    */
    public function download()
    {
        $fileName = 'filterwords';

        $headList = [
            lang('admin/filter_words.words'), lang('admin/filter_words.rank'), lang('admin/filter_words.is_open')
        ];

        $data = [
            ['filter_words' => lang('admin/filter_words.words'), 'ranks' => '1', 'status' => '0']
        ];

        $this->officeService->exportCsv($fileName, $headList, $data);
    }

    /*
    * 等级  违禁词 敏感词
    */
    public function ranks()
    {
        // 检查权限
        $this->admin_priv('filter_words');

        $this->assign('form_act', 'update');

        $filter_config = [
            'filter_words_control' => ShopConfig::where('code', 'filter_words_control')->value('value'),
        ];

        $this->assign('filter_config', $filter_config);
        return $this->display();
    }

    /*
    * 等级新增更新
    */
    public function ranksupdate()
    {
        return $this->display();
    }

    /*
    * 记录
    */
    public function logs()
    {
        // 检查权限
        $this->admin_priv('filter_words');

        $keywords = request()->get('keywords', ''); // 搜索条件

        // 分页
        $offset = $this->pageLimit(route('admin/filter/logs'), $this->page_num);

        $where = [
            'keywords' => $keywords,
            'start' => $offset['start'],
            'limit' => $offset['limit']
        ];

        $list = $this->filterwordsService->getLogsList($where);
        $total = $this->filterwordsService->getLogsCount($where);

        $this->assign('logs_list', $list);
        $this->assign('page', $this->pageShow($total));

        return $this->display();
    }

    /*
    * 更新记录里最新插入的用户信息
    */
    public function updatelogs()
    {
        $id = request()->get('id', 0); // 搜索条件
        $this->filterwordsService->updateLogs($id);
        return response()->json(['error' => 0]);
    }

    /*
    * 批量删除
    */
    public function logsdrop()
    {
        // 检查权限
        $this->admin_priv('filter_words');

        if (request()->isMethod('POST')) {
            $logs_list = request()->input('id');

            $is_true = 1;
            $error_count = 0;

            if (is_array($logs_list)) {
                foreach ($logs_list as $id) {
                    if ($id > 0) {
                        $is_delete = $this->filterwordsService->logsDrop($id);

                        if (!$is_delete) {
                            $error_count += 1;
                            $is_true = 0;
                        }
                    }
                }

                if ($is_true == 0) {
                    $message = lang('admin/filter_words.success_notice') . "（" . $error_count . lang('admin/filter_words.notice_error_count') . "）";
                }
            } else {
                $is_true = 0;
                $message = lang('admin/filter_words.error_easy_notice');
            }

            if ($is_true == 0) {
                return sys_msg($message, 1);
            }

            $link[] = ['href' => route('admin/filter/logs'), 'text' => lang('admin/filter_words.log_list')];
            return sys_msg(lang('admin/filter_words.success_notice'), 0, $link);
        }
    }

    /*
    * 统计
    */
    public function stats()
    {
        // 检查权限
        $this->admin_priv('filter_words');

        $keywords = request()->get('keywords', ''); // 搜索条件

        // 分页
        $offset = $this->pageLimit(route('admin/filter/stats'), $this->page_num);

        $where = [
            'keywords' => $keywords,
            'start' => $offset['start'],
            'limit' => $offset['limit']
        ];

        $list = $this->filterwordsService->getStatsList($where);
        $total = $this->filterwordsService->getStatsCount($where);

        $this->assign('stats_list', $list);
        $this->assign('page', $this->pageShow($total));

        return $this->display();
    }
}
