<?php

namespace App\Modules\Seller\Controllers;

use App\Libraries\Exchange;
use App\Libraries\Image;
use App\Repositories\Common\DscRepository;
use App\Services\Merchant\MerchantCommonService;
use App\Services\Order\OrderService;

/**
 * 广告管理程序
 */
class OfflineStoreController extends InitController
{
    protected $orderService;
    protected $merchantCommonService;
    protected $dscRepository;

    public function __construct(
        OrderService $orderService,
        MerchantCommonService $merchantCommonService,
        DscRepository $dscRepository
    )
    {
        $this->orderService = $orderService;
        $this->merchantCommonService = $merchantCommonService;
        $this->dscRepository = $dscRepository;
    }

    public function index()
    {
        /*交换对象*/
        $exc = new Exchange($this->dsc->table("offline_store"), $this->db, 'id', 'stores_user', 'stores_name', 'is_confirm', 'stores_tel', 'stores_opening_hours');
        $sto = new Exchange($this->dsc->table("store_user"), $this->db, 'id', 'stores_user', 'store_id');

        $this->smarty->assign('menu_select', ['action' => '10_offline_store', 'current' => '12_offline_store']);
        $adminru = get_admin_ru_id();

        /* 允许上传的文件类型 */
        $allow_file_types = '|GIF|JPG|PNG|';

        $this->smarty->assign('primary_cat', $GLOBALS['_LANG']['10_offline_store']);

        if ($_REQUEST['act'] == 'list') {
            admin_priv('offline_store');
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['12_offline_store']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['add_stores'], 'href' => 'offline_store.php?act=add', 'class' => 'icon-plus']);

            $offline_store = $this->get_offline_store_list($adminru['ru_id']);

            $this->smarty->assign('offline_store', $offline_store['pzd_list']);
            $this->smarty->assign('filter', $offline_store['filter']);
            $this->smarty->assign('record_count', $offline_store['record_count']);
            $this->smarty->assign('page_count', $offline_store['page_count']);
            $this->smarty->assign('full_page', 1);

            //分页
            $page = isset($_REQUEST['page']) && !empty(intval($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($offline_store, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);

            return $this->smarty->display("offline_store_list.dwt");
        } elseif ($_REQUEST['act'] == 'query') {
            admin_priv('offline_store');
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['12_offline_store']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['add_stores'], 'href' => 'offline_store.php?act=add']);

            $offline_store = $this->get_offline_store_list($adminru['ru_id']);

            //分页
            $page = isset($_REQUEST['page']) && !empty($_REQUEST['page']) ? intval($_REQUEST['page']) : 0;
            $page_count_arr = seller_page($offline_store, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);

            $this->smarty->assign('offline_store', $offline_store['pzd_list']);
            $this->smarty->assign('filter', $offline_store['filter']);
            $this->smarty->assign('record_count', $offline_store['record_count']);
            $this->smarty->assign('page_count', $offline_store['page_count']);

