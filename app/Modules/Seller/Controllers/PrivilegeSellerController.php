<?php

namespace App\Modules\Seller\Controllers;

use App\Libraries\Exchange;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\SessionRepository;
use App\Services\Common\CommonManageService;
use App\Services\Merchant\MerchantCommonService;

/**
 * 管理员信息以及权限管理程序
 */
class PrivilegeSellerController extends InitController
{
    protected $commonManageService;
    protected $merchantCommonService;
    protected $dscRepository;
    protected $sessionRepository;

    public function __construct(
        CommonManageService $commonManageService,
        MerchantCommonService $merchantCommonService,
        DscRepository $dscRepository,
        SessionRepository $sessionRepository
    )
    {
        $this->commonManageService = $commonManageService;
        $this->merchantCommonService = $merchantCommonService;
        $this->dscRepository = $dscRepository;
        $this->sessionRepository = $sessionRepository;
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

        $this->smarty->assign('menu_select', ['action' => '10_priv_admin', 'current' => '02_admin_seller']);

        if ($_REQUEST['act'] == 'list') {
            /* 模板赋值 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['01_admin_list']);
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['10_priv_admin']);
            /*判断是否是商家,是显示添加管理员按钮*/
            if ($adminru['ru_id'] > 0) {
                $this->smarty->assign('action_link', ['href' => 'privilege_seller.php?act=add', 'text' => $GLOBALS['_LANG']['admin_add'], 'class' => 'icon-plus']);
            }

            $this->smarty->assign('ru_id', $adminru['ru_id']);
            $this->smarty->assign('full_page', 1);

            $admin_list = $this->get_admin_userlist($adminru['ru_id']);

            // 分页
            $page = isset($_REQUEST['page']) && !empty(intval($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($admin_list, $page);

            $this->smarty->assign('page_count_arr', $page_count_arr);
            $this->smarty->assign('admin_list', $admin_list['list']);
            $this->smarty->assign('filter', $admin_list['filter']);
            $this->smarty->assign('record_count', $admin_list['record_count']);
            $this->smarty->assign('page_count', $admin_list['page_count']);

            /* 显示页面 */

            $this->smarty->assign('current', 'privilege_seller');
            return $this->smarty->display('privilege_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 查询
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            $admin_list = $this->get_admin_userlist($adminru['ru_id']);
            // 分页
            $page = isset($_REQUEST['page']) && !empty(intval($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($admin_list, $page);

            $this->smarty->assign('page_count_arr', $page_count_arr);
            $this->smarty->assign('admin_list', $admin_list['list']);
            $this->smarty->assign('filter', $admin_list['filter']);
            $this->smarty->assign('record_count', $admin_list['record_count']);
            $this->smarty->assign('page_count', $admin_list['page_count']);
            $this->smarty->assign('current', 'privilege_seller');
            return make_json_result($this->smarty->fetch('privilege_list.dwt'), '', ['filter' => $admin_list['filter'], 'page_count' => $admin_list['page_count']]);
        }

        /*------------------------------------------------------ */
        //-- 添加管理员页面
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add') {
            /* 检查权限 */
            admin_priv('seller_manage');

            /* 模板赋值 */
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['10_priv_admin']);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['admin_add']);

            $this->smarty->assign('action_link', ['href' => 'privilege_seller.php?act=list', 'text' => $GLOBALS['_LANG']['02_admin_seller'], 'class' => 'icon-reply']);

            $this->smarty->assign('action_url', 'privilege_seller.php?act=insert');
            $this->smarty->assign('form_act', 'insert');
            $this->smarty->assign('action', 'add');

            /* 显示页面 */

            $this->smarty->assign('current', 'privilege_seller');
            return $this->smarty->display('privilege_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 添加管理员的处理
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'insert') {
            admin_priv('seller_manage');

            /* 判断管理员是否已经存在 */
            if (!empty($_POST['user_name'])) {
                $is_only = $exc->is_only('user_name', stripslashes($_POST['user_name']));

                if (!$is_only) {
                    return sys_msg(sprintf($GLOBALS['_LANG']['user_name_exist'], stripslashes($_POST['user_name'])), 1);
                }
            }

            /* Email地址是否有重复 */
            if (!empty($_POST['email'])) {
                $is_only = $exc->is_only('email', stripslashes($_POST['email']));

                if (!$is_only) {
                    return sys_msg(sprintf($GLOBALS['_LANG']['email_exist'], stripslashes($_POST['email'])), 1);
                }
            }

            /* 获取添加日期及密码 */
            $add_time = gmtime();

            $password = md5($_POST['password']);
            $parent_id = '';
            $action_list = '';
            $ru_id = '';
            if (session('seller_id') > 0) {
                $res = $this->db->getRow(" SELECT ru_id,action_list FROM " . $this->dsc->table("admin_user") . " WHERE user_id = '" . session('seller_id') . "'");
                $action_list = $res['action_list'];
                $ru_id = $res['ru_id'];
            }
            $sql = "SELECT nav_list FROM " . $this->dsc->table('admin_user') . " WHERE action_list = 'all'";
            $row = $this->db->getRow($sql);
            $sql = "INSERT INTO " . $this->dsc->table('admin_user') . " (user_name, email, password, add_time, nav_list,action_list,ru_id,parent_id) " .
                "VALUES ('" . trim($_POST['user_name']) . "', '" . trim($_POST['email']) . "', '$password', '$add_time', '$row[nav_list]','$action_list','$ru_id','" . session('seller_id') . "')";

            $this->db->query($sql);
            /* 转入权限分配列表 */
            $new_id = $this->db->insert_id();

            /*添加链接*/
            $link[0]['text'] = $GLOBALS['_LANG']['go_allot_priv'];
            $link[0]['href'] = 'privilege_seller.php?act=allot&id=' . $new_id . '&user=' . $_POST['user_name'] . '';

            $link[1]['text'] = $GLOBALS['_LANG']['continue_add'];
            $link[1]['href'] = 'privilege_seller.php?act=add';

            return sys_msg($GLOBALS['_LANG']['add'] . "&nbsp;" . $_POST['user_name'] . "&nbsp;" . $GLOBALS['_LANG']['action_succeed'], 0, $link);

            /* 记录管理员操作 */
            admin_log($_POST['user_name'], 'add', 'privilege');
        }

        /*------------------------------------------------------ */
        //-- 编辑管理员信息
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit') {
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['10_priv_admin']);
            $this->smarty->assign('action_url', 'privilege_seller.php');
            /* 不能编辑demo这个管理员 */
            if (session('seller_name') == 'demo') {
                $link[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'privilege_seller.php?act=list'];
                return sys_msg($GLOBALS['_LANG']['edit_admininfo_cannot'], 0, $link);
            }

            $_REQUEST['id'] = !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
            /* 查看是否有权限编辑其他管理员的信息 */
            if (session('seller_id') != $_REQUEST['id']) {
                admin_priv('seller_manage');
            }

            /* 获取管理员信息 */
            $sql = "SELECT user_id, user_name, email, password, agency_id, role_id FROM " . $this->dsc->table('admin_user') .
                " WHERE user_id = '" . $_REQUEST['id'] . "'";
            $user_info = $this->db->getRow($sql);


            /* 取得该管理员负责的办事处名称 */
            if ($user_info['agency_id'] > 0) {
                $sql = "SELECT agency_name FROM " . $this->dsc->table('agency') . " WHERE agency_id = '$user_info[agency_id]'";
                $user_info['agency_name'] = $this->db->getOne($sql);
            }

            /* 模板赋值 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['admin_edit']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['02_admin_seller'], 'href' => 'privilege_seller.php?act=list', 'class' => 'icon-reply']);
            $this->smarty->assign('user', $user_info);

            $this->smarty->assign('form_act', 'update');
            $this->smarty->assign('action', 'edit');

            return $this->smarty->display('privilege_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 更新管理员信息
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'update' || $_REQUEST['act'] == 'update_self') {

            /* 变量初始化 */
            $admin_id = !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
            $admin_name = !empty($_REQUEST['user_name']) ? trim($_REQUEST['user_name']) : '';
            $admin_email = !empty($_REQUEST['email']) ? trim($_REQUEST['email']) : '';
            $ec_salt = rand(1, 9999);
            $password = !empty($_POST['new_password']) ? ", password = '" . md5(md5($_POST['new_password']) . $ec_salt) . "'" : '';
            if ($_REQUEST['act'] == 'update') {
                /* 查看是否有权限编辑其他管理员的信息 */
                if (session('seller_id') != $_REQUEST['id']) {
                    admin_priv('seller_manage');
                }
                $g_link = 'privilege_seller.php?act=list';
                $nav_list = '';
            } else {
                $nav_list = !empty($_POST['nav_list']) ? ", nav_list = '" . @join(",", $_POST['nav_list']) . "'" : '';
                $admin_id = session('seller_id');
                $g_link = 'privilege_seller.php?act=modif';
            }
            /* 判断管理员是否已经存在 */
            if (!empty($admin_name)) {
                $is_only = $exc->num('user_name', $admin_name, $admin_id);
                if ($is_only == 1) {
                    return sys_msg(sprintf($GLOBALS['_LANG']['user_name_exist'], stripslashes($admin_name)), 1);
                }
            }

            /* Email地址是否有重复 */
            if (!empty($admin_email)) {
                $is_only = $exc->num('email', $admin_email, $admin_id);

                if ($is_only == 1) {
                    return sys_msg(sprintf($GLOBALS['_LANG']['email_exist'], stripslashes($admin_email)), 1);
                }
            }

            //如果要修改密码
            $pwd_modified = false;

            if (!empty($_POST['new_password'])) {
                /* 比较新密码和确认密码是否相同 */
                if ($_POST['new_password'] <> $_POST['pwd_confirm']) {
                    $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'javascript:history.back(-1)'];
                    return sys_msg($GLOBALS['_LANG']['js_languages']['password_error'], 0, $link);
                } else {
                    $pwd_modified = true;
                }
            }

            //更新管理员信息
            if ($pwd_modified) {
                $sql = "UPDATE " . $this->dsc->table('admin_user') . " SET " .
                    "user_name = '$admin_name', " .
                    "email = '$admin_email', " .
                    "ec_salt = '$ec_salt' " .
                    $password .
                    $nav_list .
                    "WHERE user_id = '$admin_id'";
            } else {
                $sql = "UPDATE " . $this->dsc->table('admin_user') . " SET " .
                    "user_name = '$admin_name', " .
                    "email = '$admin_email' " .
                    $nav_list .
                    "WHERE user_id = '$admin_id'";
            }

            $this->db->query($sql);
            /* 记录管理员操作 */
            admin_log($_POST['user_name'], 'edit', 'privilege');

            /* 如果修改了密码，则需要将session中该管理员的数据清空 */
            if ($pwd_modified && $_REQUEST['act'] == 'update_self') {
                $this->sessionRepository->delete_spec_admin_session(session('seller_id'));
                $msg = $GLOBALS['_LANG']['edit_password_succeed'];
            } else {
                $msg = $GLOBALS['_LANG']['edit_profile_succeed'];
            }

            /* 提示信息 */
            $link[] = ['text' => strpos($g_link, 'list') ? $GLOBALS['_LANG']['back_admin_list'] : $GLOBALS['_LANG']['modif_info'], 'href' => $g_link];
            return sys_msg("$msg<script>parent.document.getElementById('header-frame').contentWindow.document.location.reload();</script>", 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 为管理员分�        �权限
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'allot') {
            admin_priv('seller_allot');

            $this->dscRepository->helpersLang('priv_action', 'seller');

            $user_id = !empty($_GET['id']) ? intval($_GET['id']) : 0;
            $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['10_priv_admin']);
            /* 获得该管理员的权限 */
            $priv_str = $this->db->getOne("SELECT action_list FROM " . $this->dsc->table('admin_user') . " WHERE user_id = '$_GET[id]'");

            /* 如果被编辑的管理员拥有了all这个权限，将不能编辑 */
            if ($priv_str == 'all') {
                $link[] = ['text' => $GLOBALS['_LANG']['back_admin_list'], 'href' => 'privilege_seller.php?act=list'];
                return sys_msg($GLOBALS['_LANG']['edit_admininfo_cannot'], 0, $link);
            }

            $user_parent = $this->db->getRow("SELECT action_list, parent_id FROM " . $this->dsc->table('admin_user') . " WHERE user_id = '$user_id'");
            $parent_priv = $this->db->getRow("SELECT action_list FROM " . $this->dsc->table('admin_user') . " WHERE user_id = '" . $user_parent['parent_id'] . "'");
            $parent_priv = explode(',', $parent_priv['action_list']);

            $priv_str = $user_parent['action_list'];

            /* 获取权限的分组数据 */
            $sql_query = "SELECT action_id, parent_id, action_code,relevance FROM " . $this->dsc->table('admin_action') .
                " WHERE parent_id = 0";
            $res = $this->db->query($sql_query);

            foreach ($res as $rows) {

                // 微信通
                if (!file_exists(MOBILE_WECHAT) && $rows['action_code'] == 'wechat') {
                    continue;
                }

                // 微分销
                if (!file_exists(MOBILE_DRP) && $rows['action_code'] == 'drp') {
                    continue;
                }

                // 微信小程序
                if (!file_exists(MOBILE_WXAPP) && $rows['action_code'] == 'wxapp') {
                    continue;
                }

                // 拼团
                if (!file_exists(MOBILE_TEAM) && $rows['action_code'] == 'team') {
                    continue;
                }

                // 砍价
                if (!file_exists(MOBILE_BARGAIN) && $rows['action_code'] == 'bargain_manage') {
                    continue;
                }

                $priv_arr[$rows['action_id']] = $rows;
            }

            if ($priv_arr) {
                /* 按权限组查询底级的权限名称 */
                $sql = "SELECT action_id, parent_id, action_code,relevance FROM " . $this->dsc->table('admin_action') .
                    " WHERE seller_show = 1 and  parent_id " . db_create_in(array_keys($priv_arr)) . " AND action_code " . db_create_in(array_values($parent_priv));

                $result = $this->db->query($sql);
                foreach ($result as $priv) {
                    $priv_arr[$priv["parent_id"]]["priv"][$priv["action_code"]] = $priv;
                }


                // 将同一组的权限使用 "," 连接起来，供JS全选 ecmoban模板堂 --zhuo
                foreach ($priv_arr as $action_id => $action_group) {
                    if (isset($action_group['priv']) && $action_group['priv']) {
                        $priv = @array_keys($action_group['priv']);
                        $priv_arr[$action_id]['priv_list'] = join(',', $priv);

                        foreach ($action_group['priv'] as $key => $val) {
                            $priv_arr[$action_id]['priv'][$key]['cando'] = (strpos($priv_str, $val['action_code']) !== false || $priv_str == 'all') ? 1 : 0;
                        }
                    }
                }
            }

            /* 赋值 */
            $this->smarty->assign('lang', $GLOBALS['_LANG']);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['allot_priv'] . ' [ ' . $_GET['user'] . ' ] ');
            $this->smarty->assign('action_link', ['href' => 'privilege_seller.php?act=list', 'text' => $GLOBALS['_LANG']['02_admin_seller'], 'class' => 'icon-reply']);
            $this->smarty->assign('priv_arr', $priv_arr);
            $this->smarty->assign('form_act', 'update_allot');
            $this->smarty->assign('user_id', $_GET['id']);

            /* 显示页面 */

            $this->smarty->assign('current', 'privilege_seller');
            return $this->smarty->display('privilege_allot.dwt');
        }

