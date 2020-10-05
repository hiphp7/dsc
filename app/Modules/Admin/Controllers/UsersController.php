<?php

namespace App\Modules\Admin\Controllers;

use App\Libraries\Http;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Merchant\MerchantCommonService;
use App\Services\Order\OrderService;
use App\Services\Store\StoreCommonService;
use App\Services\User\UserRankService;
use App\Services\User\UserService;

/**
 * 会员管理程序
 */
class UsersController extends InitController
{
    protected $orderService;
    protected $merchantCommonService;
    protected $dscRepository;
    protected $storeCommonService;
    protected $userRankService;

    public function __construct(
        OrderService $orderService,
        MerchantCommonService $merchantCommonService,
        DscRepository $dscRepository,
        StoreCommonService $storeCommonService,
        UserRankService $userRankService
    )
    {
        $this->orderService = $orderService;
        $this->merchantCommonService = $merchantCommonService;
        $this->dscRepository = $dscRepository;
        $this->storeCommonService = $storeCommonService;
        $this->userRankService = $userRankService;
    }

    public function index()
    {

        //ecmoban模板堂 --zhuo start
        $adminru = get_admin_ru_id();
        if ($adminru['ru_id'] == 0) {
            $this->smarty->assign('priv_ru', 1);
        } else {
            $this->smarty->assign('priv_ru', 0);
        }
        //ecmoban模板堂 --zhuo end

        /*------------------------------------------------------ */
        //-- 用户帐号列表
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'list') {
            /* 检查权限 */
            admin_priv('users_manage');

            $this->smarty->assign('menu_select', ['action' => '08_members', 'current' => '03_users_list']);

            $rs = $this->userRankService->getUserRankList();

            $ranks = [];
            foreach ($rs as $row) {
                $ranks[$row['rank_id']] = $row['rank_name'];
            }
            $this->smarty->assign('user_ranks', $ranks);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['03_users_list']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['04_users_add'], 'href' => 'users.php?act=add']);

            //ecmoban模板堂 --zhuo start 会员导出
            $this->smarty->assign('action_link2', ['text' => $GLOBALS['_LANG']['12_users_export'], 'href' => 'javascript:download_userlist();']);
            //ecmoban模板堂 --zhuo end 会员导出

            $store_list = $this->storeCommonService->getCommonStoreList();
            $this->smarty->assign('store_list', $store_list);

            $user_list = $this->user_list();

            $this->smarty->assign('user_list', $user_list['user_list']);
            $this->smarty->assign('filter', $user_list['filter']);
            $this->smarty->assign('record_count', $user_list['record_count']);
            $this->smarty->assign('page_count', $user_list['page_count']);
            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('sort_user_id', '<img src="' . __TPL__ . '/images/sort_desc.gif">');


            return $this->smarty->display('users_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- ajax返回用户列表
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            $user_list = $this->user_list();

            $this->smarty->assign('user_list', $user_list['user_list']);
            $this->smarty->assign('filter', $user_list['filter']);
            $this->smarty->assign('record_count', $user_list['record_count']);
            $this->smarty->assign('page_count', $user_list['page_count']);

            $store_list = $this->storeCommonService->getCommonStoreList();
            $this->smarty->assign('store_list', $store_list);

            $sort_flag = sort_flag($user_list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result($this->smarty->fetch('users_list.dwt'), '', ['filter' => $user_list['filter'], 'page_count' => $user_list['page_count']]);
        }

        /*------------------------------------------------------ */
        //-- 添加会员帐号
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add') {
            /* 检查权限 */
            admin_priv('users_manage');

            $user = ['rank_points' => $GLOBALS['_CFG']['register_points'],
                'pay_points' => $GLOBALS['_CFG']['register_points'],
                'sex' => 0,
                'credit_line' => 0
            ];
            /* 取出注册扩展字段 */
            $sql = 'SELECT * FROM ' . $this->dsc->table('reg_fields') . ' WHERE type < 2 AND display = 1 ORDER BY dis_order, id';
            $extend_info_list = $this->db->getAll($sql);
            $this->smarty->assign('extend_info_list', $extend_info_list);
            /* 密码提示问题 */
            $this->smarty->assign('passwd_questions', $GLOBALS['_LANG']['passwd_questions']);

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['04_users_add']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['11_users_add'], 'href' => 'mc_user.php']);
            $this->smarty->assign('action_link2', ['text' => $GLOBALS['_LANG']['03_users_list'], 'href' => 'users.php?act=list']);
            $this->smarty->assign('form_action', 'insert');
            $this->smarty->assign('user', $user);
            $this->smarty->assign('special_ranks', get_rank_list(true));

            /*获取从1956年到先前的年月日数组*/
            $select_date = [];
            $select_date['year'] = range(1956, date('Y'));
            $select_date['month'] = range(1, 12);
            $select_date['day'] = range(1, 31);
            $this->smarty->assign("select_date", $select_date);


            return $this->smarty->display('user_add.dwt');
        }

        /*------------------------------------------------------ */
        //-- 添加会员帐号
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'insert') {
            /* 检查权限 */
            admin_priv('users_manage');
            $username = empty($_POST['username']) ? '' : trim($_POST['username']);
            $password = empty($_POST['password']) ? '' : trim($_POST['password']);
            $email = empty($_POST['email']) ? '' : trim($_POST['email']);
            $sex = empty($_POST['sex']) ? 0 : intval($_POST['sex']);
            $sex = in_array($sex, [0, 1, 2]) ? $sex : 0;
            $birthday = $_POST['birthdayYear'] . '-' . $_POST['birthdayMonth'] . '-' . $_POST['birthdayDay'];
            $rank = empty($_POST['user_rank']) ? 0 : intval($_POST['user_rank']);
            $credit_line = empty($_POST['credit_line']) ? 0 : floatval($_POST['credit_line']);
            $extend_field5 = empty($_POST['extend_field5']) ? 0 : htmlspecialchars(trim($_POST['extend_field5']));
            $user_registerMode = [
                'email' => $email,
                'register_mode' => 0,
                'mobile_phone' => isset($extend_field5) ? $extend_field5 : '',
            ];
            $sel_question = empty($_POST['sel_question']) ? '' : compile_str($_POST['sel_question']);
            $passwd_answer = isset($_POST['passwd_answer']) ? compile_str(trim($_POST['passwd_answer'])) : '';

            //验证手机号
            if (!empty($other['mobile_phone'])) {
                $sql = "SELECT user_id FROM " . $this->dsc->table('users') . " WHERE mobile_phone = '" . $other['mobile_phone'] . "'";
                if ($this->db->getOne($sql) > 0) {
                    return sys_msg($GLOBALS['_LANG']['mobile_phone_existed'], 1);
                }
            }

            $users = init_users();

            if (!$users->add_user($username, $password, $user_registerMode)) {

                /* 插入会员数据失败 */
                if ($users->error == ERR_INVALID_USERNAME) {
                    $msg = $GLOBALS['_LANG']['username_invalid'];
                } elseif ($users->error == ERR_USERNAME_NOT_ALLOW) {
                    $msg = $GLOBALS['_LANG']['username_not_allow'];
                } elseif ($users->error == ERR_USERNAME_EXISTS) {
                    $msg = $GLOBALS['_LANG']['username_exists'];
                } elseif ($users->error == ERR_INVALID_EMAIL) {
                    $msg = $GLOBALS['_LANG']['email_invalid'];
                } elseif ($users->error == ERR_EMAIL_NOT_ALLOW) {
                    $msg = $GLOBALS['_LANG']['email_not_allow'];
                } elseif ($users->error == ERR_EMAIL_EXISTS) {
                    $msg = $GLOBALS['_LANG']['email_exists'];
                } else {
                    //return 'Error:'.$users->error_msg();
                }
                return sys_msg($msg, 1);
            }

            /* 注册送积分 */
            if (!empty($GLOBALS['_CFG']['register_points'])) {
                log_account_change(session('user_id'), 0, 0, $GLOBALS['_CFG']['register_points'], $GLOBALS['_CFG']['register_points'], $GLOBALS['_LANG']['register_points']);
            }

            /*把新注册用户的扩展信息插入数据库*/
            $sql = 'SELECT id FROM ' . $this->dsc->table('reg_fields') . ' WHERE type = 0 AND display = 1 ORDER BY dis_order, id';   //读出所有扩展字段的id
            $fields_arr = $this->db->getAll($sql);

            $extend_field_str = '';    //生成扩展字段的内容字符串
            $user_id_arr = $users->get_profile_by_name($username);
            foreach ($fields_arr as $val) {
                $extend_field_index = 'extend_field' . $val['id'];
                if (!empty($_POST[$extend_field_index])) {
                    $temp_field_content = strlen($_POST[$extend_field_index]) > 100 ? mb_substr($_POST[$extend_field_index], 0, 99) : $_POST[$extend_field_index];
                    $extend_field_str .= " ('" . $user_id_arr['user_id'] . "', '" . $val['id'] . "', '" . $temp_field_content . "'),";
                }
            }
            $extend_field_str = substr($extend_field_str, 0, -1);

            if ($extend_field_str) {      //插入注册扩展数据
                $sql = 'INSERT INTO ' . $this->dsc->table('reg_extend_info') . ' (`user_id`, `reg_field_id`, `content`) VALUES' . $extend_field_str;
                $this->db->query($sql);
            }

            /* 更新会员的其它信息 */
            $other = [];
            $other['credit_line'] = $credit_line;
            $other['user_rank'] = $rank;
            $other['sex'] = $sex;
            $other['birthday'] = $birthday;
            $other['reg_time'] = local_strtotime(local_date('Y-m-d H:i:s'));

            $other['msn'] = isset($_POST['extend_field1']) ? htmlspecialchars(trim($_POST['extend_field1'])) : '';
            $other['qq'] = isset($_POST['extend_field2']) ? htmlspecialchars(trim($_POST['extend_field2'])) : '';
            $other['office_phone'] = isset($_POST['extend_field3']) ? htmlspecialchars(trim($_POST['extend_field3'])) : '';
            $other['home_phone'] = isset($_POST['extend_field4']) ? htmlspecialchars(trim($_POST['extend_field4'])) : '';
            $other['mobile_phone'] = isset($_POST['extend_field5']) ? htmlspecialchars(trim($_POST['extend_field5'])) : '';

            $other['passwd_question'] = $sel_question;
            $other['passwd_answer'] = $passwd_answer;

            $this->db->autoExecute($this->dsc->table('users'), $other, 'UPDATE', "user_name = '$username'");

            /* 记录管理员操作 */
            admin_log($_POST['username'], 'add', 'users');

            /* 提示信息 */
            $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'users.php?act=list'];
            return sys_msg(sprintf($GLOBALS['_LANG']['add_success'], htmlspecialchars(stripslashes($_POST['username']))), 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 编辑用户帐号
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'edit') {
            /* 检查权限 */
            admin_priv('users_manage');

            $user_id = isset($_GET['id']) && !empty($_GET['id']) ? intval($_GET['id']) : 0;

            $sql = "SELECT u.user_name, u.sex, u.birthday, u.pay_points, u.rank_points, u.user_rank , " .
                "u.user_money, u.frozen_money, u.credit_line, u.parent_id, u2.user_name as parent_username, u.qq, u.msn, u.office_phone, u.home_phone, u.mobile_phone, " .
                "u.question, u.answer, r.front_of_id_card, r.real_id, r.reverse_of_id_card " .
                " FROM " . $this->dsc->table('users') . " u LEFT JOIN " . $this->dsc->table('users') . " u2 ON u.parent_id = u2.user_id " .
                " LEFT JOIN " . $this->dsc->table('users_real') . " r ON u.user_id = r.user_id " .
                " WHERE u.user_id = '$user_id'";

            $row = $this->db->GetRow($sql);
            $row['user_name'] = addslashes($row['user_name']);
            $users = init_users();
            $user = $users->get_user_info($row['user_name']);

            $sql = "SELECT u.user_id, u.sex, u.birthday, u.pay_points, u.rank_points, u.user_rank , u.user_money, u.frozen_money, u.credit_line, u.parent_id, u2.user_name as parent_username, u.qq, u.msn,
            u.office_phone, u.home_phone, u.mobile_phone, u.email," .
                "u.passwd_question, u.passwd_answer, r.front_of_id_card, r.real_id, r.reverse_of_id_card" .
                " FROM " . $this->dsc->table('users') . " u LEFT JOIN " . $this->dsc->table('users') . " u2 ON u.parent_id = u2.user_id " .
                " LEFT JOIN " . $this->dsc->table('users_real') . " r ON u.user_id = r.user_id " .
                " WHERE u.user_id = '$user_id'";

            $row = $this->db->GetRow($sql);

            if ($row) {

                if (isset($GLOBALS['_CFG']['show_mobile']) && $GLOBALS['_CFG']['show_mobile'] == 0) {
                    $row['mobile_phone'] = $this->dscRepository->stringToStar($row['mobile_phone']);
                    $user['user_name'] = $this->dscRepository->stringToStar($user['user_name']);
                    $user['email'] = $this->dscRepository->stringToStar($user['email']);
                }

                $user['user_id'] = $row['user_id'];
                $user['sex'] = $row['sex'];
                $user['birthday'] = local_date($row['birthday']);
                if ($user['birthday']) {
                    $birthday = explode("-", $user['birthday']);
                    $user['year'] = intval($birthday[0]);
                    $user['month'] = intval($birthday[1]);
                    $user['day'] = intval($birthday[2]);
                }
                $user['pay_points'] = $row['pay_points'];
                $user['rank_points'] = $row['rank_points'];
                $user['user_rank'] = $row['user_rank'];
                $user['user_money'] = $row['user_money'];
                $user['frozen_money'] = $row['frozen_money'];
                $user['credit_line'] = $row['credit_line'];
                $user['formated_user_money'] = price_format($row['user_money']);
                $user['formated_frozen_money'] = price_format($row['frozen_money']);
                $user['parent_id'] = $row['parent_id'];
                $user['parent_username'] = $row['parent_username'];
                $user['qq'] = $row['qq'];
                $user['msn'] = $row['msn'];
                $user['office_phone'] = $row['office_phone'];
                $user['home_phone'] = $row['home_phone'];
                $user['mobile_phone'] = $row['mobile_phone'];
                $user['passwd_question'] = $row['passwd_question'];
                $user['passwd_answer'] = $row['passwd_answer'];
                $user['front_of_id_card'] = get_image_path($row['front_of_id_card']);
                $user['reverse_of_id_card'] = get_image_path($row['reverse_of_id_card']);
                $user['real_id'] = $row['real_id'];
                $user['reg_time_format'] = app(TimeRepository::class)->getLocalDate("Y-m-d H:i:s", $user['reg_time']);
            } else {
                $links[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'users.php?act=list'];
                return sys_msg($GLOBALS['_LANG']['username_invalid'], 0, $links);
            }

            /* 密码提示问题 */
            $this->smarty->assign('passwd_questions', $GLOBALS['_LANG']['passwd_questions']);

            /* 取出注册扩展字段 */
            $sql = 'SELECT * FROM ' . $this->dsc->table('reg_fields') . ' WHERE type < 2 AND display = 1 ORDER BY dis_order, id';
            $extend_info_list = $this->db->getAll($sql);

            $sql = 'SELECT reg_field_id, content ' .
                'FROM ' . $this->dsc->table('reg_extend_info') .
                " WHERE user_id = $user[user_id]";
            $extend_info_arr = $this->db->getAll($sql);

            $temp_arr = [];
            foreach ($extend_info_arr as $val) {
                $temp_arr[$val['reg_field_id']] = $val['content'];
            }

            foreach ($extend_info_list as $key => $val) {
                switch ($val['id']) {
                    case 1:
                        $extend_info_list[$key]['content'] = $user['msn'];
                        break;
                    case 2:
                        $extend_info_list[$key]['content'] = $user['qq'];
                        break;
                    case 3:
                        $extend_info_list[$key]['content'] = $user['office_phone'];
                        break;
                    case 4:
                        $extend_info_list[$key]['content'] = $user['home_phone'];
                        break;
                    case 5:
                        $extend_info_list[$key]['content'] = $user['mobile_phone'];
                        break;
                    default:
                        $extend_info_list[$key]['content'] = empty($temp_arr[$val['id']]) ? '' : $temp_arr[$val['id']];
                }
            }

            $this->smarty->assign('extend_info_list', $extend_info_list);

            /* 当前会员推荐信息 */
            $affiliate = $GLOBALS['_CFG']['affiliate'] ? unserialize($GLOBALS['_CFG']['affiliate']) : [];
            $this->smarty->assign('affiliate', $affiliate);

            empty($affiliate) && $affiliate = [];

            if ($affiliate) {
                if (empty($affiliate['config']['separate_by'])) {

                    //推荐注册分成
                    $affdb = [];
                    $num = $affiliate['item'] ? count($affiliate['item']) : 0;
                    $up_uid = "'$user_id'";

                    if ($num) {
                        for ($i = 1; $i <= $num; $i++) {
                            $count = 0;
                            if ($up_uid) {
                                $sql = "SELECT user_id FROM " . $this->dsc->table('users') . " WHERE parent_id IN($up_uid)";
                                $query = $this->db->query($sql);
                                $up_uid = '';
                                foreach ($query as $rt) {
                                    $up_uid .= $up_uid ? ",'$rt[user_id]'" : "'$rt[user_id]'";
                                    $count++;
                                }
                            }
                            $affdb[$i]['num'] = $count;
                        }

                        if (isset($affdb[1]['num']) && $affdb[1]['num'] > 0) {
                            $this->smarty->assign('affdb', $affdb);
                        }
                    }
                }
            }

            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('action_link2', ['text' => $GLOBALS['_LANG']['03_users_list'], 'href' => 'users.php?act=list']);
            /*获取从1956年到先前的年月日数组*/
            $select_date = [];
            $select_date['year'] = range(1956, date('Y'));
            $select_date['month'] = range(1, 12);
            $select_date['day'] = range(1, 31);
            $this->smarty->assign("select_date", $select_date);
            $this->smarty->assign("user_id", $user['user_id']);

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['users_edit']);
            $this->smarty->assign('user', $user);
            $this->smarty->assign('form_action', 'update');
            $this->smarty->assign('special_ranks', get_rank_list(true));
            return $this->smarty->display('user_list_edit.dwt');
        }

        /*------------------------------------------------------ */
        //-- 更新用户帐号
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'update') {
            /* 检查权限 */
            admin_priv('users_manage');
            $username = empty($_POST['username']) ? '' : trim($_POST['username']);
            $password = empty($_POST['password']) ? '' : trim($_POST['password']);
            $email = empty($_POST['email']) ? '' : trim($_POST['email']);
            $sex = empty($_POST['sex']) ? 0 : intval($_POST['sex']);
            $sex = in_array($sex, [0, 1, 2]) ? $sex : 0;
            $birthdayDay = isset($_POST['birthdayDay']) ? intval($_POST['birthdayDay']) : 00;
            $birthdayDay = (strlen($birthdayDay) == 1) ? "0" . $birthdayDay : $birthdayDay;

            $birthdayMonth = isset($_POST['birthdayMonth']) ? intval($_POST['birthdayMonth']) : 00;
            $birthdayMonth = (strlen($birthdayMonth) == 1) ? "0" . $birthdayMonth : $birthdayMonth;

            $birthday = $_POST['birthdayYear'] . '-' . $birthdayMonth . '-' . $birthdayDay;
            $rank = empty($_POST['user_rank']) ? 0 : intval($_POST['user_rank']);
            $credit_line = empty($_POST['credit_line']) ? 0 : floatval($_POST['credit_line']);
            $id = empty($_POST['id']) ? 0 : intval($_POST['id']);

            $sel_question = empty($_POST['sel_question']) ? '' : compile_str($_POST['sel_question']);
            $passwd_answer = isset($_POST['passwd_answer']) ? compile_str(trim($_POST['passwd_answer'])) : '';

            $users = init_users();

            if (!$users->edit_user(['user_id' => $id, 'username' => $username, 'password' => $password, 'email' => $email, 'gender' => $sex, 'bday' => $birthday], 1)) {
                if ($users->error == ERR_EMAIL_EXISTS) {
                    $msg = $GLOBALS['_LANG']['email_exists'];
                } else {
                    $msg = $GLOBALS['_LANG']['edit_user_failed'];
                }
                return sys_msg($msg, 1);
            }
            if (!empty($password)) {
                $sql = "UPDATE " . $this->dsc->table('users') . "SET `ec_salt`='0' WHERE user_name= '" . $username . "'";
                $this->db->query($sql);
            }
            /* 更新用户扩展字段的数据 */
            $sql = 'SELECT id FROM ' . $this->dsc->table('reg_fields') . ' WHERE type = 0 AND display = 1 ORDER BY dis_order, id';   //读出所有扩展字段的id
            $fields_arr = $this->db->getAll($sql);
            $user_id_arr = $users->get_profile_by_name($username);
            $user_id = $user_id_arr['user_id'];

            foreach ($fields_arr as $val) {       //循环更新扩展用户信息
                $extend_field_index = 'extend_field' . $val['id'];
                if (isset($_POST[$extend_field_index])) {
                    $temp_field_content = strlen($_POST[$extend_field_index]) > 100 ? mb_substr($_POST[$extend_field_index], 0, 99) : $_POST[$extend_field_index];

                    $sql = 'SELECT * FROM ' . $this->dsc->table('reg_extend_info') . "  WHERE reg_field_id = '$val[id]' AND user_id = '$user_id'";
                    if ($this->db->getOne($sql)) {      //如果之前没有记录，则插入
                        $sql = 'UPDATE ' . $this->dsc->table('reg_extend_info') . " SET content = '$temp_field_content' WHERE reg_field_id = '$val[id]' AND user_id = '$user_id'";
                    } else {
                        $sql = 'INSERT INTO ' . $this->dsc->table('reg_extend_info') . " (`user_id`, `reg_field_id`, `content`) VALUES ('$user_id', '$val[id]', '$temp_field_content')";
                    }
                    $this->db->query($sql);
                }
            }


            /* 更新会员的其它信息 */
            $other = [];
            $other['credit_line'] = $credit_line;
            $other['user_rank'] = $rank;

            $other['msn'] = isset($_POST['extend_field1']) ? htmlspecialchars(trim($_POST['extend_field1'])) : '';
            $other['qq'] = isset($_POST['extend_field2']) ? htmlspecialchars(trim($_POST['extend_field2'])) : '';
            $other['office_phone'] = isset($_POST['extend_field3']) ? htmlspecialchars(trim($_POST['extend_field3'])) : '';
            $other['home_phone'] = isset($_POST['extend_field4']) ? htmlspecialchars(trim($_POST['extend_field4'])) : '';
            $other['mobile_phone'] = isset($_POST['extend_field5']) ? htmlspecialchars(trim($_POST['extend_field5'])) : '';

            $other['passwd_question'] = $sel_question;
            $other['passwd_answer'] = $passwd_answer;

            //验证手机是否存在
            if (!empty($other['mobile_phone'])) {
                $sql = "SELECT user_id FROM " . $this->dsc->table('users') . " WHERE mobile_phone = '$other[mobile_phone]' AND user_id != '$id'";
                if ($this->db->getOne($sql) > 0) {
                    return sys_msg(lang('admin/users.iphone_exist'), 1);
                }
            }

            //获取旧的数据
            $old_user['old_email'] = empty($_POST['old_email']) ? '' : trim($_POST['old_email']);
            $old_user['old_user_rank'] = empty($_POST['user_rank']) ? 0 : intval($_POST['user_rank']);
            $old_user['old_sex'] = empty($_POST['old_sex']) ? 0 : intval($_POST['old_sex']);
            $old_user['old_birthday'] = empty($_POST['old_birthday']) ? '' : trim($_POST['old_birthday']);
            $old_user['old_credit_line'] = empty($_POST['old_credit_line']) ? 0 : floatval($_POST['old_credit_line']);
            $old_user['old_msn'] = isset($_POST['old_extend_field1']) ? htmlspecialchars(trim($_POST['old_extend_field1'])) : '';
            $old_user['old_qq'] = isset($_POST['old_extend_field2']) ? htmlspecialchars(trim($_POST['old_extend_field2'])) : '';
            $old_user['old_office_phone'] = isset($_POST['old_extend_field3']) ? htmlspecialchars(trim($_POST['old_extend_field3'])) : '';
            $old_user['old_home_phone'] = isset($_POST['old_extend_field4']) ? htmlspecialchars(trim($_POST['old_extend_field4'])) : '';
            $old_user['old_mobile_phone'] = isset($_POST['old_extend_field5']) ? htmlspecialchars(trim($_POST['old_extend_field5'])) : '';
            $old_user['old_passwd_answer'] = isset($_POST['old_passwd_answer']) ? compile_str(trim($_POST['old_passwd_answer'])) : '';
            $old_user['old_sel_question'] = empty($_POST['old_sel_question']) ? '' : compile_str($_POST['old_sel_question']);
            $old_user['password'] = $password;
            load_helper('ipCity');
            $new_user = $other;
            $new_user['email'] = $email;
            $new_user['sex'] = $sex;
            $new_user['birthday'] = $birthday;
            users_log_change_type($old_user, $new_user, $id);

            if ($id > 0) {
                $this->db->autoExecute($this->dsc->table('users'), $other, 'UPDATE', "user_id = '$id'");
            } else {
                $this->db->autoExecute($this->dsc->table('users'), $other, 'UPDATE', "user_name = '$username'");
            }

            /* 记录管理员操作 */
            admin_log($username, 'edit', 'users');

            /* 提示信息 */
            $links[0]['text'] = $GLOBALS['_LANG']['goto_list'];
            $links[0]['href'] = 'users.php?act=list&' . list_link_postfix();
            $links[1]['text'] = $GLOBALS['_LANG']['go_back'];
            $links[1]['href'] = 'javascript:history.back()';

            return sys_msg($GLOBALS['_LANG']['update_success'], 0, $links);
        }

        /*------------------------------------------------------ */
        //-- 切换是否验证
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'toggle_is_validated') {
            $check_auth = check_authz_json('users_manage');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $val = intval($_POST['val']);

            if ($this->user_update($id, ['is_validated' => $val]) != false) {
                clear_cache_files();
                return make_json_result($val);
            } else {
                return make_json_error($this->db->error());
            }
        }

        /*------------------------------------------------------ */
        //-- 批量删除会员帐号
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'batch_remove') {
            /* 检查权限 */
            admin_priv('users_drop');

            if (isset($_POST['checkboxes'])) {
                /* 只有超级管理员才可以删除商家会员 by wu */
                $priv_str = $this->db->getOne("SELECT action_list FROM " . $this->dsc->table('admin_user') . " WHERE user_id = '" . session('admin_id') . "'");
                if ($priv_str != 'all') {
                    foreach ($_POST['checkboxes'] as $key => $val) {
                        $sql = "SELECT id FROM " . $this->dsc->table('seller_shopinfo') . " WHERE ru_id = '$val'";
                        $shopinfo = $this->db->getOne($sql);
                        if (!empty($shopinfo)) {
                            unset($_POST['checkboxes'][$key]);
                        }
                    }
                }

                $sql = "SELECT user_name FROM " . $this->dsc->table('users') . " WHERE user_id " . db_create_in($_POST['checkboxes']);
                $col = $this->db->getCol($sql);
                $usernames = implode(',', addslashes_deep($col));
                $count = count($col);
                /* 通过插件来删除用户 */
                $users = init_users();
                $users->remove_user($col);

                admin_log($usernames, 'batch_remove', 'users');

                $lnk[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'users.php?act=list'];
                return sys_msg(sprintf($GLOBALS['_LANG']['batch_remove_success'], $count), 0, $lnk);
            } else {
                $lnk[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'users.php?act=list'];
                return sys_msg($GLOBALS['_LANG']['no_select_user'], 0, $lnk);
            }
        } elseif ($_REQUEST['act'] == 'main_user') {
            load_helper('base');
            load_helper('order');
            $data = read_static_cache('main_user_str');

            if ($data === false) {
                $ecs_version = VERSION;
                $ecs_lang = $GLOBALS['_CFG']['lang'];
                $ecs_release = RELEASE;
                $php_ver = PHP_VERSION;
                $mysql_ver = $this->db->version();
                $ecs_charset = strtoupper(EC_CHARSET);

                $scount = $this->db->getOne('SELECT COUNT(*) FROM ' . $this->dsc->table('seller_shopinfo')); //会员数量
                $sql = 'SELECT COUNT(*) AS oCount, ' . "SUM(" . $this->orderService->orderAmountField('o.') . ") AS oAmount" . ' FROM ' . $this->dsc->table('order_info') . " AS o WHERE o.main_count = 0 LIMIT 1";
                $order['stats'] = $this->db->getRow($sql);
                $ocount = $order['stats']['oCount']; //订单数量
                $oamount = $order['stats']['oAmount']; //总销售金额

                $goods['total'] = $this->db->GetOne('SELECT COUNT(*) FROM ' . $this->dsc->table('goods') .
                    ' WHERE is_delete = 0 AND is_alone_sale = 1 AND is_real = 1');
                $gcount = $goods['total']; //商品数量
                $ecs_user = $this->db->getOne('SELECT COUNT(*) FROM ' . $this->dsc->table('users')); //会员数量

                $ecs_template = $this->db->getOne('SELECT value FROM ' . $this->dsc->table('shop_config') . ' WHERE code = \'template\''); //当前使用模板
                $style = $this->db->getOne('SELECT value FROM ' . $this->dsc->table('shop_config') . ' WHERE code = \'stylename\'');  //当前模板样式
                if ($style == '') {
                    $style = '0';
                }
                $ecs_style = $style;
                $shop_url = urlencode($this->dsc->url()); //当前url

                $time = app(TimeRepository::class)->getGmTime();

                $httpData = [
                    'domain' => $this->dsc->get_domain(), //当前域名
                    'url' => urldecode($shop_url), //当前url
                    'ver' => $ecs_version,
                    'lang' => $ecs_lang,
                    'release' => $ecs_release,
                    'php_ver' => $php_ver,
                    'mysql_ver' => $mysql_ver,
                    'ocount' => $ocount,
                    'oamount' => $oamount,
                    'gcount' => $gcount,
                    'scount' => $scount,
                    'charset' => $ecs_charset,
                    'usecount' => $ecs_user,
                    'template' => $ecs_template,
                    'style' => $ecs_style,
                    'add_time' => app(TimeRepository::class)->getLocalDate("Y-m-d H:i:s", $time)
                ];

                $httpData = json_encode($httpData); // 对变量进行 JSON 编码
                $argument = array(
                    'data' => $httpData
                );

                Http::doPost('', $argument);

                write_static_cache('main_user_str', $httpData);
            }
        }

        /*------------------------------------------------------ */
        //-- 删除会员帐号
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'remove') {
            /* 检查权限 */
            admin_priv('users_drop');

            $user_id = intval($_GET['id']);
            $type = isset($_GET['type']) ? $_GET['type'] : 0;

            $sql = "SELECT user_name FROM " . $this->dsc->table('users') . " WHERE user_id = '$user_id'";
            $username = $this->db->getOne($sql);

            if ($type == 0) {
                $account = app(UserService::class)->getUserAccount($user_id);
                if ($account['type']) {
                    $link[] = ['text' => $GLOBALS['_LANG']['goto_assets_list'], 'href' => 'users.php?act=virtual_assets&id=' . $user_id];
                    return sys_msg(sprintf($GLOBALS['_LANG']['user_have_account'], $username), 0, $link);
                }
            }

            /* 只有超级管理员才可以删除商家会员 by wu */
            $sql = "SELECT shop_id FROM " . $this->dsc->table('merchants_shop_information') . " WHERE user_id = '$user_id'";

            if ($this->db->getOne($sql)) {
                $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'users.php?act=list'];
                return sys_msg(sprintf($GLOBALS['_LANG']['remove_seller_fail'], $username, $user_id), 0, $link);
            }

            /* 通过插件来删除用户 */
            $users = init_users();
            $users->remove_user($username); //已经删除用户所有数据

            /* 记录管理员操作 */
            admin_log(addslashes($username), 'remove', 'users');

            /* 提示信息 */
            $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'users.php?act=list'];
            return sys_msg(sprintf($GLOBALS['_LANG']['remove_success'], $username), 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 虚拟资产列表
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'virtual_assets') {
            /* 检查权限 */
            admin_priv('users_drop');
            $user_id = intval($_GET['id']);
            //获取虚拟资产列表
            $account = app(UserService::class)->getUserAccount($user_id);

            $this->smarty->assign('menu_select', ['action' => '08_members', 'current' => '03_users_list']);

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['virtual_assets_list']);
            $this->smarty->assign('user_id', $user_id);

            $this->smarty->assign('account', $account);
            $this->smarty->assign('full_page', 1);

            return $this->smarty->display('user_assets_list.dwt');
        }

        /*------------------------------------------------------ */
        //--  收货地址查看
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'address_list') {
            $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
            $sql = "SELECT a.*, c.region_name AS country_name, p.region_name AS province, ct.region_name AS city_name, d.region_name AS district_name " .
                " FROM " . $this->dsc->table('user_address') . " as a " .
                " LEFT JOIN " . $this->dsc->table('region') . " AS c ON c.region_id = a.country " .
                " LEFT JOIN " . $this->dsc->table('region') . " AS p ON p.region_id = a.province " .
                " LEFT JOIN " . $this->dsc->table('region') . " AS ct ON ct.region_id = a.city " .
                " LEFT JOIN " . $this->dsc->table('region') . " AS d ON d.region_id = a.district " .
                " WHERE user_id='$id'";
            $address = $this->db->getAll($sql);

            if ($address) {
                foreach ($address as $key => $row) {
                    if (isset($GLOBALS['_CFG']['show_mobile']) && $GLOBALS['_CFG']['show_mobile'] == 0) {
                        $address[$key]['tel'] = $this->dscRepository->stringToStar($row['tel']);
                        $address[$key]['mobile'] = $this->dscRepository->stringToStar($row['mobile']);
                    }
                }
            }

            $this->smarty->assign('address', $address);
            $this->smarty->assign("user_id", $id);
            $this->smarty->assign('form_action', 'address_list');
            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['address_list']);
            if ($id > 0) {
                $this->smarty->assign('action_link2', ['text' => $GLOBALS['_LANG']['address_list'], 'href' => 'users.php?act=list']);
            }


            return $this->smarty->display('user_list_edit.dwt');
        }

        /*------------------------------------------------------ */
        //-- 脱离推荐�        �系
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'remove_parent') {
            /* 检查权限 */
            admin_priv('users_manage');

            $sql = "UPDATE " . $this->dsc->table('users') . " SET parent_id = 0 WHERE user_id = '" . $_GET['id'] . "'";
            $this->db->query($sql);

            /* 记录管理员操作 */
            $sql = "SELECT user_name FROM " . $this->dsc->table('users') . " WHERE user_id = '" . $_GET['id'] . "'";
            $username = $this->db->getOne($sql);
            admin_log(addslashes($username), 'edit', 'users');

            /* 提示信息 */
            $link[] = ['text' => $GLOBALS['_LANG']['go_back'], 'href' => 'users.php?act=list'];
            return sys_msg(sprintf($GLOBALS['_LANG']['update_success'], $username), 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 查看用户推荐会员列表
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'aff_list') {
            /* 检查权限 */
            admin_priv('users_manage');
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['03_users_list']);

            $auid = isset($_GET['auid']) && !empty($_GET['auid']) ? intval($_GET['auid']) : 0;;
            $user_list['user_list'] = [];

            $affiliate = $GLOBALS['_CFG']['affiliate'] ? unserialize($GLOBALS['_CFG']['affiliate']) : [];
            $this->smarty->assign('affiliate', $affiliate);

            empty($affiliate) && $affiliate = [];

            $num = count($affiliate['item']);
            $up_uid = "'$auid'";
            $all_count = 0;
            for ($i = 1; $i <= $num; $i++) {
                $count = 0;
                if ($up_uid) {
                    $sql = "SELECT user_id FROM " . $this->dsc->table('users') . " WHERE parent_id IN($up_uid)";
                    $query = $this->db->query($sql);
                    $up_uid = '';
                    foreach ($query as $rt) {
                        $up_uid .= $up_uid ? ",'$rt[user_id]'" : "'$rt[user_id]'";
                        $count++;
                    }
                }
                $all_count += $count;

                if ($count) {
                    $sql = "SELECT user_id, user_name, '$i' AS level, email, is_validated, user_money, frozen_money, rank_points, pay_points, reg_time " .
                        " FROM " . $this->dsc->table('users') . " WHERE user_id IN($up_uid)" .
                        " ORDER by level, user_id";
                    $user_list['user_list'] = array_merge($user_list['user_list'], $this->db->getAll($sql));
                }
            }

            $temp_count = count($user_list['user_list']);
            for ($i = 0; $i < $temp_count; $i++) {
                $user_list['user_list'][$i]['reg_time'] = local_date($GLOBALS['_CFG']['date_format'], $user_list['user_list'][$i]['reg_time']);
            }

            $user_list['record_count'] = $all_count;

            $this->smarty->assign('user_list', $user_list['user_list']);
            $this->smarty->assign('record_count', $user_list['record_count']);
            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['back_note'], 'href' => "users.php?act=edit&id=$auid"]);


            return $this->smarty->display('affiliate_list.dwt');
        } //ecmoban模板堂 --zhuo start 会员导出
        elseif ($_REQUEST['act'] == 'export') {
            $filename = local_date('YmdHis') . ".csv";
            header("Content-type:text/csv");
            header("Content-Disposition:attachment;filename=" . $filename);
            header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
            header('Expires:0');
            header('Pragma:public');

            $user_list = $this->user_list();
            echo $this->user_date($user_list['user_list']);
        } //会员操作日志
        elseif ($_REQUEST['act'] == 'users_log') {
            //页面赋值
            $this->smarty->assign("ur_here", $GLOBALS['_LANG']['users_log']);
            $user_id = !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

            $user_log = $this->get_user_log();
            $this->smarty->assign('user_log', $user_log['list']);
            $this->smarty->assign('filter', $user_log['filter']);
            $this->smarty->assign('record_count', $user_log['record_count']);
            $this->smarty->assign('page_count', $user_log['page_count']);

            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('user_id', $user_id);

            return $this->smarty->display("users_log.dwt");
        } //会员操作日志ajax
        elseif ($_REQUEST['act'] == 'users_log_query') {
            $user_id = !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

            $user_log = $this->get_user_log();
            $this->smarty->assign('user_log', $user_log['list']);
            $this->smarty->assign('filter', $user_log['filter']);
            $this->smarty->assign('record_count', $user_log['record_count']);
            $this->smarty->assign('page_count', $user_log['page_count']);
            $this->smarty->assign('user_id', $user_id);
            return make_json_result(
                $this->smarty->fetch('users_log.dwt'),
                '',
                ['filter' => $user_log['filter'], 'page_count' => $user_log['page_count']]
            );
        } //删除会员日志
        elseif ($_REQUEST['act'] == 'batch_log') {
            $user_id = !empty($_REQUEST['user_id']) ? intval($_REQUEST['user_id']) : 0;
            /* 取得要操作的记录编号 */
            if (empty($_POST['checkboxes'])) {
                return sys_msg($GLOBALS['_LANG']['no_record_selected']);
            } else {
                $ids = $_POST['checkboxes'];
                /* 删除记录 */
                $sql = "DELETE FROM " . $this->dsc->table('users_log') .
                    " WHERE log_id " . db_create_in($ids) . " AND user_id = '$user_id'";
                $this->db->query($sql);

                /* 清除缓存 */
                clear_cache_files();
                $link[] = ['text' => $GLOBALS['_LANG']['back'], 'href' => 'users.php?act=users_log&id=' . $user_id];
                return sys_msg($GLOBALS['_LANG']['batch_drop_ok'], '', $link);
            }
        }
    }

    /**
     * 会员操作日志
     * @return  array
     */
    private function get_user_log()
    {
        $result = get_filter();
        if ($result === false) {
            $where = ' WHERE 1 ';
            /* 初始化分页参数 */
            $filter = [];
            $filter['id'] = !empty($_REQUEST['id']) ? $_REQUEST['id'] : '0';

            $where .= " AND user_id = '" . $filter['id'] . "'";
            /* 查询记录总数，计算分页数 */
            $sql = "SELECT COUNT(*) FROM " . $this->dsc->table('users_log') . $where;
            $filter['record_count'] = $this->db->getOne($sql);
            $filter = page_and_size($filter);

            /* 查询记录 */

            $sql = "SELECT log_id,user_id,change_time,change_type,ip_address,change_city,logon_service,admin_id FROM" . $this->dsc->table('users_log')
                . "$where  ORDER BY change_time DESC";
            set_filter($filter, $sql);
        } else {
            $sql = $result['sql'];
            $filter = $result['filter'];
        }
        $res = $this->db->selectLimit($sql, $filter['page_size'], $filter['start']);

        $arr = [];
        foreach ($res as $rows) {
            if ($rows['change_time'] > 0) {
                $rows['change_time'] = local_date('Y-m-d H:i:s', $rows['change_time']);
            }
            if ($rows['admin_id'] > 0) {
                $sql = 'SELECT user_name FROM' . $this->dsc->table('admin_user') . " WHERE user_id = '" . $rows['admin_id'] . "'";
                $rows['admin_name'] = $GLOBALS['_LANG']['manage_alt'] . $this->db->getOne($sql);
            } else {
                $rows['admin_name'] = $GLOBALS['_LANG']['user_handle'];
            }
            $arr[] = $rows;
        }
        return ['list' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];
    }

    private function user_date($result)
    {
        if (empty($result)) {
            return $this->i($GLOBALS['_LANG']['not_fuhe_date']);
        }
        $data = $this->i($GLOBALS['_LANG']['user_date_notic'] . "\n");
        $count = count($result);
        for ($i = 0; $i < $count; $i++) {
            if (empty($result[$i]['ru_name'])) {
                $result[$i]['ru_name'] = $GLOBALS['_LANG']['mall_user'];
            }

            $data .= $this->i($result[$i]['user_id']) . ',' .
                $this->i($result[$i]['user_name']) . ',' . $this->i($result[$i]['ru_name']) . ',' .
                $this->i($result[$i]['mobile_phone']) . ',' . $this->i($result[$i]['email']) . ',' .
                $this->i($result[$i]['is_validated']) . ',' . $this->i($result[$i]['user_money']) . ',' .
                $this->i($result[$i]['frozen_money']) . ',' . $this->i($result[$i]['rank_points']) . ',' .
                $this->i($result[$i]['rank_name']) . ',' .
                $this->i($result[$i]['pay_points']) . ',' . $this->i($result[$i]['reg_time']) . "\n";
        }
        return $data;
    }

    private function i($strInput)
    {
        $strInput = $strInput && is_array($strInput) ? implode(",", $strInput) : $strInput;
        return iconv('utf-8', 'gb2312//IGNORE', $strInput);//页面编码为utf-8时使用，否则导出的中文为乱码
    }
    //ecmoban模板堂 --zhuo 会员导出 end

    /**
     *  返回用户列表数据
     *
     * @access  public
     * @param
     *
     * @return void
     */
    private function user_list()
    {
        $adminru = get_admin_ru_id();

        $result = get_filter();
        if ($result === false) {
            /* 过滤条件 */
            $filter['keywords'] = empty($_REQUEST['keywords']) ? '' : trim($_REQUEST['keywords']);
            if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1) {
                $filter['keywords'] = json_str_iconv($filter['keywords']);
            }
            $filter['rank'] = empty($_REQUEST['rank']) ? 0 : intval($_REQUEST['rank']);
            $filter['rank_id'] = empty($_REQUEST['rank_id']) ? 0 : intval($_REQUEST['rank_id']);
            $filter['pay_points_gt'] = empty($_REQUEST['pay_points_gt']) ? 0 : intval($_REQUEST['pay_points_gt']);
            $filter['pay_points_lt'] = empty($_REQUEST['pay_points_lt']) ? 0 : intval($_REQUEST['pay_points_lt']);
            $filter['mobile_phone'] = empty($_REQUEST['mobile_phone']) ? 0 : addslashes($_REQUEST['mobile_phone']);
            $filter['email'] = empty($_REQUEST['email']) ? 0 : addslashes($_REQUEST['email']);

            $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'u.user_id' : trim($_REQUEST['sort_by']);
            $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

            $filter['checkboxes'] = empty($_REQUEST['checkboxes']) ? '' : $_REQUEST['checkboxes'];

            $ex_where = ' WHERE 1 ';

            //管理员查询的权限 -- 店铺查询 start
            $filter['store_search'] = empty($_REQUEST['store_search']) ? 0 : intval($_REQUEST['store_search']);
            $filter['merchant_id'] = isset($_REQUEST['merchant_id']) ? intval($_REQUEST['merchant_id']) : 0;
            $filter['store_keyword'] = isset($_REQUEST['store_keyword']) ? trim($_REQUEST['store_keyword']) : '';

            $store_where = '';
            $store_search_where = '';
            if ($filter['store_search'] != 0) {
                if ($adminru['ru_id'] == 0) {
                    $store_type = isset($_REQUEST['store_type']) && !empty($_REQUEST['store_type']) ? intval($_REQUEST['store_type']) : 0;

                    if ($store_type) {
                        $store_search_where = "AND msi.shopNameSuffix = '$store_type'";
                    }

                    if ($filter['store_search'] == 1) {
                        $ex_where .= " AND u.user_id = '" . $filter['merchant_id'] . "' ";
                    } elseif ($filter['store_search'] == 2) {
                        $store_where .= " AND msi.rz_shopName LIKE '%" . mysql_like_quote($filter['store_keyword']) . "%'";
                    } elseif ($filter['store_search'] == 3) {
                        $store_where .= " AND msi.shoprz_brandName LIKE '%" . mysql_like_quote($filter['store_keyword']) . "%' " . $store_search_where;
                    }

                    if ($filter['store_search'] > 1) {
                        $ex_where .= " AND (SELECT msi.user_id FROM " . $this->dsc->table('merchants_shop_information') . ' as msi ' .
                            " WHERE msi.user_id = u.user_id $store_where) > 0 ";
                    }
                }
            }
            //管理员查询的权限 -- 店铺查询 end

            if ($filter['keywords']) {
                $ex_where .= " AND (u.user_name LIKE '%" . mysql_like_quote($filter['keywords']) . "%' OR u.nick_name LIKE '%" . mysql_like_quote($filter['keywords']) . "%' OR u.mobile_phone LIKE '%" . mysql_like_quote($filter['keywords']) . "%')";
            }

            if ($filter['mobile_phone']) {
                $ex_where .= " AND u.mobile_phone = '" . $filter['mobile_phone'] . "'";
            }

            if ($filter['email']) {
                $ex_where .= " AND u.email = '" . $filter['email'] . "'";
            }

            if ($filter['rank']) {
//                $sql = "SELECT min_points, max_points, special_rank FROM " . $this->dsc->table('user_rank') . " WHERE rank_id = '$filter[rank]'";
//                $row = $this->db->getRow($sql);
//                if ($row['special_rank'] > 0) {
                /* 特殊等级 */
                $ex_where .= " AND u.user_rank = '$filter[rank]' ";
//                } else {
//                    $ex_where .= " AND u.rank_points >= " . intval($row['min_points']) . " AND u.rank_points < " . intval($row['max_points']);
//                }
            }
            if ($filter['rank_id']) {
                $ex_where .= " AND u.user_rank = '$filter[rank_id]' ";
            }
            if ($filter['pay_points_gt']) {
                $ex_where .= " AND u.pay_points < '$filter[pay_points_gt]' ";
            }
            if ($filter['pay_points_lt']) {
                $ex_where .= " AND u.pay_points >= '$filter[pay_points_lt]' ";
            }

            if ($filter['checkboxes']) {
                $checkboxes = !is_array($filter['checkboxes']) ? explode(",", $filter['checkboxes']) : $filter['checkboxes'];

                $ex_where .= " AND u.user_id " . db_create_in($checkboxes);
            }

            $filter['record_count'] = $this->db->getOne("SELECT COUNT(*) FROM " . $this->dsc->table('users') . " AS u " . $ex_where);

            /* 分页大小 */
            $filter = page_and_size($filter);

            $limit = " LIMIT " . $filter['start'] . ',' . $filter['page_size'];

            $export = isset($_REQUEST['export']) && !empty($_REQUEST['export']) ? intval($_REQUEST['export']) : 0;

            if ($export == 1) {
                $limit = '';
            }

            $sql = "SELECT * FROM " . $this->dsc->table('users') . " AS u " . $ex_where .
                " ORDER by " . $filter['sort_by'] . ' ' . $filter['sort_order'] . $limit;

            $filter['keywords'] = stripslashes($filter['keywords']);
            set_filter($filter, $sql);
        } else {
            $sql = $result['sql'];
            $filter = $result['filter'];
        }

        $user_list = $this->db->getAll($sql);

        $count = count($user_list);
        for ($i = 0; $i < $count; $i++) {
            $user_list[$i]['ru_name'] = $this->merchantCommonService->getShopName($user_list[$i]['user_id'], 1); //ecmoban模板堂 --zhuo
            $user_list[$i]['reg_time'] = local_date($GLOBALS['_CFG']['date_format'], $user_list[$i]['reg_time']);
            $rank_info = $this->userRankService->getUserRankInfo($user_list[$i]['user_id']);
            $user_list[$i]['rank_name'] = $rank_info['rank_name'] ?? '';
            if (empty($user_list[$i]['rank_name'])) {
                $user_list[$i]['rank_name'] = $GLOBALS['_LANG']['not_rank'];
            }

            if (isset($GLOBALS['_CFG']['show_mobile']) && $GLOBALS['_CFG']['show_mobile'] == 0) {
                $user_list[$i]['mobile_phone'] = $this->dscRepository->stringToStar($user_list[$i]['mobile_phone']);
                $user_list[$i]['user_name'] = $this->dscRepository->stringToStar($user_list[$i]['user_name']);
                $user_list[$i]['email'] = $this->dscRepository->stringToStar($user_list[$i]['email']);
            }
            $user_list[$i]['user_picture'] = $this->dscRepository->getImagePath($user_list[$i]['user_picture']);
        }

        return ['user_list' => $user_list, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];
    }

    /**
     * 添加会员
     *
     * @param integer $cat_id
     * @param array $args
     *
     * @return  mix
     */
    private function user_update($user_id, $args)
    {
        if (empty($args) || empty($user_id)) {
            return false;
        }

        return $this->db->autoExecute($this->dsc->table('users'), $args, 'update', "user_id='$user_id'");
    }
}
