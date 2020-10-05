<?php

namespace App\Modules\Seller\Controllers;

use App\Libraries\Exchange;
use App\Services\Common\CommonManageService;

/**
 * 管理员信息以及权限管理程序
 */
class PrivilegeKefuController extends InitController
{
    protected $commonManageService;

    public function __construct(
        CommonManageService $commonManageService
    )
    {
        $this->commonManageService = $commonManageService;
    }

    public function index()
    {

        /* 初始化 $exc 对象 */
        $exc = new Exchange($this->dsc->table("admin_user"), $this->db, 'user_id', 'user_name');
        $menus = session('menus', '');
        $this->smarty->assign('menus', $menus);
        $this->smarty->assign('action_type', "privilege");
        $adminru = get_admin_ru_id();

        //ecmoban模板堂 --zhuo start
        if ($adminru['ru_id'] == 0) {
            $this->smarty->assign('priv_ru', 1);
        } else {
            $this->smarty->assign('priv_ru', 0);
        }

        $this->smarty->assign('seller', 0);

        $php_self = $this->commonManageService->getPhpSelf(1);
        $this->smarty->assign('php_self', $php_self);
        //ecmoban模板堂 --zhuo end

        $this->smarty->assign('menu_select', ['action' => '10_priv_admin', 'current' => 'services_list']);

        if ($_REQUEST['act'] == 'list') {
            /* 模板赋值 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['services_list']);
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['10_priv_admin']);
            /*判断是否是商家,是显示添加管理员按钮*/
            if ($adminru['ru_id'] > 0) {
                $this->smarty->assign('action_link', ['href' => 'privilege_kefu.php?act=add', 'text' => $GLOBALS['_LANG']['add_kefu'], 'class' => 'icon-plus']);
            }

            $this->smarty->assign('ru_id', $adminru['ru_id']);
            $this->smarty->assign('full_page', 1);

            $services = $this->services_list();
            $this->smarty->assign('services_list', $services['list']);
            $this->smarty->assign('filter', $services['filter']);
            $this->smarty->assign('record_count', $services['record_count']);
            $this->smarty->assign('page_count', $services['page_count']);

            /** 接待统计 */
            $times['times'] = $this->statistics_reception();
            $times['today_times'] = $this->statistics_reception(1);
            $times['people'] = $this->statistics_reception_customer();
            $times['today_people'] = $this->statistics_reception_customer(1);
            $this->smarty->assign('times', $times); //

            /* 显示页面 */
            return $this->smarty->display('privilege_kefu_list.dwt');
        } //删除客服
        elseif ($_REQUEST['act'] == 'remove') {
            $id = empty($_GET['id']) ? 0 : intval($_GET['id']);
            $isAjax = empty($_GET['is_ajax']) ? 0 : intval($_GET['is_ajax']);
            if (!$isAjax) {
                return make_json_error("invalid method");
            }
            if (!$id) {
                return make_json_error("invalid params");
            }
            $sql = "SELECT user_name, chat_status FROM  " . $this->dsc->table('im_service') . " WHERE id = " . $id . " AND status = 1";

            $res = $this->db->getRow($sql);
            if (!$res) {
                return make_json_error($GLOBALS['_LANG']['kefu_not_exist']);
            }
            if ($res['chat_status'] == 1) {
                return make_json_error($GLOBALS['_LANG']['kefu_in_login_cant_delete']);
            }

            //删除操作
            $sql = "UPDATE" . $this->dsc->table('im_service') . " SET status = 0  WHERE id = " . $id;
            $res = $this->db->query($sql);
            if (!$res) {
                return make_json_error($GLOBALS['_LANG']['kefu_not_exist']);
            }

            $services = $this->services_list();
            $this->smarty->assign('services_list', $services['list']); //
            $this->smarty->assign('services_list', $services['list']);
            $this->smarty->assign('filter', $services['filter']);
            $this->smarty->assign('record_count', $services['record_count']);
            $this->smarty->assign('page_count', $services['page_count']);
            return make_json_result($this->smarty->fetch('privilege_kefu_list.dwt'), $GLOBALS['_LANG']['delete_success_alt']);
        }
        /*------------------------------------------------------ */
        //-- 批量删除管理员
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'trash') {
            $ids = (array)$_POST['checkboxes'];
            $ids = array_filter($ids, function ($v) {
                return (int)$v;
            });
            $isAjax = empty($_GET['is_ajax']) ? 0 : intval($_GET['is_ajax']);

            $ids = implode(',', $ids);
            $sql = "UPDATE" . $this->dsc->table('im_service') . " SET status = 0  WHERE id in (" . $ids . ")";

            $res = $this->db->query($sql);
            $url = 'privilege_kefu.php?act=list';
            return dsc_header("Location: $url\n");
        }