        /*------------------------------------------------------ */
        //-- 更新管理员的权限
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'update_allot') {
            admin_priv('seller_allot');

            /* 取得当前管理员用户名 */
            $admin_name = $this->db->getOne("SELECT user_name FROM " . $this->dsc->table('admin_user') . " WHERE user_id = '" . intval($_POST['id']) . "'");

            /* 更新管理员的权限 */
            $act_list = @join(",", $_POST['action_code']);
            $sql = "UPDATE " . $this->dsc->table('admin_user') . " SET action_list = '$act_list', role_id = '' " .
                "WHERE user_id = '" . intval($_POST['id']) . "'";

            $this->db->query($sql);
            /* 动态更新管理员的SESSION */
            if (session('seller_id') == $_POST['id']) {
                session([
                    'action_list' => $act_list
                ]);
            }

            /* 记录管理员操作 */
            admin_log(addslashes($admin_name), 'edit', 'privilege');

            /* 提示信息 */
            $link[] = ['text' => $GLOBALS['_LANG']['back_admin_list'], 'href' => 'privilege_seller.php?act=list'];
            return sys_msg($GLOBALS['_LANG']['edit'] . "&nbsp;" . $admin_name . "&nbsp;" . $GLOBALS['_LANG']['action_succeed'], 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 删除一个管理员
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'remove') {
            $check_auth = check_authz_json('seller_drop');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_GET['id']);

            /* 获得管理员用户名 */
            $admin_name = $this->db->getOne('SELECT user_name FROM ' . $this->dsc->table('admin_user') . " WHERE user_id='$id'");

            /* ID为1的不允许删除 */
            if ($id == 1) {
                return make_json_error($GLOBALS['_LANG']['remove_cannot']);
            }

            /* 管理员不能删除自己 */
            if ($id == session('seller_id')) {
                return make_json_error($GLOBALS['_LANG']['remove_self_cannot']);
            }

            if ($exc->drop($id)) {
                $this->sessionRepository->delete_spec_admin_session($id); // 删除session中该管理员的记录

                admin_log(addslashes($admin_name), 'remove', 'privilege');
                clear_cache_files();
            }

            $url = 'privilege_seller.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));

            return dsc_header("Location: $url\n");
        }
    }

    /* 获取管理员列表 */
    private function get_admin_userlist($ru_id)
    {
        $result = get_filter();
        if ($result === false) {
            /* 过滤信息 */
            $filter['keywords'] = empty($_REQUEST['keywords']) ? '' : trim($_REQUEST['keywords']);
            $filter['parent_id'] = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);
            $filter['ru_id'] = $ru_id;
            /* 分页大小 */
            $filter['page'] = empty($_REQUEST['page']) || (intval($_REQUEST['page']) <= 0) ? 1 : intval($_REQUEST['page']);

            $page_size = request()->cookie('dsccp_page_size');
            if (isset($_REQUEST['page_size']) && intval($_REQUEST['page_size']) > 0) {
                $filter['page_size'] = intval($_REQUEST['page_size']);
            } elseif (intval($page_size) > 0) {
                $filter['page_size'] = intval($page_size);
            } else {
                $filter['page_size'] = 15;
            }

            $where = '';
            if ($filter['keywords']) {
                $where .= " AND (user_name LIKE '%" . mysql_like_quote($filter['keywords']) . "%')";
            }

            if ($filter['parent_id']) {
                $where .= " AND parent_id = '" . session('seller_id') . "'";
            } else {
                $where .= " AND parent_id > 0";
            }
            if ($filter['ru_id'] > 0) {
                $where .= " AND ru_id = '" . $filter['ru_id'] . "' ";
            }
            /* 记录总数 */
            $sql = 'SELECT COUNT(*) FROM ' . $this->dsc->table('admin_user') . " AS au " . " WHERE 1 AND ru_id > 0 $where";
            $record_count = $this->db->getOne($sql);

            $filter['record_count'] = $record_count;
            $filter['page_count'] = $filter['record_count'] > 0 ? ceil($filter['record_count'] / $filter['page_size']) : 1;

            $sql = 'SELECT user_id, user_name, au.ru_id, email, add_time, last_login ' .
                'FROM ' . $this->dsc->table('admin_user') . ' AS au ' .
                'WHERE 1  AND ru_id > 0 ' . $where . ' ORDER BY user_id DESC ' .
                "LIMIT " . ($filter['page'] - 1) * $filter['page_size'] . ",$filter[page_size]";

            set_filter($filter, $sql);
        } else {
            $sql = $result['sql'];
            $filter = $result['filter'];
        }
        $list = $this->db->getAll($sql);

        foreach ($list as $key => $val) {
            $list[$key]['ru_name'] = $this->merchantCommonService->getShopName($val['ru_id'], 1); //ecmoban模板堂 --zhuo
            $list[$key]['add_time'] = local_date($GLOBALS['_CFG']['time_format'], $val['add_time']);
            $list[$key]['last_login'] = local_date($GLOBALS['_CFG']['time_format'], $val['last_login']);
        }

        $arr = ['list' => $list, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }

    /* 清除购物车中过期的数据 */
    private function clear_cart()
    {
        /* 取得有效的session */
        $sql = "SELECT DISTINCT session_id " .
            "FROM " . $this->dsc->table('cart') . " AS c, " .
            $this->dsc->table('sessions') . " AS s " .
            "WHERE c.session_id = s.sesskey ";
        $valid_sess = $this->db->getCol($sql);

        // 删除cart中无效的数据
        $sql = "DELETE FROM " . $this->dsc->table('cart') .
            " WHERE session_id NOT " . db_create_in($valid_sess);
        $this->db->query($sql);
        // 删除cart_combo中无效的数据 by mike
        $sql = "DELETE FROM " . $this->dsc->table('cart_combo') .
            " WHERE session_id NOT " . db_create_in($valid_sess);
        $this->db->query($sql);
    }
}
