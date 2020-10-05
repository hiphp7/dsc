<?php

namespace App\Modules\Admin\Controllers;

/**
 * 数据迁移管理
 */
class TransferManageController extends InitController
{
    public function index()
    {
        $s_db_host = '';
        if (file_exists(storage_path('/data/source_config.php'))) {
            require(storage_path('/data/source_config.php'));
        }

        $_REQUEST['act'] = trim($_REQUEST['act']);
        if (empty($_REQUEST['act'])) {
            $_REQUEST['act'] = 'config';
        }
        /*------------------------------------------------------ */
        //-- 源站点信息显示
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'config') {
            $s_db_port = isset($s_db_port) ? $s_db_port : '';
            $s_db_user = isset($s_db_user) ? $s_db_user : '';
            $s_db_pass = isset($s_db_pass) ? $s_db_pass : '';
            $s_db_name = isset($s_db_name) ? $s_db_name : '';
            $s_db_prefix = isset($s_db_prefix) ? $s_db_prefix : '';
            $s_db_retain = isset($s_db_retain) ? $s_db_retain : '';
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['06_transfer_config']);
            $this->smarty->assign('db_host', $s_db_host);
            $this->smarty->assign('db_port', $s_db_port);
            $this->smarty->assign('db_user', $s_db_user);
            $this->smarty->assign('db_pass', $s_db_pass);
            $this->smarty->assign('db_name', $s_db_name);
            $this->smarty->assign('db_prefix', $s_db_prefix);
            $this->smarty->assign('db_retain', $s_db_retain);
            return $this->smarty->display('transfer_config.dwt');
        }
        /*------------------------------------------------------ */
        //-- 源站点信息设置
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'setup') {
            $db_host = isset($_POST['s_db_host']) ? trim($_POST['s_db_host']) : '';
            $db_port = isset($_POST['s_db_port']) ? trim($_POST['s_db_port']) : '';
            $db_user = isset($_POST['s_db_user']) ? trim($_POST['s_db_user']) : '';
            $db_pass = isset($_POST['s_db_pass']) ? trim($_POST['s_db_pass']) : '';
            $db_name = isset($_POST['s_db_name']) ? trim($_POST['s_db_name']) : '';
            $db_prefix = isset($_POST['s_db_prefix']) ? trim($_POST['s_db_prefix']) : '';
            $db_retain = isset($_POST['s_db_retain']) ? trim($_POST['s_db_retain']) : 0;

            $result = $this->create_source_config_file($db_host, $db_port, $db_user, $db_pass, $db_name, $db_prefix, $db_retain);
            if ($result === false) {
                return sys_msg(lang('admin/transfer_manage.write_fail'));
            } else {
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['06_transfer_config']);
                $this->smarty->assign('db_host', $db_host);
                $this->smarty->assign('db_port', $db_port);
                $this->smarty->assign('db_user', $db_user);
                $this->smarty->assign('db_pass', $db_pass);
                $this->smarty->assign('db_name', $db_name);
                $this->smarty->assign('db_prefix', $db_prefix);
                $this->smarty->assign('db_retain', $db_retain);
            }
            return $this->smarty->display('transfer_config.dwt');
        }
        /*------------------------------------------------------ */
        //-- 迁移数据设置
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'choose') {
            if (!file_exists(storage_path('/data/source_config.php')) || empty($s_db_host)) {
                $link = [];
                $link[1] = ['href' => 'transfer_manage.php', 'text' => $GLOBALS['_LANG']['06_transfer_config']];
                return sys_msg($GLOBALS['_LANG']['set_up_config'], 0, $link, false);
            }

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['07_transfer_choose']);
            /* 显示模板 */

            return $this->smarty->display('transfer_choose.dwt');
        }

        /*------------------------------------------------------ */
        //-- 验证数据库连接
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'sql_basic') {

            $check_auth = check_authz_json('article_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $result = ['message' => '', 'result' => '', 'error' => 0];

            $_REQUEST['basic'] = isset($_REQUEST['basic']) ? json_str_iconv($_REQUEST['basic']) : '';
            $basic = dsc_decode($_REQUEST['basic']);

            if (empty($_REQUEST['basic'])) {
                $result['error'] = 1;
                return response()->json($result);
            }

            $db_host = trim($basic->db_host);
            $db_port = trim($basic->db_port);
            $db_user = trim($basic->db_user);
            $db_pass = trim($basic->db_pass);
            $db_name = trim($basic->db_name);

            $databases = $this->get_db_list($db_host, $db_port, $db_user, $db_pass, $db_name);
            if ($databases != 0) {
                if ($databases == 1) {
                    $result['message'] = $GLOBALS['_LANG']['databases_message_one'];
                } else {
                    $result['message'] = $GLOBALS['_LANG']['databases_message_two'];
                }
            } else {
                $result['message'] = $GLOBALS['_LANG']['databases_message_three'];
            }

            return response()->json($result);
        }

        /*------------------------------------------------------ */
        //-- 迁移数据确认
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'is_ajax') {
            //连接 数据库
            $sdb = new Mysql($s_db_host, $s_db_user, $s_db_pass, $s_db_name);
            $source = new ECS($s_db_name, $s_db_prefix);
            $s_db_host = $s_db_user = $s_db_pass = $s_db_name = null;


            $data_cat = intval($_GET['data_cat']);

            switch ($data_cat) {
                case 1:
                    $table = 'category';
                    $table_title = $GLOBALS['_LANG']['category'];
                    break;
                case 2:
                    $table = 'goods';
                    $table_title = $GLOBALS['_LANG']['goods'];
                    break;
                case 3:
                    $table = 'users';
                    $table_title = $GLOBALS['_LANG']['08_members'];
                    break;
                case 4:
                    $table = 'article_cat';
                    $table_title = $GLOBALS['_LANG']['02_articlecat_list'];
                    break;
                case 5:
                    $table = 'article';
                    $table_title = $GLOBALS['_LANG']['03_article_list'];
                    break;
                case 6:
                    $table = 'merchants_shop_information,merchants_steps_fields';
                    $table_title = $GLOBALS['_LANG']['seller_apply_info'];
                    break;
                case 7:
                    $table = 'order_info';
                    $table_title = $GLOBALS['_LANG']['order_list_info'];
                    break;
                case 8:
                    $table = 'order_goods';
                    $table_title = $GLOBALS['_LANG']['order_goods_list'];
                    break;
                case 9:
                    $table = 'goods_type';
                    $table_title = $GLOBALS['_LANG']['08_goods_type'];
                    break;
                case 10:
                    $table = 'attribute';
                    $table_title = $GLOBALS['_LANG']['attr_list'];
                    break;
                default:
                    return response()->json($GLOBALS['_LANG']['please_select']);
            }

            /* 设置最长执行时间为5分钟 */
            @set_time_limit(300);

            if (isset($_GET['start'])) {
                //清除目标站表
                if ($s_db_retain == 0) {
                    $arr = explode(",", $table);
                    foreach ($arr as $vv) {
                        $this->db->query("TRUNCATE TABLE " . $this->dsc->table($vv));
                    }
                }

                $page_size = 10; // 默认50张/页
                $silent = empty($_GET['silent']) ? 0 : 1;
                if ($data_cat == 6) {
                    //源数据表--商家信息表
                    $table = 'supplier';
                }
                $count = $sdb->getOne("SELECT count(*) FROM " . $source->table($table));
                $title = $table_title . $GLOBALS['_LANG']['manage_date_import'];

                $result = ['error' => 0, 'message' => '', 'content' => '', 'done' => 1, 'title' => $title, 'page_size' => $page_size,
                    'page' => 1, 'total' => 1, 'silent' => $silent, 'data_cat' => $data_cat,
                    'row' => ['new_page' => sprintf($GLOBALS['_LANG']['page_format'], 1),
                        'new_total' => sprintf($GLOBALS['_LANG']['total_format'], ceil($count / $page_size)),
                        'new_time' => $GLOBALS['_LANG']['wait'],
                        'cur_id' => 'time_1']];

                return response()->json($result);
            } else {
                $result = ['error' => 0, 'message' => '', 'content' => '', 'done' => 2, 'data_cat' => $data_cat];
                $result['page_size'] = empty($_GET['page_size']) ? 10 : intval($_GET['page_size']);
                $result['page'] = isset($_GET['page']) ? intval($_GET['page']) : 1;
                $result['total'] = isset($_GET['total']) ? intval($_GET['total']) : 1;
                $result['silent'] = empty($_GET['silent']) ? 0 : 1;

                if ($result['silent']) {
                    $err_msg = [];
                }

                /*------------------------------------------------------ */
                //-- 迁移数据
                /*------------------------------------------------------ */
                if ($data_cat == 6) {
                    //源数据表--商家信息表
                    $table = 'supplier';
                }
                $count = $sdb->getOne("SELECT count(*) FROM " . $source->table($table));
                /* 页数在许可范围内 */
                if ($result['page'] <= ceil($count / $result['page_size'])) {
                    $start_time = gmtime(); //开始执行时间

                    /* 开始处理 */
                    if (file_exists('../data/config.php')) {
                        include('../data/config.php');
                    } else {
                        include('../includes/config.php');
                    }
                    $db = new Mysql($db_host, $db_user, $db_pass, $db_name);

                    if ($data_cat == 6) {
                        //批量导入商家入住信息
                        $this->supplier_batch($table, $result['page'], $result['page_size'], $result['silent'], $s_db_retain);
                    } else {
                        $this->process_batch($table, $result['page'], $result['page_size'], $result['silent'], $s_db_retain);
                    }

                    $end_time = gmtime();
                    $result['row']['pre_id'] = 'time_' . $result['total'];
                    $result['row']['pre_time'] = ($end_time > $start_time) ? $end_time - $start_time : 1;
                    $result['row']['pre_time'] = sprintf($GLOBALS['_LANG']['time_format'], $result['row']['pre_time']);
                    $result['row']['cur_id'] = 'time_' . ($result['total'] + 1);
                    $result['page']++; // 新行
                    $result['row']['new_page'] = sprintf($GLOBALS['_LANG']['page_format'], $result['page']);
                    $result['row']['new_total'] = sprintf($GLOBALS['_LANG']['total_format'], ceil($count / $result['page_size']));
                    $result['row']['new_time'] = $GLOBALS['_LANG']['wait'];
                    $result['total']++;
                } else {
                    --$result['total'];
                    --$result['page'];
                    $result['done'] = 0;
                    $result['message'] = $GLOBALS['_LANG']['import_success'];
                    /* 清除缓存 */
                    clear_cache_files();
                    return response()->json($result);
                }

                if ($result['silent'] && $err_msg) {
                    $result['content'] = implode('<br />', $err_msg);
                }

                return response()->json($result);
            }
        }
    }

    /**
     * 创建配置文件
     *
     * @access  public
     * @param string $db_host 主机
     * @param string $db_port 端口号
     * @param string $db_user 用户名
     * @param string $db_pass 密码
     * @param string $db_name 数据库名
     * @param string $prefix 数据表前缀
     * @return  boolean     成功返回true，失败返回false
     */
    private function create_source_config_file($db_host, $db_port, $db_user, $db_pass, $db_name, $db_prefix, $db_retain)
    {

        //$db_host = $db_host . ':' . $db_port;

        $content = '<?' . "php\n";
        $content .= "// database host\n";
        $content .= "\$s_db_host   = \"$db_host\";\n\n";
        $content .= "// database port\n";
        $content .= "\$s_db_port   = \"$db_port\";\n\n";
        $content .= "// database name\n";
        $content .= "\$s_db_name   = \"$db_name\";\n\n";
        $content .= "// database username\n";
        $content .= "\$s_db_user   = \"$db_user\";\n\n";
        $content .= "// database password\n";
        $content .= "\$s_db_pass   = \"$db_pass\";\n\n";
        $content .= "// table prefix\n";
        $content .= "\$s_db_prefix    = \"$db_prefix\";\n\n";
        $content .= "// table data_retain\n";
        $content .= "\$s_db_retain    = \"$db_retain\";\n\n";
        $content .= '?>';

        $fp = @fopen(storage_public(DATA_DIR . '/source_config.php'), 'wb+');
        if (!$fp) {
            $this->err->add($GLOBALS['_LANG']['open_config_file_failed']);
            return false;
        }
        if (!@fwrite($fp, trim($content))) {
            $this->err->add($GLOBALS['_LANG']['write_config_file_failed']);
            return false;
        }
        @fclose($fp);

        return true;
    }

    /**
     *  批量导入商家信息
     * @param type $table
     * @param type $page
     * @param type $page_size
     * @param type $silent 遇到错误是否继续执行
     * @param type $s_db_retain 是否保留数据
     */
    private function supplier_batch($table, $page = 1, $page_size = 10, $silent = true, $s_db_retain = 0)
    {
        $sql = "SELECT * FROM " . $GLOBALS['source']->table($table);
        $res = $GLOBALS['sdb']->SelectLimit($sql, $page_size, ($page - 1) * $page_size);

        foreach ($res as $row) {
            if ($s_db_retain == 0) {
                $sql = "SELECT COUNT(*) FROM " . $this->dsc->table('admin_user') . " WHERE ru_id = '$row[user_id]'";
                if ($this->db->getOne($sql) > 0) {
                    $sql = "DELETE FROM " . $this->dsc->table('admin_user') . " WHERE ru_id = '$row[user_id]'";
                    $this->db->query($sql);
                }
                //ecs_user
                $sql = "SELECT COUNT(*) FROM " . $this->dsc->table('users') . " WHERE user_id = '$row[user_id]'";
                if ($this->db->getOne($sql) == 0) {
                    continue;
                }
                //supplier_admin_user
                $sql = "SELECT * FROM " . $GLOBALS['source']->table('supplier_admin_user') . " WHERE uid = '$row[user_id]'";
                $supplier_user = $GLOBALS['sdb']->getRow($sql);

                //admin_user
                $supplier_user['action_list'] = 'goods_manage,remove_back,cat_manage,cat_drop,attr_manage,comment_priv,goods_type,goods_auto,virualcard,goods_export,goods_batch,merchants_brand,warehouse_manage,order_os_edit,order_ps_edit,order_ss_edit,order_edit,order_view,order_view_finished,repay_manage,booking,sale_order_stats,delivery_view,topic_manage,snatch_manage,ad_manage,gift_manage,bonus_manage,auction,group_by,favourable,whole_sale,package_manage,exchange_goods,merchants_commission';
                $supplier_user['nav_list'] = $GLOBALS['_LANG']['nav_list'];
                $sql = "INSERT INTO " . $this->dsc->table('admin_user') . "(user_name, ru_id, email, password, ec_salt, add_time, last_login, last_ip," .
                    "action_list, nav_list, lang_type, agency_id, suppliers_id, todolist, role_id) VALUES " .
                    "('$supplier_user[user_name]', '$supplier_user[uid]', '$supplier_user[email]', '$supplier_user[password]', '$supplier_user[ec_salt]'," .
                    "'$supplier_user[add_time]', '$supplier_user[last_login]', '$supplier_user[last_ip]', '$supplier_user[action_list]', '$supplier_user[nav_list]'," .
                    "'$supplier_user[lang_type]', '$supplier_user[agency_id]', '$supplier_user[suppliers_id]', '$supplier_user[todolist]', '$supplier_user[role_id]')";
                $this->db->query($sql);
            } else {
                continue;
            }
            //merchants_shop_information
            $sql = "INSERT INTO " . $this->dsc->table('merchants_shop_information') . "(shop_id, user_id, rz_shopName, steps_audit, merchants_audit) " .
                "VALUES ('$row[supplier_id]', '$row[user_id]', '$row[supplier_name]', 1, '$row[status]')";
            $this->db->query($sql);

            //merchants_steps_fields
            $sql = "INSERT INTO " . $this->dsc->table('merchants_steps_fields') . "(user_id, agreement, contactName, contactPhone, contactEmail," .
                "companyName, organization_code, business_license_id, busines_scope, taxpayer_id, account_number, bank_name, linked_bank_number," .
                "linked_bank_fileImg, tax_fileImg) VALUES ('$row[user_id]', 1, '$row[contacts_name]', '$row[contacts_phone]', '$row[email]','$row[company_name]'," .
                "'$row[organization_code]', '$row[business_licence_number]', '$row[business_sphere]', '$row[taxpayer_id]', '$row[settlement_bank_account_number]'," .
                "'$row[settlement_bank_name]', '$row[settlement_bank_code]', '$row[bank_licence_electronic]', '$row[tax_registration_certificate_electronic]')";
            $result = $this->db->query($sql);

            if (!$result) {
                $msg = sprintf($GLOBALS['_LANG']['error_transfer'], $table) . "\n";
                if ($silent) {
                    $GLOBALS['err_msg'][] = $msg;
                    continue;
                } else {
                    return make_json_error($msg);
                }
            }
        }
    }

    /**
     * 批量导入处理函数
     * @param type $page
     * @param type $page_size
     * @param type $silent
     */
    private function process_batch($table, $page = 1, $page_size = 10, $silent = true, $s_db_retain = 0)
    {
        $sql = "SELECT * FROM " . $GLOBALS['source']->table($table);
        $res = $GLOBALS['sdb']->SelectLimit($sql, $page_size, ($page - 1) * $page_size);

        /* 字段列表 */
        $field_arr = [];
        $upload_table = 'upload_' . $table;

        if ($s_db_retain == 1) {
            array_shift($GLOBALS['_LANG'][$upload_table]);
        }
        $field_list = array_keys($GLOBALS['_LANG'][$upload_table]);

        foreach ($res as $row) {
            /* 循环插入商品数据 */
            foreach ($field_list as $field) {
                $value = $row[$field];
                /* 转商家Id */
                if ($table == 'goods' && $field == 'user_id') {
                    $table_field = get_table_file_name($this->dsc->table('suppliers'), 'user_id');
                    if ($table_field['bool']) {
                        $sql = "SELECT user_id FROM " . $GLOBALS['source']->table('suppliers') . " WHERE supplier_id = '$row[supplier_id]'";
                        $value = $GLOBALS['sdb']->getOne($sql);
                    }
                } elseif ($table == 'order_info' && $field == 'main_order_id') {
                    $value = $row['parent_order_id'];
                } elseif ($table == 'order_goods' && $field == 'ru_id') {
                    $sql = "SELECT supplier_id FROM " . $GLOBALS['source']->table('order_info') . " WHERE order_id = '$row[order_id]'";
                    $supplier_id = $GLOBALS['sdb']->getOne($sql);

                    if ($supplier_id) {
                        $table_field = get_table_file_name($this->dsc->table('suppliers'), 'user_id');
                        if ($table_field['bool']) {
                            $sql = "SELECT user_id FROM " . $GLOBALS['source']->table('suppliers') . " WHERE supplier_id = '$supplier_id'";
                            $value = $GLOBALS['sdb']->getOne($sql);
                        }
                    }
                }

                if (!empty($value)) {
                    $field_arr[$field] = $value;
                } else {
                    unset($field_arr[$field]);
                }
            }

            $result = $this->db->autoExecute($this->dsc->table($table), $field_arr, 'INSERT');

            if (!$result) {
                $msg = sprintf($GLOBALS['_LANG']['error_transfer'], $table, $field) . "\n";
                if ($silent) {
                    $GLOBALS['err_msg'][] = $msg;
                    continue;
                } else {
                    return make_json_error($msg);
                }
            }
        }
    }

    /**
     * 把host、port重组成指定的串
     *
     * @access  public
     * @param string $db_host 主机
     * @param string $db_port 端口号
     * @return  string      host、port重组后的串，形如host:port
     */
    private function construct_db_host($db_host, $db_port)
    {
        return $db_host . ':' . $db_port;
    }

    /**
     * 获得数据库列表
     *
     * @access  public
     * @param string $db_host 主机
     * @param string $db_port 端口号
     * @param string $db_user 用户名
     * @param string $db_pass 密码
     * @return  mixed       成功返回数据库列表组成的数组，失败返回false
     */
    private function get_db_list($db_host, $db_port, $db_user, $db_pass, $db_name)
    {
        $databases = [];
        $filter_dbs = ['information_schema', 'mysql'];
        $db_host = $this->construct_db_host($db_host, $db_port);
        $link_id = @mysql_connect($db_host, $db_user, $db_pass);

        if ($link_id === false) {
            return 1; //数据库连接错误
        } else {

            /* 选择数据库 */
            if ($db_name) {
                if (mysql_select_db($db_name, $link_id) === false) {
                    return 2; //数据库不存在
                } else {
                    return 0;
                }
            }
        }
    }
}
