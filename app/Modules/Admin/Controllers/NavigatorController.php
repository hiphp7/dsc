<?php

namespace App\Modules\Admin\Controllers;

use App\Models\Nav;
use App\Repositories\Common\BaseRepository;
use App\Services\Navigator\NavigatorManageService;

/**
 * 程序说明
 */
class NavigatorController extends InitController
{
    protected $baseRepository;
    protected $navigatorManageService;

    public function __construct(
        BaseRepository $baseRepository,
        NavigatorManageService $navigatorManageService
    )
    {
        $this->baseRepository = $baseRepository;
        $this->navigatorManageService = $navigatorManageService;
    }

    public function index()
    {
        admin_priv('navigator');

        /*------------------------------------------------------ */
        //-- 自定义导航栏列表
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['navigator']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['add_new'], 'href' => 'navigator.php?act=add']);
            $this->smarty->assign('full_page', 1);

            $navdb = $this->navigatorManageService->getNav();

            $this->smarty->assign('navdb', $navdb['navdb']);
            $this->smarty->assign('filter', $navdb['filter']);
            $this->smarty->assign('record_count', $navdb['record_count']);
            $this->smarty->assign('page_count', $navdb['page_count']);


            return $this->smarty->display('navigator.dwt');
        }
        /*------------------------------------------------------ */
        //-- 自定义导航栏列表Ajax
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            $navdb = $this->navigatorManageService->getNav();
            $this->smarty->assign('navdb', $navdb['navdb']);
            $this->smarty->assign('filter', $navdb['filter']);
            $this->smarty->assign('record_count', $navdb['record_count']);
            $this->smarty->assign('page_count', $navdb['page_count']);

            $sort_flag = sort_flag($navdb['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result($this->smarty->fetch('navigator.dwt'), '', ['filter' => $navdb['filter'], 'page_count' => $navdb['page_count']]);
        }
        /*------------------------------------------------------ */
        //-- 自定义导航栏增加
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add') {
            if (empty($_REQUEST['step'])) {
                $rt = ['act' => 'add', 'ifshow' => '-1'];

                set_default_filter();

                $sysmain = $this->navigatorManageService->getSysnav();

                $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['go_list'], 'href' => 'navigator.php?act=list']);
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['navigator']);

                $this->smarty->assign('sysmain', $sysmain);
                $this->smarty->assign('rt', $rt);
                return $this->smarty->display('navigator_add.dwt');
            } elseif ($_REQUEST['step'] == 2) {
                $item_name = $_REQUEST['item_name'] ?? '';
                $item_url = $_REQUEST['item_url'] ?? '';
                $item_ifshow = $_REQUEST['item_ifshow'] ?? '';
                $item_opennew = $_REQUEST['item_opennew'] ?? '';
                $item_type = $_REQUEST['item_type'] ?? '';

                if (empty($item_type)) {
                    return sys_msg($GLOBALS['_LANG']['item_typenull'], 1);
                }

                $vieworder = Nav::where('type', $item_type)->max('vieworder');

                $item_vieworder = empty($_REQUEST['item_vieworder']) ? $vieworder + 1 : $_REQUEST['item_vieworder'];

                if ($item_ifshow == 1 && $item_type == 'middle') {
                    //如果设置为在中部显示

                    $arr = $this->navigatorManageService->analyseUri($item_url);  //分析URI
                    if ($arr) {
                        //如果为分类
                        $this->navigatorManageService->setShowInNav($arr['type'], $arr['id'], 1);   //设置显示
                        $data = [
                            'name' => $item_name,
                            'ctype' => $arr['type'],
                            'cid' => $arr['id'],
                            'ifshow' => $item_ifshow,
                            'vieworder' => $item_vieworder,
                            'opennew' => $item_opennew,
                            'url' => $item_url,
                            'type' => $item_type
                        ];
                        Nav::insert($data);
                    }
                }

                if (empty($sql)) {
                    $data = [
                        'name' => $item_name,
                        'ifshow' => $item_ifshow,
                        'vieworder' => $item_vieworder,
                        'opennew' => $item_opennew,
                        'url' => $item_url,
                        'type' => $item_type
                    ];
                    Nav::insert($data);

                }
                clear_cache_files();
                $links[] = ['text' => $GLOBALS['_LANG']['navigator'], 'href' => 'navigator.php?act=list'];
                $links[] = ['text' => $GLOBALS['_LANG']['add_new'], 'href' => 'navigator.php?act=add'];
                return sys_msg($GLOBALS['_LANG']['edit_ok'], 0, $links);
            }
        }
        /*------------------------------------------------------ */
        //-- 自定义导航栏编辑
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit') {
            $id = $_REQUEST['id'];
            if (empty($_REQUEST['step'])) {
                $rt = ['act' => 'edit', 'id' => $id];

                $res = Nav::where('id', $id);
                $row = $this->baseRepository->getToArrayFirst($res);

                $rt['item_name'] = $row['name'];
                $rt['item_url'] = $row['url'];
                $rt['item_vieworder'] = $row['vieworder'];
                $rt['item_ifshow_' . $row['ifshow']] = 'selected';
                $rt['ifshow'] = $row['ifshow']; //by wu
                $rt['item_opennew_' . $row['opennew']] = 'selected';
                $rt['opennew'] = $row['opennew']; //by wu
                $rt['item_type_' . $row['type']] = 'selected';
                $rt['type'] = $row['type']; //by wu

                set_default_filter();

                $sysmain = $this->navigatorManageService->getSysnav();

                $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['go_list'], 'href' => 'navigator.php?act=list']);
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['navigator']);

                $this->smarty->assign('sysmain', $sysmain);
                $this->smarty->assign('rt', $rt);
                return $this->smarty->display('navigator_add.dwt');
            } elseif ($_REQUEST['step'] == 2) {
                $item_name = $_REQUEST['item_name'];
                $item_url = $_REQUEST['item_url'];
                $item_ifshow = $_REQUEST['item_ifshow'];
                $item_opennew = $_REQUEST['item_opennew'];
                $item_type = $_REQUEST['item_type'];
                $item_vieworder = (int)$_REQUEST['item_vieworder'];

                $res = Nav::where('id', $id);
                $row = $this->baseRepository->getToArrayFirst($res);

                $arr = $this->navigatorManageService->analyseUri($item_url);

                if ($arr) {
                    //目标为分类
                    if ($row['ctype'] == $arr['type'] && $row['cid'] == $arr['id']) {
                        //没有修改分类
                        if ($item_type != 'middle') {
                            //位置不在中部
                            $this->navigatorManageService->setShowInNav($arr['type'], $arr['id'], 0);
                        }
                    } else {
                        //修改了分类
                        if ($row['ifshow'] == 1 && $row['type'] == 'middle') {
                            //原来在中部显示
                            $this->navigatorManageService->setShowInNav($row['ctype'], $row['cid'], 0); //设置成不显示
                        } elseif ($row['ifshow'] == 0 && $row['type'] == 'middle') {
                            //原来不显示
                        }
                    }

                    //分类判断
                    if ($item_ifshow != $this->navigatorManageService->isShowInNav($arr['type'], $arr['id']) && $item_type == 'middle') {
                        $this->navigatorManageService->setShowInNav($arr['type'], $arr['id'], $item_ifshow);
                    }

                    $data = [
                        'name' => $item_name,
                        'ctype' => $arr['type'],
                        'cid' => $arr['id'],
                        'ifshow' => $item_ifshow,
                        'vieworder' => $item_vieworder,
                        'opennew' => $item_opennew,
                        'url' => $item_url,
                        'type' => $item_type
                    ];
                    Nav::where('id', $id)->update($data);

                } else {
                    //目标不是分类
                    if ($row['ctype'] && $row['cid']) {
                        //原来是分类
                        $this->navigatorManageService->setShowInNav($row['ctype'], $row['cid'], 0);
                    }

                    $data = [
                        'name' => $item_name,
                        'ctype' => '',
                        'cid' => '',
                        'ifshow' => $item_ifshow,
                        'vieworder' => $item_vieworder,
                        'opennew' => $item_opennew,
                        'url' => $item_url,
                        'type' => $item_type
                    ];
                    Nav::where('id', $id)->update($data);

                }

                clear_cache_files();
                $links[] = ['text' => $GLOBALS['_LANG']['navigator'], 'href' => 'navigator.php?act=list'];
                return sys_msg($GLOBALS['_LANG']['edit_ok'], 0, $links);
            }
        }
        /*------------------------------------------------------ */
        //-- 自定义导航栏删除
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'del') {
            $id = (int)$_GET['id'];

            $res = Nav::where('id', $id);
            $row = $this->baseRepository->getToArrayFirst($res);

            if ($row['type'] == 'middle' && $row['ctype'] && $row['cid']) {
                $this->navigatorManageService->setShowInNav($row['ctype'], $row['cid'], 0);
            }

            Nav::where('id', $id)->delete();
            clear_cache_files();
            return dsc_header("Location: navigator.php?act=list\n");
        }

        /*------------------------------------------------------ */
        //-- 编辑排序
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit_sort_order') {
            $check_auth = check_authz_json('nav');
            if ($check_auth !== true) {
                return $check_auth;
            }

            $id = intval($_POST['id']);
            $order = json_str_iconv(trim($_POST['val']));

            /* 检查输入的值是否合法 */
            if (!preg_match("/^[0-9]+$/", $order)) {
                return make_json_error(sprintf($GLOBALS['_LANG']['enter_int'], $order));
            } else {
                $data = ['vieworder' => $order];
                $res = Nav::where('id', $id)->update($data);

                if ($res >= 0) {
                    clear_cache_files();
                    return make_json_result(stripslashes($order));
                } else {
                    return make_json_error($this->db->error());
                }
            }
        }

        /*------------------------------------------------------ */
        //-- 切换是否显示
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'toggle_ifshow') {
            $id = intval($_POST['id']);
            $val = intval($_POST['val']);

            $res = Nav::where('id', $id);
            $row = $this->baseRepository->getToArrayFirst($res);

            if ($row['type'] == 'middle' && $row['ctype'] && $row['cid']) {
                $this->navigatorManageService->setShowInNav($row['ctype'], $row['cid'], $val);
            }
            $data = ['ifshow' => $val];
            $res = Nav::where('id', $id)->update($data);

            if ($res >= 0) {
                clear_cache_files();
                return make_json_result($val);
            } else {
                return make_json_error($this->db->error());
            }
        }

        /*------------------------------------------------------ */
        //-- 切换是否新窗口
        /*------------------------------------------------------ */

        if ($_REQUEST['act'] == 'toggle_opennew') {
            $id = intval($_POST['id']);
            $val = intval($_POST['val']);

            $data = ['opennew' => $val];
            $res = Nav::where('id', $id)->update($data);

            if ($res >= 0) {
                clear_cache_files();
                return make_json_result($val);
            } else {
                return make_json_error($this->db->error());
            }
        }
    }
}
