<?php

namespace App\Modules\Admin\Controllers;

use App\Models\OfflineStore;
use App\Models\StoreUser;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Merchant\MerchantCommonService;
use App\Services\OfflineStore\OfflineStoreManageService;
use App\Services\Order\OrderService;
use App\Services\Store\StoreCommonService;

/**
 * 广告管理程序
 */
class OfflineStoreController extends InitController
{
    protected $orderService;
    protected $merchantCommonService;
    protected $baseRepository;
    protected $offlineStoreManageService;
    protected $storeCommonService;
    protected $dscRepository;

    public function __construct(
        OrderService $orderService,
        MerchantCommonService $merchantCommonService,
        BaseRepository $baseRepository,
        OfflineStoreManageService $offlineStoreManageService,
        StoreCommonService $storeCommonService,
        DscRepository $dscRepository
    )
    {
        $this->orderService = $orderService;
        $this->merchantCommonService = $merchantCommonService;
        $this->baseRepository = $baseRepository;
        $this->offlineStoreManageService = $offlineStoreManageService;
        $this->storeCommonService = $storeCommonService;
        $this->dscRepository = $dscRepository;
    }

    public function index()
    {
        /* 允许上传的文件类型 */
        $allow_file_types = '|GIF|JPG|PNG|';

        //ecmoban模板堂 --zhuo start
        $adminru = get_admin_ru_id();
        if ($adminru['ru_id'] == 0) {
            $this->smarty->assign('priv_ru', 1);
        } else {
            $this->smarty->assign('priv_ru', 0);
        }
        //ecmoban模板堂 --zhuo end

        /* 时间参数 begin */
        $start_date = $end_date = '';
        if (isset($_POST) && !empty($_POST)) {
            $_POST['start_date'] = isset($_POST['start_date']) ? $_POST['start_date'] : '';
            $_POST['end_date'] = isset($_POST['end_date']) ? $_POST['end_date'] : '';
            $start_date = local_strtotime($_POST['start_date']);
            $end_date = local_strtotime($_POST['end_date']);
        } elseif (isset($_GET['start_date']) && !empty($_GET['end_date'])) {
            $_GET['start_date'] = isset($_GET['start_date']) ? $_GET['start_date'] : '';
            $_GET['end_date'] = isset($_GET['end_date']) ? $_GET['end_date'] : '';
            $start_date = local_strtotime($_GET['start_date']);
            $end_date = local_strtotime($_GET['end_date']);
        } else {
            $today = local_strtotime(local_date('Y-m-d'));
            $start_date = $today - 86400 * 7;
            $end_date = $today;
        }
        /* 时间参数 end */
        /*------------------------------------------------------ */
        //-- 门店列表
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            admin_priv('offline_store');

            $this->smarty->assign('menu_select', ['action' => '17_merchants', 'current' => '12_seller_store']);

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['12_offline_store']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['add_stores'], 'href' => 'offline_store.php?act=add']);

            /*获取店铺*/
            $store_list = $this->storeCommonService->getCommonStoreList();
            $this->smarty->assign('store_list', $store_list);

            $offline_store = $this->offlineStoreManageService->getOfflineStoreList();

            $this->smarty->assign('offline_store', $offline_store['pzd_list']);
            $this->smarty->assign('filter', $offline_store['filter']);
            $this->smarty->assign('record_count', $offline_store['record_count']);
            $this->smarty->assign('page_count', $offline_store['page_count']);
            $this->smarty->assign('full_page', 1);


            return $this->smarty->display("offline_store_list.dwt");
        }

        /*------------------------------------------------------ */
        //-- 翻页、搜索、排序
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            admin_priv('offline_store');
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['12_offline_store']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['add_stores'], 'href' => 'offline_store.php?act=add']);

            /*获取店铺*/
            $store_list = $this->storeCommonService->getCommonStoreList();
            $this->smarty->assign('store_list', $store_list);

            $offline_store = $this->offlineStoreManageService->getOfflineStoreList();

            $this->smarty->assign('offline_store', $offline_store['pzd_list']);
            $this->smarty->assign('filter', $offline_store['filter']);
            $this->smarty->assign('record_count', $offline_store['record_count']);
            $this->smarty->assign('page_count', $offline_store['page_count']);

            //跳转页面
            return make_json_result($this->smarty->fetch('offline_store_list.dwt'), '', ['filter' => $offline_store['filter'], 'page_count' => $offline_store['page_count']]);
        }

        /*------------------------------------------------------ */
        //-- 添加/编辑
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add' || $_REQUEST['act'] == 'edit') {
            admin_priv('offline_store');
            //页面赋值
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['add_stores']);
            $href = isset($_REQUEST['type']) && $_REQUEST['type'] == 1 ? 'offline_store.php?act=list&type=' . intval($_REQUEST['type']) : 'offline_store.php?act=list';
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['12_offline_store'], 'href' => $href]);

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
                $res = OfflineStore::where('id', $id);
                $offline_store = $this->baseRepository->getToArrayFirst($res);

                $res = StoreUser::where('store_id', $offline_store['id'])->where('parent_id', 0);
                $store_user_info = $this->baseRepository->getToArrayFirst($res);

                $offline_store['stores_user'] = $store_user_info['stores_user'] ?? '';
                $offline_store['stores_email'] = $store_user_info['email'] ?? '';
                $offline_store['stores_img'] = get_image_path($offline_store['stores_img']);

                if (isset($GLOBALS['_CFG']['show_mobile']) && $GLOBALS['_CFG']['show_mobile'] == 0 && $offline_store) {
                    $offline_store['stores_tel'] = $this->dscRepository->stringToStar($offline_store['stores_tel']);
                }

                /* 取得国家列表、商店所在国家、商店所在国家的省列表 */
                $this->smarty->assign('countries', get_regions());
                $this->smarty->assign('provinces', get_regions(1, 1));
                $this->smarty->assign('cities', get_regions(2, $offline_store['province']));
                $this->smarty->assign('districts', get_regions(3, $offline_store['city']));
                $this->smarty->assign("offline_store", $offline_store);
            }

            return $this->smarty->display("offline_store_info.dwt");
        }

        /*------------------------------------------------------ */
        //-- 插入/更新
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'insert' || $_REQUEST['act'] == 'update') {
            $stores_user = isset($_REQUEST['stores_user']) ? $_REQUEST['stores_user'] : '';
            $stores_name = isset($_REQUEST['stores_name']) ? $_REQUEST['stores_name'] : '';
            $country = isset($_REQUEST['country']) ? $_REQUEST['country'] : '';
            $province = isset($_REQUEST['province']) ? $_REQUEST['province'] : '';
            $city = isset($_REQUEST['city']) ? $_REQUEST['city'] : '';
            $district = isset($_REQUEST['district']) ? $_REQUEST['district'] : '';
            $stores_address = isset($_REQUEST['stores_address']) ? $_REQUEST['stores_address'] : '';
            $stores_tel = isset($_REQUEST['stores_tel']) ? $_REQUEST['stores_tel'] : '';
            $stores_email = isset($_REQUEST['stores_email']) ? $_REQUEST['stores_email'] : '';
            $stores_opening_hours = isset($_REQUEST['stores_opening_hours']) ? $_REQUEST['stores_opening_hours'] : '';
            $stores_traffic_line = isset($_REQUEST['stores_traffic_line']) ? $_REQUEST['stores_traffic_line'] : '';
            $is_confirm = isset($_REQUEST['is_confirm']) ? $_REQUEST['is_confirm'] : 0;
            /*入库*/
            if ($_REQUEST['act'] == 'insert') {
                $stores_pwd = isset($_REQUEST['stores_pwd']) ? $_REQUEST['stores_pwd'] : '';
                $confirm_pwd = isset($_REQUEST['confirm_pwd']) ? $_REQUEST['confirm_pwd'] : '';
                /*检查门店名是否重复*/
                $is_only = OfflineStore::where('stores_name', $stores_name)->count();
                if ($is_only > 0) {
                    return sys_msg(sprintf($GLOBALS['_LANG']['title_exist'], stripslashes($stores_name)), 1);
                }

                /*检查登陆名是否重复*/
                $is_only_stores_name = StoreUser::where('stores_user', $stores_user)->count();
                if ($is_only_stores_name > 0) {
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
                    $res = $this->offlineStoreManageService->uploadArticleFile($_FILES['stores_img']);
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

                $store_id = OfflineStore::insertGetId($offline_store);

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
                        'email' => $stores_email
                    ];

                    StoreUser::insert($store_user);

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
                $is_only = OfflineStore::where('stores_name', $stores_name)
                    ->where('id', '<>', $id)
                    ->count();
                if ($is_only > 0) {
                    return sys_msg(sprintf($GLOBALS['_LANG']['title_exist'], stripslashes($stores_name)), 1);
                }
                /*检查登陆名是否重复*/
                $is_only_stores_name = StoreUser::where('stores_user', $stores_user)->where('store_id', '<>', $id)->count();

                if ($is_only_stores_name > 0) {
                    return sys_msg($GLOBALS['_LANG']['only_stores_name']);
                }
                /*判断两次密码是否一样*/
                if (strlen($newconfirm_pwd) !== strlen($newpass)) {
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
                    $res = $this->offlineStoreManageService->uploadArticleFile($_FILES['stores_img']);
                    if ($res != false) {
                        $file_url = $res;
                    }
                }

                if ($file_url == '') {
                    $file_url = $_POST['file_url'];
                }
                /* 如果 file_url 跟以前不一样，且原来的文件是本地文件，删除原来的文件 */
                $os_res = OfflineStore::where('id', $id);
                $offline_store_info = $this->baseRepository->getToArrayFirst($os_res);

                $old_url = $offline_store_info ? $offline_store_info['stores_img'] : '';

                if ($old_url != '' && $old_url != $file_url && strpos($old_url, 'http: ') === false && strpos($old_url, 'https: ') === false) {
                    @unlink(storage_public($old_url));
                }

                $ec_salt = StoreUser::where('store_id', $id)->where('parent_id', 0)->value('ec_salt');
                $ec_salt = $ec_salt ? $ec_salt : '';

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

                OfflineStore::where('id', $id)->update($offline_store);

                $data = [
                    'stores_user' => $stores_user,
                    'tel' => $stores_tel,
                    'email' => $stores_email
                ];

                if ($newpass != '') {
                    $data['stores_pwd'] = md5(md5($newpass) . $ec_salt);
                    $data['login_status'] = '';
                }
                StoreUser::where('store_id', $id)
                    ->where('parent_id', 0)
                    ->update($data);

                $link[0]['text'] = $GLOBALS['_LANG']['bank_list'];

                if ($offline_store_info['ru_id'] > 0) {
                    $link[0]['href'] = 'offline_store.php?act=list&type=1';
                } else {
                    $link[0]['href'] = 'offline_store.php?act=list';
                }

                return sys_msg($GLOBALS['_LANG']['edit_succeed'], 0, $link);
            }
        } /*订单统计*/
        elseif ($_REQUEST['act'] == 'order_stats') {
            admin_priv('offline_store');

            $this->smarty->assign('menu_select', ['action' => '17_merchants', 'current' => '16_seller_users_real']);

            //页面赋值
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['2_order_stats']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['12_offline_store'], 'href' => 'offline_store.php?act=list']);

            $start_time = local_mktime(0, 0, 0, date('m'), 1, date('Y')); //本月第一天
            $end_time = local_mktime(0, 0, 0, date('m'), date('t'), date('Y')) + 24 * 60 * 60 - 1; //本月最后一天
            $start_time = local_date($GLOBALS['_CFG']['time_format'], $start_time);
            $end_time = local_date($GLOBALS['_CFG']['time_format'], $end_time);

            $this->smarty->assign('start_time', $start_time);
            $this->smarty->assign('end_time', $end_time);

            $this->smarty->assign('os_list', $this->offlineStoreManageService->getStatusList('order'));
            $this->smarty->assign('ss_list', $this->offlineStoreManageService->getStatusList('shipping'));

            /*获取店铺*/
            $store_list = $this->storeCommonService->getCommonStoreList();
            $this->smarty->assign('store_list', $store_list);

            $data = $this->offlineStoreManageService->getDataList($type = 1);

            $data['start_time'] = isset($data['start_time']) ? $data['start_time'] : '';
            $data['end_time'] = isset($data['end_time']) ? $data['end_time'] : '';

            //分页
            $this->smarty->assign('data_list', $data['data_list']);
            $this->smarty->assign('filter', $data['filter']);
            $this->smarty->assign('record_count', $data['record_count']);
            $this->smarty->assign('page_count', $data['page_count']);
            $this->smarty->assign('store_total', $data['store_total']);
            $this->smarty->assign('date_start_time', $data['start_time']);
            $this->smarty->assign('date_end_time', $data['end_time']);

            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('sort_order_time', '<img src="' . __TPL__ . '/images/sort_desc.gif">');


            return $this->smarty->display("store_stats_order.dwt");
        } elseif ($_REQUEST['act'] == 'order_stats_query') {
            $data = $this->offlineStoreManageService->getDataList(1);

            //分页
            $this->smarty->assign('data_list', $data['data_list']);
            $this->smarty->assign('filter', $data['filter']);
            $this->smarty->assign('store_total', $data['store_total']);
            $this->smarty->assign('record_count', $data['record_count']);
            $this->smarty->assign('page_count', $data['page_count']);

            $sort_flag = sort_flag($data['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result($this->smarty->fetch('store_stats_order.dwt'), '', ['filter' => $data['filter'], 'page_count' => $data['page_count']]);
        } /*页面编辑*/
        elseif ($_REQUEST['act'] == 'edit_stores_tel') {
            $check_auth = check_authz_json('offline_store');
            if ($check_auth !== true) {
                return $check_auth;
            }
            $id = intval($_POST['id']);
            $order = json_str_iconv(trim($_POST['val']));

            $data = ['stores_tel' => $order];
            $res = OfflineStore::where('id', $id)->update($data);
            if ($res > 0) {
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

            $data = ['is_confirm' => $order];
            $res = OfflineStore::where('id', $id)->update($data);
            if ($res > 0) {
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

            $data = ['stores_opening_hours' => $order];
            $res = OfflineStore::where('id', $id)->update($data);
            if ($res > 0) {
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
            $old_url = OfflineStore::where('id', $id)->value('stores_img');
            $old_url = $old_url ? $old_url : '';

            if ($old_url != '' && @strpos($old_url, 'http://') === false && @strpos($old_url, 'https://') === false) {
                @unlink(storage_public($old_url));
            }

            // 删除门店
            OfflineStore::where('id', $id)->delete();
            // 删除门店会员
            StoreUser::where('store_id', $id)->delete();

            // 操作日志
            admin_log(addslashes(session('admin_name')), 'remove', 'offline_store');

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

                $checkboxes = $this->baseRepository->getExplode($checkboxes);
                $data = ['is_confirm' => $is_confirm];
                OfflineStore::whereIn('id', $checkboxes)->update($data);

                admin_log(addslashes(session('admin_name')), 'batch_remove', 'offline_batch');

                $link[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'offline_store.php?act=list&' . list_link_postfix()];
                return sys_msg($GLOBALS['_LANG']['handle_succeed'], 0, $link);
            } elseif ($_REQUEST['batch_handle'] == 'drop_batch') {
                if (!empty($checkboxes)) {
                    /*处理上传图片*/
                    $checkboxes = $this->baseRepository->getExplode($checkboxes);
                    $res = OfflineStore::whereIn('id', $checkboxes);
                    $stores_img = $this->baseRepository->getToArrayGet($res);
                    /*存在  删除图片*/
                    if (!empty($stores_img)) {
                        foreach ($stores_img as $k => $v) {
                            if ($v['stores_img']) {
                                @unlink(storage_public($v['stores_img']));
                            }
                        }
                    }

                    // 删除门店
                    OfflineStore::whereIn('id', $checkboxes)->delete();
                    // 删除门店会员
                    StoreUser::whereIn('store_id', $checkboxes)->delete();

                    // 操作日志
                    admin_log(addslashes(session('admin_name')), 'batch_remove', 'offline_batch');

                    $link[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'offline_store.php?act=list&' . list_link_postfix()];
                    return sys_msg($GLOBALS['_LANG']['delete_succeed'], 0, $link);
                } else {
                    $link[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'offline_store.php?act=list&' . list_link_postfix()];
                    return sys_msg($GLOBALS['_LANG']['delete_fail'], 0, $link);
                }
            }
        }
        /*------------------------------------------------------ */
        //-- 门店销量统计
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'stat') {
            admin_priv('offline_store');
            //页面赋值
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['sale_stat']);
            $type = isset($_REQUEST['type']) && $_REQUEST['type'] == 1 ? 1 : 0;
            $href = $type ? 'offline_store.php?act=list&type=' . $type : 'offline_store.php?act=list';
            $this->smarty->assign('type', $type);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['12_offline_store'], 'href' => $href]);
            $this->smarty->assign('start_date', local_date('Y-m-d', $start_date));
            $this->smarty->assign('end_date', local_date('Y-m-d', $end_date));

            return $this->smarty->display("offline_store_stat.dwt");
        }

        /* ------------------------------------------------------ */
        //-- 获取店铺统计结果
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'stat_filter') {
            $result = ['error' => 0, 'message' => '', 'content' => ''];
            $res = $this->offlineStoreManageService->statFilter();

            $result['order_num'] = $res['order_num'];
            $result['total_fee'] = $res['total_fee'];

            $this->smarty->assign('goods_list', $res['goods_list']);

            $result['content'] = $this->smarty->fetch('library/offline_store_stat_filter.lbi');
            return response()->json($result);
        }
    }
}
