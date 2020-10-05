<?php

namespace App\Modules\Admin\Controllers;

use App\Models\Users;
use App\Repositories\Common\TimeRepository;

class McUserController extends InitController
{
    protected $timeRepository;

    public function __construct(TimeRepository $timeRepository)
    {
        $this->timeRepository = $timeRepository;
    }

    public function index()
    {
        load_helper('function', 'admin');

        /* 检查权限 */
        admin_priv('users_manage');

        /* act操作项的初始化 */
        if (empty($_REQUEST['act'])) {
            $_REQUEST['act'] = 'list';
        } else {
            $_REQUEST['act'] = trim($_REQUEST['act']);
        }


        /*------------------------------------------------------ */
        //-- 批量写�        �
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'mc_add') {
            $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'mc_user.php'];
            //$upfile_flash
            $password = $_REQUEST['password'];
            $confirm_password = $_REQUEST['confirm_password'];

            if (!$password || $password != $confirm_password) {
                return sys_msg($GLOBALS['_LANG']['confirm_password_error'], 0, $link);
            }

            if (!$_FILES['upfile']) {
                return sys_msg($GLOBALS['_LANG']['not_upload_file_error'], 0, $link);
            }

            //文件上传
            $path = storage_public(DATA_DIR . "/mc_upfile/" . date("Ym") . "/");
            //上传,备份;
            $file_chk = uploadfile("upfile", $path, 'mc_user.php', 1024000, 'txt');
            if ($file_chk) {
                $filename = $path . $file_chk[0];
                //读取内容;
                $str = mc_read_txt($filename);
                //注册用户
                if ($str) {
                    $this->mc_reg_user($str, $password);
                } else {
                    return sys_msg($GLOBALS['_LANG']['read_file_error'], 0, $link);
                }

                return sys_msg($GLOBALS['_LANG']['batch_add_user_success'], 0, $link);
            } else {
                return sys_msg($GLOBALS['_LANG']['file_not_uplaod_success'], 0, $link);
            }
        }

        /*------------------------------------------------------ */
        //-- 操作界面
        /*------------------------------------------------------ */
        else {
            //示例文件，演示网页地址格式化
            $this->smarty->assign('demo', ['download' => asset('/') . 'storage/' . DATA_DIR . '/mc_upfile/user_list.zip', 'html' => asset('/') . 'storage/' . DATA_DIR . '/mc_upfile/user_list.html']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['03_users_list'], 'href' => 'users.php?act=list']);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['batch_add_user']);
            return $this->smarty->display('mc_user.dwt');
        }
    }

    private function mc_reg_user($str = '', $password = 'admin123')
    {
        if (!$str) {
            return false;
        }

        $str = get_preg_replace($str);
        $str_arr = explode(',', $str);

        //用户信息
        $password = md5($password);

        $time = $this->timeRepository->getGmTime();

        if ($str_arr) {
            for ($i = 0; $i < count($str_arr); $i++) {
                if (!empty($str_arr[$i])) {
                    $str_arr[$i] = explode("|", $str_arr[$i]);
                    $other = [
                        'user_name' => $str_arr[$i][0],
                        'password' => $password,
                        'email' => $str_arr[$i][1],
                        'msn' => $str_arr[$i][2],
                        'qq' => $str_arr[$i][3],
                        'office_phone' => $str_arr[$i][4],
                        'home_phone' => $str_arr[$i][5],
                        'mobile_phone' => $str_arr[$i][6],
                        'reg_time' => $time
                    ];

                    if ($other['mobile_phone']) {
                        $count = Users::where('user_name', $other['user_name'])
                            ->orWhere('mobile_phone', $other['mobile_phone'])
                            ->count();
                    }

                    //用户不存在时
                    if ($count <= 0) {
                        Users::insert($other);
                    }
                }
            }
        }
    }
}
