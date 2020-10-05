<?php

namespace App\Http\Controllers\Install;

use App\Http\Controllers\Install\Helpers\CheckingDirs;
use App\Http\Controllers\Install\Helpers\EnvChecker;
use App\Http\Controllers\Install\Helpers\InitController;
use App\Http\Controllers\Install\Helpers\Installer;
use App\Http\Controllers\Install\Helpers\Template;
use App\Libraries\Http;
use Illuminate\Support\Facades\Storage;

/**
 * 安装界面
 */
class InstallController extends InitController
{
    protected $template;
    protected $envChecker;
    protected $checkingDirs;
    protected $installer;

    public function __construct(
        Template $template,
        EnvChecker $envChecker,
        CheckingDirs $checkingDirs,
        Installer $installer
    )
    {
        $this->template = $template;
        $this->envChecker = $envChecker;
        $this->checkingDirs = $checkingDirs;
        $this->installer = $installer;
    }

    public function index()
    {
        $this->template->path = app_path('Http/Controllers/Install/Views/');

        defined('ROOT_PATH') or define('ROOT_PATH', str_replace('\\', '/', base_path()) . '/');
        define('VIEWS_PATH', str_replace('\\', '/', app_path('Http/Controllers/Install/Views')) . '/');

        define('DSC_DB_CHARSET', config('database.connections.mysql.charset') . ' collate "' . config('database.connections.mysql.collation') . '"');

        if (isset($_REQUEST['dbhost']) || isset($_REQUEST['dbname']) || isset($_REQUEST['dbuser']) || isset($_REQUEST['dbpass']) || isset($_REQUEST['password']) || isset($_REQUEST['data'])) {
            return 111;
        }

        /* 初始化语言变量 */
        $installer_lang = isset($_REQUEST['lang']) ? trim($_REQUEST['lang']) : 'zh_cn';


        if ($installer_lang != 'zh_cn' && $installer_lang != 'zh_tw' && $installer_lang != 'en_us') {
            $installer_lang = 'zh_cn';
        }

        session(['sc_lang' => $installer_lang]);

        /* 加载安装程序所使用的语言包 */
        $installer_lang_package_path = app_path('Http/Controllers/Install/lang/' . $installer_lang . '.php');

        if (is_file($installer_lang_package_path)) {
            $lang = include_once($installer_lang_package_path);
            $this->template->assign('lang', $lang);
        } else {
            die('Can\'t find language package!');
        }

        /* 初始化流程控制变量 */
        $step = isset($_REQUEST['step']) ? $_REQUEST['step'] : 'welcome';

        $lockfile = Storage::disk('local')->exists('seeder/install.lock.php');
        if ($lockfile && $step != 'active') {
            $step = 'error';
            $this->err->add($lang['has_locked_installer']);

            if (isset($_REQUEST['IS_AJAX_REQUEST'])
                && $_REQUEST['IS_AJAX_REQUEST'] === 'yes') {
                die(implode(',', $this->err->get_all()));
            }
        }

        $_REQUEST['db_pass'] = base64_decode($_REQUEST['db_pass']);

        switch ($step) {
            case 'welcome':

                $ucapi = $_POST['ucapi'] ?? '';
                $ucfounderpw = $_POST['ucfounderpw'] ?? '';

                session([
                    'welcome' => [
                        'ucapi' => $ucapi,
                        'ucfounderpw' => $ucfounderpw,
                        'installer_lang' => $installer_lang,
                    ]
                ]);

                $this->template->assign('ucapi', $ucapi);
                $this->template->assign('ucfounderpw', $ucfounderpw);
                $this->template->assign('installer_lang', $installer_lang);

                return $this->template->display('welcome.php');
                break;

            case 'uccheck':

                $this->template->assign('ucapi', $_POST['ucapi']);
                $this->template->assign('ucfounderpw', $_POST['ucfounderpw']);
                $this->template->assign('installer_lang', $installer_lang);
                return $this->template->display('uc_check.php');

                break;

            case 'check':

                $checking_dirs = $this->checkingDirs->getCheckingDirsLang();
                $dir_checking = $this->envChecker->getCheckDirsPriv($checking_dirs);

                $templates_root = [
                    'dwt' => resource_path('views/themes/ecmoban_dsc2017/'),
                    'lbi' => resource_path('views/themes/ecmoban_dsc2017/library/')
                ];

                $template_checking = $this->envChecker->getCheckTemplatesPriv($templates_root);

                $rename_priv = $this->envChecker->getCheckRenamePriv();

                $disabled = '';
                if ($dir_checking['result'] === 'ERROR'
                    || !empty($template_checking)
                    || !empty($rename_priv)
                    || !(config('database.connections.mysql.driver') == 'mysql')) {
                    $disabled = 'disabled="true"';
                }

                $has_unwritable_tpl = 'yes';
                if (empty($template_checking)) {
                    $template_checking = $lang['all_are_writable'];
                    $has_unwritable_tpl = 'no';
                }

                $system_info = $this->installer->get_system_info();

                $_GET['ui'] = $_GET['ui'] ?? '';
                $_POST['ucapi'] = $_POST['ucapi'] ?? '';
                $_POST['ucfounderpw'] = $_POST['ucfounderpw'] ?? '';

                $ui = (!empty($_POST['user_interface'])) ? $_POST['user_interface'] : $_GET['ui'];

                session([
                    'check' => [
                        'ucapi' => $_POST['ucapi'],
                        'ucfounderpw' => $_POST['ucfounderpw'],
                        'installer_lang' => $installer_lang,
                        'system_info' => $system_info,
                        'dir_checking' => $dir_checking['detail'],
                        'has_unwritable_tpl' => $has_unwritable_tpl,
                        'template_checking' => $template_checking,
                        'rename_priv' => $rename_priv,
                        'disabled' => $disabled,
                        'userinterface' => $ui
                    ]
                ]);

                $this->template->assign('ucapi', $_POST['ucapi']);
                $this->template->assign('ucfounderpw', $_POST['ucfounderpw']);
                $this->template->assign('installer_lang', $installer_lang);
                $this->template->assign('system_info', $system_info);
                $this->template->assign('dir_checking', $dir_checking['detail']);
                $this->template->assign('has_unwritable_tpl', $has_unwritable_tpl);
                $this->template->assign('template_checking', $template_checking);
                $this->template->assign('rename_priv', $rename_priv);
                $this->template->assign('disabled', $disabled);
                $this->template->assign('userinterface', $ui);

                return $this->template->display('checking.php');
                break;

            case 'setting_ui':

                if (!$this->installer->has_supported_gd()) {
                    $checked = 'checked="checked"';
                    $disabled = 'disabled="true"';
                } else {
                    $checked = '';
                    $disabled = '';
                }

                $local_timezone = $this->installer->get_local_timezone();

                $show_timezone = PHP_VERSION >= '5.1' ? 'yes' : 'no';
                $timezones = $this->installer->get_timezone_list($installer_lang);

                session([
                    'setting_ui' => [
                        'ucapi' => $_POST['ucapi'],
                        'ucfounderpw' => $_POST['ucfounderpw'],
                        'installer_lang' => $installer_lang,
                        'checked' => $checked,
                        'disabled' => $disabled,
                        'show_timezone' => $show_timezone,
                        'local_timezone' => $local_timezone,
                        'timezones' => $timezones,
                        'userinterface' => empty($_GET['ui']) ? 'dscmall' : $_GET['ui']
                    ]
                ]);

                $this->template->assign('ucapi', $_POST['ucapi']);
                $this->template->assign('ucfounderpw', $_POST['ucfounderpw']);
                $this->template->assign('installer_lang', $installer_lang);
                $this->template->assign('checked', $checked);
                $this->template->assign('disabled', $disabled);
                $this->template->assign('show_timezone', $show_timezone);
                $this->template->assign('local_timezone', $local_timezone);
                $this->template->assign('timezones', $timezones);

                $userinterface = isset($_GET['ui']) && !empty($_GET['ui']) ? $_GET['ui'] : 'dscmall';
                $this->template->assign('userinterface', $userinterface);

                $path = public_path('assets/install/data/demo_zh_cn.sql');

                if (is_file($path)) {
                    $is_path = 1;
                } else {
                    $is_path = 0;
                }
                $this->template->assign('is_path', $is_path);

                return $this->template->display('setting.php');
                break;

            case 'get_db_list':

                $db_host = isset($_REQUEST['db_host']) ? trim($_REQUEST['db_host']) : '';
                $db_port = isset($_REQUEST['db_port']) ? trim($_REQUEST['db_port']) : '';
                $db_user = isset($_REQUEST['db_user']) ? trim($_REQUEST['db_user']) : '';
                $db_pass = isset($_REQUEST['db_pass']) ? trim($_REQUEST['db_pass']) : '';

                $databases = $this->installer->get_db_list($db_host, $db_port, $db_user, $db_pass);
                if ($databases === false) {
                    return json_encode(implode(',', $this->err->get_all()));
                } else {
                    $result = array('msg' => 'OK', 'list' => implode(',', $databases));
                    return response()->json($result);
                }

                break;

            case 'create_config_file':
                $db_host = isset($_REQUEST['db_host']) ? trim($_REQUEST['db_host']) : '';
                $db_port = isset($_REQUEST['db_port']) ? trim($_REQUEST['db_port']) : '';
                $db_user = isset($_REQUEST['db_user']) ? trim($_REQUEST['db_user']) : '';
                $db_pass = isset($_REQUEST['db_pass']) ? trim($_REQUEST['db_pass']) : '';
                $db_name = isset($_REQUEST['db_name']) ? trim($_REQUEST['db_name']) : '';
                $prefix = isset($_REQUEST['db_prefix']) ? trim($_REQUEST['db_prefix']) : '';
                $timezone = isset($_REQUEST['timezone']) ? trim($_REQUEST['timezone']) : 'Asia/Shanghai';

                $result = $this->installer->create_config_file($db_host, $db_port, $db_user, $db_pass, $db_name, $prefix, $timezone);
                if ($result === false) {
                    return json_encode(implode(',', $this->err->get_all()));
                } else {
                    $result = array('msg' => 'OK');
                    return response()->json($result);
                }

                break;

            case 'setup_ucenter':

                $result = array('error' => 0, 'message' => '');

                $app_type = 'DSCMALL';
                $app_name = 'DSCMALL 网店';
                $app_url = url();
                $app_charset = EC_CHARSET;
                $app_dbcharset = DSC_DB_CHARSET;
                $dns_error = false;

                $ucapi = !empty($_POST['ucapi']) ? trim($_POST['ucapi']) : '';
                $ucip = !empty($_POST['ucip']) ? trim($_POST['ucip']) : '';
                if (!$ucip) {
                    $temp = @parse_url($ucapi);
                    $ucip = gethostbyname($temp['host']);
                    if (ip2long($ucip) == -1 || ip2long($ucip) === false) {
                        $ucip = '';
                        $dns_error = true;
                    }
                }
                if ($dns_error) {
                    $result['error'] = 2;
                    $result['message'] = '';//$lang['ucenter_data_error'];

                    return response()->json($result);
                }
                $ucfounderpw = trim($_POST['ucfounderpw']);
                $app_tagtemplates = 'apptagtemplates[template]=' . urlencode('<a href="{url}" target="_blank">{goods_name}</a>') . '&' .
                    'apptagtemplates[fields][goods_name]=' . urlencode($lang['tagtemplates_goodsname']) . '&' .
                    'apptagtemplates[fields][uid]=' . urlencode($lang['tagtemplates_uid']) . '&' .
                    'apptagtemplates[fields][username]=' . urlencode($lang['tagtemplates_username']) . '&' .
                    'apptagtemplates[fields][dateline]=' . urlencode($lang['tagtemplates_dateline']) . '&' .
                    'apptagtemplates[fields][url]=' . urlencode($lang['tagtemplates_url']) . '&' .
                    'apptagtemplates[fields][image]=' . urlencode($lang['tagtemplates_image']) . '&' .
                    'apptagtemplates[fields][goods_price]=' . urlencode($lang['tagtemplates_price']);
                $postdata = "m=app&a=add&ucfounder=&ucfounderpw=" . urlencode($ucfounderpw) . "&apptype=" . urlencode($app_type) .
                    "&appname=" . urlencode($app_name) . "&appurl=" . urlencode($app_url) . "&appip=&appcharset=" . $app_charset .
                    '&appdbcharset=' . $app_dbcharset . '&' . $app_tagtemplates;
                $ucconfig = dfopen($ucapi . '/index.php', 500, $postdata, '', 1, $ucip);
                if (empty($ucconfig)) {
                    //ucenter 验证失败
                    $result['error'] = 1;
                    $result['message'] = $lang['ucenter_validation_fails'];
                } elseif ($ucconfig == '-1') {
                    //管理员密码无效
                    $result['error'] = 1;
                    $result['message'] = $lang['ucenter_creator_wrong_password'];
                } else {
                    list($appauthkey, $appid) = explode('|', $ucconfig);
                    if (empty($appauthkey) || empty($appid)) {
                        //ucenter 安装数据错误
                        $result['error'] = 1;
                        $result['message'] = $lang['ucenter_data_error'];
                    } elseif (($succeed = save_uc_config($ucconfig . "|$ucapi|$ucip"))) {
                        $result['error'] = 0;
                        $result['message'] = 'OK';
                    } else {
                        //config文件写入错误
                        $result['error'] = 1;
                        $result['message'] = $lang['ucenter_config_error'];
                    }
                }

                return response()->json($result);
                break;

            case 'create_database':
                $db_host = isset($_REQUEST['db_host']) ? trim($_REQUEST['db_host']) : '';
                $db_port = isset($_REQUEST['db_port']) ? trim($_REQUEST['db_port']) : '';
                $db_user = isset($_REQUEST['db_user']) ? trim($_REQUEST['db_user']) : '';
                $db_pass = isset($_REQUEST['db_pass']) ? trim($_REQUEST['db_pass']) : '';
                $db_name = isset($_REQUEST['db_name']) ? trim($_REQUEST['db_name']) : '';

                $result = $this->installer->create_database($db_host, $db_port, $db_user, $db_pass, $db_name);
                if ($result === false) {
                    $result = implode(',', $this->err->get_all());
                } else {
                    $result = [
                        'msg' => 'OK',
                        'config' => [
                            'db_host' => $db_host,
                            'db_port' => $db_port,
                            'db_user' => $db_user,
                            'db_pass' => $db_pass,
                            'db_name' => $db_name
                        ]
                    ];
                }

                return response()->json($result);

                break;

            case 'install_base_data':
                $system_lang = isset($_POST['system_lang']) ? $_POST['system_lang'] : 'zh_cn';

                $data_path = public_path('assets/install/data/data_' . $system_lang . '.sql');
                if (!is_file($data_path)) {
                    $data_path = public_path('assets/install/data/data_zh_cn.sql');
                }

                $sql_files = array(
                    public_path('assets/install/data/structure.sql'),
                    $data_path
                );

                $db_host = isset($_REQUEST['db_host']) ? trim($_REQUEST['db_host']) : '';
                $db_port = isset($_REQUEST['db_port']) ? trim($_REQUEST['db_port']) : '';
                $db_user = isset($_REQUEST['db_user']) ? trim($_REQUEST['db_user']) : '';
                $db_pass = isset($_REQUEST['db_pass']) ? trim($_REQUEST['db_pass']) : '';
                $db_name = isset($_REQUEST['db_name']) ? trim($_REQUEST['db_name']) : '';

                $result = $this->installer->install_data($sql_files, $db_host, $db_port, $db_user, $db_pass, $db_name);

                if ($result === false) {
                    return json_encode(implode(',', $this->err->get_all()));
                } else {
                    $result = [
                        'msg' => 'OK',
                        'config' => [
                            'db_host' => $db_host,
                            'db_port' => $db_port,
                            'db_user' => $db_user,
                            'db_pass' => $db_pass,
                            'db_name' => $db_name
                        ]
                    ];
                    return response()->json($result);
                }

                break;

            case 'create_admin_passport':
                $admin_name = isset($_POST['admin_name']) ? json_str_iconv(trim($_POST['admin_name'])) : '';
                $admin_password = isset($_POST['admin_password']) ? trim($_POST['admin_password']) : '';
                $admin_password2 = isset($_POST['admin_password2']) ? trim($_POST['admin_password2']) : '';
                $admin_email = isset($_POST['admin_email']) ? trim($_POST['admin_email']) : '';

                $db_host = isset($_REQUEST['db_host']) ? trim($_REQUEST['db_host']) : '';
                $db_port = isset($_REQUEST['db_port']) ? trim($_REQUEST['db_port']) : '';
                $db_user = isset($_REQUEST['db_user']) ? trim($_REQUEST['db_user']) : '';
                $db_pass = isset($_REQUEST['db_pass']) ? trim($_REQUEST['db_pass']) : '';
                $db_name = isset($_REQUEST['db_name']) ? trim($_REQUEST['db_name']) : '';

                $pdo = [
                    'db_host' => $db_host,
                    'db_port' => $db_port,
                    'db_user' => $db_user,
                    'db_pass' => $db_pass,
                    'db_name' => $db_name
                ];

                $result = $this->installer->create_admin_passport($admin_name, $admin_password, $admin_password2, $admin_email, $pdo);
                if ($result === false) {
                    return json_encode(implode(',', $this->err->get_all()));
                } else {
                    $result = [
                        'msg' => 'OK',
                        'config' => [
                            'db_host' => $db_host,
                            'db_port' => $db_port,
                            'db_user' => $db_user,
                            'db_pass' => $db_pass,
                            'db_name' => $db_name
                        ]
                    ];

                    return response()->json($result);
                }

                break;

            case 'do_others':
                $system_lang = isset($_POST['system_lang']) ? $_POST['system_lang'] : 'zh_cn';
                $captcha = isset($_POST['disable_captcha']) ? intval($_POST['disable_captcha']) : '0';
                $install_demo = isset($_POST['install_demo']) ? intval($_POST['install_demo']) : 0;
                $integrate = isset($_POST['userinterface']) ? trim($_POST['userinterface']) : 'dscmall';

                $config = [];
                $config['db_host'] = isset($_REQUEST['db_host']) ? trim($_REQUEST['db_host']) : '';
                $config['db_port'] = isset($_REQUEST['db_port']) ? trim($_REQUEST['db_port']) : '';
                $config['db_user'] = isset($_REQUEST['db_user']) ? trim($_REQUEST['db_user']) : '';
                $config['db_pass'] = isset($_REQUEST['db_pass']) ? trim($_REQUEST['db_pass']) : '';
                $config['db_name'] = isset($_REQUEST['db_name']) ? trim($_REQUEST['db_name']) : '';

                $result = $this->installer->do_others($system_lang, $config, $captcha, $install_demo, $integrate);
                if ($result === false) {
                    return json_encode(implode(',', $this->err->get_all()));
                } else {
                    $result = [
                        'msg' => 'OK',
                        $config
                    ];

                    return response()->json($result);
                }

                break;

            case 'done':
                $result = $this->installer->deal_aftermath();

                if ($result === false) {
                    $err_msg = implode(',', $this->err->get_all());
                    $this->template->assign('err_msg', $err_msg);
                    return $this->template->display('error.php');
                } else {
                    $config_temp = app_path('Http/Controllers/Install/data/config_temp.php');

                    if (is_file($config_temp)) {
                        unlink($config_temp);
                    }

                    $web_info = $this->installer->get_web_info();
                    $appid = $this->INSTALL_GUID();
                    $install_mobile = '';

                    $apiget = "&mobile=$install_mobile&sc_version=" . $web_info['sc_version'] . "&domain=" . urlencode($web_info['user_domain']) . "&ip=" . $web_info['user_ip'] . "&os=" . $web_info['user_os'] . "&webserver=" . urlencode($web_info['user_webserver']) . "&phpversion=" . $web_info['user_phpversion'] . "&time=" . $web_info['install_time'] . "&appid=" . $appid;
                    $this->api_request('record_info', $apiget);

                    return $this->template->display('done.php');
                }

                break;

            case 'error':
                $err_msg = implode(',', $this->err->get_all());
                $exists = Storage::disk('local')->exists('seeder/install.lock.php');
                $this->template->assign('err_msg', $err_msg);
                $this->template->assign('exists', $exists);
                return $this->template->display('error.php');

                break;

            default:
                die('Error, unknown step!');
        }
    }

    private function api_request($url, $apiget)
    {
        global $sc_charset;

        $install_api = 'http://dsc.ecmoban.com/ecmoban_sc/dsc_pms/sc_admin.php?c=Api&a=';

        $api_comment = app(Http::class)->doGet($install_api . $url . $apiget);
        $api_str = substr($api_comment, 3);

        $api_arr = dsc_decode($api_str, true);

        if (!empty($api_arr)) {
            if ($sc_charset != 'UTF-8') {
                $api_arr['content'] = sc_iconv('UTF-8', strtoupper(EC_CHARSET), $api_arr['content']);
            }
            return $api_arr;
        } else {
            return false;
        }
    }

    private function INSTALL_GUID()
    {
        if (function_exists('com_create_guid') === true) {
            return trim(com_create_guid(), '{}');
        }

        return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
    }
}
