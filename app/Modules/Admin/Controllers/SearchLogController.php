<?php

namespace App\Modules\Admin\Controllers;

use App\Models\Keywords;
use App\Repositories\Common\BaseRepository;

/**
 * 程序说明
 */
class SearchLogController extends InitController
{

    protected $baseRepository;

    public function __construct(
        BaseRepository $baseRepository
    )
    {
        $this->baseRepository = $baseRepository;
    }

    public function index()
    {
        $_REQUEST['act'] = trim($_REQUEST['act']);

        admin_priv('search_log');
        if ($_REQUEST['act'] == 'list') {
            $logdb = $this->get_search_log();
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['search_log']);
            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('logdb', $logdb['logdb']);
            $this->smarty->assign('filter', $logdb['filter']);
            $this->smarty->assign('record_count', $logdb['record_count']);
            $this->smarty->assign('page_count', $logdb['page_count']);
            $this->smarty->assign('start_date', local_date('Y-m-d'));
            $this->smarty->assign('end_date', local_date('Y-m-d'));

            return $this->smarty->display('search_log_list.htm');
        } elseif ($_REQUEST['act'] == 'query') {
            $logdb = $this->get_search_log();
            $this->smarty->assign('full_page', 0);
            $this->smarty->assign('logdb', $logdb['logdb']);
            $this->smarty->assign('filter', $logdb['filter']);
            $this->smarty->assign('record_count', $logdb['record_count']);
            $this->smarty->assign('page_count', $logdb['page_count']);
            $this->smarty->assign('start_date', local_date('Y-m-d'));
            $this->smarty->assign('end_date', local_date('Y-m-d'));
            return make_json_result(
                $this->smarty->fetch('search_log_list.htm'),
                '',
                ['filter' => $logdb['filter'], 'page_count' => $logdb['page_count']]
            );
        }
    }

    private function get_search_log()
    {
        $res = Keywords::where('searchengine', 'dscmall');
        if (isset($_REQUEST['start_dateYear']) && isset($_REQUEST['end_dateYear'])) {
            $start_date = $_POST['start_dateYear'] . '-' . $_POST['start_dateMonth'] . '-' . $_POST['start_dateDay'];
            $end_date = $_POST['end_dateYear'] . '-' . $_POST['end_dateMonth'] . '-' . $_POST['end_dateDay'];

            $res = $res->where('date', '<=', $end_date)->where('date', '>=', $start_date);

            $filter['start_dateYear'] = $_REQUEST['start_dateYear'];
            $filter['start_dateMonth'] = $_REQUEST['start_dateMonth'];
            $filter['start_dateDay'] = $_REQUEST['start_dateDay'];

            $filter['end_dateYear'] = $_REQUEST['end_dateYear'];
            $filter['end_dateMonth'] = $_REQUEST['end_dateMonth'];
            $filter['end_dateDay'] = $_REQUEST['end_dateDay'];
        }
        $filter['record_count'] = $res->count();

        $logdb = [];
        $filter = page_and_size($filter);

        $res = $res->orderBy('date', 'DESC')
            ->orderBy('count', 'DESC')
            ->offset($filter['start'])
            ->limit($filter['page_size']);
        $query = $this->baseRepository->getToArrayGet($res);

        foreach ($query as $rt) {
            $logdb[] = $rt;
        }
        $arr = ['logdb' => $logdb, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }
}
