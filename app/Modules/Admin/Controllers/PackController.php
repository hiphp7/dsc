<?php

namespace App\Modules\Admin\Controllers;

use App\Libraries\Image;
use App\Models\Pack;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Merchant\MerchantCommonService;

/**
 * 包装管理程序
 */
class PackController extends InitController
{
    protected $dscRepository;
    protected $merchantCommonService;
    protected $baseRepository;

    public function __construct(
        DscRepository $dscRepository,
        MerchantCommonService $merchantCommonService,
        BaseRepository $baseRepository
    )
    {
        $this->dscRepository = $dscRepository;
        $this->merchantCommonService = $merchantCommonService;
        $this->baseRepository = $baseRepository;
    }

    public function index()
    {
        $image = new Image(['bgcolor' => $GLOBALS['_CFG']['bgcolor']]);

        //ecmoban模板堂 --zhuo start
        $adminru = get_admin_ru_id();
        if ($adminru['ru_id'] == 0) {
            $this->smarty->assign('priv_ru', 1);
        } else {
            $this->smarty->assign('priv_ru', 0);
        }
        //ecmoban模板堂 --zhuo end
        /*------------------------------------------------------ */
        //-- 包        装        列表
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['06_pack_list']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['pack_add'], 'href' => 'pack.php?act=add']);
            $this->smarty->assign('full_page', 1);

            $packs_list = $this->packs_list($adminru['ru_id']);

            $this->smarty->assign('packs_list', $packs_list['packs_list']);
            $this->smarty->assign('filter', $packs_list['filter']);
            $this->smarty->assign('record_count', $packs_list['record_count']);
            $this->smarty->assign('page_count', $packs_list['page_count']);


            return $this->smarty->display('pack_list.htm');
        }
        /*------------------------------------------------------ */
        //-- ajax 列表
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            $packs_list = $this->packs_list($adminru['ru_id']);
            $this->smarty->assign('packs_list', $packs_list['packs_list']);
            $this->smarty->assign('filter', $packs_list['filter']);
            $this->smarty->assign('record_count', $packs_list['record_count']);
            $this->smarty->assign('page_count', $packs_list['page_count']);

            $sort_flag = sort_flag($packs_list['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result($this->smarty->fetch('pack_list.htm'), '', ['filter' => $packs_list['filter'], 'page_count' => $packs_list['page_count']]);
        }
        /*------------------------------------------------------ */
        //-- 添加新包        装
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'add') {
            /* 权限判断 */
            admin_priv('pack');

            $pack['pack_fee'] = 0;
            $pack['free_money'] = 0;

            $this->smarty->assign('pack', $pack);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['pack_add']);
            $this->smarty->assign('form_action', 'insert');
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['06_pack_list'], 'href' => 'pack.php?act=list']);


            return $this->smarty->display('pack_info.htm');
        }
        if ($_REQUEST['act'] == 'insert') {
            /* 权限判断 */
            admin_priv('pack');

            $pack_name = isset($_POST['pack_name']) ? trim($_POST['pack_name']) : '';
            $pack_fee = isset($_POST['pack_fee']) ? $_POST['pack_fee'] : 0;
            $free_money = isset($_POST['free_money']) ? $_POST['free_money'] : 0;
            $pack_desc = isset($_POST['pack_desc']) ? trim($_POST['pack_desc']) : '';

            /*检查包装名是否重复*/
            $is_only = Pack::where('pack_name', $pack_name)->count();

            if ($is_only > 0) {
                return sys_msg(sprintf($GLOBALS['_LANG']['packname_exist'], stripslashes($pack_name)), 1);
            }

            /* 处理图片 */
            if (!empty($_FILES['pack_img'])) {
                $upload_img = $image->upload_image($_FILES['pack_img'], "packimg", $_POST['old_packimg']);
                if ($upload_img == false) {
                    return sys_msg($image->error_msg);
                }
                $img_name = basename($upload_img);
            } else {
                $img_name = '';
            }

            $this->dscRepository->getOssAddFile([DATA_DIR . '/packimg/' . $img_name]);

            /*插入数据*/
            $data = [
                'pack_name' => $pack_name,
                'pack_fee' => $pack_fee,
                'free_money' => $free_money,
                'pack_desc' => $pack_desc,
                'pack_img' => $img_name,
                'user_id' => $adminru['ru_id']
            ];
            Pack::insert($data);

            /*添加链接*/
            $link[0]['text'] = $GLOBALS['_LANG']['back_list'];
            $link[0]['href'] = 'pack.php?act=list';
            $link[1]['text'] = $GLOBALS['_LANG']['continue_add'];
            $link[1]['href'] = 'pack.php?act=add';
            return sys_msg($pack_name . $GLOBALS['_LANG']['packadd_succed'], 0, $link);
            admin_log($pack_name, 'add', 'pack');
        }

        /*------------------------------------------------------ */
        //-- 编辑包        装
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'edit') {
            /* 权限判断 */
            admin_priv('pack');

            $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
            $res = Pack::where('pack_id', $id);
            $pack = $this->baseRepository->getToArrayFirst($res);

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['pack_edit']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['06_pack_list'], 'href' => 'pack.php?act=list&' . list_link_postfix()]);
            $this->smarty->assign('pack', $pack);
            $this->smarty->assign('form_action', 'update');
            return $this->smarty->display('pack_info.htm');
        }
        if ($_REQUEST['act'] == 'update') {
            /* 权限判断 */
            admin_priv('pack');

            $pack_name = isset($_POST['pack_name']) ? trim($_POST['pack_name']) : '';
            $pack_fee = isset($_POST['pack_fee']) ? $_POST['pack_fee'] : 0;
            $free_money = isset($_POST['free_money']) ? $_POST['free_money'] : 0;
            $pack_desc = isset($_POST['pack_desc']) ? trim($_POST['pack_desc']) : '';
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;

            if ($pack_name != $_POST['old_packname']) {
                /*检查品牌名是否相同*/

                $is_only = Pack::where('pack_name', $pack_name)
                    ->where('pack_id', '<>', $id)
                    ->count();

                if ($is_only > 0) {
                    return sys_msg(sprintf($GLOBALS['_LANG']['packname_exist'], stripslashes($pack_name)), 1);
                }
            }
            $param = [
                'pack_name' => $pack_name,
                'pack_fee' => $pack_fee,
                'free_money' => $free_money,
                'pack_desc' => $pack_desc
            ];
            /* 处理图片 */
            if (!empty($_FILES['pack_img']['name'])) {
                $upload_img = $image->upload_image($_FILES['pack_img'], "packimg", $_POST['old_packimg']);
                if ($upload_img == false) {
                    return sys_msg($image->error_msg);
                }
                $img_name = basename($upload_img);
            } else {
                $img_name = '';
            }

            $this->dscRepository->getOssAddFile([DATA_DIR . '/packimg/' . $img_name]);

            if (!empty($img_name)) {
                $param['pack_img'] = $img_name;
            }
            $res = Pack::where('pack_id', $id)->update($param);

            if ($res > 0) {
                $link[0]['text'] = $GLOBALS['_LANG']['back_list'];
                $link[0]['href'] = 'pack.php?act=list&' . list_link_postfix();
                $note = sprintf($GLOBALS['_LANG']['packedit_succed'], $_POST['pack_name']);
                return sys_msg($note, 0, $link);
                admin_log($_POST['pack_name'], 'edit', 'pack');
            } else {
                return $this->db->error();
            }
        }

        /* 删除卡片图片 */
        if ($_REQUEST['act'] == 'drop_pack_img') {
            /* 权限判断 */
            admin_priv('pack');
            $pack_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

            $this->dscRepository->getDelBatch('', $pack_id, ['pack_img'], 'pack_id', Pack::whereRaw(1), 0, DATA_DIR . '/packimg/'); //删除图片

            $data = ['pack_img' => ''];
            Pack::where('pack_id', $pack_id)->update($data);

            $link = [['text' => $GLOBALS['_LANG']['pack_edit_lnk'], 'href' => 'pack.php?act=edit&id=' . $pack_id], ['text' => $GLOBALS['_LANG']['pack_list_lnk'], 'href' => 'pack.php?act=list']];
            return sys_msg($GLOBALS['_LANG']['drop_pack_img_success'], 0, $link);
        }

        /*------------------------------------------------------ */
        //-- 编辑包        装        名称
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'edit_name') {
            $check_auth = check_authz_json('pack');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $val = json_str_iconv(trim($_POST['val']));

            /* 取得该属性所属商品类型id */

            $pack_name = Pack::where('pack_id', $id)->value('pack_name');
            $pack_name = $pack_name ? $pack_name : '';

            $is_only = Pack::where('pack_name', $val)
                ->where('pack_id', '<>', $id)
                ->count();

            if ($is_only > 0) {
                return make_json_error(sprintf($GLOBALS['_LANG']['packname_exist'], $pack_name));
            } else {
                $data = ['pack_name' => $val];
                Pack::where('pack_id', $id)->update($data);

                admin_log($val, 'edit', 'pack');
                return make_json_result(stripslashes($val));
            }
        }

        /*------------------------------------------------------ */
        //-- 编辑包        装        费用
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'edit_pack_fee') {
            $check_auth = check_authz_json('pack');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $val = floatval($_POST['val']);

            /* 取得该属性所属商品类型id */

            $pack_name = Pack::where('pack_id', $id)->value('pack_name');
            $pack_name = $pack_name ? $pack_name : '';

            $data = ['pack_fee' => $val];
            Pack::where('pack_id', $id)->update($data);

            admin_log(addslashes($pack_name), 'edit', 'pack');
            return make_json_result(number_format($val, 2));
        }

        /*------------------------------------------------------ */
        //-- 编辑�        �费额度
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'edit_free_money') {
            $check_auth = check_authz_json('pack');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $val = floatval($_POST['val']);

            /* 取得该属性所属商品类型id */
            $pack_name = Pack::where('pack_id', $id)->value('pack_name');
            $pack_name = $pack_name ? $pack_name : '';

            $data = ['free_money' => $val];
            Pack::where('pack_id', $id)->update($data);

            admin_log(addslashes($pack_name), 'edit', 'pack');
            return make_json_result(number_format($val, 2));
        }

        /*------------------------------------------------------ */
        //-- 删除包        装
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'remove') {
            $check_auth = check_authz_json('pack');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_GET['id']);

            $name = Pack::where('pack_id', $id)->value('pack_name');
            $name = $name ? $name : '';

            $this->dscRepository->getDelBatch('', $id, ['pack_img'], 'pack_id', Pack::whereRaw(1), 0, DATA_DIR . '/packimg/'); //删除图片

            $res = Pack::where('pack_id', $id)->delete();
            if ($res > 0) {
                admin_log(addslashes($name), 'remove', 'pack');

                $url = 'pack.php?act=query&' . str_replace('act=remove', '', request()->server('QUERY_STRING'));

                return dsc_header("Location: $url\n");
            } else {
                return make_json_error($GLOBALS['_LANG']['packremove_falure']);
                return false;
            }
        }
    }

    private function packs_list($ru_id)
    {
        $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'pack_id' : trim($_REQUEST['sort_by']);
        $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);


        $res = Pack::whereRaw(1);
        if ($ru_id > 0) {
            $res = $res->where('user_id', $ru_id);
        }

        $filter['record_count'] = $res->count();

        /* 分页大小 */
        $filter = page_and_size($filter);

        /* 查询 */

        $res = $res->orderBy($filter['sort_by'], $filter['sort_order'])
            ->offset($filter['start'])
            ->limit($filter['page_size']);

        $packs_list = $this->baseRepository->getToArrayGet($res);

        if ($packs_list) {
            for ($i = 0; $i < count($packs_list); $i++) {
                $packs_list[$i]['ru_name'] = $this->merchantCommonService->getShopName($packs_list[$i]['user_id'], 1); //ecmoban模板堂 --zhuo
            }
        }

        $arr = ['packs_list' => $packs_list, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']];

        return $arr;
    }
}
