<?php

namespace App\Modules\Admin\Controllers;

use App\Libraries\Image;
use App\Models\ZcInitiator;
use App\Models\ZcRankLogo;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Services\Message\ZcManageService;

/**
 * 众筹发起人管理
 */
class ZcInitiatorController extends InitController
{
    protected $zcManageService;
    protected $baseRepository;
    protected $dscRepository;

    public function __construct(
        ZcManageService $zcManageService,
        BaseRepository $baseRepository,
        DscRepository $dscRepository
    )
    {
        $this->zcManageService = $zcManageService;
        $this->baseRepository = $baseRepository;
        $this->dscRepository = $dscRepository;
    }

    public function index()
    {
        $image = new Image(['bgcolor' => $GLOBALS['_CFG']['bgcolor']]);
        /* act操作项的初始化 */
        if (empty($_REQUEST['act'])) {
            $_REQUEST['act'] = 'list';
        } else {
            $_REQUEST['act'] = trim($_REQUEST['act']);
        }
        $this->smarty->assign('act', $_REQUEST['act']);
        /*------------------------------------------------------ */
        //-- 项目发起人列表页面
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            /* 权限检查 */
            admin_priv('zc_initiator_manage');

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['03_project_initiator']);
            $action_link = ['href' => 'zc_initiator.php?act=rank_logo', 'text' => $GLOBALS['_LANG']['rank_logo_manage']];
            $action_link2 = ['href' => 'zc_initiator.php?act=add', 'text' => $GLOBALS['_LANG']['add_zc_initiator']];
            $this->smarty->assign('action_link2', $action_link2);
            $this->smarty->assign('action_link', $action_link);
            $list = $this->zcManageService->zcInitiatorList();

            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);
            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('initiator', $list['zc_initiator']);
            return $this->smarty->display('zc_initiator_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 翻页、排序
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'query') {
            $list = $this->zcManageService->zcInitiatorList();
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);
            $this->smarty->assign('initiator', $list['zc_initiator']);   //  把结果赋值给页面
            return make_json_result(
                $this->smarty->fetch('zc_initiator_list.dwt'),
                '',
                ['filter' => $list['filter'], 'page_count' => $list['page_count']]
            );
        }

        /*------------------------------------------------------ */
        //-- 添加/编辑发起人
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add' || $_REQUEST['act'] == 'edit') {
            /* 权限检查 */
            admin_priv('zc_initiator_manage');

            if ($_REQUEST['act'] == 'add') {
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['add_zc_initiator']);
            }
            if ($_REQUEST['act'] == 'edit') {
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['edit_zc_initiator']);
            }

            $action_link = ['href' => 'zc_initiator.php?act=list', 'text' => $GLOBALS['_LANG']['03_project_initiator']];
            $this->smarty->assign('action_link', $action_link);

            $res = ZcRankLogo::query();
            $res = $this->baseRepository->getToArrayGet($res);

            if (isset($_GET['id']) && !empty($_GET['id'])) {
                $id = intval($_GET['id']);
                $result = ZcInitiator::where('id', $id);
                $result = $this->baseRepository->getToArrayFirst($result);

                $this->smarty->assign('logo', $res);
                $this->smarty->assign('state', 'update');

                if ($result) {
                    $result['img'] = $this->dscRepository->getImagePath($result['img']);
                }

                $this->smarty->assign('result', $result);
                return $this->smarty->display('zc_initiator_info.dwt');
            } else {

                $this->smarty->assign('logo', $res);
                $this->smarty->assign('state', 'insert');
                return $this->smarty->display('zc_initiator_info.dwt');
            }
        }

        /*------------------------------------------------------ */
        //-- 添加发起人时的处理
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'insert') {
            /* 权限检查 */
            admin_priv('zc_initiator_manage');

            //处理接收数据
            $name = !empty($_POST['name']) ? trim($_POST['name']) : '';
            $company = !empty($_POST['company']) ? trim($_POST['company']) : '';
            $intro = !empty($_POST['intro']) ? trim($_POST['intro']) : '';
            $describe = !empty($_POST['describe']) ? trim($_POST['describe']) : '';
            $logo = !empty($_POST['logo']) ? intval($_POST['logo']) : 0;

            //判断名称不能重复
            $is_exist = ZcInitiator::where('name', $name)->count();

            if ($is_exist) {
                $links[0]['text'] = $GLOBALS['_LANG']['go_back'];
                $links[0]['href'] = 'javascript:history.go(-1)';
                return sys_msg($GLOBALS['_LANG']['name_repeat'], 1, $links);
            }

            /* 处理商品图片 */
            $dir = 'initiator_image';

            $img = '';
            if (!empty($_FILES['img']['name'])) {
                $img = $image->upload_image($_FILES['img'], $dir);
            }

            $other = [
                'name' => $name,
                'company' => $company,
                'img' => $img,
                'intro' => $intro,
                'describe' => $describe,
                'rank' => $logo
            ];

            ZcInitiator::insert($other);

            $links[0]['text'] = $GLOBALS['_LANG']['go_list'];
            $links[0]['href'] = 'zc_initiator.php?act=list';
            return sys_msg($GLOBALS['_LANG']['add_succeed'], 0, $links);
        }

        /*------------------------------------------------------ */
        //-- 编辑发起人时的处理
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'update') {
            /* 权限检查 */
            admin_priv('zc_initiator_manage');

            //处理接收数据
            $id = !empty($_POST['init_id']) ? trim($_POST['init_id']) : 0;
            $name = !empty($_POST['name']) ? trim($_POST['name']) : '';
            $company = !empty($_POST['company']) ? trim($_POST['company']) : '';
            $intro = !empty($_POST['intro']) ? trim($_POST['intro']) : '';
            $describe = !empty($_POST['describe']) ? trim($_POST['describe']) : '';
            $logo = !empty($_POST['logo']) ? intval($_POST['logo']) : 0;

            //判断名称不能重复
            $is_exist = ZcInitiator::where('name', $name)->where('id', '<>', $id)->count();

            if ($is_exist) {
                $links[0]['text'] = $GLOBALS['_LANG']['go_back'];
                $links[0]['href'] = 'javascript:history.go(-1)';
                return sys_msg($GLOBALS['_LANG']['name_repeat'], 1, $links);
            }

            /* 处理商品图片 */
            $img = '';  // 初始化说明图片
            $dir = 'initiator_image';
            if (!empty($_FILES['img']['name'])) {
                $img = $image->upload_image($_FILES['img'], $dir);
            }

            //有上传图片，删除原图
            $row = ZcInitiator::where('id', $id);
            $row = $this->baseRepository->getToArrayFirst($row);

            if ($img != '' && $row && $row['img']) {
                @unlink(storage_public($row['img']));
            }

            $other = [
                'name' => $name,
                'company' => $company,
                'intro' => $intro,
                'describe' => $describe,
                'rank' => $logo
            ];

            if ($img) {
                $other['img'] = $img;
            }

            ZcInitiator::where('id', $id)->update($other);

            $links[0]['text'] = $GLOBALS['_LANG']['go_list'];
            $links[0]['href'] = 'zc_initiator.php?act=list';
            return sys_msg($GLOBALS['_LANG']['edit_success'], 0, $links);
        }

        /*------------------------------------------------------ */
        //-- 删除发起人
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'del') {
            /* 权限检查 */
            admin_priv('zc_initiator_manage');

            $id = intval($_GET['id']);

            $row = ZcInitiator::where('id', $id);
            $row = $this->baseRepository->getToArrayFirst($row);

            if ($row && $row['img']) {
                @unlink(storage_public($row['img']));
            }

            ZcInitiator::where('id', $id)->delete();

            return dsc_header('Location:zc_initiator.php?act=list');
        }

        /*------------------------------------------------------ */
        //-- 等级标识列表
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'rank_logo') {
            /* 权限检查 */
            admin_priv('zc_initiator_manage');

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['rank_logo_manage']);
            $action_link = ['href' => 'zc_initiator.php?act=list', 'text' => $GLOBALS['_LANG']['03_project_initiator']];
            $action_link2 = ['href' => 'zc_initiator.php?act=add_rank_logo', 'text' => $GLOBALS['_LANG']['add_rank_logo']];
            $this->smarty->assign('action_link', $action_link);
            $this->smarty->assign('action_link2', $action_link2);

            $list = $this->zcManageService->zcRankLogoList();

            $this->smarty->assign('arr_zc', $list);
            $this->smarty->assign('full_page', 1);
            return $this->smarty->display('zc_rank_logo_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 添加/编辑等级身份标识
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add_rank_logo' || $_REQUEST['act'] == 'edit_rank_logo') {
            /* 权限检查 */
            admin_priv('zc_initiator_manage');

            if ($_REQUEST['act'] == 'add_rank_logo') {
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['add_rank_logo']);
            }
            if ($_REQUEST['act'] == 'edit_rank_logo') {
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['edit_rank_logo']);
            }

            $action_link = ['href' => 'zc_initiator.php?act=rank_logo', 'text' => $GLOBALS['_LANG']['rank_logo_manage']];
            $this->smarty->assign('action_link', $action_link);

            if (isset($_GET['id']) && !empty($_GET['id'])) {
                $id = intval($_GET['id']);

                $result = ZcRankLogo::where('id', $id);
                $result = $this->baseRepository->getToArrayFirst($result);

                if ($result) {
                    $result['img'] = $this->dscRepository->getImagePath($result['img']);
                }

                $this->smarty->assign('logo_id', $id);
                $this->smarty->assign('state', 'update_rank');
                $this->smarty->assign('result', $result);
                return $this->smarty->display('zc_rank_logo_info.dwt');
            } else {
                $this->smarty->assign('state', 'insert_rank');
                return $this->smarty->display('zc_rank_logo_info.dwt');
            }
        }

        /*------------------------------------------------------ */
        //-- 添加等级身份标识时的处理
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'insert_rank') {
            /* 权限检查 */
            admin_priv('zc_initiator_manage');

            //处理接收数据
            $logo_name = !empty($_POST['logo_name']) ? trim($_POST['logo_name']) : '';
            $intro = !empty($_POST['intro']) ? trim($_POST['intro']) : '';

            //判断名称不能重复
            $is_exist = ZcRankLogo::where('logo_name', $logo_name)->count();

            if ($is_exist) {
                $links[0]['text'] = $GLOBALS['_LANG']['go_back'];
                $links[0]['href'] = 'javascript:history.go(-1)';
                return sys_msg($GLOBALS['_LANG']['name_repeat'], 1, $links);
            }
            /* 处理商品图片 */
            $img = '';  // 初始化说明图片
            $dir = 'rank_image';
            if (!empty($_FILES['img']['name'])) {
                $img = $image->upload_image($_FILES['img'], $dir);
            }

            $other = [
                'logo_name' => $logo_name,
                'img' => $img,
                'logo_intro' => $intro
            ];

            ZcRankLogo::insert($other);

            $links[0]['text'] = $GLOBALS['_LANG']['go_list'];
            $links[0]['href'] = 'zc_initiator.php?act=rank_logo';
            return sys_msg($GLOBALS['_LANG']['add_succeed'], 0, $links);
        }

        /*------------------------------------------------------ */
        //-- 编辑等级身份标识时的处理
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'update_rank') {
            /* 权限检查 */
            admin_priv('zc_initiator_manage');

            //处理接收数据
            $id = !empty($_POST['logo_id']) ? trim($_POST['logo_id']) : 0;
            $logo_name = !empty($_POST['logo_name']) ? trim($_POST['logo_name']) : '';
            $intro = !empty($_POST['intro']) ? trim($_POST['intro']) : '';

            /* 处理商品图片 */
            $img = '';  // 初始化说明图片
            $dir = 'rank_image';
            if (!empty($_FILES['img']['name'])) {
                $img = $image->upload_image($_FILES['img'], $dir);
            }

            //有上传图片，删除原图
            $row = ZcRankLogo::where('id', $id);
            $row = $this->baseRepository->getToArrayFirst($row);

            if ($img != '' && $row && $row['img']) {
                @unlink(storage_public($row['img']));
            }

            $other = [
                'logo_name' => $logo_name,
                'logo_intro' => $intro
            ];

            if ($img) {
                $other['img'] = $img;
            }

            ZcRankLogo::where('id', $id)->update($other);

            $links[0]['text'] = $GLOBALS['_LANG']['go_list'];
            $links[0]['href'] = 'zc_initiator.php?act=rank_logo';
            return sys_msg($GLOBALS['_LANG']['edit_success'], 0, $links);
        }

        /*------------------------------------------------------ */
        //-- 删除等级身份标识
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'del_rank_logo') {
            /* 权限检查 */
            admin_priv('zc_initiator_manage');

            $id = intval($_GET['id']);

            $row = ZcRankLogo::where('id', $id);
            $row = $this->baseRepository->getToArrayFirst($row);

            if ($row && $row['img']) {
                @unlink(storage_public($row['img']));
            }

            ZcRankLogo::where('id', $id)->delete();

            return dsc_header('Location:zc_initiator.php?act=rank_logo');
        }
    }
}
