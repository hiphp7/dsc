<?php

namespace App\Modules\Admin\Controllers;

use App\Libraries\Exchange;
use App\Repositories\Common\CommonRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Merchant\MerchantCommonService;

class SuppliersController extends InitController
{
    protected $commonRepository;
    protected $merchantCommonService;
    protected $dscRepository;

    public function __construct(
        CommonRepository $commonRepository,
        MerchantCommonService $merchantCommonService,
        DscRepository $dscRepository
    )
    {
        $this->commonRepository = $commonRepository;
        $this->merchantCommonService = $merchantCommonService;
        $this->dscRepository = $dscRepository;
    }

    public function index()
    {
        /* 初始化 $exc 对象 */
        $exc = new Exchange($this->dsc->table("admin_user"), $this->db, 'user_id', 'user_name');
        $exc_sup = new Exchange($this->dsc->table("suppliers"), $this->db, 'suppliers_id', 'suppliers_name');

        $adminru = get_admin_ru_id();
        if ($adminru['ru_id'] == 0) {
            $this->smarty->assign('priv_ru', 1);
        } else {
            $this->smarty->assign('priv_ru', 0);
        }

        /*------------------------------------------------------ */
        //-- 供货商列表
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            /* 检查权限 */
            admin_priv('suppliers_list');

            /* 查询 */
            $result = $this->suppliers_list();

            /* 模板赋值 */
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['01_suppliers_list']); // 当前导航

            $this->smarty->assign('full_page', 1); // 翻页参数

            $this->smarty->assign('suppliers_list', $result['result']);
            $this->smarty->assign('filter', $result['filter']);
            $this->smarty->assign('record_count', $result['record_count']);
            $this->smarty->assign('page_count', $result['page_count']);
            $this->smarty->assign('sort_suppliers_id', '<img src="images/sort_desc.gif">');

            /* 显示模板 */
            return $this->smarty->display('suppliers_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 排序、分页、查询
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            /* 检查权限 */
            $check_auth = check_authz_json('suppliers_list');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $result = $this->suppliers_list();

            $this->smarty->assign('suppliers_list', $result['result']);
            $this->smarty->assign('filter', $result['filter']);
            $this->smarty->assign('record_count', $result['record_count']);
            $this->smarty->assign('page_count', $result['page_count']);

            /* 排序标记 */
            $sort_flag = sort_flag($result['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result(
                $this->smarty->fetch('suppliers_list.dwt'),
                '',
                array('filter' => $result['filter'], 'page_count' => $result['page_count'])
            );
        }


        /*------------------------------------------------------ */
        //-- 删除供货商
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'remove') {
            /* 检查权限 */
            $check_auth = check_authz_json('suppliers_list');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_REQUEST['id']);
            $sql = "SELECT *
            FROM " . $this->dsc->table('suppliers') . "
            WHERE suppliers_id = '$id'";
            $suppliers = $this->db->getRow($sql, true);

            if ($suppliers['suppliers_id']) {
                /* 判断供货商是否存在订单 */
                $sql = "SELECT COUNT(*)
                FROM " . $this->dsc->table('order_info') . "AS O, " . $this->dsc->table('order_goods') . " AS OG, " . $this->dsc->table('goods') . " AS G
                WHERE O.order_id = OG.order_id
                AND OG.goods_id = G.goods_id
                AND G.suppliers_id = '$id'";
                $order_exists = $this->db->getOne($sql, true);
                if ($order_exists > 0) {
                    make_json_error(lang('admin/suppliers.order_dot_delete'));
                }

                /* 判断供货商是否存在商品 */
                $sql = "SELECT COUNT(*)
                FROM " . $this->dsc->table('goods') . "AS G
                WHERE G.suppliers_id = '$id'";
                $goods_exists = $this->db->getOne($sql, true);
                if ($goods_exists > 0) {
                    make_json_error(lang('admin/suppliers.goods_dot_delete'));
                }

                $sql = "DELETE FROM " . $this->dsc->table('suppliers') . "
            WHERE suppliers_id = '$id'";
                $this->db->query($sql);

                /* 删除管理员、发货单关联、退货单关联和订单关联的供货商 */
                $table_array = array('admin_user', 'delivery_order', 'back_order');
                foreach ($table_array as $value) {
                    $sql = "DELETE FROM " . $this->dsc->table($value) . " WHERE suppliers_id = '$id'";
                    $this->db->query($sql, 'SILENT');
                }

                /* 记日志 */
                admin_log($suppliers['suppliers_name'], 'remove', 'suppliers');

                /* 清除缓存 */
                clear_cache_files();
            }

