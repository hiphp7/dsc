<?php

namespace App\Modules\Admin\Controllers;

use App\Libraries\Image;
use App\Models\OrderInfo;
use App\Models\ZcGoods;
use App\Models\ZcInitiator;
use App\Models\ZcProgress;
use App\Models\ZcProject;
use App\Repositories\Common\BaseRepository;
use App\Repositories\Common\DscRepository;
use App\Repositories\Common\TimeRepository;
use App\Services\Message\ZcManageService;

/**
 * 众筹项目管理
 */
class ZcProjectController extends InitController
{
    protected $dscRepository;
    protected $zcManageService;
    protected $baseRepository;
    protected $timeRepository;

    public function __construct(
        DscRepository $dscRepository,
        ZcManageService $zcManageService,
        BaseRepository $baseRepository,
        TimeRepository $timeRepository
    )
    {
        $this->dscRepository = $dscRepository;
        $this->zcManageService = $zcManageService;
        $this->baseRepository = $baseRepository;
        $this->timeRepository = $timeRepository;
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
        //-- 众筹商品列表页面
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            /* 权限检查 */
            admin_priv('zc_project_manage');

            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['01_crowdfunding_list']);
            $action_link = ['href' => 'zc_project.php?act=add', 'text' => $GLOBALS['_LANG']['add_zc_project']];
            $this->smarty->assign('action_link', $action_link);

            $list = $this->zcManageService->zcProjectList();

