<?php

namespace App\Modules\Admin\Controllers;

use App\Libraries\Transport;
use App\Models\ShopConfig;
use App\Models\Users;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Integrate\IntegrateManageService;
use Illuminate\Support\Str;

/**
 * 第三方程序会员数据整合插件管理程序
 * Class IntegrateController
 * @package App\Modules\Admin\Controllers
 */
class IntegrateController extends InitController
{
    /**
     * @var BaseRepository
     */
    protected $baseRepository;

    /**
     * @var DscRepository
     */
    protected $dscRepository;
    protected $integrateManageService;

    /**
     * IntegrateController constructor.
     * @param BaseRepository $baseRepository
     * @param DscRepository $dscRepository
     */
    public function __construct(
        BaseRepository $baseRepository,
        DscRepository $dscRepository,
        IntegrateManageService $integrateManageService
    )
    {
        $this->baseRepository = $baseRepository;
        $this->dscRepository = $dscRepository;
        $this->integrateManageService = $integrateManageService;
    }

    public function index()
    {
        /*------------------------------------------------------ */
        //-- 会员数据整合插件列表
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            $modules = $this->dscRepository->readModules(plugin_path('Integrates'));
            for ($i = 0; $i < count($modules); $i++) {
                $modules[$i]['code'] = $modules[$i]['code'] === 'passport' ? 'dscmall' : $modules[$i]['code'];
                $modules[$i]['installed'] = ($modules[$i]['code'] == $GLOBALS['_CFG']['integrate_code']) ? 1 : 0;
            }

            $collection = collect($modules);
            $sorted = $collection->sortByDesc('installed');
            $modules = $sorted->values()->all();

            $allow_set_points = 0; // $GLOBALS['_CFG']['integrate_code'] == 'dscmall' ? 0 : 1;

            $this->smarty->assign('allow_set_points', $allow_set_points);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['06_list_integrate']);
            $this->smarty->assign('modules', $modules);

