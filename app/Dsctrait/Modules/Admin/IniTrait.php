<?php

namespace App\Dsctrait\Modules\Admin;

use App\Libraries\Error;
use App\Libraries\Mysql;
use App\Libraries\Shop;
use App\Libraries\Template;
use App\Libraries\Transport;
use App\Models\AdminUser;
use App\Models\ShopConfig;
use App\Repositories\Common\CommonRepository;
use App\Repositories\Common\SessionRepository;
use Illuminate\Support\Str;

/**
 * 管理中心公用文件
 */
define('ECS_ADMIN', true);

trait IniTrait
{
    public $action_list = '';

    protected function initialize()
    {
        $php_self = Str::snake(basename($this->getCurrentControllerName(), 'Controller'));
        defined('PHP_SELF') or define('PHP_SELF', '/' . ADMIN_PATH . '/' . $php_self . '.php');

        $_GET = request()->query() + request()->route()->parameters();
        $_POST = request()->post();
        $_REQUEST = $_GET + $_POST;
        $_REQUEST['act'] = isset($_REQUEST['act']) ? $_REQUEST['act'] : '';

        load_helper(['time', 'base', 'common', 'main', 'scws', 'ecmoban', 'function', 'publicfunc', 'commission']);
        load_helper(['main'], 'admin');

        /* 对用户传入的变量进行转义操作。*/
        if (!empty($_GET)) {
            $_GET = addslashes_deep($_GET);
        }
        if (!empty($_POST)) {
            $_POST = addslashes_deep($_POST);
        }

        $_REQUEST = addslashes_deep($_REQUEST);

        /* 创建 SHOP 对象 */
        $this->dsc = $GLOBALS['dsc'] = new Shop();
        defined('DATA_DIR') or define('DATA_DIR', $this->dsc->data_dir());
        defined('IMAGE_DIR') or define('IMAGE_DIR', $this->dsc->image_dir());

        /* 初始化数据库类 */
        $this->db = $GLOBALS['db'] = new Mysql();

        /* 创建错误处理对象 */
        $this->err = $GLOBALS['err'] = new Error();

        $config = cache('shop_config');
        $config = !is_null($config) ? $config : false;
        if ($config === false) {
            $config = app(\App\Services\Common\ConfigService::class)->getConfig();
        }

        /* 载入系统参数 */
        $GLOBALS['_CFG'] = $config;
        config(['shop' => $GLOBALS['_CFG']]);

        /* 初始化session */
        app(SessionRepository::class)->sessionRepy('ECSCP_ID');

        /* 初始化 action */
        if (!isset($_REQUEST['act'])) {
            $_REQUEST['act'] = '';
        } elseif (($_REQUEST['act'] == 'login' || $_REQUEST['act'] == 'logout' || $_REQUEST['act'] == 'signin') && strpos(PHP_SELF, 'privilege.php') === false) {
            $_REQUEST['act'] = '';
        } elseif (($_REQUEST['act'] == 'forget_pwd' || $_REQUEST['act'] == 'reset_pwd' || $_REQUEST['act'] == 'get_pwd') && strpos(PHP_SELF, 'get_password.php') === false) {
            $_REQUEST['act'] = '';
        }

        load_lang(['common', 'log_action', basename(PHP_SELF, '.php')], 'admin');

        clearstatcache();

        /* 如果有新版本，升级 */
        if (!isset($GLOBALS['_CFG']['dsc_version'])) {
            $GLOBALS['_CFG']['dsc_version'] = 'v1.0';
        }

        defined('__ROOT__') or define('__ROOT__', url('/') . '/');
        defined('__PUBLIC__') or define('__PUBLIC__', asset('/assets'));
        defined('__TPL__') or define('__TPL__', asset('/assets/admin'));
        defined('__STORAGE__') or define('__STORAGE__', __ROOT__ . "storage");

        /* 创建 Smarty 对象。 */
        $this->smarty = $GLOBALS['smarty'] = new Template();

        $template_dir = app_path('Modules/Admin/Views');
        $this->smarty->template_dir = $template_dir;
        $this->smarty->compile_dir = storage_path('framework/temp/compiled/admin');
        if (config('app.debug')) {
            $this->smarty->force_compile = true;
        }

        $this->smarty->assign('lang', $GLOBALS['_LANG']);
        $this->smarty->assign('cfg_lang', $GLOBALS['_CFG']['lang']);
        $this->smarty->assign('help_open', $GLOBALS['_CFG']['help_open']);

        if (isset($GLOBALS['_CFG']['enable_order_check'])) {  // 为了从旧版本顺利升级到2.5.0
            $this->smarty->assign('enable_order_check', $GLOBALS['_CFG']['enable_order_check']);
        } else {
            $this->smarty->assign('enable_order_check', 0);
        }

        /* 验证通行证信息 */
        if (isset($_GET['ent_id']) && isset($_GET['ent_ac']) && isset($_GET['ent_sign']) && isset($_GET['ent_email'])) {
            $ent_id = addslashes(trim($_GET['ent_id']));
            $ent_ac = addslashes(trim($_GET['ent_ac']));
            $ent_sign = addslashes(trim($_GET['ent_sign']));
            $ent_email = addslashes(trim($_GET['ent_email']));
            $certificate_id = addslashes(trim($GLOBALS['_CFG']['certificate_id']));
            $domain_url = $this->dsc->url();
            $token = addslashes($_GET['token']);
            if ($token == md5(md5($GLOBALS['_CFG']['token']) . $domain_url . ADMIN_PATH)) {
                $t = new Transport('-1', 5);
                $apiget = "act=ent_sign&ent_id= $ent_id & certificate_id=$certificate_id";

                $t->request('', $apiget);

                $configArr = [
                    'ent_id' => $ent_id,
                    'ent_ac' => $ent_ac,
                    'ent_sign' => $ent_sign,
                    'ent_email' => $ent_email,
                ];

                foreach ($configArr as $k => $v) {
                    ShopConfig::where('code', $k)
                        ->update([
                            'value' => $v
                        ]);
                }

                clear_cache_files();
                return dsc_header("Location: index.php\n")->send();
            }
        }

        /* 验证管理员身份 */
        if ((!session()->has('admin_id') || intval(session('admin_id')) <= 0) &&
            $_REQUEST['act'] != 'login' && $_REQUEST['act'] != 'signin' &&
            $_REQUEST['act'] != 'forget_pwd' && $_REQUEST['act'] != 'reset_pwd' && $_REQUEST['act'] != 'check_order'
        ) {
            if (!empty($_REQUEST['is_ajax'])) {
                return make_json_error($GLOBALS['_LANG']['priv_error']);
            } else {
                return dsc_header("Location: privilege.php?act=login\n")->send();
            }
        }

        $this->smarty->assign('token', $GLOBALS['_CFG']['token']);

        if ($_REQUEST['act'] != 'login' && $_REQUEST['act'] != 'signin' &&
            $_REQUEST['act'] != 'forget_pwd' && $_REQUEST['act'] != 'reset_pwd' && $_REQUEST['act'] != 'check_order'
        ) {
            $admin_path = preg_replace('/:\d+/', '', $this->dsc->url()) . ADMIN_PATH;
            if (request()->server('HTTP_REFERER') && strpos(preg_replace('/:\d+/', '', request()->server('HTTP_REFERER')), $admin_path) === false) {
                if (!empty($_REQUEST['is_ajax'])) {
                    return make_json_error($GLOBALS['_LANG']['priv_error']);
                } else {
                    return dsc_header("Location: privilege.php?act=login\n")->send();
                }
            }
        }

        $admin_info = AdminUser::where('user_id', session('admin_id'))->first();
        $admin_info = $admin_info ? $admin_info->toArray() : [];

        $rs_id = $admin_info['rs_id'] ?? 0;
        $this->action_list = $admin_info['action_list'] ?? '';

        set_current_page();

        $letter = range('A', 'Z');
        $this->smarty->assign('letter', $letter);

        $this->smarty->assign('cat_belongs', $GLOBALS['_CFG']['cat_belongs']);
        $this->smarty->assign('brand_belongs', $GLOBALS['_CFG']['brand_belongs']);
        $this->smarty->assign('ecs_version', VERSION);

        $open = open_study();
        $this->smarty->assign('open', $open);

        $this->smarty->assign('admin_id', session('admin_id', 0));
        $this->smarty->assign('admin_name', session('admin_name', ''));

        $this->smarty->assign('rs_enabled', $GLOBALS['_CFG']['region_store_enabled']);

        if ($rs_id > 0) {
            $this->smarty->assign('rs_id', $rs_id);
            if ($GLOBALS['_CFG']['region_store_enabled']) {
                if ($rs_id) {
                    $_REQUEST['seller_order_list'] = 1;
                    $_REQUEST['seller_list'] = 1;
                } else {
                    $this->smarty->assign('region_store_list', get_region_store_list());
                }
            }
        }

        //判断供应链是否可用
        $supplierEnabled = app(CommonRepository::class)->judgeSupplierEnabled();
        $this->smarty->assign('supplier_enabled', $supplierEnabled);
    }
}