        /*------------------------------------------------------ */
        //-- 添加管理员页面
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add') {
            /* 检查权限 */

            /* 模板赋值 */
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['10_priv_admin']);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['admin_add']);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['services_list']); // 当前导航

            $admins = $this->admin_list();

            $this->smarty->assign('admin_list', $admins); //
            $this->smarty->assign('form_act', 'insert');
            $this->smarty->assign('action', 'add');

            /* 显示页面 */
            return $this->smarty->display('privilege_kefu_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 编辑管理员页面
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit') {
            /* 检查权限 */

            /* 模板赋值 */
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['10_priv_admin']);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['admin_add']);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['services_list']); // 当前导航

            $admins = $this->admin_list();

            $this->smarty->assign('admin_list', $admins); //
            $this->smarty->assign('form_act', 'update');
            $this->smarty->assign('action', 'edit');

            //客服信息
            $id = isset($_GET['id']) && !empty($_GET['id']) ? intval($_GET['id']) : 0;
            $services = $this->service_info($id);

            $this->smarty->assign('services', $services);

            /* 显示页面 */
            return $this->smarty->display('privilege_kefu_info.dwt');
        } //添加、更新客服功能
        elseif ($_REQUEST['act'] == 'insert' || $_REQUEST['act'] == 'update') {
            $services_name = empty($_POST['nick_name']) ? 0 : strip_tags($_POST['nick_name']);
            $services_desc = empty($_POST['kefu_desc']) ? 0 : strip_tags($_POST['kefu_desc']);
            $services = empty($_POST['services']) ? 0 : intval($_POST['services']);

            if (empty($services_name)) {
                return sys_msg($GLOBALS['_LANG']['input_nick_name'], 1);
            } elseif (empty($services_desc)) {
                return sys_msg($GLOBALS['_LANG']['input_intro'], 1);
            } elseif (empty($services)) {
                return sys_msg($GLOBALS['_LANG']['select_admin'], 1);
            }

            $sql = "SELECT user_name FROM  " . $this->dsc->table('admin_user') . " WHERE user_id=" . $services;

            $userName = $this->db->getOne($sql);
            if (!$userName) {
                return sys_msg($GLOBALS['_LANG']['no_this_admin'], 1);
            }

            if ($_REQUEST['act'] == 'insert') {
                $sql = "SELECT user_name, status FROM  " . $this->dsc->table('im_service') . " WHERE user_id=" . $services;

                $res = $this->db->getRow($sql);
                if ($res['status'] == 1) {
                    return sys_msg($GLOBALS['_LANG']['this_admin_was_kefu'], 1);
                } elseif ($res['status'] === 0 || $res['status'] === '0') {
                    $sql = "UPDATE" . $this->dsc->table('im_service') . " SET nick_name='{$services_name}', post_desc='{$services_desc}', status=1  WHERE user_id=" . $services;
                    $res = $this->db->query($sql);
                } else {
                    $sql = "INSERT INTO " . $this->dsc->table('im_service') . "(user_id, user_name, nick_name, post_desc, chat_status, status) " .
                        "VALUES ($services, '$userName', '$services_name',  " .
                        "'$services_desc', '0', '1')";
                    $res = $this->db->query($sql);
                }

                if (!$res) {
                    return sys_msg($GLOBALS['_LANG']['add_kefu_fail'], 1);
                }
            } elseif ($_REQUEST['act'] == 'update') {
                $id = empty($_POST['id']) ? 0 : intval($_POST['id']);

                $sql = "UPDATE" . $this->dsc->table('im_service') . " SET nick_name='{$services_name}', post_desc='{$services_desc}', status=1  WHERE id=" . $id;

                $res = $this->db->query($sql);
                if (!$res) {
                    return sys_msg($GLOBALS['_LANG']['update_kefu_fail'], 1);
                }
            }
            admin_log('', 'service', $_REQUEST['act']); // 记录日志

            $url = 'privilege_kefu.php?act=list';
            return dsc_header("Location: $url\n");
        }

        /*------------------------------------------------------ */
        //-- 会话记录页面
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'dialog_list') {
            /* 检查权限 */
            // admin_priv('seller_manage');

            //会话记录
            $id = empty($_GET['id']) ? 0 : intval($_GET['id']);
            $list = $this->dialog_list($id, 0);

            $this->smarty->assign('id', $id); //
            $this->smarty->assign('dialog_list', $list); //
            $this->smarty->assign('full_page', 1);

            /* 模板赋值 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['dialog_log']);
            $this->smarty->assign('action_link', ['href' => 'privilege_kefu.php?act=list', 'text' => $GLOBALS['_LANG']['services_list'], 'class' => 'icon-reply']);

            /* 显示页面 */

            $this->smarty->assign('current', 'privilege_seller');
            return $this->smarty->display('privilege_dialog_list.dwt');
        } elseif ($_REQUEST['act'] == 'dialog_list_ajax') {
            //异步获取会话列表
            $id = empty($_POST['id']) ? 0 : intval($_POST['id']);
            $val = empty($_POST['val']) ? 0 : intval($_POST['val']);

            $list = $this->dialog_list($id, $val);

            $this->smarty->assign('dialog_list', $list); //
            return make_json_result($this->smarty->fetch('privilege_dialog_list.dwt'));
        } elseif ($_REQUEST['act'] == 'message_list_ajax') {
            //消息列表
            $id = empty($_POST['id']) ? 0 : intval($_POST['id']);
            $customer_id = empty($_POST['customer_id']) ? 0 : intval($_POST['customer_id']);
            $service_id = empty($_POST['service_id']) ? 0 : intval($_POST['service_id']);
            $page = empty($_POST['page']) ? 0 : intval($_POST['page']);
            $keyword = empty($_POST['keyword']) ? 0 : strip_tags(trim($_POST['keyword']));

            $dialog = $this->dialog($id);
            $message = $this->message_list($customer_id, $service_id, $page, $keyword);
            $list = $message['list'];
            $count = $message['count'];
            $this->smarty->assign('message_page', 1); //
            $this->smarty->assign('dialog', $dialog); //
            $this->smarty->assign('message_list', $list); //

            return make_json_result($this->smarty->fetch('privilege_dialog_list.dwt'), $count);
        } /** 生成word */
        elseif ($_REQUEST['act'] == 'generage_word') {
            $id = empty($_GET['id']) ? 0 : intval($_GET['id']);
            $customer_id = empty($_GET['customer_id']) ? 0 : intval($_GET['customer_id']);
            $service_id = empty($_GET['service_id']) ? 0 : intval($_GET['service_id']);

            $message = $this->message_list($customer_id, $service_id);
            $list = $message['list'];
            $dialog = $this->dialog($id);

            //生成消息
            require(dirname(__FILE__) . '/../mobile/vendor/phpoffice/phpexcel/Classes/PHPExcel.php');

            $excel = new PHPExcel();
            $letter = ['A', 'B', 'C', 'D', 'E', 'F', 'F', 'G'];

            foreach ($list as $k => $v) {
                $excel->getActiveSheet()->setCellValue("$letter[0]$k", strip_tags($v['message']));
                $excel->getActiveSheet()->setCellValue("$letter[1]$k", $v['add_time']);

                if ($v['user_type'] == 1) {
                    $excel->getActiveSheet()->setCellValue("$letter[2]$k", $dialog['user_name']);
                } elseif ($v['user_type'] == 2) {
                    $excel->getActiveSheet()->setCellValue("$letter[2]$k", $dialog['nick_name']);
                }
            }
            $write = new PHPExcel_Writer_Excel5($excel);
            header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
            header("Content-Type:application/force-download");
            header("Content-Type:application/vnd.ms-execl");
            header("Content-Type:application/octet-stream");
            header("Content-Type:application/download");;
            header('Content-Disposition:attachment;filename="' . $dialog[user_name] . '.xls"');
            header("Content-Transfer-Encoding:binary");
            $write->save('php://output');
        }
        /*--------------------------------------------------*/
        //--根据日期找出记录
        /*--------------------------------------------------*/
        elseif ($_REQUEST['act'] == 'get_message_by_date') {
            //消息列表
            $customer_id = empty($_POST['customer_id']) ? 0 : intval($_POST['customer_id']);
            $service_id = empty($_POST['service_id']) ? 0 : intval($_POST['service_id']);
            $page = empty($_POST['page']) ? 0 : intval($_POST['page']);
            $start_time = empty($_POST['start_time']) ? 0 : strip_tags(trim($_POST['start_time']));

            $message = $this->message_list($customer_id, $service_id, $page, '', strtotime($start_time));
            $list = $message['list'];
            $count = $message['count'];
            $this->smarty->assign('message_page', 1); //
            $this->smarty->assign('message_list', $list); //
            return make_json_result($this->smarty->fetch('privilege_dialog_list.dwt'), $count);
        }
    }

    /** 客服列表 */
    private function services_list()
    {
        $filter = [];
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
        $adminru = get_admin_ru_id();

        /** 获得总记录数据 */
        $sql = 'SELECT COUNT(s.id) FROM ' . $this->dsc->table('im_service') . ' s'
            . " INNER JOIN " . $this->dsc->table('admin_user') . " a ON a.user_id = s.user_id  AND a.ru_id = " . $adminru['ru_id']
            . ' WHERE s.status = 1';
        $filter['record_count'] = $this->db->getOne($sql);

        $filter = page_and_size($filter);
        $list = [];
        $sql = 'SELECT s.* FROM ' . $this->dsc->table('im_service') . ' s'
            . " INNER JOIN " . $this->dsc->table('admin_user') . " a ON a.user_id = s.user_id  AND a.ru_id = " . $adminru['ru_id']
            . ' WHERE s.status = 1';
        $sql .= ' ORDER by ' . $filter['sort_by'] . ' ' . $filter['sort_order'];

        $res = $this->db->selectLimit($sql, $filter['page_size'], $filter['start']);

        foreach ($res as $rows) {
            $rows['chat_status'] = ($rows['chat_status'] == 0) ? lang('seller/privilege_kefu.not_logged') : lang('seller/privilege_kefu.landing');
            $rows['avatar'] = '../data/images_user/' . (empty($rows['avatar']) ? '/no_picture.jpg' : $rows['avatar']);
            $list[] = $rows;
        }

        return ['list' => $list, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];
    }


    /** 管理员列表 */
    private function admin_list()
    {
        $sql = "SELECT ru_id FROM " . $this->dsc->table("admin_user") . " WHERE user_id = '" . session('seller_id') . "'";

        $ruId = $this->db->getOne($sql);

        $sql = "SELECT a.user_id, a.user_name FROM " . $this->dsc->table("admin_user") . " a"
            . " LEFT JOIN " . $this->dsc->table("im_service") . " s ON a.user_id = s.user_id AND s.status = 1"
            . " WHERE a.ru_id = " . $ruId;

        $list = $this->db->getAll($sql);

        return $list;
    }

    /**
     * 会话记录
     * 以客户为单位
     */
    private function dialog_list($id, $val = 0)
    {
        $sql = "SELECT id,  customer_id, goods_id, store_id, start_time, end_time FROM " . $this->dsc->table("im_dialog") . " WHERE services_id = {$id}";

        if ($val === 0) {
            $time = strtotime(date('Y-m-d', time()));
            $sql .= " AND start_time > " . $time;
        } elseif ($val === 1) {
            $time = strtotime('-1 week');
            $sql .= " AND start_time > " . $time;
        } elseif ($val === 2) {
            $time = strtotime('-1 month');
            $sql .= " AND start_time > " . $time;
        }
        $sql .= ' ORDER BY start_time DESC';
        $res = $this->db->getAll($sql);

        $temp = [];
        foreach ($res as $k => $v) {
            if (in_array($v['customer_id'], $temp)) {
                unset($res[$k]);
                continue;
            }
            $temp[] = $v['customer_id'];
        }

        foreach ($res as $k => $v) {
            $res[$k]['goods_thumb'] = '../mobile/public/assets/chat/images/no_picture.jpg';

            if ($v['goods_id'] > 0) {
                $sql = "SELECT goods_name, goods_thumb FROM " . $this->dsc->table("goods") . " WHERE goods_id = " . $v['goods_id'];
                $goods = $this->db->getRow($sql);
                $res[$k]['goods_name'] = $goods['goods_name'];
                $res[$k]['goods_thumb'] = $this->format_goods_pic($goods['goods_thumb']);
            }

            $sql = "SELECT user_name FROM " . $this->dsc->table("users") . " WHERE user_id = " . $v['customer_id'];
            $res[$k]['user_name'] = $this->db->getOne($sql);

            $res[$k]['start_time'] = local_date('Y-m-d H:i:s', $v['start_time']);
            $res[$k]['end_time'] = empty($v['end_time']) ? $GLOBALS['_LANG']['have_not_end'] : date('Y-m-d H:i:s', $v['end_time']);
        }

        return $res;
    }

    /** 消息列表 */
    private function message_list($customer_id, $service_id, $page = 0, $keyword = '', $date = '')
    {
        $size = 10;
        $start = ($page - 1) * $size;
        $start = (intval($start) < 0) ? 0 : (int)$start;

        $sql = "SELECT message, user_type, FROM_UNIXTIME(add_time) AS add_time FROM " . $this->dsc->table("im_message") . " WHERE ((from_user_id = " . $customer_id . " AND to_user_id = " . $service_id . ") OR (from_user_id = " . $service_id . " AND to_user_id = " . $customer_id . ")";
        $sqlCount = "SELECT count(id) FROM " . $this->dsc->table("im_message") . " WHERE ((from_user_id = " . $customer_id . " AND to_user_id = " . $service_id . ") OR (from_user_id = " . $service_id . " AND to_user_id = " . $customer_id . ")";

        if (!empty($keyword)) {
            $sql .= ") AND (message like '%{$keyword}%') ORDER BY add_time";
            $sqlCount .= ") AND (message like '%{$keyword}%') ORDER BY add_time";
        } elseif (!empty($date)) {
            //日期
            $sql .= ") AND UNIX_TIMESTAMP(FROM_UNIXTIME(add_time, '%Y-%m-%d')) = $date";
            $sqlCount .= ") AND UNIX_TIMESTAMP(FROM_UNIXTIME(add_time, '%Y-%m-%d')) = $date";
        } elseif (!empty($page)) {
            $sql .= ") ORDER BY add_time";
            $sqlCount .= ")";
        } else {
            $sql .= ") ORDER BY add_time";
            $sqlCount .= ")";
        }
        $sql .= " limit $start, $size";


        $res = $this->db->getAll($sql);
        $count = $this->db->getOne($sqlCount);

        foreach ($res as $k => $v) {
            $res[$k]['message'] = htmlspecialchars_decode($v['message']);
        }

        return ['list' => $res, 'count' => ceil($count / $size)];
    }

    /** 接待人次统计 */
    private function statistics_reception($now = false)
    {
        $sql = "SELECT count(id) FROM " . $this->dsc->table("im_dialog");
        $adminru = get_admin_ru_id();

        $nowTime = strtotime(date('Y-m-d', time()));
        if ($now) {
            $sql .= ' WHERE start_time > ' . $nowTime;
            $sql .= ' AND store_id = ' . $adminru['ru_id'];
        } else {
            $sql .= ' WHERE store_id = ' . $adminru['ru_id'];
        }


        $times = $this->db->getOne($sql);
        return $times;
    }

    /** 接待人数统计 */
    private function statistics_reception_customer($now = false)
    {
        $sql = "SELECT COUNT(DISTINCT customer_id) FROM " . $this->dsc->table("im_dialog");
        $adminru = get_admin_ru_id();

        $nowTime = strtotime(date('Y-m-d', time()));
        if ($now) {
            $sql .= ' WHERE start_time > ' . $nowTime;
            $sql .= ' AND store_id = ' . $adminru['ru_id'];
        } else {
            $sql .= ' WHERE store_id = ' . $adminru['ru_id'];
        }


        $times = $this->db->getOne($sql);
        return $times;
    }

    /** 客服信息 */
    private function service_info($id)
    {
        $sql = 'SELECT id, nick_name, post_desc FROM ' . $this->dsc->table("im_service") . '  WHERE status = 1 AND id = ' . $id;
        $res = $this->db->getRow($sql);
        return $res;
    }

    /** 会话信息 */
    private function dialog($id)
    {
        $sql = "SELECT u.user_name, s.nick_name, FROM_UNIXTIME(start_time) AS start_time FROM " . $this->dsc->table("im_dialog") . " d"
            . " LEFT JOIN " . $this->dsc->table("im_service") . " s ON d.services_id = s.id"
            . " LEFT JOIN " . $this->dsc->table("users") . " u ON d.customer_id = u.user_id"
            . " WHERE d.id = " . $id;

        $res = $this->db->getRow($sql);
        return $res;
    }

    /** 格式化商品图片 */
    private function format_goods_pic($path)
    {
        $path = '../' . $path;

        return $path;
    }
}