            set_default_filter(0, 0, 0, 0, 'zc_category'); //设置默认筛选
            $this->smarty->assign('table', 'zc_category');

            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);
            $this->smarty->assign('full_page', 1);
            $this->smarty->assign('arr_zc', $list['zc_projects']);
            return $this->smarty->display('zc_project_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 翻页、排序
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'query') {
            $list = $this->zcManageService->zcProjectList();
            $this->smarty->assign('filter', $list['filter']);
            $this->smarty->assign('record_count', $list['record_count']);
            $this->smarty->assign('page_count', $list['page_count']);
            $this->smarty->assign('arr_zc', $list['zc_projects']);   //  把结果赋值给页面

            return make_json_result($this->smarty->fetch('zc_project_list.dwt'), '', ['filter' => $list['filter'], 'page_count' => $list['page_count']]);
        }

        /*------------------------------------------------------ */
        //-- 添加/编辑众筹商品
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add' || $_REQUEST['act'] == 'edit') {
            /* 权限检查 */
            admin_priv('zc_project_manage');

            if ($_REQUEST['act'] == 'add') {
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['add_zc_project']);
            }
            if ($_REQUEST['act'] == 'edit') {
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['edit_zc_project']);
            }

            $action_link = ['href' => 'zc_project.php?act=list', 'text' => $GLOBALS['_LANG']['01_crowdfunding_list']];
            $this->smarty->assign('action_link', $action_link);

            $id = isset($_GET['id']) && !empty($_GET['id']) ? intval($_GET['id']) : 0;

            $initiator = ZcInitiator::query();
            $initiator = $this->baseRepository->getToArrayGet($initiator);

            if ($id) {

                $result = ZcProject::where('id', $id)
                    ->with([
                        'getZcCategory'
                    ]);
                $result = $this->baseRepository->getToArrayFirst($result);

                //创建 html editor
                create_html_editor2('details', 'details', $result['details'] ?? '');
                create_html_editor2('describe', 'describe', $result['describe'] ?? '');
                create_html_editor2('risk_instruction', 'risk_instruction', $result['risk_instruction'] ?? '');

                $result['start_time'] = $this->timeRepository->getLocalDate('Y-m-d', $result['start_time'] ?? 0);
                $result['end_time'] = $this->timeRepository->getLocalDate('Y-m-d', $result['end_time'] ?? 0);

                $result['cat_name'] = $result['get_zc_category']['cat_name'] ?? '';


                $this->smarty->assign('initiator', $initiator);
                $this->smarty->assign('item_id', $id);

                $this->smarty->assign('parent_category', get_every_category($result['cat_id'] ?? 0, 'zc_category'));
                set_default_filter(0, $result['cat_id'] ?? 0, 0, 0, 'zc_category'); //设置默认筛选
                $this->smarty->assign('table', 'zc_category');

                $result['title_img'] = $this->dscRepository->getImagePath($result['title_img'] ?? '');

                $this->smarty->assign('info', $result);
                $this->smarty->assign('state', 'update');
                return $this->smarty->display('zc_project_info.dwt');
            } else {
                //创建 html editor
                create_html_editor2('details', 'details');
                create_html_editor2('describe', 'describe');
                create_html_editor2('risk_instruction', 'risk_instruction');
                $this->smarty->assign('initiator', $initiator);
                $start_date = $this->timeRepository->getLocalDate('Y-m-d');
                $end_date = $this->timeRepository->getLocalDate('Y-m-d', $this->timeRepository->getLocalStrtoTime('+1 month'));
                $this->smarty->assign('state', 'insert');
                $this->smarty->assign('start_date', $start_date);
                $this->smarty->assign('end_date', $end_date);

                set_default_filter(0, 0, 0, 0, 'zc_category'); //设置默认筛选
                $this->smarty->assign('table', 'zc_category');

                return $this->smarty->display('zc_project_info.dwt');
            }
        }

        /*------------------------------------------------------ */
        //-- 添加众筹商品时的处理
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'insert') {
            /* 权限检查 */
            admin_priv('zc_project_manage');

            //处理接收数据
            $title = !empty($_POST['title']) ? trim($_POST['title']) : '';
            $cat_id = !empty($_POST['cat_id']) ? trim($_POST['cat_id']) : 0;
            $amount = !empty($_POST['money']) ? trim($_POST['money']) : 0;
            $start_time = !empty($_POST['promote_start_date']) ? $this->timeRepository->getLocalStrtoTime(trim($_POST['promote_start_date'])) : $this->timeRepository->getLocalDate('Y-m-d');
            $end_time = !empty($_POST['promote_end_date']) ? $this->timeRepository->getLocalStrtoTime(trim($_POST['promote_end_date'])) : $this->timeRepository->getLocalDate('Y-m-d', $this->timeRepository->getLocalStrtoTime('+1 month'));
            $details = !empty($_POST['details']) ? trim($_POST['details']) : $GLOBALS['_LANG']['not_desc'];
            $describe = !empty($_POST['describe']) ? trim($_POST['describe']) : $GLOBALS['_LANG']['not_desc'];
            $risk_instruction = !empty($_POST['risk_instruction']) ? trim($_POST['risk_instruction']) : $GLOBALS['_LANG']['not_desc'];
            $initiator = !empty($_POST['initiator']) ? trim($_POST['initiator']) : '';
            $is_best = !empty($_POST['is_best']) ? trim($_POST['is_best']) : 0;

            /* 处理商品图片 */
            $dir_title = 'zc_title_images';

            $title_img = '';
            if (!empty($_FILES['tit_img']['name'])) {
                $title_img = $image->upload_image($_FILES['tit_img'], $dir_title);
                $this->dscRepository->getOssAddFile([$title_img]);
            }

            $other = [
                'cat_id' => $cat_id,
                'title' => $title,
                'init_id' => $initiator,
                'is_best' => $is_best,
                'start_time' => $start_time,
                'end_time' => $end_time,
                'amount' => $amount,
                'title_img' => $title_img,
                'details' => $details,
                'describe' => $describe,
                'risk_instruction' => $risk_instruction
            ];
            ZcProject::insert($other);

            $links[0]['text'] = $GLOBALS['_LANG']['go_list'];
            $links[0]['href'] = 'zc_project.php?act=list';
            return sys_msg($GLOBALS['_LANG']['add_succeed'], 0, $links);
        }

        /*------------------------------------------------------ */
        //-- 编辑众筹项目时的处理
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'update') {
            /* 权限检查 */
            admin_priv('zc_project_manage');

            //处理接收数据
            $id = $_POST['item_id'];
            $title = !empty($_POST['title']) ? trim($_POST['title']) : '';
            $cat_id = !empty($_POST['cat_id']) ? trim($_POST['cat_id']) : 0;
            $amount = !empty($_POST['money']) ? trim($_POST['money']) : 0;
            $start_time = !empty($_POST['promote_start_date']) ? $this->timeRepository->getLocalStrtoTime(trim($_POST['promote_start_date'])) : $this->timeRepository->getLocalDate('Y-m-d');
            $end_time = !empty($_POST['promote_end_date']) ? $this->timeRepository->getLocalStrtoTime(trim($_POST['promote_end_date'])) : $this->timeRepository->getLocalDate('Y-m-d', $this->timeRepository->getLocalStrtoTime('+1 month'));
            $details = !empty($_POST['details']) ? trim($_POST['details']) : lang('admin/zc_project.no_description');
            $describe = !empty($_POST['describe']) ? trim($_POST['describe']) : lang('admin/zc_project.no_description');
            $risk_instruction = !empty($_POST['risk_instruction']) ? trim($_POST['risk_instruction']) : lang('admin/zc_project.no_description');
            $initiator = !empty($_POST['initiator']) ? trim($_POST['initiator']) : '';
            $is_best = !empty($_POST['is_best']) ? trim($_POST['is_best']) : 0;

            /* 处理商品图片 */
            $title_img = '';  // 初始化基本图片
            $dir_title = 'zc_title_images';

            if (!empty($_FILES['tit_img']['name'])) {
                $title_img = $image->upload_image($_FILES['tit_img'], $dir_title);
                $this->dscRepository->getOssAddFile([$title_img]);

                $zcproject = ZcProject::where('id', $id);
                $zcproject = $this->baseRepository->getToArrayFirst($zcproject);

                if ($zcproject && $zcproject['title_img']) {
                    dsc_unlink(storage_public($zcproject['title_img']));
                    $this->dscRepository->getOssDelFile([$zcproject['title_img']]);
                }
            }

            $other = [
                'cat_id' => $cat_id,
                'title' => $title,
                'init_id' => $initiator,
                'is_best' => $is_best,
                'start_time' => $start_time,
                'end_time' => $end_time,
                'amount' => $amount,
                'details' => $details,
                'risk_instruction' => $risk_instruction,
                'describe' => $describe
            ];

            if ($title_img) {
                $other['title_img'] = $title_img;
            }

            ZcProject::where('id', $id)->update($other);

            $links[0]['text'] = $GLOBALS['_LANG']['go_list'];
            $links[0]['href'] = 'zc_project.php?act=list';
            return sys_msg($GLOBALS['_LANG']['edit_success'], 0, $links);
        }

        /*------------------------------------------------------ */
        //-- 删除众筹项目
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'del') {
            /* 权限检查 */
            admin_priv('zc_project_manage');

            $id = isset($_REQUEST['id']) && !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

            $count = ZcGoods::where('pid', $id)->count();

            if ($count > 0) {
                $links[0]['text'] = $GLOBALS['_LANG']['go_back'];
                $links[0]['href'] = 'javascript:history.go(-1)';
                return sys_msg($GLOBALS['_LANG']['zc_project_del_fail'], 1, $links);
            } else {

                $row = ZcProject::where('id', $id);
                $row = $this->baseRepository->getToArrayFirst($row);

                if ($row && $row['title_img']) {
                    dsc_unlink(storage_public($row['title_img']));
                    $this->dscRepository->getOssDelFile([$row['title_img']]);
                }

                $img = $row && $row['img'] ? unserialize($row['img']) : [];

                if ($img && is_array($img)) {
                    foreach ($img as $v) {
                        @unlink(storage_public($v));
                    }

                    $this->dscRepository->getOssDelFile($img);
                }

                ZcProject::where('id', $id)->delete();

                return dsc_header('Location:zc_project.php?act=list');
            }
        }

        /*------------------------------------------------------ */
        //-- 修改精品推荐状态
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'toggle_best') {
            $id = intval($_POST['id']);
            $is_best = intval($_POST['val']);

            ZcProject::where('id', $id)->update([
                'is_best' => $is_best
            ]);

            return make_json_result($is_best);
        }

        /*------------------------------------------------------ */
        //-- 项目方案列表
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'product_list') {
            /* 权限检查 */
            admin_priv('zc_project_manage');

            $id = isset($_REQUEST['id']) && !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['zc_goods_manage']);
            $action_link = ['href' => 'zc_project.php?act=add_product&id=' . $id, 'text' => $GLOBALS['_LANG']['add_zc_goods']];

            //取得当前项目下的方案列表
            $result = ZcGoods::where('pid', $id);
            $result = $this->baseRepository->getToArrayGet($result);

            if ($result) {
                foreach ($result as $key => $val) {
                    $result[$key]['img'] = $this->dscRepository->getImagePath($val['img']);
                }
            }

            $this->smarty->assign('product_list', $result);
            $this->smarty->assign('id', $id);
            $this->smarty->assign('action_link', $action_link);
            $this->smarty->assign('full_page', 1);
            return $this->smarty->display('zc_goods_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 添加项目方案
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add_product' || $_REQUEST['act'] == 'edit_product') {
            /* 权限检查 */
            admin_priv('zc_project_manage');

            if ($_REQUEST['act'] == 'add_product') {
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['add_zc_goods']);
            }
            if ($_REQUEST['act'] == 'edit_product') {
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['edit_zc_goods']);
            }

            $action_link = ['href' => 'zc_project.php?act=product_list&id=' . intval($_GET['id']), 'text' => $GLOBALS['_LANG']['zc_goods_manage']];
            $this->smarty->assign('action_link', $action_link);

            if (isset($_GET['product_id']) && !empty($_GET['product_id'])) {
                $id = intval($_GET['id']);
                $product_id = intval($_GET['product_id']);

                $row = ZcGoods::where('id', $product_id);
                $row = $this->baseRepository->getToArrayFirst($row);

                if ($row) {
                    $row['img'] = $this->dscRepository->getImagePath($row['img']);
                }

                $this->smarty->assign('id', $id);
                $this->smarty->assign('item_id', $product_id);
                $this->smarty->assign('product', $row);
                $this->smarty->assign('state', 'update_product');
                return $this->smarty->display('zc_goods_info.dwt');
            } else {
                $id = intval($_GET['id']);
                $this->smarty->assign('item_id', $id);
                $this->smarty->assign('state', 'insert_product');
                return $this->smarty->display('zc_goods_info.dwt');
            }
        }

        /*------------------------------------------------------ */
        //-- 添加项目方案处理
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'insert_product') {
            /* 权限检查 */
            admin_priv('zc_project_manage');

            //处理接收数据
            $id = $_POST['item_id'];
            $limit = !empty($_POST['limit']) ? trim(intval($_POST['limit'])) : 0;
            $price = !empty($_POST['price']) ? trim($_POST['price']) : 0;
            $carriage = !empty($_POST['yunfei']) ? trim($_POST['yunfei']) : 0;
            $return_time = !empty($_POST['return_time']) ? trim($_POST['return_time']) : '';
            $return_cont = !empty($_POST['content']) ? trim($_POST['content']) : '';

            /* 处理商品图片 */
            $product_img = '';  // 初始化方案图片
            $dir_product = 'zc_product_images';

            if (!empty($_FILES['product_img']['name'])) {
                $product_img = $image->upload_image($_FILES['product_img'], $dir_product);
                $this->dscRepository->getOssAddFile([$product_img]);
            }

            $other = [
                'pid' => $id,
                'limit' => $limit,
                'price' => $price,
                'shipping_fee' => $carriage,
                'content' => $return_cont,
                'img' => $product_img,
                'return_time' => $return_time
            ];
            ZcGoods::insert($other);

            $links[0]['text'] = $GLOBALS['_LANG']['go_list'];
            $links[0]['href'] = 'zc_project.php?act=product_list&id=' . $id;
            return sys_msg($GLOBALS['_LANG']['add_succeed'], 0, $links);
        }

        /*------------------------------------------------------ */
        //-- 编辑项目方案处理
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'update_product') {
            /* 权限检查 */
            admin_priv('zc_project_manage');

            //处理接收数据
            $id = $_POST['id'];
            $product_id = $_POST['item_id'];
            $limit = !empty($_POST['limit']) ? trim($_POST['limit']) : 0;
            $price = !empty($_POST['price']) ? trim($_POST['price']) : 0;
            $carriage = !empty($_POST['shipping_fee']) ? trim($_POST['shipping_fee']) : 0;
            $return_time = !empty($_POST['return_time']) ? trim($_POST['return_time']) : '';
            $return_cont = !empty($_POST['content']) ? trim($_POST['content']) : '';

            if (!empty($_POST['infinite'])) {
                $limit = -1;
            }

            /* 处理商品图片 */
            $product_img = '';  // 初始化基本图片
            $dir_product = 'zc_product_images';
            if (!empty($_FILES['product_img']['name'])) {
                $product_img = $image->upload_image($_FILES['product_img'], $dir_product);
                $this->dscRepository->getOssAddFile([$product_img]);
            }

            //有上传图片，删除原图
            $row = ZcGoods::where('id', $product_id);
            $row = $this->baseRepository->getToArrayFirst($row);

            if ($product_img != '' && $row && $row['img']) {
                dsc_unlink(storage_public($row['product_img']));
                $this->dscRepository->getOssDelFile([$row['product_img']]);
            }

            $other = [
                'limit' => $limit,
                'price' => $price,
                'shipping_fee' => $carriage,
                'return_time' => $return_time,
                'content' => $return_cont
            ];

            if ($product_img) {
                $other['img'] = $product_img;
            }

            ZcGoods::where('id', $product_id)->update($other);

            $links[0]['text'] = $GLOBALS['_LANG']['go_list'];
            $links[0]['href'] = 'zc_project.php?act=product_list&id=' . $id;
            return sys_msg($GLOBALS['_LANG']['edit_success'], 0, $links);
        }

        /*------------------------------------------------------ */
        //-- 删除项目方案
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'del_product') {
            /* 权限检查 */
            admin_priv('zc_project_manage');

            $id = intval($_GET['id']);
            $product_id = intval($_GET['product_id']);

            //检查是否有订单
            $count = OrderInfo::where('zc_goods_id', $product_id)->count();

            if ($count > 0) {
                $links[0]['text'] = $GLOBALS['_LANG']['go_back'];
                $links[0]['href'] = 'javascript:history.go(-1)';
                return sys_msg($GLOBALS['_LANG']['zc_goods_del_fail'], 1, $links);
            } else {

                $row = ZcGoods::where('id', $product_id);
                $row = $this->baseRepository->getToArrayFirst($row);

                if ($row && $row['img']) {
                    dsc_unlink(storage_public($row['img']));
                    $this->dscRepository->getOssDelFile([$row['img']]);
                }

                ZcGoods::where('id', $product_id)->delete();

                return dsc_header('Location:zc_project.php?act=product_list&id=' . $id);
            }
        }

        /*------------------------------------------------------ */
        //-- 项目进展列表
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'progress') {
            /* 权限检查 */
            admin_priv('zc_project_manage');

            $item_id = intval($_GET['id']);
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['zc_progress_manage']);
            $action_link = ['href' => 'zc_project.php?act=add_evolve&id=' . $item_id, 'text' => $GLOBALS['_LANG']['add_zc_progress']];

            //取得当前项目下的方案列表
            $result = ZcProgress::where('pid', $item_id);
            $result = $this->baseRepository->getToArrayGet($result);

            if ($result) {
                foreach ($result as $k => $v) {
                    $result[$k]['add_time'] = $this->timeRepository->getLocalDate("Y-m-d", $v['add_time']);
                    $result[$k]['img'] = $v['img'] ? unserialize($v['img']) : [];

                    if ($result[$k]['img']) {
                        foreach ($result[$k]['img'] as $img_key => $img_val) {
                            $result[$k]['img'][$img_key] = $this->dscRepository->getImagePath($img_val);
                        }
                    }
                }
            }

            $this->smarty->assign('item_id', $item_id);
            $this->smarty->assign('evolve_list', $result);
            $this->smarty->assign('action_link', $action_link);
            $this->smarty->assign('full_page', 1);
            return $this->smarty->display('zc_progress_list.dwt');
        }

        /*------------------------------------------------------ */
        //-- 添加/编辑项目进展
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add_evolve' || $_REQUEST['act'] == 'edit_evolve') {
            /* 权限检查 */
            admin_priv('zc_project_manage');

            if ($_REQUEST['act'] == 'add_evolve') {
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['add_zc_progress']);
            }
            if ($_REQUEST['act'] == 'edit_evolve') {
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['edit_zc_progress']);
            }

            $action_link = ['href' => 'zc_project.php?act=progress&id=' . intval($_GET['id']), 'text' => $GLOBALS['_LANG']['zc_progress_manage']];
            $this->smarty->assign('action_link', $action_link);

            if (isset($_GET['evolve_id']) && !empty($_GET['evolve_id'])) {
                $id = intval($_GET['id']);
                $evolve_id = intval($_GET['evolve_id']);

                $row = ZcProgress::where('id', $evolve_id);
                $row = $this->baseRepository->getToArrayFirst($row);

                if ($row) {
                    $row['img'] = $row['img'] ? unserialize($row['img']) : [];
                    if ($row['img']) {
                        foreach ($row['img'] as $key => $val) {
                            $row['img'][$key] = $this->dscRepository->getImagePath($val);
                        }
                    }
                }

                $this->smarty->assign('id', $evolve_id);
                $this->smarty->assign('item_id', $id);
                $this->smarty->assign('evolve', $row);
                $this->smarty->assign('state', 'update_evolve');
                return $this->smarty->display('zc_progress_info.dwt');
            } else {
                $id = intval($_GET['id']);
                $this->smarty->assign('item_id', $id);
                $this->smarty->assign('state', 'insert_evolve');
                return $this->smarty->display('zc_progress_info.dwt');
            }
        }

        /*------------------------------------------------------ */
        //-- 添加项目进展处理
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'insert_evolve') {
            /* 权限检查 */
            admin_priv('zc_project_manage');

            //处理接收数据
            $item_id = $_POST['item_id'];
            $progress = !empty($_POST['progress']) ? trim($_POST['progress']) : '';
            $add_time = gmtime();
            /* 处理商品图片 */
            $evolve_img = [];  // 初始化方案图片
            $dir_evolve = 'funding_evolve_images';

            if (have_file_upload()) {
                for ($i = 0; $i < count($_FILES); $i++) {
                    if ($_FILES['img_' . $i]) {
                        $evolve_img[] = $image->upload_image($_FILES['img_' . $i], $dir_evolve);
                    }
                }
            }

            if ($evolve_img) {
                $this->dscRepository->getOssAddFile($evolve_img);
            }

            $evolve_img = serialize($evolve_img);

            $other = [
                'pid' => $item_id,
                'progress' => $progress,
                'add_time' => $add_time,
                'img' => $evolve_img
            ];
            ZcProgress::insert($other);

            $links[0]['text'] = $GLOBALS['_LANG']['go_list'];
            $links[0]['href'] = 'zc_project.php?act=progress&id=' . $item_id;
            return sys_msg($GLOBALS['_LANG']['add_succeed'], 0, $links);
        }

        /*------------------------------------------------------ */
        //-- 编辑项目进展处理
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'update_evolve') {
            /* 权限检查 */
            admin_priv('zc_project_manage');

            //处理接收数据
            $id = $_POST['id'];
            $item_id = $_POST['item_id'];
            $progress = !empty($_POST['progress']) ? trim($_POST['progress']) : '';

            /* 处理商品图片 */
            $evolve_img = [];  // 初始化方案图片
            $dir_evolve = 'funding_evolve_images';
            for ($i = 0; $i < count($_FILES); $i++) {
                if (!empty($_FILES['img_' . $i]['name'])) {
                    $evolve_img[] = $image->upload_image($_FILES['img_' . $i], $dir_evolve);
                }
            }

            if ($evolve_img) {
                $this->dscRepository->getOssAddFile($evolve_img);
            }

            if ($evolve_img) {

                $row = ZcProgress::where('id', $id);
                $row = $this->baseRepository->getToArrayFirst($row);

                if ($row && $row['img']) {
                    $row['img'] = unserialize($row['img']);
                    $evolve_img = array_merge($evolve_img, $row['img']);
                }
                $evolve_img = serialize($evolve_img);
            }

            $other = [
                'progress' => $progress
            ];

            if ($evolve_img) {
                $other['img'] = $evolve_img;
            }

            ZcProgress::where('id', $id)->update($other);

            $links[0]['text'] = $GLOBALS['_LANG']['go_list'];
            $links[0]['href'] = 'zc_project.php?act=progress&id=' . $item_id;

            return sys_msg($GLOBALS['_LANG']['edit_success'], 0, $links);
        }

        /*------------------------------------------------------ */
        //-- 删除项目进展
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'del_evolve') {

            /* 权限检查 */
            admin_priv('zc_project_manage');

            $id = intval($_GET['id']);
            $evolve_id = intval($_GET['evolve_id']);

            $row = ZcProgress::where('id', $evolve_id);
            $row = $this->baseRepository->getToArrayFirst($row);

            if ($row && $row['img']) {
                $img = unserialize($row['img']);
                foreach ($img as $v) {
                    dsc_unlink(storage_public($v));
                }

                $this->dscRepository->getOssDelFile($img);
            }

            ZcProgress::where('id', $evolve_id)->delete();

            return dsc_header('Location:zc_project.php?act=progress&id=' . $id);
        }

        /*------------------------------------------------------ */
        //-- 删除图片 by wu
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'delete_image') {
            /* 权限检查 */
            admin_priv('zc_project_manage');

            $result = ['error' => 0, 'message' => '', 'content' => ''];
            $type = empty($_REQUEST['type']) ? '' : trim($_REQUEST['type']);
            $id = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);
            $key = empty($_REQUEST['key']) ? 0 : intval($_REQUEST['key']);

            //处理众筹项目图片
            if ($type == 'project') {

                $img = ZcProject::where('id', $id)->value('img');
                $img = $img ? $img : '';

                if ($img) {
                    //处理数据
                    $img_arr = unserialize($img);
                    dsc_unlink(storage_public($img_arr[$key]));

                    $this->dscRepository->getOssDelFile([$img_arr[$key]]);

                    unset($img_arr[$key]);

                    //更新数据
                    $img = serialize($img_arr);
                    ZcProject::where('id', $id)->update([
                        'img' => $img
                    ]);
                }
            }

            //处理众筹进展图片
            if ($type == 'progress') {

                $img = ZcProgress::where('id', $id)->value('img');
                $img = $img ? $img : '';

                if ($img) {
                    //处理数据
                    $img_arr = unserialize($img);
                    dsc_unlink(storage_public($img_arr[$key]));

                    $this->dscRepository->getOssDelFile([$img_arr[$key]]);

                    unset($img_arr[$key]);

                    //更新数据
                    $img = serialize($img_arr);
                    ZcProgress::where('id', $id)->update([
                        'img' => $img
                    ]);
                }
            }

            return response()->json($result);
        }
    }
}
