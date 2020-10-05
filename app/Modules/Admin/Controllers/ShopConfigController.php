<?php

namespace App\Modules\Admin\Controllers;

use App\Libraries\Http;
use App\Models\Region;
use App\Models\ShopConfig;
use App\Repositories\Common\CommonRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;

/**
 * 管理中心商店设置
 */
class ShopConfigController extends InitController
{
    protected $dscRepository;
    protected $commonRepository;

    public function __construct(
        DscRepository $dscRepository,
        CommonRepository $commonRepository
    )
    {
        $this->dscRepository = $dscRepository;
        $this->commonRepository = $commonRepository;
    }

    public function index()
    {
        /*------------------------------------------------------ */
        //-- 列表编辑 ?act=list_edit
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list_edit') {
            /* 检查权限 */
            admin_priv('shop_config');

            /* 可选语言 */
            $dir = opendir(resource_path('lang'));
            $lang_list = [];
            while (@$file = readdir($dir)) {
                if ($file != '.' && $file != '..' && $file != '.svn' && $file != '_svn' && is_dir(resource_path('lang/' . $file))) {
                    $lang_list[] = $file;
                }
            }
            @closedir($dir);

            $this->smarty->assign('lang_list', $lang_list);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['01_shop_config']);

            $group_list = $this->get_settings(null, ['5']);
            $this->smarty->assign('group_list', $group_list);
            $this->smarty->assign('countries', get_regions());

            if (strpos(strtolower(request()->server('SERVER_SOFTWARE')), 'iis') !== false) {
                $rewrite_confirm = $GLOBALS['_LANG']['rewrite_confirm_iis'];
            } else {
                $rewrite_confirm = $GLOBALS['_LANG']['rewrite_confirm_apache'];
            }
            $this->smarty->assign('rewrite_confirm', $rewrite_confirm);

            if ($GLOBALS['_CFG']['shop_country'] > 0) {
                $this->smarty->assign('provinces', get_regions(1, $GLOBALS['_CFG']['shop_country']));
                if ($GLOBALS['_CFG']['shop_province']) {
                    $this->smarty->assign('cities', get_regions(2, $GLOBALS['_CFG']['shop_province']));
                }
                if ($GLOBALS['_CFG']['shop_city']) {
                    $this->smarty->assign('districts', get_regions(3, $GLOBALS['_CFG']['shop_city']));
                }
            }
            $this->smarty->assign('cfg', $GLOBALS['_CFG']);

            $invoice_list = $this->commonRepository->getInvoiceList($GLOBALS['_CFG']['invoice_type']);
            $this->smarty->assign('invoice_list', $invoice_list); //发票类型及税率


