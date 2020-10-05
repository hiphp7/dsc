<?php

namespace App\Modules\Admin\Controllers;

use App\Models\MerchantsNav;
use App\Repositories\Common\BaseRepository;
use App\Services\Merchant\MerchantsNavigatorManageService;

/**
 * 程序说明
 */
class MerchantsNavigatorController extends InitController
{

    protected $baseRepository;
    protected $merchantsNavigatorManageService;

    public function __construct(
        BaseRepository $baseRepository,
        MerchantsNavigatorManageService $merchantsNavigatorManageService
    )
    {
        $this->baseRepository = $baseRepository;
        $this->merchantsNavigatorManageService = $merchantsNavigatorManageService;
    }

    public function index()
    {

//admin_priv('navigator');

        /*------------------------------------------------------ */
        //-- 自定义导航栏列表
        /*------------------------------------------------------ */
        if ($_REQUEST['act'] == 'list') {
            admin_priv('seller_store_other'); //by kong
            $this->smarty->assign('ur_here', $GLOBALS['_LANG']['navigator']);
            $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['add_new'], 'href' => 'merchants_navigator.php?act=add']);
            $this->smarty->assign('full_page', 1);

            $navdb = $this->merchantsNavigatorManageService->getNav();

            //ecmoban模板堂 --zhuo start
            $adminru = get_admin_ru_id();
            if ($adminru['ru_id'] == 0) {
                $this->smarty->assign('priv_ru', 1);
            } else {
                $this->smarty->assign('priv_ru', 0);
            }
            //ecmoban模板堂 --zhuo end

            $this->smarty->assign('navdb', $navdb['navdb']);
            $this->smarty->assign('filter', $navdb['filter']);
            $this->smarty->assign('record_count', $navdb['record_count']);
            $this->smarty->assign('page_count', $navdb['page_count']);