            $url = 'suppliers.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));
            return dsc_header("Location: $url\n");

            exit;
        }

        /*------------------------------------------------------ */
        //-- 修改供货商状态
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'is_check') {
            /* 检查权限 */
            $check_auth = check_authz_json('suppliers_list');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_REQUEST['id']);
            $sql = "SELECT suppliers_id, is_check
            FROM " . $this->dsc->table('suppliers') . "
            WHERE suppliers_id = '$id'";
            $suppliers = $this->db->getRow($sql, true);

            if ($suppliers['suppliers_id']) {
                $_suppliers['is_check'] = empty($suppliers['is_check']) ? 1 : 0;
                $this->db->autoExecute($this->dsc->table('suppliers'), $_suppliers, '', "suppliers_id = '$id'");
                clear_cache_files();
                return make_json_result($_suppliers['is_check']);
            }

            exit;
        }

        /*------------------------------------------------------ */
        //-- 批量操作
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'batch') {
            /* 取得要操作的记录编号 */
            if (empty($_POST['checkboxes'])) {
                return sys_msg($GLOBALS['_LANG']['no_record_selected']);
            } else {
                /* 检查权限 */
                admin_priv('suppliers_list');

                $ids = $_POST['checkboxes'];

                if (isset($_POST['remove'])) {
                    $sql = "SELECT *
                    FROM " . $this->dsc->table('suppliers') . "
                    WHERE suppliers_id " . db_create_in($ids);
                    $suppliers = $this->db->getAll($sql);

                    foreach ($suppliers as $key => $value) {
                        /* 判断供货商是否存在订单 */
                        $sql = "SELECT COUNT(*)
                        FROM " . $this->dsc->table('order_info') . "AS O, " . $this->dsc->table('order_goods') . " AS OG, " . $this->dsc->table('goods') . " AS G
                        WHERE O.order_id = OG.order_id
                        AND OG.goods_id = G.goods_id
                        AND G.suppliers_id = '" . $value['suppliers_id'] . "'";
                        $order_exists = $this->db->getOne($sql, true);
                        if ($order_exists > 0) {
                            unset($suppliers[$key]);
                        }

                        /* 判断供货商是否存在商品 */
                        $sql = "SELECT COUNT(*)
                        FROM " . $this->dsc->table('goods') . "AS G
                        WHERE G.suppliers_id = '" . $value['suppliers_id'] . "'";
                        $goods_exists = $this->db->getOne($sql, true);
                        if ($goods_exists > 0) {
                            unset($suppliers[$key]);
                        }
                    }
                    if (empty($suppliers)) {
                        return sys_msg($GLOBALS['_LANG']['batch_drop_no']);
                    }


                    $sql = "DELETE FROM " . $this->dsc->table('suppliers') . "
                WHERE suppliers_id " . db_create_in($ids);
                    $this->db->query($sql);

                    /* 更新管理员、发货单关联、退货单关联和订单关联的供货商 */
                    $table_array = array('admin_user', 'delivery_order', 'back_order');
                    foreach ($table_array as $value) {
                        $sql = "DELETE FROM " . $this->dsc->table($value) . " WHERE suppliers_id " . db_create_in($ids) . " ";
                        $this->db->query($sql, 'SILENT');
                    }

                    /* 记日志 */
                    $suppliers_names = '';
                    foreach ($suppliers as $value) {
                        $suppliers_names .= $value['suppliers_name'] . '|';
                    }
                    admin_log($suppliers_names, 'remove', 'suppliers');

                    /* 清除缓存 */
                    clear_cache_files();
                    $link[] = array('text' => lang('admin/suppliers.back'), 'href' => 'suppliers.php?act=list');
                    return sys_msg($GLOBALS['_LANG']['batch_drop_ok'], '', $link);
                }
            }
        }

        /*------------------------------------------------------ */
        //-- 编辑供货商
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit') {
            /* 检查权限 */
            admin_priv('suppliers_list');

            /* 取得供货商信息 */
            $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
            $sql = "SELECT s.*, au.user_name FROM " . $this->dsc->table('suppliers') . " AS s LEFT JOIN " . $this->dsc->table('admin_user') . " AS au ON s.suppliers_id = au.suppliers_id WHERE s.suppliers_id = '$id'";
            $suppliers = $this->db->getRow($sql);

            if (empty($suppliers)) {
                return sys_msg('suppliers does not exist');
            }

            if ($suppliers) {
                $suppliers['front_of_id_card'] = $suppliers['front_of_id_card'] ? get_image_path($suppliers['front_of_id_card']) : '';
                $suppliers['reverse_of_id_card'] = $suppliers['reverse_of_id_card'] ? get_image_path($suppliers['reverse_of_id_card']) : '';
                $suppliers['suppliers_logo'] = $suppliers['suppliers_logo'] ? get_image_path($suppliers['suppliers_logo']) : '';
                // 默认用供应商名称 审核通过后 使用管理员名称
                $suppliers['user_name'] = empty($suppliers['user_name']) ? $suppliers['suppliers_name'] : $suppliers['user_name'];
            }

            if (isset($GLOBALS['_CFG']['show_mobile']) && $GLOBALS['_CFG']['show_mobile'] == 0 && $suppliers) {
                $suppliers['real_name'] = $this->dscRepository->stringToStar($suppliers['real_name']);
                $suppliers['mobile_phone'] = $this->dscRepository->stringToStar($suppliers['mobile_phone']);
                $suppliers['email'] = $this->dscRepository->stringToStar($suppliers['email']);
                $suppliers['user_name'] = $this->dscRepository->stringToStar($suppliers['user_name']);
            }

            $region_level = get_region_level($suppliers['region_id']);
            $region_level[0] = $region_level[0] ?? 1;
            $region_level[1] = $region_level[1] ?? 0;
            $region_level[2] = $region_level[2] ?? 0;

            $country_list = $this->get_regions_log(0, 0);
            $province_list = $this->get_regions_log(1, $region_level[0]);
            $city_list = $this->get_regions_log(2, $region_level[1]);
            $district_list = $this->get_regions_log(3, $region_level[2]);

            $this->smarty->assign('region_level', $region_level);
            $this->smarty->assign('country_list', $country_list);
            $this->smarty->assign('province_list', $province_list);
            $this->smarty->assign('city_list', $city_list);
            $this->smarty->assign('district_list', $district_list);

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['edit_suppliers']);
            $this->smarty->assign('action_link', array('href' => 'suppliers.php?act=list', 'text' => $GLOBALS['_LANG']['01_suppliers_list']));
            $this->smarty->assign('form_action', 'update');

            $this->smarty->assign('suppliers', $suppliers);

            return $this->smarty->display('suppliers_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 提交添加、编辑供货商
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'update') {
            /* 检查权限 */
            admin_priv('suppliers_list');

            /* 获取需要使用的语言包 */
            $lang = lang('admin/suppliers.update_array');

            $suppliers_id = !empty($_POST['id']) ? intval($_POST['id']) : 0;
            $review_status = empty($_POST['review_status']) ? 1 : intval($_POST['review_status']);
            $review_content = empty($_POST['review_content']) ? '' : trim($_POST['review_content']);

            $ec_salt = rand(1, 9999);
            $password = !empty($_POST['password']) ? md5(md5(trim($_POST['password'])) . $ec_salt) : '';
            $user_name = !empty($_POST['user_name']) ? stripslashes(trim($_POST['user_name'])) : '';

            $suppliers_percent = !empty($_POST['suppliers_percent']) ? trim($_POST['suppliers_percent']) : 0;
            $suppliers_percent = floatval($suppliers_percent);

            $sql = " SELECT user_id FROM " . $this->dsc->table('suppliers') . " WHERE suppliers_id = '$suppliers_id' ";
            $user_id = $this->db->getOne($sql);

            /* 判断名称是否重复 */
            $sql = "SELECT suppliers_id FROM " . $this->dsc->table('suppliers') . "  WHERE suppliers_name = '" . $user_name . "' AND suppliers_id <> '$suppliers_id'";
            if ($this->db->getOne($sql)) {
                return sys_msg($GLOBALS['_LANG']['suppliers_name_exist']);
            }

            $admin_user = [];

            if ($review_status == 3) {
                //审核通后方可编辑登录名和密码
                if ($user_name == $lang['buyer'] || $user_name == $lang['seller'] || empty($user_name)) {
                    /* 提示信息 */
                    $link[] = array('text' => $lang['unavailable'], 'href' => "suppliers.php?act=list");
                    return sys_msg($lang['add_fail'], 0, $link);
                }

                /* 判断管理员是否已经存在 */
                if (!empty($user_name)) {
                    $is_only = $exc->is_only('user_name', $user_name, '', " suppliers_id <> '$suppliers_id' ");
                    if (!$is_only) {
                        return sys_msg(sprintf($lang['user_exist'], $user_name), 1);
                    }
                }

                $sql = " SELECT user_name FROM " . $this->dsc->table('admin_user') . " WHERE suppliers_id = '$suppliers_id' AND parent_id = '0' ";
                $old_name = $this->db->getOne($sql);
                if ($user_name != $old_name) {
                    $admin_user['user_name'] = $user_name;
                } else {
                    $admin_user['user_name'] = $old_name;
                }

                if ($password) {
                    $admin_user['password'] = $password;
                    $admin_user['ec_salt'] = $ec_salt;
                    $admin_user['login_status'] = '';
                }
            }

            /* 提交值 */
            $suppliers = array(
                'suppliers_name' => trim($_POST['suppliers_name']),
                'suppliers_desc' => trim($_POST['suppliers_desc']),
                'real_name' => trim($_POST['real_name']),
                'self_num' => trim($_POST['self_num']),
                'company_name' => trim($_POST['company_name']),
                'company_address' => trim($_POST['company_address']),
                'mobile_phone' => trim($_POST['mobile_phone']),
                'kf_qq' => empty($_POST['kf_qq']) ? '' : trim($_POST['kf_qq']),
                'email' => empty($_POST['email']) ? '' : trim($_POST['email']),
                'region_id' => empty($_POST['district']) ? '' : intval($_POST['district']),
                'suppliers_percent' => $suppliers_percent
            );

            if ($review_status) {
                $suppliers['review_status'] = intval($review_status);
                $suppliers['review_content'] = $review_content;
            }

            if ($review_status == 3 && $suppliers_id) {//插入admin_user表数据

                $sql = " SELECT user_id, user_name, password FROM " . $this->dsc->table('admin_user') . " WHERE ru_id = '$user_id' AND suppliers_id = '$suppliers_id' ";
                $row = $this->db->getRow($sql);

                if ($row['user_id']) {
                    if (($admin_user['user_name'] != $row['user_name']) || (isset($admin_user['password']) && $admin_user['password'] != $row['password'])) {

                        $admin_user['email'] = $suppliers['email'];
                        $this->db->autoExecute($this->dsc->table('admin_user'), $admin_user, 'UPDATE', "user_id = '" . $row['user_id'] . "'");
                    }
                } else {
                    $admin_user['ru_id'] = $user_id;
                    $admin_user['suppliers_id'] = $suppliers_id;
                    $admin_user['add_time'] = gmtime();
                    $admin_user['nav_list'] = $row['nav_list'];
                    $admin_user['email'] = $suppliers['email'];

                    $this->db->autoExecute($this->dsc->table('admin_user'), $admin_user, 'INSERT');
                }

                if ($GLOBALS['_CFG']['sms_seller_signin'] == '1') {
                    if (!empty($suppliers['mobile_phone']) && ($admin_user['user_name'] != '' || $admin_user['password'] != '')) {
                        //短信接口参数
                        $smsParams = [
                            'seller_name' => $suppliers['suppliers_name'] ? $suppliers['suppliers_name'] : '',
                            'sellername' => $suppliers['suppliers_name'] ? $suppliers['suppliers_name'] : '',
                            'login_name' => $user_name ? htmlspecialchars($user_name) : '',
                            'loginname' => $user_name ? htmlspecialchars($user_name) : '',
                            'password' => isset($_POST['password']) ? htmlspecialchars($_POST['password']) : '',
                            'admin_name' => $admin_user['user_name'] ? htmlspecialchars($admin_user['user_name']) : '',
                            'adminname' => $admin_user['user_name'] ? htmlspecialchars($admin_user['user_name']) : '',
                            'edit_time' => local_date('Y-m-d H:i:s', gmtime()),
                            'edittime' => local_date('Y-m-d H:i:s', gmtime()),
                            'mobile_phone' => $suppliers['mobile_phone'] ? $suppliers['mobile_phone'] : '',
                            'mobilephone' => $suppliers['mobile_phone'] ? $suppliers['mobile_phone'] : ''
                        ];

                        $this->commonRepository->smsSend($suppliers['mobile_phone'], $smsParams, 'sms_seller_signin', false);
                    }
                }

                /* 发送邮件 */
                $template = get_mail_template('seller_signin');

                if ($adminru['ru_id'] == 0 && $template['template_content'] != '') {
                    if ($suppliers['email'] && ($admin_user['user_name'] != '' || $admin_user['password'] != '')) {

                        $template['template_subject'] = str_replace('商家', '供应商', $template['template_subject']);
                        $template['template_content'] = str_replace('商家', '供应商', $template['template_content']);

                        $this->smarty->assign('shop_name', $suppliers['suppliers_name']);
                        $this->smarty->assign('seller_name', $user_name);
                        $this->smarty->assign('seller_psw', trim($_POST['password']));
                        $this->smarty->assign('site_name', $GLOBALS['_CFG']['shop_name']);
                        $this->smarty->assign('send_date', local_date($GLOBALS['_CFG']['time_format'], gmtime()));
                        $content = $this->smarty->fetch('str:' . $template['template_content']);

                        $this->commonRepository->sendEmail($suppliers['suppliers_name'], $suppliers['email'], $template['template_subject'], $content, $template['is_html']);
                    }
                }
            }

            /* 保存供货商信息 */
            $this->db->autoExecute($this->dsc->table('suppliers'), $suppliers, 'UPDATE', "suppliers_id = '$suppliers_id'");

            /* 记日志 */
            admin_log($suppliers['suppliers_name'], 'edit', 'suppliers');

            /* 清除缓存 */
            clear_cache_files();

            /* 提示信息 */
            $links[] = array('href' => 'suppliers.php?act=list', 'text' => $GLOBALS['_LANG']['back_suppliers_list']);
            return sys_msg($GLOBALS['_LANG']['edit_suppliers_ok'], 0, $links);
        }

        /*------------------------------------------------------ */
        //-- 为供应商分配权限
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'allot') {
            /* 检查权限 */
            admin_priv('suppliers_list');

            $this->dscRepository->helpersLang('priv_action', 'admin');

            $suppliers_id = !empty($_GET['id']) ? intval($_GET['id']) : 0;
            $user_id = $this->db->getOne(" SELECT user_id FROM " . $this->dsc->table('suppliers') . " WHERE suppliers_id = '$suppliers_id' ");
            /* 获得该管理员的权限 */
            $priv_str = $this->db->getOne("SELECT action_list FROM " . $this->dsc->table('admin_user') . " WHERE ru_id = '$user_id' AND suppliers_id = '$suppliers_id'");

            /* 如果被编辑的管理员拥有了all这个权限，将不能编辑 */
            if ($priv_str == 'all') {
                $link[] = array('text' => $GLOBALS['_LANG']['back_suppliers_list'], 'href' => 'privilege.php?act=list');
                return sys_msg($GLOBALS['_LANG']['edit_admininfo_cannot'], 0, $link);
            }

            //子管理员 start
            $admin_id = get_admin_id();
            $action_list = get_table_date('admin_user', "user_id='$admin_id'", array('action_list'), 2);
            $action_array = explode(',', $action_list);
            //子管理员 end

            /* 获取权限的分组数据 */
            $sql_query = "SELECT action_id, parent_id, action_code,relevance FROM " . $this->dsc->table('admin_action') .
                " WHERE parent_id = 0 AND seller_show = 2";
            $res = $this->db->getAll($sql_query);

            $priv_arr = [];
            if ($res) {
                foreach ($res as $key => $rows) {
                    //卖场 start
                    if (!$GLOBALS['_CFG']['region_store_enabled'] && $rows['action_code'] == 'region_store') {
                        continue;
                    }
                    //卖场 end
                    $priv_arr[$rows['action_id']] = $rows;
                }
            }

            if ($priv_arr) {
                /* 按权限组查询底级的权限名称 */
                $sql = "SELECT action_id, parent_id, action_code,relevance FROM " . $this->dsc->table('admin_action') .
                    " WHERE parent_id " . db_create_in(array_keys($priv_arr)) . " AND seller_show = 2 ";
                $result = $this->db->getAll($sql);

                if ($result) {
                    foreach ($result as $key => $priv) {
                        //子管理员 start
                        if (!empty($action_list) && $action_list != 'all' && !in_array($priv['action_code'], $action_array)) {
                            continue;
                        }
                        //子管理员 end
                        $priv_arr[$priv["parent_id"]]["priv"][$priv["action_code"]] = $priv;
                    }
                }

                // 将同一组的权限使用 "," 连接起来，供JS全选 ecmoban模板堂 --zhuo
                foreach ($priv_arr as $action_id => $action_group) {
                    if (isset($action_group['priv']) && $action_group['priv']) {
                        $priv = @array_keys($action_group['priv']);
                        $priv_arr[$action_id]['priv_list'] = join(',', $priv);

                        if (!empty($action_group['priv'])) {
                            foreach ($action_group['priv'] as $key => $val) {
                                $priv_arr[$action_id]['priv'][$key]['cando'] = (strpos($priv_str, $val['action_code']) !== false || $priv_str == 'all') ? 1 : 0;
                            }
                        }
                    }
                }
            }

            /* 赋值 */
            $this->smarty->assign('lang', $GLOBALS['_LANG']);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['allot_priv'] . ' [ ' . $_GET['user'] . ' ] ');
            $this->smarty->assign('action_link', array('href' => 'privilege.php?act=list', 'text' => $GLOBALS['_LANG']['01_admin_list']));
            $this->smarty->assign('priv_arr', $priv_arr);
            $this->smarty->assign('is_supplier', 1);
            $this->smarty->assign('form_act', 'update_allot');
            $this->smarty->assign('user_id', $user_id);
            $this->smarty->assign('suppliers_id', $suppliers_id);

            /* 显示页面 */
            return $this->smarty->display('privilege_allot.dwt');
        }

        /*------------------------------------------------------ */
        //-- 更新供应商的权限
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'update_allot') {
            /* 检查权限 */
            admin_priv('suppliers_list');

            $suppliers_id = !empty($_POST['suppliers_id']) ? intval($_POST['suppliers_id']) : 0;
            $ru_id = !empty($_POST['id']) ? intval($_POST['id']) : 0;
            /* 取得当前管理员用户名 */
            $admin_name = $this->db->getOne("SELECT user_name FROM " . $this->dsc->table('admin_user') . " WHERE ru_id = '$ru_id' AND suppliers_id = '$suppliers_id' ");
            /* 更新管理员的权限 */
            $act_list = @join(",", $_POST['action_code']);
            $sql = "UPDATE " . $this->dsc->table('admin_user') . " SET action_list = '$act_list', role_id = '' " .
                "WHERE ru_id = '$ru_id' AND suppliers_id = '$suppliers_id' ";

            $this->db->query($sql);

            /* 记录管理员操作 */
            admin_log(addslashes($admin_name), 'edit', 'privilege');

            /* 提示信息 */
            $link[] = array('text' => $GLOBALS['_LANG']['back_suppliers_list'], 'href' => 'suppliers.php?act=list&uselastfilter');
            return sys_msg($GLOBALS['_LANG']['edit'] . "&nbsp;" . $admin_name . "&nbsp;" . $GLOBALS['_LANG']['action_succeed'], 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 修改结算比例
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_suppliers_percent') {
            /* 检查权限 */
            $check_auth = check_authz_json('suppliers_list');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $val = json_str_iconv(floatval($_POST['val']));

            if ($exc_sup->edit("suppliers_percent = '$val'", $id)) {
                clear_cache_files();
                return make_json_result(stripslashes($val));
            }
        }
    }

    /**
     *  获取供应商列表信息
     *
     * @access  public
     * @param
     *
     * @return void
     */
    private function suppliers_list()
    {
        $result = get_filter();
        if ($result === false) {
            $aiax = isset($_GET['is_ajax']) ? $_GET['is_ajax'] : 0;

            /* 过滤信息 */
            $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'suppliers_id' : trim($_REQUEST['sort_by']);
            $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);
            $filter['review_status'] = empty($_REQUEST['review_status']) ? 0 : intval($_REQUEST['review_status']); //审核状态

            $where = 'WHERE 1 ';
            if ($filter['review_status'] > 0) {
                $where .= 'AND  review_status = ' . $filter['review_status'];
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

            /* 记录总数 */
            $sql = "SELECT COUNT(*) FROM " . $GLOBALS['dsc']->table('suppliers') . $where;
            $filter['record_count'] = $GLOBALS['db']->getOne($sql);
            $filter['page_count'] = $filter['record_count'] > 0 ? ceil($filter['record_count'] / $filter['page_size']) : 1;

            /* 查询 */
            $sql = "SELECT suppliers_id, suppliers_name, suppliers_desc, is_check, real_name, company_name, add_time, review_status, region_id, user_id,suppliers_percent
                FROM " . $GLOBALS['dsc']->table("suppliers") . $where .
                " ORDER BY " . $filter['sort_by'] . " " . $filter['sort_order'] . "
                LIMIT " . ($filter['page'] - 1) * $filter['page_size'] . ", " . $filter['page_size'] . " ";

            set_filter($filter, $sql);
        } else {
            $sql = $result['sql'];
            $filter = $result['filter'];
        }

        $res = $GLOBALS['db']->getAll($sql);
        if ($res) {
            foreach ($res as $k => $v) {
                $res[$k]['shop_name'] = $this->merchantCommonService->getShopName($v['user_id'], 1);
                $res[$k]['add_time'] = local_date($GLOBALS['_CFG']['time_format'], $v['add_time']);
                $res[$k]['user_name'] = $GLOBALS['db']->getOne(" SELECT user_name FROM " . $GLOBALS['dsc']->table('admin_user') . " WHERE parent_id = '0' AND suppliers_id = '" . $v['suppliers_id'] . "' LIMIT 1 ");
                $res[$k]['region_name'] = $GLOBALS['db']->getOne(" SELECT region_name FROM " . $GLOBALS['dsc']->table('region') . " WHERE region_id = '" . $v['region_id'] . "' ");

                if (isset($GLOBALS['_CFG']['show_mobile']) && $GLOBALS['_CFG']['show_mobile'] == 0) {
                    $res[$k]['real_name'] = $this->dscRepository->stringToStar($v['real_name']);
                }
            }
        }

        $arr = array('result' => $res, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);

        return $arr;
    }

    /**
     * 获得指定国家的所有省份
     *
     * @access      public
     * @param       int     country    国家的编号
     * @return      array
     */
    private function get_regions_log($type = 0, $parent = 0)
    {
        $sql = 'SELECT region_id, region_name FROM ' . $GLOBALS['dsc']->table('region') .
            " WHERE region_type = '$type' AND parent_id = '$parent'";

        return $GLOBALS['db']->GetAll($sql);
    }
}