            return $this->smarty->display('integrates_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 安装        会员数据整合插件
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'install') {
            admin_priv('integrate_users');

            if ($_GET['code'] == 'dscmall' || $_GET['code'] == 'ecjia') {
                $integrate_config = serialize([
                    'uc_id' => 'uc_id',
                    'uc_key' => 'uc_key'
                ]);
                $data = ['value' => $_GET['code']];
                ShopConfig::where('code', 'integrate_code')->update($data);
                $data = ['value' => $integrate_config];
                ShopConfig::where('code', 'integrate_config')->update($data);
                $data = ['value' => ''];
                ShopConfig::where('code', 'points_rule')->update($data);

                /* 清除系统设置 */
                $this->baseRepository->getCacheForgetlist(['shop_config']);

                $links[0]['text'] = $GLOBALS['_LANG']['go_back'];
                $links[0]['href'] = 'integrate.php?act=list';
                return sys_msg($GLOBALS['_LANG']['update_success'], 0, $links);
            } else {
                // 如果有标记，清空标记
                $data = [
                    'flag' => 0,
                    'alias' => ''
                ];
                Users::where('flag', '>', 0)->update($data);

                $cfg = require plugin_path('Integrates/' . Str::studly($_GET['code']) . '/config.php');
                $cfg['integrate_url'] = "http://";
                /* 判断 */
                $this->smarty->assign('cfg', $cfg);
                $this->smarty->assign('save', 0);
                $this->smarty->assign('set_list', get_charset_list());
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['integrate_setup']);
                $this->smarty->assign('code', $_GET['code']);
                return $this->smarty->display('integrates_setup.dwt');
            }
        }

        /*------------------------------------------------------ */
        //-- 设置会员数据整合插件
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'setup') {
            admin_priv('integrate_users', '');

            if ($_GET['code'] == 'dscmall') {
                return sys_msg($GLOBALS['_LANG']['need_not_setup']);
            } else {
                $cfg = unserialize($GLOBALS['_CFG']['integrate_config']);

                $this->smarty->assign('save', 1);
                $this->smarty->assign('set_list', get_charset_list());
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['integrate_setup']);
                $this->smarty->assign('code', $_GET['code']);
                $this->smarty->assign('cfg', $cfg);
                return $this->smarty->display('integrates_setup.dwt');
            }
        }

        /*------------------------------------------------------ */
        //-- 保存UCenter填写的资料
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'save_uc_config') {
            $code = $_POST['code'];

            $cfg = unserialize($GLOBALS['_CFG']['integrate_config']);

            $plugin = 'App\\Plugins\\Integrates\\' . Str::studly($code) . '\\' . Str::studly($code);
            $_POST['cfg']['quiet'] = 1;
            $cls_user = new $plugin($_POST['cfg']);

            if ($cls_user->error) {
                /* 出错提示 */
                if ($cls_user->error == 1) {
                    return sys_msg($GLOBALS['_LANG']['error_db_msg']);
                } elseif ($cls_user->error == 2) {
                    return sys_msg($GLOBALS['_LANG']['error_table_exist']);
                } elseif ($cls_user->error == 1049) {
                    return sys_msg($GLOBALS['_LANG']['error_db_exist']);
                } else {
                    return sys_msg($cls_user->db->error());
                }
            }

            /* 合并数组，保存原值 */
            $cfg = array_merge($cfg, $_POST['cfg']);

            /* 直接保存修改 */
            if ($this->integrateManageService->saveIntegrateConfig($code, $cfg)) {
                return sys_msg($GLOBALS['_LANG']['save_ok'], 0, [['text' => $GLOBALS['_LANG']['06_list_integrate'], 'href' => 'integrate.php?act=list']]);
            } else {
                return sys_msg($GLOBALS['_LANG']['save_error'], 0, [['text' => $GLOBALS['_LANG']['06_list_integrate'], 'href' => 'integrate.php?act=list']]);
            }
        }

        /*------------------------------------------------------ */
        //-- 第一次保存UCenter安装的资料
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'save_uc_config_first') {
            $code = $_POST['code'];

            $plugin = 'App\\Plugins\\Integrates\\' . Str::studly($code) . '\\' . Str::studly($code);
            $_POST['cfg']['quiet'] = 1;
            $cls_user = new $plugin($_POST['cfg']);

            if ($cls_user->error) {
                /* 出错提示 */
                if ($cls_user->error == 1) {
                    return sys_msg($GLOBALS['_LANG']['error_db_msg']);
                } elseif ($cls_user->error == 2) {
                    return sys_msg($GLOBALS['_LANG']['error_table_exist']);
                } elseif ($cls_user->error == 1049) {
                    return sys_msg($GLOBALS['_LANG']['error_db_exist']);
                } else {
                    return sys_msg($cls_user->db->error());
                }
            }
            $ucconfig = array_pad(explode('|', $_POST['ucconfig']), 11, null);
            list($appauthkey, $appid, $ucdbhost, $ucdbname, $ucdbuser, $ucdbpw, $ucdbcharset, $uctablepre, $uccharset, $ucapi, $ucip) = $ucconfig;
            $uc_ip = !empty($ucip) ? $ucip : trim($_POST['uc_ip']);
            $uc_url = !empty($ucapi) ? $ucapi : trim($_POST['uc_url']);
            $cfg = [
                'uc_id' => $appid,
                'uc_key' => $appauthkey,
                'uc_url' => $uc_url,
                'uc_ip' => $uc_ip,
                'uc_connect' => 'post',
                'uc_charset' => $uccharset,
                'db_host' => $ucdbhost,
                'db_user' => $ucdbuser,
                'db_name' => $ucdbname,
                'db_pass' => $ucdbpw,
                'db_pre' => $uctablepre,
                'db_charset' => $ucdbcharset,
            ];
            /* 增加UC语言项 */
            $cfg['uc_lang'] = $GLOBALS['_LANG']['uc_lang'];

            /* 检测成功临时保存论坛配置参数 */
            session([
                'cfg' => $cfg,
                'code' => $code
            ]);

            /* 直接保存修改 */
            if (!empty($_POST['save'])) {
                if ($this->integrateManageService->saveIntegrateConfig($code, $cfg)) {
                    return sys_msg($GLOBALS['_LANG']['save_ok'], 0, [['text' => $GLOBALS['_LANG']['06_list_integrate'], 'href' => 'integrate.php?act=list']]);
                } else {
                    return sys_msg($GLOBALS['_LANG']['save_error'], 0, [['text' => $GLOBALS['_LANG']['06_list_integrate'], 'href' => 'integrate.php?act=list']]);
                }
            }

            $data = $this->db->getRow("SHOW TABLE STATUS LIKE '" . $GLOBALS['dsc']->prefix . 'users' . "'");
            if ($data["Auto_increment"]) {
                $maxuid = $data["Auto_increment"] - 1;
            } else {
                $maxuid = 0;
            }

            /* 保存完成整合 */
            $this->integrateManageService->saveIntegrateConfig($code, $cfg);

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['ucenter_import_username']);
            $this->smarty->assign('user_startid_intro', sprintf($GLOBALS['_LANG']['user_startid_intro'], $maxuid, $maxuid));
            return $this->smarty->display('integrates_uc_import.dwt');
        }

        /*------------------------------------------------------ */
        //-- 保存UCenter设置
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'setup_ucenter') {
            $result = ['error' => 0, 'message' => ''];

            $app_type = 'Dscmall';
            $app_name = ShopConfig::where('code', 'shop_name')->value('value');
            $app_name = $app_name ? $app_name : '';
            $app_url = $this->dsc->url();
            $app_charset = EC_CHARSET;
            $app_dbcharset = strtolower((str_replace('-', '', EC_CHARSET)));
            $ucapi = !empty($_POST['ucapi']) ? trim($_POST['ucapi']) : '';
            $ucip = !empty($_POST['ucip']) ? trim($_POST['ucip']) : '';
            $dns_error = false;
            if (!$ucip) {
                $temp = parse_url($ucapi);
                $ucip = isset($temp['host']) ? gethostbyname($temp['host']) : false;
                if (ip2long($ucip) == -1 || ip2long($ucip) === false) {
                    $dns_error = true;
                }
            }
            if ($dns_error) {
                $result['error'] = 2;
                $result['message'] = '';
                return response()->json($result);
            }

            $ucfounderpw = trim($_POST['ucfounderpw']);
            $postdata = "m=app&a=add&ucfounder=&ucfounderpw=" . urlencode($ucfounderpw) . "&apptype=" . urlencode($app_type) .
                "&appname=" . urlencode($app_name) . "&appurl=" . urlencode($app_url) . "&appip=&appcharset=" . $app_charset .
                '&appdbcharset=' . $app_dbcharset;
            $t = new Transport();
            $ucconfig = $t->request($ucapi . '/index.php', $postdata);
            $ucconfig = $ucconfig['body'];
            if (empty($ucconfig)) {
                //ucenter 验证失败
                $result['error'] = 1;
                $result['message'] = $GLOBALS['_LANG']['uc_msg_verify_failur'];
            } elseif ($ucconfig == '-1') {
                //管理员密码无效
                $result['error'] = 1;
                $result['message'] = $GLOBALS['_LANG']['uc_msg_password_wrong'];
            } else {
                list($appauthkey, $appid) = explode('|', $ucconfig);
                if (empty($appauthkey) || empty($appid)) {
                    //ucenter 安装数据错误
                    $result['error'] = 1;
                    $result['message'] = $GLOBALS['_LANG']['uc_msg_data_error'];
                } else {
                    $result['error'] = 0;
                    $result['message'] = $ucconfig;
                }
            }

            return response()->json($result);
        }

        /* 显示整合成功信息 */
        if ($_REQUEST['act'] == 'complete') {
            return sys_msg($GLOBALS['_LANG']['sync_ok'], 0, [['text' => $GLOBALS['_LANG']['06_list_integrate'], 'href' => 'integrate.php?act=list']]);
        }
    }
}
