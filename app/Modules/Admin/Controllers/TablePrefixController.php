<?php

namespace App\Modules\Admin\Controllers;

class TablePrefixController extends InitController
{
    public function index()
    {
        $db_name = config('database.connections.mysql.database');
        $prefix = config('database.connections.mysql.prefix');

        /*------------------------------------------------------ */
        //-- 商品列表，商品回收站
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'edit') {
            $this->smarty->assign('prefix', $prefix);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['05_table_prefix']);

            return $this->smarty->display('table_prefix.dwt');
        } elseif ($_REQUEST['act'] == 'update') {
            $new_prefix = !empty($_REQUEST['new_prefix']) ? trim($_REQUEST['new_prefix']) : ''; //新的表前缀
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['05_table_prefix']);

            $sql = "SELECT CONCAT( 'ALTER TABLE ', table_name, ' RENAME TO ', replace(table_name,'$prefix','$new_prefix'),';') AS prefix FROM information_schema.tables WHERE TABLE_SCHEMA = '$db_name' and table_name LIKE '$prefix%';";
            $res = $this->db->getAll($sql);

            $list = [];
            foreach ($res as $k => $v) {
                $list[$k]['prefix'] = $prefix;
                $list[$k]['new_prefix'] = $new_prefix;
                $list[$k]['edit_table'] = $v['prefix'];
            }

            if ($list) {
                write_static_cache('table_prefix', $list);
            }

            $table_list = read_static_cache('table_prefix');

            if ($table_list !== false) {
                $table_list = $this->dsc->page_array(1, 1, $table_list);
                $this->smarty->assign('record_count', $table_list['filter']['record_count']);
            }

            $this->smarty->assign('page', 1);


            return $this->smarty->display('table_list.dwt');
        } elseif ($_REQUEST['act'] == 'ajax_update') {

            $page = !empty($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
            $page_size = isset($_REQUEST['page_size']) ? intval($_REQUEST['page_size']) : 1;

            $table_list = read_static_cache('table_prefix');

            /* 设置最长执行时间为5分钟 */
            @set_time_limit(300);

            if ($table_list !== false) {
                $table_list = $this->dsc->page_array($page_size, $page, $table_list);
            }

            $result['list'] = isset($table_list['list']) && $table_list['list'] ? $table_list['list'][0] : [];

            $result['page'] = $table_list['filter']['page'] + 1;
            $result['page_size'] = $table_list['filter']['page_size'];
            $result['record_count'] = $table_list['filter']['record_count'];
            $result['page_count'] = $table_list['filter']['page_count'];

            $result['is_stop'] = 1;
            if ($page > $table_list['filter']['page_count']) {
                $result['is_stop'] = 0;
            } else {
                $this->db->query($table_list['list'][0]['edit_table']);
                $result['filter_page'] = $table_list['filter']['page'];
            }

            return response()->json($result);
        }
    }
}