            return $this->smarty->display('merchants_navigator.dwt');
        }
        /*------------------------------------------------------ */
        //-- 自定义导航栏列表Ajax
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'query') {
            $navdb = $this->merchantsNavigatorManageService->getNav();

            //ecmoban模板堂 --zhuo start
            $adminru = get_admin_ru_id();
            if ($adminru['ru_id'] == 0) {
                $this->smarty->assign('priv_ru', 1);
            } else {
                $this->smarty->assign('priv_ru', 0);
            }
            //ecmoban模板堂 --zhuo end

            $this->smarty->assign('navdb', $navdb['navdb']);
            $this->smarty->assign('filter', $navdb['filter']);
            $this->smarty->assign('record_count', $navdb['record_count']);
            $this->smarty->assign('page_count', $navdb['page_count']);

            $sort_flag = sort_flag($navdb['filter']);
            $this->smarty->assign($sort_flag['tag'], $sort_flag['img']);

            return make_json_result($this->smarty->fetch('merchants_navigator.dwt'), '', ['filter' => $navdb['filter'], 'page_count' => $navdb['page_count']]);
        }
        /*------------------------------------------------------ */
        //-- 自定义导航栏增加
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'add') {
            $adminru = get_admin_ru_id();

            if (empty($_REQUEST['step'])) {
                $rt = ['act' => 'add'];

                set_default_filter();

                $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['go_list'], 'href' => 'merchants_navigator.php?act=list']);
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['navigator']);


                $this->smarty->assign('rt', $rt);
                return $this->smarty->display('merchants_navigator_add.dwt');
            } elseif ($_REQUEST['step'] == 2) {
                $item_name = $_REQUEST['item_name'];
                $item_url = $_REQUEST['item_url'];
                $item_ifshow = $_REQUEST['item_ifshow'];
                $item_opennew = $_REQUEST['item_opennew'];
                $item_type = $_REQUEST['item_type'];
                $item_catId = trim($_REQUEST['item_catId']);
                $item_catId = intval($item_catId);

                $vieworder = MerchantsNav::where('type', $item_type)->max('vieworder');

                $item_vieworder = empty($_REQUEST['item_vieworder']) ? $vieworder + 1 : $_REQUEST['item_vieworder'];

                if ($item_ifshow == 1 && $item_type == 'middle') {
                    //如果设置为在中部显示

                    $arr = $this->merchantsNavigatorManageService->analyseUri($item_url);  //分析URI
                    if ($arr) {
                        //如果为分类
                        $this->merchantsNavigatorManageService->setShowInNav($arr['type'], $arr['id'], 1);   //设置显示

                        $data = [
                            'name' => $item_name,
                            'ctype' => $arr['type'],
                            'cid' => $arr['id'],
                            'ifshow' => $item_ifshow,
                            'cat_id' => $item_catId,
                            'vieworder' => $item_vieworder,
                            'opennew' => $item_opennew,
                            'url' => $item_url,
                            'type' => $item_type,
                            'ru_id' => $adminru['ru_id']
                        ];
                        MerchantsNav::insert($data);
                    }
                }

                if (empty($sql)) {
                    $data = [
                        'name' => $item_name,
                        'ifshow' => $item_ifshow,
                        'vieworder' => $item_vieworder,
                        'opennew' => $item_opennew,
                        'url' => $item_url,
                        'type' => $item_type,
                        'ru_id' => $adminru['ru_id']
                    ];
                    MerchantsNav::insert($data);
                }

                clear_cache_files();
                $links[] = ['text' => $GLOBALS['_LANG']['navigator'], 'href' => 'merchants_navigator.php?act=list'];
                $links[] = ['text' => $GLOBALS['_LANG']['add_new'], 'href' => 'merchants_navigator.php?act=add'];
                return sys_msg($GLOBALS['_LANG']['edit_ok'], 0, $links);
            }
        }

        /* ------------------------------------------------------ */
        //-- 自定义导航栏编辑
        /* ------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'edit') {
            $id = $_REQUEST['id'];
            if (empty($_REQUEST['step'])) {
                $rt = ['act' => 'edit', 'id' => $id];

                $res = MerchantsNav::where('id', $id);
                $row = $this->baseRepository->getToArrayFirst($res);

                if (!empty($row)) {
                    $rt['item_name'] = $row['name'];
                    $rt['item_url'] = $row['url'];
                    $rt['item_vieworder'] = $row['vieworder'];
                    $rt['item_ifshow_' . $row['ifshow']] = 'selected';
                    $rt['item_opennew_' . $row['opennew']] = 'selected';
                    $rt['item_type_' . $row['type']] = 'selected';
                    $rt['item_catId'] = $row['cat_id'];
                    $rt['item_ifshow'] = $row['ifshow']; //by wu
                    $rt['item_opennew'] = $row['opennew']; //by wu
                }
                set_default_filter();

                $this->smarty->assign('action_link', ['text' => $GLOBALS['_LANG']['go_list'], 'href' => 'merchants_navigator.php?act=list']);
                $this->smarty->assign('ur_here', $GLOBALS['_LANG']['navigator']);

                $this->smarty->assign('rt', $rt);
                return $this->smarty->display('merchants_navigator_add.dwt');
            } elseif ($_REQUEST['step'] == 2) {
                $item_name = $_REQUEST['item_name'];
                $item_url = $_REQUEST['item_url'];
                $item_ifshow = $_REQUEST['item_ifshow'];
                $item_opennew = $_REQUEST['item_opennew'];
                $item_type = $_REQUEST['item_type'];
                $item_vieworder = (int)$_REQUEST['item_vieworder'];
                $item_catId = trim($_REQUEST['item_catId']);
                $item_catId = intval($item_catId);

                $arr = $this->merchantsNavigatorManageService->analyseUri($item_url);

                $res = MerchantsNav::where('id', $id);
                $row = $this->baseRepository->getToArrayFirst($res);

                if (!empty($arr) && !empty($row)) {
                    //目标为分类
                    if ($row['ctype'] == $arr['type'] && $row['cid'] == $arr['id']) {
                        //没有修改分类
                        if ($item_type != 'middle') {
                            //位置不在中部
                            $this->merchantsNavigatorManageService->setShowInNav($arr['type'], $arr['id'], 0);
                        }
                    } else {
                        //修改了分类
                        if ($row['ifshow'] == 1 && $row['type'] == 'middle') {
                            //原来在中部显示
                            $this->merchantsNavigatorManageService->setShowInNav($row['ctype'], $row['cid'], 0); //设置成不显示
                        } elseif ($row['ifshow'] == 0 && $row['type'] == 'middle') {
                            //原来不显示
                        }
                    }

                    //分类判断
                    if ($item_ifshow != $this->merchantsNavigatorManageService->isShowInNav($arr['type'], $arr['id']) && $item_type == 'middle') {
                        $this->merchantsNavigatorManageService->setShowInNav($arr['type'], $arr['id'], $item_ifshow);
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
                    MerchantsNav::where('id', $id)->update($data);

                } else {
                    //目标不是分类
                    if ($row['ctype'] && $row['cid']) {
                        //原来是分类
                        $this->merchantsNavigatorManageService->setShowInNav($row['ctype'], $row['cid'], 0);
                    }

                    $sql = "UPDATE " . $this->dsc->table('merchants_nav') .
                        " SET name='$item_name',ctype='',cid='',ifshow='$item_ifshow',cat_id='$item_catId',vieworder='$item_vieworder'
                        ,opennew='$item_opennew',url='$item_url',type='$item_type' WHERE id='$id'";

                    $data = [
                        'name' => $item_name,
                        'ctype' => '',
                        'cid' => '',
                        'ifshow' => $item_ifshow,
                        'cat_id' => $item_catId,
                        'vieworder' => $item_vieworder,
                        'opennew' => $item_opennew,
                        'url' => $item_url,
                        'type' => $item_type
                    ];
                    MerchantsNav::where('id', $id)->update($data);

                }

                clear_cache_files();
                $links[] = ['text' => $GLOBALS['_LANG']['navigator'], 'href' => 'merchants_navigator.php?act=list'];
                return sys_msg($GLOBALS['_LANG']['edit_ok'], 0, $links);
            }
        }
        /*------------------------------------------------------ */
        //-- 自定义导航栏删除
        /*------------------------------------------------------ */
        elseif ($_REQUEST['act'] == 'del') {
            $id = (int)$_GET['id'];

            $res = MerchantsNav::where('id', $id);
            $row = $this->baseRepository->getToArrayFirst($res);
            if (!empty($row) && $row['type'] == 'middle' && $row['ctype'] && $row['cid']) {
                $this->merchantsNavigatorManageService->setShowInNav($row['ctype'], $row['cid'], 0);
            }

            MerchantsNav::where('id', $id)->delete();
            clear_cache_files();
            return dsc_header("Location: merchants_navigator.php?act=list\n");
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
                $res = MerchantsNav::where('id', $id)->update($data);
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

            $res = MerchantsNav::where('id', $id);
            $row = $this->baseRepository->getToArrayFirst($res);

            if ($row['type'] == 'middle' && $row['ctype'] && $row['cid']) {
                $this->merchantsNavigatorManageService->setShowInNav($row['ctype'], $row['cid'], $val);
            }

            $data = ['ifshow' => $val];
            $res = MerchantsNav::where('id', $id)->update($data);

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
            $res = MerchantsNav::where('id', $id)->update($data);

            if ($res >= 0) {
                clear_cache_files();
                return make_json_result($val);
            } else {
                return make_json_error($this->db->error());
            }
        }
    }
}