            //跳转页面
            return make_json_result($this->smarty->fetch('offline_store_list.dwt'), '', ['filter' => $offline_store['filter'], 'page_count' => $offline_store['page_count']]);
        } elseif ($_REQUEST['act'] == 'add' || $_REQUEST['act'] == 'edit') {
            admin_priv('offline_store');
            //页面赋值
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['add_stores']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['12_offline_store'], 'href' => 'offline_store.php?act=list', 'class' => 'icon-reply']);

            $act = ($_REQUEST['act'] == 'add') ? 'insert' : 'update';
            $this->smarty->assign('act', $act);
            if ($_REQUEST['act'] == 'add') {
                /* 取得国家列表、商店所在国家、商店所在国家的省列表 */
                $this->smarty->assign('countries', get_regions());
                $this->smarty->assign('provinces', get_regions(1, 1));
                $this->smarty->assign('cities', []);
                $this->smarty->assign('districts', []);
            } else {
                $id = isset($_REQUEST['id']) ? $_REQUEST['id'] : 0;
                $sql = "SELECT * FROM " . $this->dsc->table("offline_store") . " WHERE id = '$id' LIMIT 1";
                $offline_store = $this->db->getRow($sql);

                $store_user_info = $this->db->getRow("SELECT stores_user, email FROM " . $this->dsc->table('store_user') . " WHERE store_id = '" . $offline_store['id'] . "' AND parent_id = 0");

                $offline_store['stores_user'] = $store_user_info['stores_user'];
                $offline_store['email'] = $store_user_info['email'];
                $offline_store['stores_img'] = $this->dscRepository->getImagePath($offline_store['stores_img']);

                /* 取得国家列表、商店所在国家、商店所在国家的省列表 */
                $this->smarty->assign('countries', get_regions());
                $this->smarty->assign('provinces', get_regions(1, 1));
                $this->smarty->assign('cities', get_regions(2, $offline_store['province']));
                $this->smarty->assign('districts', get_regions(3, $offline_store['city']));
                $this->smarty->assign("offline_store", $offline_store);
            }

            return $this->smarty->display("offline_store_info.dwt");
        } elseif ($_REQUEST['act'] == 'insert' || $_REQUEST['act'] == 'update') {
            $stores_user = isset($_REQUEST['stores_user']) ? $_REQUEST['stores_user'] : '';
            $stores_name = isset($_REQUEST['stores_name']) ? $_REQUEST['stores_name'] : '';
            $country = isset($_REQUEST['country']) ? $_REQUEST['country'] : '';
            $province = isset($_REQUEST['province']) ? $_REQUEST['province'] : '';
            $city = isset($_REQUEST['city']) ? $_REQUEST['city'] : '';
            $district = isset($_REQUEST['district']) ? $_REQUEST['district'] : '';
            $email = isset($_REQUEST['email']) ? $_REQUEST['email'] : '';
            $stores_address = isset($_REQUEST['stores_address']) ? $_REQUEST['stores_address'] : '';
            $stores_tel = isset($_REQUEST['stores_tel']) ? $_REQUEST['stores_tel'] : '';
            $stores_opening_hours = isset($_REQUEST['stores_opening_hours']) ? $_REQUEST['stores_opening_hours'] : '';
            $stores_traffic_line = isset($_REQUEST['stores_traffic_line']) ? $_REQUEST['stores_traffic_line'] : '';
            $is_confirm = isset($_REQUEST['is_confirm']) ? $_REQUEST['is_confirm'] : 0;
            /*入库*/
            if ($_REQUEST['act'] == 'insert') {
                $stores_pwd = isset($_REQUEST['stores_pwd']) ? $_REQUEST['stores_pwd'] : '';
                $confirm_pwd = isset($_REQUEST['confirm_pwd']) ? $_REQUEST['confirm_pwd'] : '';
                /*检查门店名是否重复*/
                $is_only = $exc->is_only('stores_name', $stores_name, 0);
                if (!$is_only) {
                    return sys_msg(sprintf($GLOBALS['_LANG']['title_exist'], stripslashes($stores_name)), 1);
                }

                /*检查登陆名是否重复*/
                $is_only_email = $sto->is_only('email', $email, 0, "email != ''");

                if (!$is_only_email) {
                    return sys_msg($GLOBALS['_LANG']['email_exist']);
                }

                /*检查登陆名是否重复*/
                $is_only_stores_name = $sto->is_only('stores_user', $stores_user, 0);

                if (!$is_only_stores_name) {
                    return sys_msg($GLOBALS['_LANG']['only_stores_name']);
                }

                /*判断两次密码是否一样*/
                if (strlen($stores_pwd) !== strlen($confirm_pwd)) {
                    return sys_msg($GLOBALS['_LANG']['is_different']);
                }

                /* 取得文件地址 */
                $file_url = '';
                if ((isset($_FILES['stores_img']['error']) && $_FILES['stores_img']['error'] == 0) || (!isset($_FILES['stores_img']['error']) && isset($_FILES['stores_img']['tmp_name']) && $_FILES['stores_img']['tmp_name'] != 'none')) {
                    // 检查文件格式
                    if (!check_file_type($_FILES['stores_img']['tmp_name'], $_FILES['stores_img']['name'], $allow_file_types)) {
                        return sys_msg($GLOBALS['_LANG']['invalid_file']);
                    }

                    // 复制文件
                    $res = $this->upload_article_file($_FILES['stores_img']);
                    if ($res != false) {
                        $file_url = $res;
                    }
                }
                if ($file_url == '') {
                    $file_url = $_POST['file_url'];
                }
                $ec_salt = rand(1, 9999);
                $stores_pwd = md5(md5($stores_pwd) . $ec_salt);

                $time = gmtime();
                $offline_store = [
                    'stores_name' => $stores_name,
                    'country' => $country,
                    'province' => $province,
                    'city' => $city,
                    'district' => $district,
                    'stores_address' => $stores_address,
                    'stores_tel' => $stores_tel,
                    'stores_opening_hours' => $stores_opening_hours,
                    'stores_traffic_line' => $stores_traffic_line,
                    'stores_img' => $file_url,
                    'is_confirm' => $is_confirm,
                    'ru_id' => $adminru['ru_id'],
                    'add_time' => $time
                ];

                $this->db->autoExecute($this->dsc->table('offline_store'), $offline_store);
                $store_id = $this->db->insert_id();

                if ($store_id) {
                    $store_user = [
                        'ru_id' => $adminru['ru_id'],
                        'store_id' => $store_id,
                        'stores_user' => $stores_user,
                        'stores_pwd' => $stores_pwd,
                        'ec_salt' => $ec_salt,
                        'store_action' => 'all',
                        'add_time' => $time,
                        'tel' => $stores_tel,
                        'email' => $email
                    ];

                    $this->db->autoExecute($this->dsc->table('store_user'), $store_user);

                    $link[0]['text'] = $GLOBALS['_LANG']['GO_add'];
                    $link[0]['href'] = 'offline_store.php?act=add';

                    $link[1]['text'] = $GLOBALS['_LANG']['bank_list'];
                    $link[1]['href'] = 'offline_store.php?act=list';

                    return sys_msg($GLOBALS['_LANG']['add_succeed'], 0, $link);
                }
            } else {
                $newpass = isset($_REQUEST['newpass']) ? $_REQUEST['newpass'] : '';
                $newconfirm_pwd = isset($_REQUEST['newconfirm_pwd']) ? $_REQUEST['newconfirm_pwd'] : '';
                $id = isset($_REQUEST['id']) ? $_REQUEST['id'] : 0;
                /*检查是否重复*/
                $is_only = $exc->is_only('stores_name', $stores_name, 0, "id != '$id'");
                if (!$is_only) {
                    return sys_msg(sprintf($GLOBALS['_LANG']['title_exist'], stripslashes($stores_name)), 1);
                }
                /*检查登陆名是否重复*/
                $is_only_email = $sto->is_only('email', $email, 0, "store_id != '$id' AND email != ''");

                if (!$is_only_email) {
                    return sys_msg($GLOBALS['_LANG']['email_exist']);
                }

                /*检查登陆名是否重复*/
                $is_only_stores_name = $sto->is_only('stores_user', $stores_user, 0, "store_id != $id");

                if (!$is_only_stores_name) {
                    return sys_msg($GLOBALS['_LANG']['only_stores_name']);
                }


                /* 取得文件地址 */
                $file_url = '';
                if ((isset($_FILES['stores_img']['error']) && $_FILES['stores_img']['error'] == 0) || (!isset($_FILES['stores_img']['error']) && isset($_FILES['stores_img']['tmp_name']) && $_FILES['stores_img']['tmp_name'] != 'none')) {
                    // 检查文件格式
                    if (!check_file_type($_FILES['stores_img']['tmp_name'], $_FILES['stores_img']['name'], $allow_file_types)) {
                        return sys_msg($GLOBALS['_LANG']['invalid_file']);
                    }
                    // 复制文件
                    $res = $this->upload_article_file($_FILES['stores_img']);
                    if ($res != false) {
                        $file_url = $res;
                    }
                }

                if ($file_url == '') {
                    $file_url = $_POST['file_url'];
                }
                /* 如果 file_url 跟以前不一样，且原来的文件是本地文件，删除原来的文件 */
                $sql = "SELECT stores_img FROM " . $this->dsc->table('offline_store') . " WHERE id = '$id'";
                $old_url = $this->db->getOne($sql);
                if ($old_url != '' && $old_url != $file_url && strpos($old_url, 'http: ') === false && strpos($old_url, 'https: ') === false) {
                    @unlink(storage_public($old_url));
                }

                $sql = "SELECT ec_salt FROM " . $this->dsc->table('store_user') . " WHERE store_id = '$id' AND parent_id = 0";
                $ec_salt = $this->db->getOne($sql);
                $where = '';
                if ($newpass != '') {
                    $where = "stores_pwd = '" . md5(md5($newpass) . $ec_salt) . "',";
                }

                $offline_store = [
                    'stores_name' => $stores_name,
                    'country' => $country,
                    'province' => $province,
                    'city' => $city,
                    'district' => $district,
                    'stores_address' => $stores_address,
                    'stores_tel' => $stores_tel,
                    'stores_opening_hours' => $stores_opening_hours,
                    'stores_traffic_line' => $stores_traffic_line,
                    'stores_img' => $file_url,
                    'is_confirm' => $is_confirm
                ];

                $this->db->autoExecute($this->dsc->table('offline_store'), $offline_store, 'UPDATE', "id = '$id'");

                $sql = "UPDATE" . $this->dsc->table('store_user') . " SET $where stores_user='$stores_user' , tel = '$stores_tel' ,email='$email' WHERE store_id = '$id' AND parent_id = 0";
                $this->db->query($sql);
                $link[0]['text'] = $GLOBALS['_LANG']['bank_list'];
                $link[0]['href'] = 'offline_store.php?act=list';
                return sys_msg($GLOBALS['_LANG']['edit_succeed'], 0, $link);
            }
        } /*订单统计*/
        elseif ($_REQUEST['act'] == 'order_stats') {
            admin_priv('offline_store');
            $this->smarty->assign('menu_select', ['action' => '10_offline_store', 'current' => '2_order_stats']);

            //页面赋值
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['2_order_stats']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['12_offline_store'], 'href' => 'offline_store.php?act=list', 'class' => 'icon-reply']);

            $start_time = local_mktime(0, 0, 0, date('m'), 1, date('Y')); //本月第一天
            $end_time = local_mktime(0, 0, 0, date('m'), date('t'), date('Y')) + 24 * 60 * 60 - 1; //本月最后一天
            $start_time = local_date($GLOBALS['_CFG']['time_format'], $start_time);
            $end_time = local_date($GLOBALS['_CFG']['time_format'], $end_time);

            $this->smarty->assign('start_time', $start_time);
            $this->smarty->assign('end_time', $end_time);

            $this->smarty->assign('os_list', $this->get_status_list('order'));
            $this->smarty->assign('ss_list', $this->get_status_list('shipping'));

            $data = $this->get_data_list(1);

            //分页
            $page = isset($_REQUEST['page']) && !empty(intval($_REQUEST['page'])) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($data, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);

            $this->smarty->assign('data_list', $data['data_list']);
            $this->smarty->assign('filter', $data['filter']);
            $this->smarty->assign('record_count', $data['record_count']);
            $this->smarty->assign('page_count', $data['page_count']);
            $this->smarty->assign('store_total', $data['store_total']);
            $this->smarty->assign('date_start_time', $data['filter']['date_start_time']);
            $this->smarty->assign('date_end_time', $data['filter']['date_end_time']);

            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('sort_order_time', '<img src="__TPL__/images/sort_desc.gif">');


            return $this->smarty->display("store_starts_order.dwt");
        } elseif ($_REQUEST['act'] == 'order_stats_query') {
            $data = $this->get_data_list(1);

            //分页
            $page = isset($_REQUEST['page']) && !empty($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
            $page_count_arr = seller_page($data, $page);
            $this->smarty->assign('page_count_arr', $page_count_arr);

            $this->smarty->assign('data_list', $data['data_list']);
            $this->smarty->assign('filter', $data['filter']);
            $this->smarty->assign('store_total', $data['store_total']);
            $this->smarty->assign('record_count', $data['record_count']);
            $this->smarty->assign('page_count', $data['page_count']);

            $sort_flag = sort_flag($data['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result($this->smarty->fetch('store_starts_order.dwt'), '', ['filter' => $data['filter'], 'page_count' => $data['page_count']]);
        } /*页面编辑*/
        elseif ($_REQUEST['act'] == 'edit_stores_tel') {
            $check_auth = check_authz_json('offline_store');
            if ($check_auth !== true) {
                return $check_auth;
            }
            $id = intval($_POST['id']);
            $order = json_str_iconv(trim($_POST['val']));

            if ($exc->edit("stores_tel = '$order'", $id)) {
                clear_cache_files();
                return make_json_result(stripslashes($order));
            } else {
                return make_json_error($this->db->error());
            }
        } /*页面编辑*/
        elseif ($_REQUEST['act'] == 'toggle_show') {
            $check_auth = check_authz_json('offline_store');
            if ($check_auth !== true) {
                return $check_auth;
            }
            $id = intval($_POST['id']);
            $order = json_str_iconv(trim($_POST['val']));
            if ($exc->edit("is_confirm = '$order'", $id)) {
                clear_cache_files();
                return make_json_result(stripslashes($order));
            } else {
                return make_json_error($this->db->error());
            }
        } /*页面编辑*/
        elseif ($_REQUEST['act'] == 'edit_stores_opening_hours') {
            $check_auth = check_authz_json('offline_store');
            if ($check_auth !== true) {
                return $check_auth;
            }
            $id = intval($_POST['id']);
            $order = json_str_iconv(trim($_POST['val']));

            if ($exc->edit("stores_opening_hours = '$order'", $id)) {
                clear_cache_files();
                return make_json_result(stripslashes($order));
            } else {
                return make_json_error($this->db->error());
            }
        } /*删除*/
        elseif ($_REQUEST['act'] == 'remove') {
            // 权限
            $check_auth = check_authz_json('offline_store');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_GET['id']);
            /* 删除原来的文件 */
            $sql = "SELECT stores_img FROM " . $this->dsc->table('offline_store') . " WHERE id = " . $id;
            $old_url = $this->db->getOne($sql);
            if ($old_url != '' && @strpos($old_url, 'http://') === false && @strpos($old_url, 'https://') === false) {
                @unlink(storage_public($old_url));
            }

            // 删除门店
            $exc->drop($id);
            // 删除门店会员
            $sql = 'DELETE FROM ' . $this->dsc->table('store_user') . " WHERE store_id = " . $id;
            $this->db->query($sql);

            $seller_name = session('seller_name', '');
            admin_log(addslashes($seller_name), 'remove', 'business');
            $url = 'offline_store.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));
            return dsc_header("Location: $url\n");
        }
        /*------------------------------------------------------ */
        //-- 批量操作
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'batch_remove') {
            $checkboxes = !empty($_REQUEST['checkboxes']) ? $_REQUEST['checkboxes'] : [];
            if ($_REQUEST['batch_handle'] == 'open_batch' || $_REQUEST['batch_handle'] == 'off_batch') {
                $is_confirm = '';
                if ($_REQUEST['batch_handle'] == 'open_batch') {
                    $is_confirm = 1;
                } elseif ($_REQUEST['batch_handle'] == 'off_batch') {
                    $is_confirm = 0;
                }
                $sql = 'UPDATE' . $this->dsc->table('offline_store') . " SET is_confirm = '$is_confirm' WHERE id" . db_create_in($checkboxes);
                if ($this->db->query($sql) == true) {
                    $seller_name = session('seller_name', '');
                    admin_log(addslashes($seller_name), 'batch_remove', 'offline_batch');
                    $link[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'offline_store.php?act=list&' . list_link_postfix()];
                    return sys_msg($GLOBALS['_LANG']['handle_succeed'], 0, $link);
                }
            } elseif ($_REQUEST['batch_handle'] == 'drop_batch') {
                if (!empty($checkboxes)) {
                    /*处理上传图片*/
                    $sql = " SELECT stores_img FROM" . $this->dsc->table("offline_store") . "WHERE id" . db_create_in($checkboxes);
                    $stores_img = $this->db->getAll($sql);
                    /*存在  删除图片*/
                    if (!empty($stores_img)) {
                        foreach ($stores_img as $k => $v) {
                            if ($v['stores_img'] != '') {
                                @unlink(storage_public($v['stores_img']));
                            }
                        }
                    }
                    // 删除门店
                    $sql = 'DELETE FROM ' . $this->dsc->table('offline_store') . " WHERE id" . db_create_in($checkboxes);
                    $res = $this->db->query($sql);
                    // 删除门店会员
                    $sql = 'DELETE FROM ' . $this->dsc->table('store_user') . " WHERE store_id" . db_create_in($checkboxes);
                    $this->db->query($sql);

                    if ($res == true) {
                        // 操作日志
                        $seller_name = session('seller_name', '');
                        admin_log(addslashes($seller_name), 'batch_remove', 'offline_batch');

                        $link[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'offline_store.php?act=list&' . list_link_postfix()];
                        return sys_msg($GLOBALS['_LANG']['delete_succeed'], 0, $link);
                    }
                } else {
                    $link[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'offline_store.php?act=list&' . list_link_postfix()];
                    return sys_msg($GLOBALS['_LANG']['delete_fail'], 0, $link);
                }
            }
        }
    }

    /* 上传文件 */
    private function upload_article_file($upload)
    {
        $file_dir = storage_public(DATA_DIR . "/offline_store");
        if (!file_exists($file_dir)) {
            if (!make_dir($file_dir)) {
                /* 创建目录失败 */
                return false;
            }
        }
        $image = new Image();
        $filename = $image->random_filename() . substr($upload['name'], strpos($upload['name'], '.'));
        $path = storage_public(DATA_DIR . "/offline_store/" . $filename);

        if (move_upload_file($upload['tmp_name'], $path)) {
            return DATA_DIR . "/offline_store/" . $filename;
        } else {
            return false;
        }
    }

    /*获取门店列表*/
    private function get_offline_store_list($ru_id)
    {
        $result = get_filter();
        if ($result === false) {
            /*筛选信息*/
            $filter['stores_user'] = empty($_REQUEST['stores_user']) ? '' : trim($_REQUEST['stores_user']);
            $filter['stores_name'] = empty($_REQUEST['stores_name']) ? '' : trim($_REQUEST['stores_name']);
            $filter['is_confirm'] = isset($_REQUEST['is_confirm']) ? intval($_REQUEST['is_confirm']) : -1;

            $filter['keywords'] = isset($_REQUEST['keywords']) ? trim($_REQUEST['keywords']) : '';
            /*拼装筛选*/
            $where = ' WHERE 1 ';
            if ($filter['stores_user']) {
                $sql = "SELECT store_id FROM" . $this->dsc->table('store_user') . "stores_user LIKE '%" . mysql_like_quote($filter['stores_user']) . "%' AND parent_id = 0";
                $store_id = $this->db->getOne($sql);
                $where .= " AND id = '" . $store_id . "'  ";
            }
            if ($filter['stores_name']) {
                $where .= " AND stores_name LIKE '%" . mysql_like_quote($filter['stores_name']) . "%'";
            }
            if ($filter['is_confirm'] != -1) {
                $where .= " AND is_confirm = '" . $filter['is_confirm'] . "'";
            }
            if ($ru_id > 0) {
                $filter['ru_id'] = $ru_id;
                $where .= " AND ru_id = '" . $filter['ru_id'] . "'";
            }
            $sql = "SELECT COUNT(*) FROM " . $this->dsc->table('offline_store') . $where;
            $filter['record_count'] = $this->db->getOne($sql);
            $filter = page_and_size($filter);
            /* 获活动数据 */
            $sql = "SELECT o.ru_id,o.id,o.stores_name,o.stores_address,o.stores_tel,o.stores_opening_hours,"
                . "o.stores_traffic_line,o.stores_img,o.is_confirm,a.region_name as country , "
                . "b.region_name as province ,c.region_name as city, d.region_name as district "
                . "FROM" . $this->dsc->table('offline_store') . " AS o "
                . "LEFT JOIN " . $this->dsc->table('region') . " AS a ON a.region_id = o.country "
                . "LEFT JOIN " . $this->dsc->table('region') . " AS b ON b.region_id = o.province "
                . "LEFT JOIN " . $this->dsc->table('region') . " AS c ON c.region_id = o.city "
                . "LEFT JOIN " . $this->dsc->table('region') . " AS d ON d.region_id = o.district $where ORDER BY o.id ASC LIMIT " . $filter['start'] . "," . $filter['page_size'];

            $filter['keywords'] = isset($filter['keywords']) ? stripslashes($filter['keywords']) : '';
            set_filter($filter, $sql);
        } else {
            $sql = $result['sql'];
            $filter = $result['filter'];
        }
        $row = $this->db->getAll($sql);
        foreach ($row as $k => $v) {
            $row[$k]['shop_name'] = $this->merchantCommonService->getShopName($v['ru_id'], 1);
            $row[$k]['stores_user'] = $this->db->getOne("SELECT stores_user FROM" . $this->dsc->table('store_user') . " WHERE store_id = '" . $v['id'] . "' AND parent_id = 0");
            $row[$k]['stores_img'] = get_image_path($v['stores_img']);
        }
        $arr = ['pzd_list' => $row, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];
        return $arr;
    }

    /**
     * 取得状态列表
     * @param string $type 类型：all | order | shipping | payment
     */
    private function get_status_list($type = 'all')
    {
        $list = [];

        if ($type == 'all' || $type == 'order') {
            $pre = $type == 'all' ? 'os_' : '';
            foreach ($GLOBALS['_LANG']['os'] as $key => $value) {
                $list[$pre . $key] = $value;
            }
        }

        if ($type == 'all' || $type == 'shipping') {
            $pre = $type == 'all' ? 'ss_' : '';
            foreach ($GLOBALS['_LANG']['ss'] as $key => $value) {
                $list[$pre . $key] = $value;
            }
        }

        if ($type == 'all' || $type == 'payment') {
            $pre = $type == 'all' ? 'ps_' : '';
            foreach ($GLOBALS['_LANG']['ps'] as $key => $value) {
                $list[$pre . $key] = $value;
            }
        }
        return $list;
    }

    private function get_data_list($type = 0)
    {
        $adminru = get_admin_ru_id();
        $where = " 1 AND o.main_count = 0 ";  //主订单下有子订单时，则主订单不显示

        if ($type != 0) {
            $result = get_filter();

            if ($result === false) {
                $filter['order_type'] = !empty($_REQUEST['order_type']) ? intval($_REQUEST['order_type']) : 0;
                $filter['date_start_time'] = !empty($_REQUEST['date_start_time']) ? trim($_REQUEST['date_start_time']) : '';
                $filter['date_end_time'] = !empty($_REQUEST['date_end_time']) ? trim($_REQUEST['date_end_time']) : '';
                $filter['store_name'] = !empty($_REQUEST['store_name']) ? trim($_REQUEST['store_name']) : '';

                $filter['sort_by'] = isset($_REQUEST['sort_by']) ? trim($_REQUEST['sort_by']) : '';

                $filter['order_status'] = isset($_REQUEST['order_status']) ? explode(',', $_REQUEST['order_status']) : '-1';
                $filter['shipping_status'] = isset($_REQUEST['shipping_status']) ? explode(',', $_REQUEST['shipping_status']) : '-1';
                if ($filter['date_start_time'] == '' && $filter['date_end_time'] == '') {
                    $start_time = local_mktime(0, 0, 0, date('m'), 1, date('Y')); //本月第一天
                    $end_time = local_mktime(0, 0, 0, date('m'), date('t'), date('Y')) + 24 * 60 * 60 - 1; //本月最后一天
                } else {
                    $start_time = local_strtotime($filter['date_start_time']);
                    $end_time = local_strtotime($filter['date_end_time']);
                }

                $where .= " AND o.add_time > '" . $start_time . "' AND o.add_time < '" . $end_time . "'";
                if (isset($filter['store_name']) && $filter['store_name']) {
                    $sql = "SELECT id FROM" . $this->dsc->table('offline_store') . " WHERE stores_name LIKE '%" . mysql_like_quote($filter['store_name']) . "%'";
                    $filter['store_id'] = $this->db->getOne($sql);
                    $where .= " AND sto.store_id = '" . $filter['store_id'] . "'";
                }
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

                if ($filter['order_status'] != '-1') { //多选
                    $order_status = implode(',', $filter['order_status']);

                    if ($order_status != '') {
                        $where .= " AND o.order_status in($order_status)";
                    }
                }

                if ($filter['shipping_status'] != '-1') { //多选
                    $shipping_status = implode(',', $filter['shipping_status']);
                    if ($shipping_status != '') {
                        $where .= " AND o.shipping_status in($shipping_status)";
                    }
                }

                if ($filter['order_type'] > 0) {
                    $where .= " AND sto.is_grab_order = 1 ";
                }

                if ($adminru['ru_id'] > 0) {
                    $where .= " AND og.ru_id = '" . $adminru['ru_id'] . "'";
                }

                $sql = "SELECT og.goods_id, og.order_id, og.goods_id, og.goods_name, og.ru_id, og.goods_sn, og.goods_price, o.add_time, " .
                    "(" . $this->orderService->orderAmountField('o.') . ") AS total_fee, og.goods_number ,sto.store_id " .
                    " FROM " . $this->dsc->table('order_goods') . " AS og " .
                    " LEFT JOIN " . $this->dsc->table('order_info') . " AS o " . " ON o.order_id = og.order_id " .
                    "LEFT JOIN " . $this->dsc->table("store_order") . " AS sto ON sto.order_id = o.order_id " .
                    " LEFT JOIN " . $this->dsc->table('goods') . " AS g " . " ON g.goods_id = og.goods_id " .
                    " WHERE " . $where . " AND sto.store_id > 0  GROUP BY o.order_id ORDER BY og.goods_id DESC";
                set_filter($filter, $sql);
            } else {
                $sql = $result['sql'];
                $filter = $result['filter'];
            }
        }

        $data_list = $this->db->getAll($sql);
        /* 记录总数 */
        $filter['record_count'] = count($data_list);
        $filter['page_count'] = $filter['record_count'] > 0 ? ceil($filter['record_count'] / $filter['page_size']) : 1;
        $store_total = 0;
        if ($type != 0) {
            for ($i = 0; $i < count($data_list); $i++) {
                $data_list[$i]['shop_name'] = $this->merchantCommonService->getShopName($data_list[$i]['ru_id'], 1); //ecmoban模板堂 --zhuo
                $store_total += $data_list[$i]['total_fee'] = $data_list[$i]['goods_number'] * $data_list[$i]['goods_price'];

                $data_list[$i]['stores_name'] = $this->db->getOne("SELECT stores_name FROM " . $this->dsc->table('offline_store') . "  WHERE id = '" . $data_list[$i]['store_id'] . "' ");
                $data_list[$i]['add_time'] = local_date($GLOBALS['_CFG']['time_format'], $data_list[$i]['add_time']);
            }
            if (isset($filter['sort_by']) && $filter['sort_by'] == 'goods_number') {
                $data_list = get_array_sort($data_list, 'goods_number', 'DESC');
            }
            $arr = [
                'data_list' => $data_list,
                'filter' => $filter,
                'page_count' => $filter['page_count'],
                'record_count' => $filter['record_count'],
                'store_total' => price_format($store_total)
            ];

            return $arr;
        }
    }
}
