<?php

namespace App\Modules\Admin\Controllers;

use App\Models\RegExtendInfo;
use App\Models\RegFields;
use App\Repositories\Common\BaseRepository;

/**
 * 会员等级管理程序
 */
class RegFieldsController extends InitController
{
    protected $baseRepository;

    public function __construct(
        BaseRepository $baseRepository
    )
    {
        $this->baseRepository = $baseRepository;
    }

    public function index()
    {
        /*------------------------------------------------------ */
        //-- 会员注册项列表
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'list') {
            $fields = [];

            $res = RegFields::orderBy('dis_order')->orderBy('id');
            $fields = $this->baseRepository->getToArrayGet($res);

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['16_reg_fields']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['add_reg_field'], 'href' => 'reg_fields.php?act=add']);
            $this->smarty->assign('full_page', 1);

            $this->smarty->assign('reg_fields', $fields);


            return $this->smarty->display('reg_fields.dwt');
        }


        /*------------------------------------------------------ */
        //-- 翻页，排序
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            $fields = [];

            $res = RegFields::orderBy('id');
            $fields = $this->baseRepository->getToArrayGet($res);

            $this->smarty->assign('reg_fields', $fields);
            return make_json_result($this->smarty->fetch('reg_fields.dwt'));
        }

        /*------------------------------------------------------ */
        //-- 添加会员注册项
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'add') {
            admin_priv('reg_fields');

            $form_action = 'insert';

            $reg_field['reg_field_order'] = 100;
            $reg_field['reg_field_display'] = 1;
            $reg_field['reg_field_need'] = 1;

            $this->smarty->assign('reg_field', $reg_field);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['add_reg_field']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['16_reg_fields'], 'href' => 'reg_fields.php?act=list']);
            $this->smarty->assign('form_action', $form_action);


            return $this->smarty->display('reg_field_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 增加会员注册项到数据库
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'insert') {
            admin_priv('reg_fields');
            $reg_field_name = isset($_POST['reg_field_name']) ? trim($_POST['reg_field_name']) : '';
            $reg_field_order = isset($_POST['reg_field_order']) ? trim($_POST['reg_field_order']) : '';
            $reg_field_display = isset($_POST['reg_field_display']) ? trim($_POST['reg_field_display']) : '';
            $reg_field_need = isset($_POST['reg_field_need']) ? trim($_POST['reg_field_need']) : '';

            /* 检查是否存在重名的会员注册项 */
            $is_only = RegFields::where('reg_field_name', trim($reg_field_name))->count();
            if ($is_only > 0) {
                return sys_msg(sprintf($GLOBALS['_LANG']['field_name_exist'], trim($reg_field_name)), 1);
            }

            $data = [
                'reg_field_name' => $reg_field_name,
                'dis_order' => $reg_field_order,
                'display' => $reg_field_display,
                'is_need' => $reg_field_need
            ];
            RegFields::insert($data);

            /* 管理员日志 */
            admin_log(trim($_POST['reg_field_name']), 'add', 'reg_fields');
            clear_cache_files();

            $lnk[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'reg_fields.php?act=list'];
            $lnk[] = ['text' => $GLOBALS['_LANG']['add_continue'], 'href' => 'reg_fields.php?act=add'];
            return sys_msg($GLOBALS['_LANG']['add_field_success'], 0, $lnk);
        }

        /*------------------------------------------------------ */
        //-- 编辑会员注册项
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'edit') {
            admin_priv('reg_fields');
            $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
            $form_action = 'update';

            $res = RegFields::where('id', $id);
            $reg_field = $this->baseRepository->getToArrayFirst($res);
            if (!empty($reg_field)) {
                $reg_field['reg_field_id'] = $reg_field['id'];
                $reg_field['reg_field_order'] = $reg_field['dis_order'];
                $reg_field['reg_field_display'] = $reg_field['display'];
                $reg_field['reg_field_need'] = $reg_field['is_need'];
            }

            $this->smarty->assign('reg_field', $reg_field);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['add_reg_field']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['16_reg_fields'], 'href' => 'reg_fields.php?act=list']);
            $this->smarty->assign('form_action', $form_action);


            return $this->smarty->display('reg_field_info.dwt');
        }

        /*------------------------------------------------------ */
        //-- 更新会员注册项
        /*------------------------------------------------------ */

        elseif ($_REQUEST['act'] == 'update') {
            admin_priv('reg_fields');
            $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
            $reg_field_name = isset($_POST['reg_field_name']) ? trim($_POST['reg_field_name']) : '';
            $reg_field_order = isset($_POST['reg_field_order']) ? trim($_POST['reg_field_order']) : '';
            $reg_field_display = isset($_POST['reg_field_display']) ? trim($_POST['reg_field_display']) : '';
            $reg_field_need = isset($_POST['reg_field_need']) ? trim($_POST['reg_field_need']) : '';

            /* 检查是否存在重名的会员注册项 */
            $is_only = RegFields::where('reg_field_name', $reg_field_name)->count();
            if ($_POST['reg_field_name'] != $_POST['old_field_name'] && $is_only) {
                return sys_msg(sprintf($GLOBALS['_LANG']['field_name_exist'], trim($_POST['reg_field_name'])), 1);
            }

            $data = [
                'reg_field_name' => $reg_field_name,
                'dis_order' => $reg_field_order,
                'display' => $reg_field_display,
                'is_need' => $reg_field_need
            ];
            RegFields::where('id', $id)->update($data);

            /* 管理员日志 */
            admin_log(trim($_POST['reg_field_name']), 'edit', 'reg_fields');
            clear_cache_files();

            $lnk[] = ['text' => $GLOBALS['_LANG']['back_list'], 'href' => 'reg_fields.php?act=list'];
            return sys_msg($GLOBALS['_LANG']['update_field_success'], 0, $lnk);
        }

        /*------------------------------------------------------ */
        //-- 删除会员注册项
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'remove') {
            $check_auth = check_authz_json('reg_fields');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $field_id = intval($_GET['id']);

            $field_name = RegFields::where('id', $field_id)->value('reg_field_name');
            $field_name = $field_name ? $field_name : '';

            $res = RegFields::where('id', $field_id)->delete();
            if ($res > 0) {
                /* 删除会员扩展信息表的相应信息 */
                RegExtendInfo::where('reg_field_id', $field_id)->delete();

                admin_log(addslashes($field_name), 'remove', 'reg_fields');
                clear_cache_files();
            }

            $url = 'reg_fields.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));

            return dsc_header("Location: $url\n");
        } /*
 *  编辑会员注册项排序权值
 */
        elseif ($_REQUEST['act'] == 'edit_order') {
            $id = intval($_REQUEST['id']);
            $val = isset($_REQUEST['val']) ? json_str_iconv(trim($_REQUEST['val'])) : '';
            $check_auth = check_authz_json('reg_fields');
            if ($check_auth !== true) {
                return $check_auth;
            }
            if (is_numeric($val)) {
                $data = ['dis_order' => $val];
                $res = RegFields::where('id', $id)->update($data);
                if ($res >= 0) {
                    /* 管理员日志 */
                    admin_log($val, 'edit', 'reg_fields');
                    clear_cache_files();
                    return make_json_result(stripcslashes($val));
                } else {
                    return make_json_error($this->db->error());
                }
            } else {
                return make_json_error($GLOBALS['_LANG']['order_not_num']);
            }
        }

        /*------------------------------------------------------ */
        //-- 修改会员注册项显示状态
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'toggle_dis') {
            $check_auth = check_authz_json('reg_fields');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $is_dis = intval($_POST['val']);

            $data = ['display' => $is_dis];
            $res = RegFields::where('id', $id)->update($data);
            if ($res >= 0) {
                clear_cache_files();
                return make_json_result($is_dis);
            }
        }

        /*------------------------------------------------------ */
        //-- 修改会员注册项必        填状态
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'toggle_need') {
            $check_auth = check_authz_json('reg_fields');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $is_need = intval($_POST['val']);

            $data = ['is_need' => $is_need];
            $res = RegFields::where('id', $id)->update($data);
            if ($res >= 0) {
                clear_cache_files();
                return make_json_result($is_need);
            }
        }
    }
}