            return $this->smarty->display('shop_config.dwt');
        }

        /*------------------------------------------------------ */
        //-- 邮件服务器设置
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'mail_settings') {
            /* 检查权限 */
            admin_priv('mail_settings');

            $arr = $this->get_settings([5]);


            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['01_mail_settings']);
            $this->smarty->assign('cfg', $arr[5]['vars']);
            return $this->smarty->display('shop_config_mail_settings.dwt');
        }

        /*------------------------------------------------------ */
        //-- 提交   ?act=post
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'post') {
            $type = empty($_POST['type']) ? '' : $_POST['type'];
            /* 检查权限 */
            admin_priv('shop_config');

            /* 允许上传的文件类型 */
            $allow_file_types = '|GIF|JPG|PNG|BMP|SWF|DOC|XLS|PPT|MID|WAV|ZIP|RAR|PDF|CHM|RM|TXT|CERT|';

            /* 保存变量值 */
            $count = isset($_POST['value']) ? count($_POST['value']) : 0;
            $arr = [];
            $sql = 'SELECT id, value FROM ' . $this->dsc->table('shop_config');
            $res = $this->db->query($sql);
            foreach ($res as $row) {
                $arr[$row['id']] = $row['value'];
            }

            $_POST['value'][107] = isset($_POST['value'][107]) ? intval($_POST['value'][107]) : 0;

            $region_info = Region::select('region_id', 'region_name', 'parent_id')->where('region_id', $_POST['value'][107])->first();
            $region_info = $region_info ? $region_info->toArray() : [];

            if ($region_info && $_POST['value'][106] != $region_info['parent_id']) {
                $_POST['value'][107] = 0;
            }

            foreach ($_POST['value'] as $key => $val) {
                if ($arr[$key] != $val) {
                    ShopConfig::where('id', $key)->update(['value' => trim($val)]);
                }
            }

            /* 处理上传文件 */
            $file_var_list = [];
            $sql = "SELECT * FROM " . $this->dsc->table('shop_config') . " WHERE parent_id > 0 AND type = 'file'";
            $res = $this->db->query($sql);
            foreach ($res as $row) {
                if (strpos($row['store_dir'], '../') !== false) {
                    $row = str_replace('../', '', $row);
                }
                $file_var_list[$row['code']] = $row;
            }

            foreach ($_FILES as $code => $file) {
                if (!file_exists(storage_public($file_var_list[$code]['store_dir']))) {
                    make_dir(storage_public($file_var_list[$code]['store_dir']));
                }

                /* 判断用户是否选择了文件 */
                if ((isset($file['error']) && $file['error'] == 0) || (!isset($file['error']) && $file['tmp_name'] != 'none')) {
                    /* 检查上传的文件类型是否合法 */
                    if (!check_file_type($file['tmp_name'], $file['name'], $allow_file_types)) {
                        return sys_msg(sprintf($GLOBALS['_LANG']['msg_invalid_file'], $file['name']));
                    } else {
                        $code_store_dir = [
                            'shop_logo',
                            'ecjia_qrcode',
                            'ectouch_qrcode',
                            'index_down_logo',
                            'user_login_logo',
                            'login_logo_pic',
                            'admin_login_logo',
                            'admin_logo',
                            'seller_login_logo',
                            'seller_logo',
                            'stores_login_logo',
                            'stores_logo',
                            'order_print_logo'
                        ];

                        if ($code == 'business_logo') {
                            load_helper('template', 'admin');
                            $info = get_template_info($GLOBALS['_CFG']['template']);

                            $file_name = str_replace('{$template}', $GLOBALS['_CFG']['template'], $file_var_list[$code]['store_dir']) . $info['business_logo'];
                        } elseif ($code == 'watermark') {
                            $ext = !empty($file['name']) ? explode('.', $file['name']) : '';
                            $ext = !empty($ext) ? array_pop($ext) : '';
                            $file_name = storage_public($file_var_list[$code]['store_dir'] . 'watermark.' . $ext);
                            $dir_name = $file_var_list[$code]['store_dir'] . 'watermark.' . $ext;
                            if (file_exists($file_var_list[$code]['value'])) {
                                @unlink($file_var_list[$code]['value']);
                            }
                        } elseif ($code == 'wap_logo') {
                            $ext = !empty($file['name']) ? explode('.', $file['name']) : '';
                            $ext = !empty($ext) ? array_pop($ext) : '';

                            $file_name = storage_public($file_var_list[$code]['store_dir'] . $code . "." . $ext);
                            $dir_name = $file_var_list[$code]['store_dir'] . $code . "." . $ext;

                            if (file_exists($file_var_list[$code]['value'])) {
                                @unlink($file_var_list[$code]['value']);
                            }
                        } elseif ($code == 'two_code_logo') {
                            $ext = !empty($file['name']) ? explode('.', $file['name']) : '';
                            $ext = !empty($ext) ? array_pop($ext) : '';

                            $file_name = storage_public($file_var_list[$code]['store_dir'] . $code . "." . $ext);
                            $dir_name = $file_var_list[$code]['store_dir'] . $code . "." . $ext;

                            if (file_exists($file_var_list[$code]['value'])) {
                                @unlink($file_var_list[$code]['value']);
                            }
                        } elseif (in_array($code, $code_store_dir)) {
                            $ext = !empty($file['name']) ? explode('.', $file['name']) : '';
                            $ext = !empty($ext) ? array_pop($ext) : '';

                            if (in_array($code, ['admin_login_logo', 'admin_logo', 'seller_login_logo', 'seller_logo', 'stores_login_logo', 'stores_logo', 'order_print_logo'])) {
                                $file_name = public_path('/assets/' . $file_var_list[$code]['store_dir'] . $code . "." . $ext);
                            } else {
                                $file_name = storage_public($file_var_list[$code]['store_dir'] . $code . "." . $ext);
                            }

                            $dir_name = $file_var_list[$code]['store_dir'] . $code . "." . $ext;

                            if (file_exists($file_var_list[$code]['value'])) {
                                @unlink(storage_public($file_var_list[$code]['value']));
                            }
                        } else {
                            $file_name = storage_public($file_var_list[$code]['store_dir'] . $file['name']);
                            $dir_name = $file_var_list[$code]['store_dir'] . $file['name'];
                        }

                        /* 判断是否上传成功 */
                        if (move_upload_file($file['tmp_name'], $file_name)) {
                            $file_name = $dir_name;
                            $sql = "SELECT value FROM" . $this->dsc->table("shop_config") . " WHERE code = '$code'";
                            $olde_value = $this->db->getOne($sql);
                            if ($file_name) {
                                $oss_file_name = str_replace(['../'], '', $file_name);
                                if ($olde_value != $file_name && $olde_value != "../images/errorImg.png" && $olde_value != '' && strpos($olde_value, 'http://') === false && strpos($olde_value, 'https://') === false) {
                                    $oss_olde_file = str_replace(['../'], '', $olde_value);
                                    $this->dscRepository->getOssDelFile([$oss_olde_file]);
                                    //做判断，判断文件是否存在，如果存在则删除

                                    $oss_olde_file = get_image_path($oss_olde_file);
                                    dsc_unlink($oss_olde_file);
                                }
                                $this->dscRepository->getOssAddFile([$oss_file_name]);
                            }

                            $sql = "UPDATE " . $this->dsc->table('shop_config') . " SET value = '$file_name' WHERE code = '$code'";
                            $this->db->query($sql);
                        } else {
                            return sys_msg(sprintf($GLOBALS['_LANG']['msg_upload_failed'], $file['name'], $file_var_list[$code]['store_dir']));
                        }
                    }
                }
            }

            $_POST['invoice_type'] = isset($_POST['invoice_type']) ? $_POST['invoice_type'] : '';
            $_POST['invoice_rate'] = isset($_POST['invoice_rate']) ? $_POST['invoice_rate'] : '';

            $invoice_list = $this->get_post_invoice($_POST['invoice_type'], $_POST['invoice_rate']);

            /* 处理发票类型及税率 */
            if (!empty($invoice_list['type'])) {
                $invoice = [
                    'type' => $invoice_list['type'],
                    'rate' => $invoice_list['rate']
                ];
                $sql = "UPDATE " . $this->dsc->table('shop_config') . " SET value = '" . serialize($invoice) . "' WHERE code = 'invoice_type'";
                $this->db->query($sql);
            }

            if (empty($invoice_list['type']) && empty($invoice_list['rate'])) {
                $sql = "UPDATE " . $this->dsc->table('shop_config') . " SET value = '' WHERE code = 'invoice_type'";
                $this->db->query($sql);
            }

            /* 记录日志 */
            admin_log('', 'edit', 'shop_config');

            /* 清除缓存 */
            cache()->forget('shop_config');

            $shop_url = urlencode($this->dsc->url());

            $sql = 'SELECT id, code, value FROM ' . $this->dsc->table('shop_config') . " WHERE code IN('shop_name','shop_title','shop_desc','shop_keywords','shop_address','qq','ww','service_phone','msn','service_email','sms_shop_mobile','icp_number','lang', 'certi', 'shop_country', 'shop_province', 'shop_city', 'shop_address', 'shop_district')";
            $row = $this->db->getAll($sql);
            $row = get_cfg_val($row);

            //平台同步设置手机，邮箱 by wu start  by kong 改
            $back_array = ['mail_setting', 'seller_setup', 'report_conf', 'complaint_conf', 'sms_setup', 'goods_setup', 'order_delay'];
            if (!in_array($type, $back_array)) {
                $update_arr = [
                    //'mobile' => $_POST['value'][801], //手机
                    'seller_email' => isset($_POST['value'][114]) ? $_POST['value'][114] : '', //邮箱
                    'kf_qq' => isset($_POST['value'][109]) ? $_POST['value'][109] : '', //QQ
                    'kf_ww' => isset($_POST['value'][110]) ? $_POST['value'][110] : '', //旺旺
                    'shop_title' => isset($_POST['value'][102]) ? $_POST['value'][102] : '', //商店标题
                    'shop_keyword' => isset($_POST['value'][104]) ? $_POST['value'][104] : '', //商店关键字
                    'country' => isset($_POST['value'][105]) ? $_POST['value'][105] : '', //国家
                    'province' => isset($_POST['value'][106]) ? $_POST['value'][106] : '', //省份
                    'city' => isset($_POST['value'][107]) ? $_POST['value'][107] : '', //城市
                    'district' => $row['shop_district'] ?? 0, //区域
                    'shop_address' => isset($_POST['value'][108]) ? $_POST['value'][108] : '', //详细地址
                    'kf_tel' => isset($_POST['value'][115]) ? $_POST['value'][115] : '', //客服电话
                    'notice' => isset($_POST['value'][121]) ? $_POST['value'][121] : '', //店铺公告
                ];
                foreach ($update_arr as $key => $val) {
                    $sql = "UPDATE " . $this->dsc->table('seller_shopinfo') . " SET " . $key . " = '" . $val . "' WHERE ru_id = 0 ";
                    $this->db->query($sql);
                }
            }
            //平台同步设置手机，邮箱 by wu end

            $shop_info = get_shop_info_content(0);

            if ($shop_info && $shop_info['country'] && $shop_info['province'] && $shop_info['city']) {
                $shop_country = $shop_info['country'];
                $shop_province = $shop_info['province'];
                $shop_city = $shop_info['city'];
                $shop_address = $shop_info['shop_address'];
            } else {
                $shop_country = $row['shop_country'];
                $shop_province = $row['shop_province'];
                $shop_city = $row['shop_city'];
                $shop_address = $row['shop_address'];
            }

            $row['qq'] = !empty($row['qq']) ? $row['qq'] : $shop_info['kf_qq'];
            $row['ww'] = !empty($row['ww']) ? $row['ww'] : $shop_info['kf_ww'];
            $row['service_email'] = !empty($row['service_email']) ? $row['service_email'] : $shop_info['seller_email'];
            $row['service_phone'] = !empty($row['service_phone']) ? $row['service_phone'] : $shop_info['kf_tel'];

            $shop_country = $this->db->getOne("SELECT region_name FROM " . $this->dsc->table('region') . " WHERE region_id='$shop_country'");
            $shop_province = $this->db->getOne("SELECT region_name FROM " . $this->dsc->table('region') . " WHERE region_id='$shop_province'");
            $shop_city = $this->db->getOne("SELECT region_name FROM " . $this->dsc->table('region') . " WHERE region_id='$shop_city'");

            $time = app(TimeRepository::class)->getGmTime();

            $httpData = [
                'domain' => $this->dsc->get_domain(), //当前域名
                'url' => urldecode($shop_url), //当前url
                'shop_name' => $row['shop_name'],
                'shop_title' => $row['shop_title'],
                'shop_desc' => $row['shop_desc'],
                'shop_keywords' => $row['shop_keywords'],
                'country' => $shop_country,
                'province' => $shop_province,
                'city' => $shop_city,
                'address' => $shop_address,
                'qq' => $row['qq'],
                'ww' => $row['ww'],
                'ym' => $row['service_phone'], //客服电话
                'msn' => $row['msn'],
                'email' => $row['service_email'],
                'phone' => $row['sms_shop_mobile'], //手机号
                'icp' => $row['icp_number'],
                'version' => VERSION,
                'release' => RELEASE,
                'language' => $GLOBALS['_CFG']['lang'],
                'php_ver' => PHP_VERSION,
                'mysql_ver' => $this->db->version(),
                'charset' => EC_CHARSET,
                'add_time' => app(TimeRepository::class)->getLocalDate("Y-m-d H:i:s", $time)
            ];

            $httpData = json_encode($httpData); // 对变量进行 JSON 编码
            $argument = array(
                'data' => $httpData
            );

            Http::doPost($row['certi'], $argument);
            write_static_cache('seller_goods_str', $httpData);

            $back_array = ['mail_setting', 'seller_setup', 'report_conf', 'complaint_conf', 'sms_setup', 'cloud_setup', 'goods_setup', 'order_delay'];
            if (in_array($type, $back_array)) {

                /* 邮箱设置 */
                if ($type == 'mail_setting') {
                    $back = $GLOBALS['_LANG']['back_mail_settings'];
                    $href = 'shop_config.php?act=mail_settings';
                    $sys_msg = $GLOBALS['_LANG']['mail_save_success'];
                } /* 店铺设置 */
                elseif ($type == 'seller_setup') {
                    $back = $GLOBALS['_LANG']['back_seller_settings'];
                    $href = 'merchants_steps.php?act=step_up';
                    $sys_msg = $GLOBALS['_LANG']['seller_save_success'];
                } /* 短信设置 */
                elseif ($type == 'sms_setup') {
                    $back = $GLOBALS['_LANG']['back_sms_settings'];
                    $href = 'sms_setting.php?act=step_up';
                    $sys_msg = $GLOBALS['_LANG']['sms_success'];
                } /* 文件存储设置 */
                elseif ($type == 'cloud_setup') {
                    $back = $GLOBALS['_LANG']['back_cloud_settings'];
                    $href = 'cloud_setting.php?act=step_up';
                    $sys_msg = $GLOBALS['_LANG']['cloud_success'];
                } /* 举报设置 */
                elseif ($type == 'report_conf') {
                    $back = $GLOBALS['_LANG']['report_conf'];
                    $href = 'goods_report.php?act=report_conf';
                    $sys_msg = $GLOBALS['_LANG']['report_conf_success'];
                } /* 投诉设置 */
                elseif ($type == 'complaint_conf') {
                    $back = $GLOBALS['_LANG']['complain_conf'];
                    $href = 'complaint.php?act=complaint_conf';
                    $sys_msg = $GLOBALS['_LANG']['complain_conf_success'];
                } /* 商品设置 */
                elseif ($type == 'goods_setup') {
                    $back = $GLOBALS['_LANG']['goods_setup'];
                    $href = 'goods.php?act=step_up';
                    $sys_msg = $GLOBALS['_LANG']['goods_setup_success'];
                } /* 商品设置 */
                elseif ($type == 'order_delay') {
                    $back = $GLOBALS['_LANG']['order_delay_conf'];
                    $href = 'order_delay.php?act=complaint_conf';
                    $sys_msg = $GLOBALS['_LANG']['order_delay_success'];
                }
            } else {
                $back = $GLOBALS['_LANG']['back_shop_config'];
                $href = 'shop_config.php?act=list_edit';
                $sys_msg = $GLOBALS['_LANG']['save_success'];
            }

            return $this->get_back_settings($back, $href, $sys_msg);
        }

        /*------------------------------------------------------ */
        //-- 发送测试邮件
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'send_test_email') {
            /* 检查权限 */
            $check_auth = check_authz_json('shop_config');
            if ($check_auth !== true) {
                return $check_auth;
            }

            /* 取得参数 */
            $email = trim($_POST['email']);

            if ($this->commonRepository->sendEmail('', $email, $GLOBALS['_LANG']['test_mail_title'], $GLOBALS['_LANG']['cfg_name']['email_content'], 0)) {
                return make_json_result('', $GLOBALS['_LANG']['sendemail_success'] . $email);
            } else {
                return make_json_error(join("\n", $this->err->_message));
            }
        }

        /*------------------------------------------------------ */
        //-- 删除上传文件
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'del') {
            /* 检查权限 */
            $check_auth = check_authz_json('shop_config');
            if ($check_auth !== true) {
                return $check_auth;
            }

            /* 取得参数 */
            $code = trim($_GET['code']);

            $filename = $GLOBALS['_CFG'][$code];

            if (isset($filename) && !empty($filename)) {
                $oss_file_name = str_replace(['../'], '', $filename);
                $this->dscRepository->getOssDelFile([$oss_file_name]);
            }

            //删除文件
            if (in_array($code, ['admin_login_logo', 'admin_logo', 'seller_login_logo', 'seller_logo', 'stores_login_logo', 'stores_logo', 'order_print_logo'])) {
                dsc_unlink(public_path('/assets/' . $filename));
            } else {
                dsc_unlink(storage_public($filename));
            }

            $sql = "UPDATE " . $this->dsc->table('shop_config') . " SET value = '' WHERE code = '$code'";
            $this->db->query($sql);

            //更新设置
            $this->update_configure($code, '');

            /* 记录日志 */
            admin_log('', 'edit', 'shop_config');

            /* 清除缓存 */
            clear_all_files();

            //跳转链接
            $shop_group = get_table_date('shop_config', "code='$code'", ['shop_group'], 2);
            switch ($shop_group) {
                case 'goods':
                    $text = $GLOBALS['_LANG']['goods_setup'];
                    $href = 'goods.php?act=step_up';
                    $sys_msg = $GLOBALS['_LANG']['goods_setup_success'];
                    break;
                default:
                    $text = $GLOBALS['_LANG']['back_shop_config'];
                    $href = 'shop_config.php?act=list_edit';
                    $sys_msg = $GLOBALS['_LANG']['save_success'];
            }

            $links[] = ['text' => $text, 'href' => $href];
            return sys_msg($sys_msg, 0, $links);
        }
    }

    /**
     * 设置系统设置
     *
     * @param string $key
     * @param string $val
     *
     * @return  boolean
     */
    private function update_configure($key, $val = '')
    {
        if (!empty($key)) {
            $sql = "UPDATE " . $this->dsc->table('shop_config') . " SET value='$val' WHERE code='$key'";

            return $this->db->query($sql);
        }

        return true;
    }

    /**
     * 获得设置信息
     *
     * @param array $groups 需要获得的设置组
     * @param array $excludes 不需要获得的设置组
     *
     * @return  array
     */
    private function get_settings($groups = null, $excludes = null)
    {
        $config_groups = '';
        $excludes_groups = '';

        if (!empty($groups)) {
            foreach ($groups as $key => $val) {
                $config_groups .= " AND (id='$val' OR parent_id='$val')";
            }
        }

        if (!empty($excludes)) {
            foreach ($excludes as $key => $val) {
                $excludes_groups .= " AND (parent_id<>'$val' AND id<>'$val')";
            }
        }

        /**
         * 不显示的内容
         */
        $shop_group = [
            'seller',
            'complaint_conf',
            'report_conf',
            'sms',
            'goods',
            'order_delay',
            'cloud',
            'ecjia'
        ];
        $shop_group = db_create_in($shop_group, 'shop_group', 'not');
        $where = " AND $shop_group";

        /* 取出全部数据：分组和变量 */
        $sql = "SELECT * FROM " . $this->dsc->table('shop_config') .
            " WHERE type<>'hidden' $config_groups $excludes_groups $where ORDER BY parent_id, sort_order, id";
        $item_list = $this->db->getAll($sql);

        /* 整理数据 */
        $filter_item = [
            'sms',
            'hidden',
            'goods'
        ];
        $group_list = [];
        $code_arr = [
            'shop_logo',
            'no_picture',
            'watermark',
            'shop_slagon',
            'wap_logo',
            'two_code_logo',
            'ectouch_qrcode',
            'ecjia_qrcode',
            'index_down_logo',
            'user_login_logo',
            'login_logo_pic',
            'business_logo',
            'admin_login_logo',
            'admin_logo',
            'seller_login_logo',
            'seller_logo',
            'stores_login_logo',
            'stores_logo',
            'order_print_logo'
        ];
        foreach ($item_list as $key => $item) {
            if (!in_array($item['code'], $filter_item)) {
                $pid = $item['parent_id'];
                $item['name'] = isset($GLOBALS['_LANG']['cfg_name'][$item['code']]) ? $GLOBALS['_LANG']['cfg_name'][$item['code']] : $item['code'];
                $item['desc'] = isset($GLOBALS['_LANG']['cfg_desc'][$item['code']]) ? $GLOBALS['_LANG']['cfg_desc'][$item['code']] : '';

                if ($item['code'] == 'sms_shop_mobile') {
                    $item['url'] = 1;
                }
                if ($pid == 0) {
                    /* 分组 */
                    if ($item['type'] == 'group') {
                        $group_list[$item['id']] = $item;
                    }
                } else {
                    /* 变量 */
                    if (isset($group_list[$pid])) {
                        if ($item['store_range']) {
                            $item['store_options'] = explode(',', $item['store_range']);

                            foreach ($item['store_options'] as $k => $v) {
                                $item['display_options'][$k] = isset($GLOBALS['_LANG']['cfg_range'][$item['code']][$v]) ?
                                    $GLOBALS['_LANG']['cfg_range'][$item['code']][$v] : $v;
                            }
                        }

                        if ($item) {
                            if ($item['type'] == 'file' && in_array($item['code'], $code_arr) && $item['value']) {
                                $item['del_img'] = 1;

                                if (strpos($item['value'], '../') === false) {
                                    if (in_array($item['code'], ['admin_login_logo', 'admin_logo', 'seller_login_logo', 'seller_logo', 'stores_login_logo', 'stores_logo', 'order_print_logo'])) {
                                        $item['value'] = $this->dsc->url() . 'assets/' . $item['value'];
                                    } else {
                                        $item['value'] = get_image_path($item['value']);
                                    }
                                }
                            } else {
                                $item['del_img'] = 0;
                            }

                            if ($item['code'] == 'stats_code') {
                                $item['value'] = html_out($item['value']);
                            }
                        }

                        $group_list[$pid]['vars'][] = $item;
                    }
                }
            }
        }

        return $group_list;
    }

    //过滤提交票税信息
    private function get_post_invoice($type, $rate)
    {
        if ($type) {
            for ($i = 0; $i < count($type); $i++) {
                if (empty($type[$i]) && empty($rate[$i])) {
                    unset($type[$i]);
                    unset($rate[$i]);
                } else {
                    $rate[$i] = round(floatval($rate[$i]), 2);
                }
            }
        } else {
            $type = [];
            $rate = [];
        }

        $arr = ['type' => $type, 'rate' => $rate];
        return $arr;
    }

    /* 基本信息设置返回 */
    private function get_back_settings($text, $href, $sys_msg)
    {
        $links[] = ['text' => $text, 'href' => $href];
        return sys_msg($sys_msg, 0, $links);
    }
}
