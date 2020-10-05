<?php

namespace App\Modules\Stores\Controllers;

use App\Libraries\CaptchaVerify;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\SessionRepository;
use App\Services\Common\CommonManageService;
use App\Services\Store\StoreManageService;

/**
 * 管理员信息以及权限管理程序
 */
class PrivilegeController extends InitController
{
    protected $commonManageService;
    protected $config;
    protected $storeManageService;
    protected $sessionRepository;
    protected $dscRepository;

    public function __construct(
        CommonManageService $commonManageService,
        StoreManageService $storeManageService,
        SessionRepository $sessionRepository,
        DscRepository $dscRepository
    )
    {
        // 验证密码路由限制1分钟3次
        if (request()->input('act') == 'signin') {
            $this->middleware('throttle:3');
        }
        $this->commonManageService = $commonManageService;
        $this->storeManageService = $storeManageService;
        $this->sessionRepository = $sessionRepository;
        $this->dscRepository = $dscRepository;
        $this->config = $this->dscRepository->dscConfig();
    }

    public function index()
    {

        /* act操作项的初始化 */
        $act = request()->input('act', 'login');
        $step = request()->input('step', '');

        $this->smarty->assign('seller', 1);
        $php_self = $this->commonManageService->getPhpSelf(1);
        $this->smarty->assign('php_self', $php_self);

        /*------------------------------------------------------ */
        //-- 登陆界面
        /*------------------------------------------------------ */
        if ($act == 'login') {
            header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
            header("Cache-Control: no-cache, must-revalidate");
            header("Pragma: no-cache");

            $dsc_token = get_dsc_token();
            $this->smarty->assign('dsc_token', $dsc_token);

            $stores_login_logo = strstr($this->config['stores_login_logo'], "images");
            $this->smarty->assign('stores_login_logo', $stores_login_logo);

            if (!empty($step) && $step == 'captcha') {
                $captcha_width = isset($this->config['captcha_width']) ? $this->config['captcha_width'] : 120;
                $captcha_height = isset($this->config['captcha_height']) ? $this->config['captcha_height'] : 36;
                $captcha_font_size = isset($this->config['captcha_font_size']) ? $this->config['captcha_font_size'] : 18;
                $captcha_length = isset($this->config['captcha_length']) ? $this->config['captcha_length'] : 4;

                $code_config = [
                    'imageW' => $captcha_width, //验证码图片宽度
                    'imageH' => $captcha_height, //验证码图片高度
                    'fontSize' => $captcha_font_size, //验证码字体大小
                    'length' => $captcha_length, //验证码位数
                    'useNoise' => false, //关闭验证码杂点
                ];

                $code_config['seKey'] = 'admin_login';
                $verify = new CaptchaVerify($code_config);
                return $verify->entry();
            }

            if ((intval($this->config['captcha']) & CAPTCHA_ADMIN) && gd_version() > 0) {
                $this->smarty->assign('gd_version', gd_version());
                $this->smarty->assign('random', mt_rand());
            }

            return $this->smarty->display('login.dwt');
        }

        /*------------------------------------------------------ */
        //-- 验证登陆信息
        /*------------------------------------------------------ */
        elseif ($act == 'signin') {
            $_POST = get_request_filter($_POST, 1);

            $_POST['username'] = isset($_POST['stores_user']) && !empty($_POST['stores_user']) ? addslashes($_POST['stores_user']) : '';
            $password = isset($_POST['stores_pwd']) && !empty($_POST['stores_pwd']) ? addslashes($_POST['stores_pwd']) : '';
            $_POST['username'] = !empty($_POST['username']) ? str_replace(["=", " "], '', $_POST['username']) : '';
            $username = !empty($_POST['username']) ? $_POST['username'] : addslashes($_POST['username']);

            if ((intval($this->config['captcha']) & CAPTCHA_ADMIN) && gd_version() > 0) {

                /* 检查验证码是否正确 */
                $captcha = isset($_POST['captcha']) ? trim($_POST['captcha']) : '';

                $verify = app(CaptchaVerify::class);
                $captcha_code = $verify->check($captcha, 'admin_login');

                if (!$captcha_code) {
                    return make_json_response('', 0, $GLOBALS['_LANG']['captcha_error']);
                }
            }

            // 检查门店会员
            $row = $this->storeManageService->storeUser($username, $password);

            if ($row) {
                // 登录成功
                set_admin_session($row['id'], $row['stores_user'], $row['store_id']);

                $this->commonManageService->updateLoginStatus(session('store_login_hash'), 'store'); // 插入登录状态

                if (empty($row['ec_salt'])) {
                    // 更新门店会员信息
                    $store_user_id = session('store_user_id');
                    $ec_salt = rand(1, 9999);
                    $new_stores_pwd = md5(md5($password) . $ec_salt);
                    $updata = [
                        'ec_salt' => $ec_salt,
                        'stores_pwd' => $new_stores_pwd
                    ];
                    $this->storeManageService->updateStoreUser($store_user_id, $updata);
                }

                // 清除购物车中过期的数据
                $this->clear_cart();
                return make_json_response('', 1, '登陆成功', ['url' => 'index.php']);
            } else {
                return make_json_response('', 0, $GLOBALS['_LANG']['login_faild']);
            }
        }
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
